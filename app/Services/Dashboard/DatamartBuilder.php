<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDatamart;
use App\Models\DashboardImportBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DatamartBuilder
{
    public function rebuild(int $batchId): array
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        DB::connection()->disableQueryLog();

        $batch = DashboardImportBatch::query()->find($batchId);
        if (! $batch) {
            throw new \RuntimeException('Datamart batch not found.');
        }

        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
            'completed_at' => null,
            'imported_at' => null,
            'imported_rows' => 0,
            'error_message' => null,
        ]);

        try {
            $sourceBatch = DashboardImportBatch::query()
                ->where('status', 'completed')
                ->where('source_type', 'upload_file')
                ->latest('id')
                ->first();

            if (! $sourceBatch) {
                throw new \RuntimeException('No completed raw batch found (source_type=upload_file).');
            }

            $sourceRowCount = (int) DB::table('dashboard_records')
                ->where('batch_id', $sourceBatch->id)
                ->count();

            if ($sourceRowCount <= 0) {
                throw new \RuntimeException('Raw batch has no dashboard_records.');
            }

            $payload = $this->buildAggregatedPayload($sourceBatch);

            DashboardDatamart::query()->create([
                'batch_id' => $batch->id,
                'source_batch_id' => $sourceBatch->id,
                'status' => 'completed',
                'records_count' => $sourceRowCount,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $batch->update([
                'status' => 'completed',
                'total_rows' => $sourceRowCount,
                'imported_rows' => $sourceRowCount,
                'completed_at' => now(),
                'imported_at' => now(),
            ]);

            return [
                'batch_id' => $batch->id,
                'source_batch_id' => $sourceBatch->id,
                'inserted_rows' => $sourceRowCount,
            ];
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

    private function buildAggregatedPayload(DashboardImportBatch $sourceBatch): array
    {
        $mode = $sourceBatch->calculation_mode === 'tat' ? 'tat' : 'date';
        $durationExpr = $mode === 'tat'
            ? 'COALESCE(tat_days, 0)'
            : 'CASE WHEN start_date IS NOT NULL AND end_date IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, start_date, end_date), 0) / 86400 ELSE 0 END';
        $statusDurationExpr = $mode === 'tat'
            ? 'SUM(COALESCE(tat_days, 0))'
            : 'CASE WHEN MIN(start_date) IS NOT NULL AND MAX(COALESCE(complete_date, end_date)) IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, MIN(start_date), MAX(COALESCE(complete_date, end_date))), 0) / 86400 ELSE 0 END';

        $perAppRows = DB::table('dashboard_records')
            ->where('batch_id', $sourceBatch->id)
            ->selectRaw("
                app_id,
                MAX(segment) as segment,
                MAX(purpose) as purpose,
                MAX(approved_limit) as approved_limit,
                MAX(branch_name) as branch_name,
                MAX(booking_month) as booking_month,
                MIN(start_date) as min_start_date,
                MAX(end_date) as max_end_date,
                SUM({$durationExpr}) as sum_duration,
                MIN(row_order) as min_row_order
            ")
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
            ->where('batch_id', $sourceBatch->id)
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
            $statusAgg[$appId . '##' . $status] = round($this->safeFloat($row->total_duration), 4);
        }

        $flowEventRows = DB::table('dashboard_records')
            ->where('batch_id', $sourceBatch->id)
            ->selectRaw("
                app_id,
                status_flow,
                DATE(MAX(complete_date)) as complete_key,
                UNIX_TIMESTAMP(MIN(start_date)) * 1000 as start_ms,
                UNIX_TIMESTAMP(MAX(end_date)) * 1000 as end_ms,
                UNIX_TIMESTAMP(MAX(complete_date)) * 1000 as complete_ms,
                {$statusDurationExpr} as duration_sum,
                MIN(row_order) as seq,
                COALESCE(MAX(complete_date), MIN(start_date), MAX(end_date)) as sort_key
            ")
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
                'seq' => (int) $row->seq,
            ];
        }

        return [
            'has_data' => true,
            'pre_aggregated' => true,
            'batch' => [
                'id' => $sourceBatch->id,
                'filename' => $sourceBatch->filename,
                'calculation_mode' => $sourceBatch->calculation_mode,
                'status' => $sourceBatch->status,
                'total_rows' => $sourceBatch->total_rows,
                'imported_rows' => $sourceBatch->imported_rows,
                'imported_at' => optional($sourceBatch->imported_at)->toIso8601String(),
            ],
            'summary' => [
                'applications' => count($e2eData),
                'status_pairs' => count($statusAgg),
                'flow_groups' => count($flowEventRows),
            ],
            'e2e_data' => $e2eData,
            'status_agg' => $statusAgg,
            'app_flow_events' => $appFlowEvents,
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
