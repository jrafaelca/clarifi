<?php

namespace App\Jobs;

use App\Ai\Pipelines\FinanceImportPipeline;
use App\Models\Ai\IngestionBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIngestionBatch implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public IngestionBatch $batch,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FinanceImportPipeline $financeImportPipeline): void
    {
        $financeImportPipeline->process($this->batch);
    }
}
