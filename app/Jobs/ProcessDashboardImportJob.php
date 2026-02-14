<?php

namespace App\Jobs;

use App\Http\Controllers\Api\DashboardApiController;
use App\Models\DashboardImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessDashboardImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        public int $batchId,
        public int $payloadId
    ) {}

    public function handle(): void
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        @ignore_user_abort(true);

        DB::connection()->disableQueryLog();

        $batch = DashboardImportBatch::query()->find($this->batchId);
        if (! $batch) {
            return;
        }

        $payload = DB::table('dashboard_import_payloads')->where('id', $this->payloadId)->first();
        if (! $payload) {
            $batch->update([
                'status' => 'failed',
                'error_message' => 'Import payload not found.',
                'completed_at' => now(),
            ]);
            return;
        }

        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
            'imported_rows' => 0,
        ]);

        try {
            $mapping = json_decode((string) $payload->mapping_json, true, 512, JSON_THROW_ON_ERROR);
            $rows = json_decode((string) $payload->rows_json, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($mapping) || ! is_array($rows)) {
                throw new \RuntimeException('Payload format is invalid.');
            }

            $chunkSize = 3000;
            $insertRows = [];
            $inserted = 0;
            $rowOrder = 0;
            $now = now()->toDateTimeString();

            DB::table('dashboard_records')->delete();

            $mapper = app(DashboardApiController::class);

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $mapped = $mapper->mapRowForInsert($row, $mapping, $this->batchId, $rowOrder, $now);
                if ($mapped === null) {
                    continue;
                }

                $insertRows[] = $mapped;
                $rowOrder++;

                if (count($insertRows) >= $chunkSize) {
                    DB::table('dashboard_records')->insert($insertRows);
                    $inserted += count($insertRows);
                    $insertRows = [];

                    DashboardImportBatch::query()
                        ->where('id', $this->batchId)
                        ->update(['imported_rows' => $inserted]);
                }
            }

            if (! empty($insertRows)) {
                DB::table('dashboard_records')->insert($insertRows);
                $inserted += count($insertRows);
            }

            DashboardImportBatch::query()->where('id', $this->batchId)->update([
                'status' => 'completed',
                'imported_rows' => $inserted,
                'imported_at' => now(),
                'completed_at' => now(),
                'error_message' => null,
            ]);

            DB::table('dashboard_import_payloads')->where('id', $this->payloadId)->delete();
        } catch (Throwable $e) {
            DashboardImportBatch::query()->where('id', $this->batchId)->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);
        }
    }
}
