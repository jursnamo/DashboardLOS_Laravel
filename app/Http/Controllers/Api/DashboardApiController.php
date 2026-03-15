<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BuildDashboardDatamartJob;
use App\Models\DashboardDatamart;
use App\Models\DashboardImportBatch;
use App\Models\DashboardRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    public function content(Request $request): JsonResponse
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        DB::connection()->disableQueryLog();

        $latestDatamart = DashboardDatamart::query()
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if ($latestDatamart) {
            $payload = json_decode((string) $latestDatamart->payload_json, true);
            if (is_array($payload) && ! empty($payload)) {
                $payload['datamart_batch_id'] = $latestDatamart->batch_id;
                $payload['datamart_source_batch_id'] = $latestDatamart->source_batch_id;
                $payload['datamart_generated_at'] = optional($latestDatamart->created_at)->toIso8601String();
                return response()->json($payload);
            }
        }

        $latestBatch = DashboardImportBatch::query()
            ->where('status', 'completed')
            ->where('source_type', 'upload_file')
            ->latest('id')
            ->first();

        if (! $latestBatch) {
            return response()->json([
                'message' => 'No imported data yet.',
                'has_data' => false,
                'batch' => null,
                'records' => [],
            ]);
        }

        $mode = $latestBatch->calculation_mode === 'tat' ? 'tat' : 'date';
        $useAggregated = $request->boolean('aggregated', true);

        if (! $useAggregated) {
            return $this->contentPaginated($request, $latestBatch);
        }

        $durationExpr = $mode === 'tat'
            ? 'COALESCE(tat_days, 0)'
            : 'CASE WHEN start_date IS NOT NULL AND end_date IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, start_date, end_date), 0) / 86400 ELSE 0 END';
        $statusDurationExpr = $mode === 'tat'
            ? 'SUM(COALESCE(tat_days, 0))'
            : 'CASE WHEN MIN(start_date) IS NOT NULL AND MAX(COALESCE(complete_date, end_date)) IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, MIN(start_date), MAX(COALESCE(complete_date, end_date))), 0) / 86400 ELSE 0 END';

        $perAppRows = DB::table('dashboard_records')
            ->where('batch_id', $latestBatch->id)
            ->selectRaw("\n                app_id,\n                MAX(segment) as segment,\n                MAX(purpose) as purpose,\n                MAX(approved_limit) as approved_limit,\n                MAX(branch_name) as branch_name,\n                MAX(booking_month) as booking_month,\n                MIN(start_date) as min_start_date,\n                MAX(end_date) as max_end_date,\n                SUM({$durationExpr}) as sum_duration,\n                MIN(row_order) as min_row_order\n            ")
            ->groupBy('app_id')
            ->orderBy('min_row_order')
            ->get();

        $e2eData = [];
        $appMetaById = [];

        foreach ($perAppRows as $row) {
            $monthKey = $this->normalizeMonth($row->booking_month);
            $tat = $mode === 'tat'
                ? $this->safeFloat($row->sum_duration)
                : $this->calcDateTat($row->min_start_date, $row->max_end_date);

            $appPayload = [
                'id' => (string) $row->app_id,
                'seg' => $this->stringOrDefault($row->segment, 'Unknown'),
                'purp' => $this->stringOrDefault($row->purpose, 'General'),
                'purpOriginal' => $this->stringOrDefault($row->purpose, 'General'),
                'limit' => $this->safeFloat($row->approved_limit),
                'branch' => $this->stringOrDefault($row->branch_name, 'Unknown'),
                'mon' => $monthKey,
                'displayMon' => $this->formatMonthDisplay($monthKey),
                'tat' => max(0, round($tat, 1)),
            ];

            $e2eData[] = $appPayload;
            $appMetaById[$appPayload['id']] = $appPayload;
        }

        $statusAggRows = DB::table('dashboard_records')
            ->where('batch_id', $latestBatch->id)
            ->selectRaw("app_id, status_flow, {$statusDurationExpr} as total_duration")
            ->groupBy('app_id', 'status_flow')
            ->orderBy('app_id')
            ->orderBy('status_flow')
            ->get();

        $statusAgg = [];
        foreach ($statusAggRows as $row) {
            $appId = trim((string) $row->app_id);
            $status = trim((string) $row->status_flow);

            if ($appId === '' || $status === '') {
                continue;
            }

            $statusAgg[$appId.'##'.$status] = round($this->safeFloat($row->total_duration), 4);
        }

        $flowEventRows = DB::table('dashboard_records')
            ->where('batch_id', $latestBatch->id)
            ->selectRaw("\n                app_id,\n                status_flow,\n                DATE(MAX(complete_date)) as complete_key,\n                UNIX_TIMESTAMP(MIN(start_date)) * 1000 as start_ms,\n                UNIX_TIMESTAMP(MAX(end_date)) * 1000 as end_ms,\n                UNIX_TIMESTAMP(MAX(complete_date)) * 1000 as complete_ms,\n                {$statusDurationExpr} as duration_sum,\n                COUNT(*) as step_count,\n                MIN(row_order) as seq,\n                COALESCE(MAX(complete_date), MIN(start_date), MAX(end_date)) as sort_key\n            ")
            ->groupBy('app_id', 'status_flow')
            ->orderBy('app_id')
            ->orderBy('sort_key')
            ->orderBy('seq')
            ->get();

        $appFlowEvents = [];
        foreach ($flowEventRows as $row) {
            $appId = trim((string) $row->app_id);
            $status = trim((string) $row->status_flow);

            if ($appId === '' || $status === '') {
                continue;
            }

            if (! isset($appFlowEvents[$appId])) {
                $appMeta = $appMetaById[$appId] ?? null;
                $appFlowEvents[$appId] = [
                    'id' => $appId,
                    'branch' => $appMeta['branch'] ?? 'Unknown',
                    'mon' => $appMeta['mon'] ?? 'Unknown',
                    'displayMon' => $appMeta['displayMon'] ?? 'Unknown',
                    'events' => [],
                ];
            }

            $appFlowEvents[$appId]['events'][] = [
                'status' => $status,
                'duration' => round($this->safeFloat($row->duration_sum), 4),
                'startMs' => $row->start_ms !== null ? (int) $row->start_ms : null,
                'endMs' => $row->end_ms !== null ? (int) $row->end_ms : null,
                'completeMs' => $row->complete_ms !== null ? (int) $row->complete_ms : null,
                'completeKey' => $row->complete_key,
                'count' => (int) ($row->step_count ?? 1),
                'seq' => (int) $row->seq,
            ];
        }

        return response()->json([
            'has_data' => true,
            'pre_aggregated' => true,
            'batch' => [
                'id' => $latestBatch->id,
                'filename' => $latestBatch->filename,
                'calculation_mode' => $latestBatch->calculation_mode,
                'status' => $latestBatch->status,
                'total_rows' => $latestBatch->total_rows,
                'imported_rows' => $latestBatch->imported_rows,
                'imported_at' => optional($latestBatch->imported_at)->toIso8601String(),
            ],
            'summary' => [
                'applications' => count($e2eData),
                'status_pairs' => count($statusAgg),
                'flow_groups' => count($flowEventRows),
            ],
            'e2e_data' => $e2eData,
            'status_agg' => $statusAgg,
            'app_flow_events' => $appFlowEvents,
        ]);
    }

    private function contentPaginated(Request $request, DashboardImportBatch $latestBatch): JsonResponse
    {
        $perPage = max(100, min(10000, (int) $request->query('per_page', 5000)));
        $page = max(1, (int) $request->query('page', 1));

        $baseQuery = DashboardRecord::query()
            ->where('batch_id', $latestBatch->id)
            ->orderBy('row_order');

        $total = (clone $baseQuery)->count();
        $records = (clone $baseQuery)
            ->forPage($page, $perPage)
            ->get([
                'app_id',
                'segment',
                'purpose',
                'approved_limit',
                'branch_name',
                'booking_month',
                'start_date',
                'end_date',
                'complete_date',
                'tat_days',
                'status_flow',
            ]);

        $lastPage = max(1, (int) ceil($total / $perPage));

        return response()->json([
            'has_data' => true,
            'pre_aggregated' => false,
            'batch' => [
                'id' => $latestBatch->id,
                'filename' => $latestBatch->filename,
                'calculation_mode' => $latestBatch->calculation_mode,
                'status' => $latestBatch->status,
                'total_rows' => $latestBatch->total_rows,
                'imported_rows' => $latestBatch->imported_rows,
                'imported_at' => optional($latestBatch->imported_at)->toIso8601String(),
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
            'records' => $records,
        ]);
    }

    // START import session (no big rows payload)
    public function import(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'filename' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:date,tat'],
            'mapping' => ['required', 'array'],
            'mapping.id' => ['required', 'string'],
            'mapping.seg' => ['nullable', 'string'],
            'mapping.purp' => ['nullable', 'string'],
            'mapping.limit' => ['nullable', 'string'],
            'mapping.branch' => ['nullable', 'string'],
            'mapping.mon' => ['nullable', 'string'],
            'mapping.c' => ['required', 'string'],
            'mapping.s' => ['required', 'string'],
            'mapping.e' => ['nullable', 'string'],
            'mapping.t' => ['nullable', 'string'],
            'mapping.stat' => ['required', 'string'],
            'total_rows' => ['required', 'integer', 'min:1'],
        ]);

        DB::connection()->disableQueryLog();

        $batch = DashboardImportBatch::query()->create([
            'filename' => $payload['filename'] ?? 'manual-upload',
            'source_type' => 'upload_file',
            'calculation_mode' => $payload['mode'],
            'status' => 'uploading',
            'total_rows' => (int) $payload['total_rows'],
            'imported_rows' => 0,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'imported_at' => null,
        ]);

        DB::table('dashboard_import_payloads')->insert([
            'batch_id' => $batch->id,
            'mapping_json' => json_encode($payload['mapping'], JSON_UNESCAPED_UNICODE),
            'rows_json' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Import session created.',
            'batch_id' => $batch->id,
            'status' => 'uploading',
            'total_rows' => $batch->total_rows,
        ], 202);
        //php artisan queue:work --queue=imports
    }

    // Upload chunk rows and directly insert into dashboard_records for this batch
    public function importChunk(Request $request, int $batchId): JsonResponse
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $payload = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*' => ['array'],
        ]);

        $batch = DashboardImportBatch::query()->find($batchId);
        if (! $batch) {
            return response()->json(['message' => 'Import batch not found.'], 404);
        }

        if (in_array($batch->status, ['completed', 'failed'], true)) {
            return response()->json(['message' => 'Import batch already finished.'], 422);
        }

        $mappingRow = DB::table('dashboard_import_payloads')->where('batch_id', $batchId)->first();
        if (! $mappingRow) {
            return response()->json(['message' => 'Mapping payload not found.'], 404);
        }

        $mapping = json_decode((string) $mappingRow->mapping_json, true);
        if (! is_array($mapping)) {
            return response()->json(['message' => 'Mapping payload invalid.'], 422);
        }

        DB::connection()->disableQueryLog();

        $rows = $payload['rows'];
        $insertedNow = 0;

        DB::transaction(function () use ($batchId, $rows, $mapping, &$insertedNow) {
            $batchLocked = DashboardImportBatch::query()->where('id', $batchId)->lockForUpdate()->firstOrFail();

            if ($batchLocked->status === 'uploading') {
                $batchLocked->status = 'processing';
                $batchLocked->started_at = now();
            }

            $startOrder = (int) $batchLocked->imported_rows;
            $rowOrder = $startOrder;
            $now = now();
            $insertRows = [];
            $chunkSize = 1000;

            foreach ($rows as $row) {
                $mapped = $this->mapRowForInsert($row, $mapping, $batchId, $rowOrder, $now);
                if ($mapped === null) {
                    continue;
                }
                $insertRows[] = $mapped;
                $rowOrder++;

                if (count($insertRows) >= $chunkSize) {
                    DB::table('dashboard_records')->insert($insertRows);
                    $insertedNow += count($insertRows);
                    $insertRows = [];
                }
            }

            if (! empty($insertRows)) {
                DB::table('dashboard_records')->insert($insertRows);
                $insertedNow += count($insertRows);
            }

            $batchLocked->imported_rows = $startOrder + $insertedNow;
            $batchLocked->save();
        });

        $batch->refresh();

        return response()->json([
            'message' => 'Chunk uploaded.',
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'imported_rows' => (int) $batch->imported_rows,
            'total_rows' => (int) $batch->total_rows,
            'progress_pct' => $batch->total_rows > 0
                ? (int) floor(((int) $batch->imported_rows / (int) $batch->total_rows) * 100)
                : 0,
        ]);
    }

    public function importFinalize(int $batchId): JsonResponse
    {
        $batch = DashboardImportBatch::query()->find($batchId);
        if (! $batch) {
            return response()->json(['message' => 'Import batch not found.'], 404);
        }

        if ($batch->status === 'failed') {
            return response()->json([
                'message' => $batch->error_message ?: 'Import failed.',
            ], 422);
        }

        $batch->update([
            'status' => 'completed',
            'completed_at' => now(),
            'imported_at' => now(),
            'error_message' => null,
        ]);

        DB::table('dashboard_import_payloads')->where('batch_id', $batchId)->delete();

        return response()->json([
            'message' => 'Import completed.',
            'batch_id' => $batch->id,
            'imported_rows' => (int) $batch->imported_rows,
            'total_rows' => (int) $batch->total_rows,
            'status' => 'completed',
        ]);
    }

    public function importStatus(int $batchId): JsonResponse
    {
        $batch = DashboardImportBatch::query()->find($batchId);

        if (! $batch) {
            return response()->json([
                'message' => 'Import batch not found.',
            ], 404);
        }

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_rows' => (int) $batch->total_rows,
            'imported_rows' => (int) $batch->imported_rows,
            'progress_pct' => $batch->total_rows > 0
                ? (int) floor(((int) $batch->imported_rows / (int) $batch->total_rows) * 100)
                : 0,
            'error_message' => $batch->error_message,
            'started_at' => optional($batch->started_at)->toIso8601String(),
            'completed_at' => optional($batch->completed_at)->toIso8601String(),
        ]);
    }

    public function executeDatamart(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'async' => ['nullable', 'boolean'],
        ]);

        $runAsync = (bool) ($payload['async'] ?? false);

        $sourceBatch = DashboardImportBatch::query()
            ->where('status', 'completed')
            ->where('source_type', 'upload_file')
            ->latest('id')
            ->first();
        if (! $sourceBatch) {
            return response()->json([
                'message' => 'No completed raw batch found to build datamart.',
            ], 422);
        }

        $estimatedRows = (int) DB::table('dashboard_records')
            ->where('batch_id', $sourceBatch->id)
            ->count();

        $batch = DashboardImportBatch::query()->create([
            'filename' => 'raw-database-datamart',
            'source_type' => 'raw_datamart',
            'calculation_mode' => 'date',
            'status' => $runAsync ? 'queued' : 'processing',
            'total_rows' => max(1, (int) $estimatedRows),
            'imported_rows' => 0,
            'error_message' => null,
            'started_at' => $runAsync ? null : now(),
            'completed_at' => null,
            'imported_at' => null,
        ]);

        if ($runAsync) {
            BuildDashboardDatamartJob::dispatch($batch->id)->onQueue('imports');
        } else {
            BuildDashboardDatamartJob::dispatchSync($batch->id);
            $batch->refresh();
        }

        return response()->json([
            'message' => $runAsync ? 'Datamart job queued.' : 'Datamart job executed.',
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_rows' => (int) $batch->total_rows,
            'imported_rows' => (int) $batch->imported_rows,
            'run_async' => $runAsync,
            'source_batch_id' => $sourceBatch->id,
        ], $runAsync ? 202 : 200);
    }

    public function datamartStatus(?int $batchId = null): JsonResponse
    {
        $query = DashboardImportBatch::query()
            ->where('source_type', 'raw_datamart');

        $batch = $batchId
            ? $query->where('id', $batchId)->first()
            : $query->latest('id')->first();

        if (! $batch) {
            return response()->json([
                'message' => 'Datamart batch not found.',
            ], 404);
        }

        $latestDatamart = DashboardDatamart::query()
            ->where('batch_id', $batch->id)
            ->latest('id')
            ->first();

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'source_type' => $batch->source_type,
            'total_rows' => (int) $batch->total_rows,
            'imported_rows' => (int) $batch->imported_rows,
            'progress_pct' => $batch->total_rows > 0
                ? (int) floor(((int) $batch->imported_rows / (int) $batch->total_rows) * 100)
                : 0,
            'error_message' => $batch->error_message,
            'started_at' => optional($batch->started_at)->toIso8601String(),
            'completed_at' => optional($batch->completed_at)->toIso8601String(),
            'imported_at' => optional($batch->imported_at)->toIso8601String(),
            'source_batch_id' => $latestDatamart?->source_batch_id,
            'datamart_records_count' => $latestDatamart?->records_count,
        ]);
    }

    public function mapRowForInsert(array $row, array $mapping, int $batchId, int $rowOrder, $now): ?array
    {
        $appId = trim((string) ($row[$mapping['id']] ?? ''));
        $statusFlow = trim((string) ($row[$mapping['stat']] ?? ''));

        if ($appId === '' || $statusFlow === '') {
            return null;
        }

        return [
            'batch_id' => $batchId,
            'app_id' => $appId,
            'segment' => $this->stringOrNull($row[$mapping['seg'] ?? ''] ?? null),
            'purpose' => $this->stringOrNull($row[$mapping['purp'] ?? ''] ?? null),
            'approved_limit' => $this->toFloat($row[$mapping['limit'] ?? ''] ?? null),
            'branch_name' => $this->stringOrNull($row[$mapping['branch'] ?? ''] ?? null),
            'booking_month' => $this->normalizeMonth($row[$mapping['mon'] ?? ''] ?? null),
            'start_date' => $this->parseDateTime($row[$mapping['s'] ?? ''] ?? null),
            'end_date' => $this->parseDateTime($row[$mapping['e'] ?? ''] ?? null),
            'complete_date' => $this->parseDateTime($row[$mapping['c'] ?? ''] ?? null),
            'tat_days' => $this->toFloat($row[$mapping['t'] ?? ''] ?? null),
            'status_flow' => $statusFlow,
            'row_order' => $rowOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function stringOrDefault($value, string $default): string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : $default;
    }

    private function safeFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function calcDateTat($minStart, $maxEnd): float
    {
        if (! $minStart || ! $maxEnd) {
            return 0.0;
        }

        try {
            $start = Carbon::parse((string) $minStart);
            $end = Carbon::parse((string) $maxEnd);
            $seconds = max(0, $start->diffInSeconds($end, false));

            return $seconds / 86400;
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function stringOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9,.-]/', '', (string) $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace(',', '.', $normalized);
        } elseif (substr_count($normalized, '.') > 1) {
            $normalized = str_replace('.', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function parseDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $base = Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC');
            return $base->copy()->addDays((int) $value)->toDateTimeString();
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeMonth($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $base = Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC');
            return $base->copy()->addDays((int) $value)->format('Y-m');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatMonthDisplay(?string $monthKey): string
    {
        if (! $monthKey) {
            return 'Unknown';
        }

        if (preg_match('/^\d{4}-\d{2}$/', $monthKey) !== 1) {
            return $monthKey;
        }

        try {
            $date = Carbon::createFromFormat('Y-m', $monthKey);
            return $date->format('M y');
        } catch (\Throwable $e) {
            return $monthKey;
        }
    }
}
