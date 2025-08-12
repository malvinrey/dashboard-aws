<?php

namespace App\Jobs;

use App\Services\ScadaDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLargeScadaDatasetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2; // Kurang retry untuk dataset besar

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 1800; // 30 menit untuk dataset besar

    /**
     * The SCADA payload data to process.
     */
    protected array $payload;

    /**
     * Chunk size for processing large datasets.
     */
    protected int $chunkSize;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload, int $chunkSize = 1000)
    {
        $this->payload = $payload;
        $this->chunkSize = $chunkSize;
        $this->onQueue('scada-large-datasets'); // Queue khusus untuk dataset besar
    }

    /**
     * Execute the job.
     */
    public function handle(ScadaDataService $scadaDataService): void
    {
        $startTime = microtime(true);
        $dataCount = count($this->payload['DataArray'] ?? []);
        $totalChunks = ceil($dataCount / $this->chunkSize);

        Log::info('Starting large SCADA dataset processing job', [
            'job_id' => $this->job->getJobId(),
            'data_count' => $dataCount,
            'chunk_size' => $this->chunkSize,
            'total_chunks' => $totalChunks,
            'queue' => $this->queue,
            'start_time' => now()->format('Y-m-d H:i:s')
        ]);

        try {
            // Gunakan method khusus untuk dataset besar dengan chunking
            $scadaDataService->processLargeDataset($this->payload, $this->chunkSize);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Large SCADA dataset processing job completed successfully', [
                'job_id' => $this->job->getJobId(),
                'data_count' => $dataCount,
                'chunk_size' => $this->chunkSize,
                'total_chunks' => $totalChunks,
                'processing_time_ms' => $processingTime,
                'completion_time' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Large SCADA dataset processing job failed', [
                'job_id' => $this->job->getJobId(),
                'data_count' => $dataCount,
                'chunk_size' => $this->chunkSize,
                'total_chunks' => $totalChunks,
                'processing_time_ms' => $processingTime,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'failure_time' => now()->format('Y-m-d H:i:s')
            ]);

            // Re-throw exception agar job bisa di-retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Large SCADA dataset processing job permanently failed', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'data_count' => count($this->payload['DataArray'] ?? []),
            'chunk_size' => $this->chunkSize,
            'error' => $exception->getMessage(),
            'failure_time' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'scada',
            'large-dataset',
            'chunked-processing',
            'batch-' . count($this->payload['DataArray'] ?? []),
            'chunk-size-' . $this->chunkSize
        ];
    }
}
