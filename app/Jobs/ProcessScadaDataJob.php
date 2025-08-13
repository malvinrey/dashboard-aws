<?php

namespace App\Jobs;

use App\Events\ScadaDataReceived;
use App\Services\ScadaDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScadaDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 menit

    /**
     * The SCADA payload data to process.
     */
    protected array $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->onQueue('scada-processing'); // Queue khusus untuk SCADA
    }

    /**
     * Execute the job.
     */
    public function handle(ScadaDataService $scadaDataService): void
    {
        $startTime = microtime(true);
        $dataCount = count($this->payload['DataArray'] ?? []);

        Log::info('Starting SCADA data processing job', [
            'job_id' => $this->job->getJobId(),
            'data_count' => $dataCount,
            'queue' => $this->queue,
            'start_time' => now()->format('Y-m-d H:i:s')
        ]);

        try {
            // Proses data menggunakan service yang sudah ada
            $scadaDataService->processScadaPayload($this->payload);

            // Broadcast event untuk real-time update
            $this->broadcastProcessedData($scadaDataService);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('SCADA data processing job completed successfully', [
                'job_id' => $this->job->getJobId(),
                'data_count' => $dataCount,
                'processing_time_ms' => $processingTime,
                'completion_time' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('SCADA data processing job failed', [
                'job_id' => $this->job->getJobId(),
                'data_count' => $dataCount,
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
     * Broadcast processed data for real-time updates
     */
    protected function broadcastProcessedData(ScadaDataService $scadaDataService): void
    {
        try {
            // Get latest data for broadcasting
            $latestData = $scadaDataService->getLatestDataForTags([
                'temperature',
                'humidity',
                'pressure',
                'rainfall',
                'wind_speed',
                'wind_direction',
                'par_sensor',
                'solar_radiation'
            ]);

            if ($latestData) {
                // Broadcast to main channel
                ScadaDataReceived::dispatch($latestData, 'scada-data', $this->job->getJobId());

                // Broadcast to real-time channel for immediate updates
                ScadaDataReceived::dispatch($latestData, 'scada-realtime', $this->job->getJobId());

                Log::info('SCADA data broadcasted successfully', [
                    'job_id' => $this->job->getJobId(),
                    'channels' => ['scada-data', 'scada-realtime'],
                    'data_timestamp' => $latestData['timestamp'] ?? 'unknown'
                ]);
            } else {
                Log::warning('No latest data available for broadcasting', [
                    'job_id' => $this->job->getJobId()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to broadcast SCADA data', [
                'job_id' => $this->job->getJobId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SCADA data processing job permanently failed', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'data_count' => count($this->payload['DataArray'] ?? []),
            'error' => $exception->getMessage(),
            'failure_time' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['scada', 'data-processing', 'batch-' . count($this->payload['DataArray'] ?? [])];
    }
}
