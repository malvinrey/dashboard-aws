<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessScadaDataJob;
use App\Jobs\ProcessLargeScadaDatasetJob;
use App\Services\ScadaBroadcastingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReceiverController extends Controller
{
    /**
     * Threshold untuk menentukan apakah dataset dianggap "besar"
     * dan perlu diproses dengan job khusus
     */
    private const LARGE_DATASET_THRESHOLD = 5000;

    protected $broadcastingService;

    public function __construct(ScadaBroadcastingService $broadcastingService)
    {
        $this->broadcastingService = $broadcastingService;
    }

    public function store(Request $request)
    {
        $startTime = microtime(true);
        $dataCount = count($request->input('DataArray', []));

        Log::info('Incoming SCADA Payload received', [
            'data_count' => $dataCount,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        $validator = Validator::make($request->all(), [
            'DataArray' => 'required|array|min:1|max:10000', // Batasi maksimal 10k data per request
            'DataArray.*._groupTag' => 'required|string|max:255',
            'DataArray.*._terminalTime' => 'required|date',
            'DataArray.*.temperature' => 'nullable|numeric|between:-50,100',
            'DataArray.*.humidity' => 'nullable|numeric|between:0,100',
            'DataArray.*.pressure' => 'nullable|numeric|between:800,1200',
            'DataArray.*.rainfall' => 'nullable|numeric|min:0',
            'DataArray.*.wind_speed' => 'nullable|numeric|min:0',
            'DataArray.*.wind_direction' => 'nullable|numeric|between:0,360',
            'DataArray.*.par_sensor' => 'nullable|numeric|min:0',
            'DataArray.*.solar_radiation' => 'nullable|numeric|min:0',
        ], [
            'DataArray.max' => 'Payload terlalu besar. Maksimal 10.000 data per request.',
            'DataArray.*._groupTag.required' => 'Group tag wajib diisi untuk setiap data.',
            'DataArray.*._terminalTime.required' => 'Timestamp wajib diisi untuk setiap data.',
            'DataArray.*.temperature.between' => 'Suhu harus antara -50°C sampai 100°C.',
            'DataArray.*.humidity.between' => 'Kelembaban harus antara 0% sampai 100%.',
            'DataArray.*.pressure.between' => 'Tekanan harus antara 800-1200 hPa.',
            'DataArray.*.wind_direction.between' => 'Arah angin harus antara 0° sampai 360°.',
        ]);

        if ($validator->fails()) {
            Log::warning('SCADA Payload validation failed', [
                'errors' => $validator->errors(),
                'payload_sample' => array_slice($request->input('DataArray', []), 0, 3)
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Data validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Broadcast real-time data untuk dashboard
            $this->broadcastRealtimeData($request->input('DataArray'));

            // Tentukan job yang akan digunakan berdasarkan ukuran dataset
            if ($dataCount >= self::LARGE_DATASET_THRESHOLD) {
                // Dataset besar: gunakan job khusus dengan chunking
                $job = new ProcessLargeScadaDatasetJob($request->all(), 1000);
                $queueName = 'scada-large-datasets';

                Log::info('Dispatching large dataset job', [
                    'data_count' => $dataCount,
                    'queue' => $queueName,
                    'chunk_size' => 1000
                ]);
            } else {
                // Dataset normal: gunakan job standar
                $job = new ProcessScadaDataJob($request->all());
                $queueName = 'scada-processing';

                Log::info('Dispatching standard dataset job', [
                    'data_count' => $dataCount,
                    'queue' => $queueName
                ]);
            }

            // Dispatch job ke queue
            dispatch($job);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('SCADA Payload queued successfully', [
                'response_time_ms' => $responseTime,
                'data_count' => $dataCount,
                'queue' => $queueName,
                'job_class' => get_class($job),
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'accepted',
                'message' => 'Data accepted and queued for processing.',
                'data_count' => $dataCount,
                'queue' => $queueName,
                'response_time_ms' => $responseTime,
                'estimated_processing_time' => $this->estimateProcessingTime($dataCount),
                'note' => 'Data will be processed in the background. Check logs for progress updates.'
            ], 202); // HTTP 202 Accepted

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('SCADA Payload queuing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_count' => $dataCount,
                'response_time_ms' => $responseTime,
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Server error while queuing data for processing.',
                'error_code' => 'QUEUING_ERROR',
                'data_count' => $dataCount
            ], 500);
        }
    }

    /**
     * Broadcast real-time data untuk dashboard
     */
    private function broadcastRealtimeData(array $dataArray): void
    {
        try {
            if (empty($dataArray)) {
                return;
            }

            // Ambil data terbaru untuk real-time broadcasting
            $latestData = end($dataArray);

            // Format data untuk broadcasting
            $broadcastData = [
                'timestamp' => $latestData['_terminalTime'] ?? now()->toISOString(),
                'group_tag' => $latestData['_groupTag'] ?? 'unknown',
                'temperature' => $latestData['temperature'] ?? null,
                'humidity' => $latestData['humidity'] ?? null,
                'pressure' => $latestData['pressure'] ?? null,
                'rainfall' => $latestData['rainfall'] ?? null,
                'wind_speed' => $latestData['wind_speed'] ?? null,
                'wind_direction' => $latestData['wind_direction'] ?? null,
                'par_sensor' => $latestData['par_sensor'] ?? null,
                'solar_radiation' => $latestData['solar_radiation'] ?? null,
                'data_count' => count($dataArray)
            ];

            // Broadcast data real-time dengan throttling
            $this->broadcastingService->broadcastAggregatedData(
                $broadcastData,
                'scada-realtime',
                100 // Throttle 100ms
            );

            Log::debug('Real-time data broadcasted', [
                'group_tag' => $broadcastData['group_tag'],
                'timestamp' => $broadcastData['timestamp']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast real-time data', [
                'error' => $e->getMessage(),
                'data_count' => count($dataArray)
            ]);
        }
    }

    /**
     * Estimate processing time based on data count
     */
    private function estimateProcessingTime(int $dataCount): string
    {
        if ($dataCount <= 1000) {
            return '1-2 minutes';
        } elseif ($dataCount <= 5000) {
            return '3-5 minutes';
        } elseif ($dataCount <= 10000) {
            return '5-10 minutes';
        } else {
            return '10+ minutes';
        }
    }
}
