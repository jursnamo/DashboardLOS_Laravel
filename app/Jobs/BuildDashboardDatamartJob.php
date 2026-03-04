<?php

namespace App\Jobs;

use App\Services\Dashboard\DatamartBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildDashboardDatamartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(public int $batchId) {}

    public function handle(DatamartBuilder $builder): void
    {
        $builder->rebuild($this->batchId);
    }
}
