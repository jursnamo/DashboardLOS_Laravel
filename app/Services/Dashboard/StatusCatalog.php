<?php

namespace App\Services\Dashboard;

use App\Models\DashboardImportBatch;
use Illuminate\Support\Facades\DB;

class StatusCatalog
{
    public function getAvailableStatuses(): array
    {
        $latestBatchId = DashboardImportBatch::query()
            ->where('status', 'completed')
            ->latest('id')
            ->value('id');

        $query = DB::table('dashboard_records')
            ->select('status_flow')
            ->whereNotNull('status_flow')
            ->whereRaw('TRIM(status_flow) <> ""');

        if ($latestBatchId) {
            $query->where('batch_id', $latestBatchId);
        }

        $rows = $query
            ->distinct()
            ->orderBy('status_flow')
            ->pluck('status_flow')
            ->all();

        $statuses = array_values(array_filter(array_map(
            fn ($x) => trim((string) $x),
            $rows
        ), fn ($x) => $x !== ''));

        if (!empty($statuses)) {
            return $statuses;
        }

        // Fallback: ambil dari semua batch jika batch terbaru belum punya data status.
        return array_values(array_filter(array_map(
            fn ($x) => trim((string) $x),
            DB::table('dashboard_records')
                ->select('status_flow')
                ->whereNotNull('status_flow')
                ->whereRaw('TRIM(status_flow) <> ""')
                ->distinct()
                ->orderBy('status_flow')
                ->pluck('status_flow')
                ->all()
        ), fn ($x) => $x !== ''));
    }
}
