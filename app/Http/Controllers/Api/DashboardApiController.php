<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $latestBatch = DashboardImportBatch::query()
            ->where('status', 'completed')
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
            'mapping.c' => ['nullable', 'string'],
            'mapping.s' => ['nullable', 'string'],
            'mapping.e' => ['nullable', 'string'],
            'mapping.t' => ['nullable', 'string'],
            'mapping.stat' => ['required', 'string'],
            'total_rows' => ['required', 'integer', 'min:1'],
        ]);

        DB::connection()->disableQueryLog();

        $batch = DashboardImportBatch::query()->create([
            'filename' => $payload['filename'] ?? 'manual-upload',
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
            $chunkSize = 2000;

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
}

