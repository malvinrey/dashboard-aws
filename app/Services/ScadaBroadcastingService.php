<?php

namespace App\Services;

use App\Events\ScadaDataReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ScadaBroadcastingService
{
    /**
     * Broadcast single data point
     */
    public function broadcastData($data, $channel = 'scada-data'): bool
    {
        try {
            ScadaDataReceived::dispatch($data, $channel);

            Log::info('Data broadcasted successfully', [
                'channel' => $channel,
                'data_size' => is_array($data) ? count($data) : 1,
                'timestamp' => now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast data', [
                'error' => $e->getMessage(),
                'channel' => $channel,
                'timestamp' => now()->toISOString()
            ]);

            return false;
        }
    }

    /**
     * Broadcast batch data with aggregation to prevent firehose
     */
    public function broadcastBatchData($dataArray, $channel = 'scada-batch'): bool
    {
        try {
            // Aggregate data untuk batch processing
            $aggregatedData = $this->aggregateBatchData($dataArray);

            ScadaDataReceived::dispatch($aggregatedData, $channel);

            Log::info('Batch data broadcasted successfully', [
                'channel' => $channel,
                'original_count' => count($dataArray),
                'aggregated_count' => count($aggregatedData),
                'timestamp' => now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast batch data', [
                'error' => $e->getMessage(),
                'channel' => $channel,
                'timestamp' => now()->toISOString()
            ]);

            return false;
        }
    }

    /**
     * Broadcast aggregated data with throttling
     */
    public function broadcastAggregatedData($data, $channel = 'scada-aggregated', $throttleMs = 100): bool
    {
        $cacheKey = "broadcast_throttle:{$channel}";
        $lastBroadcast = Cache::get($cacheKey);

        if ($lastBroadcast && (now()->timestamp - $lastBroadcast) * 1000 < $throttleMs) {
            // Skip broadcast due to throttling
            Log::debug('Broadcast throttled', [
                'channel' => $channel,
                'throttle_ms' => $throttleMs,
                'last_broadcast' => $lastBroadcast
            ]);
            return false;
        }

        try {
            ScadaDataReceived::dispatch($data, $channel);

            // Update throttle timestamp
            Cache::put($cacheKey, now()->timestamp, 60);

            Log::info('Aggregated data broadcasted successfully', [
                'channel' => $channel,
                'throttle_ms' => $throttleMs,
                'timestamp' => now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast aggregated data', [
                'error' => $e->getMessage(),
                'channel' => $channel,
                'timestamp' => now()->toISOString()
            ]);

            return false;
        }
    }

    /**
     * Aggregate batch data to prevent overwhelming clients
     */
    protected function aggregateBatchData($dataArray): array
    {
        if (empty($dataArray)) {
            return [];
        }

        $aggregated = [];

        foreach ($dataArray as $data) {
            $channel = $data['channel'] ?? $data['nama_group'] ?? 'unknown';

            if (!isset($aggregated[$channel])) {
                $aggregated[$channel] = [
                    'values' => [],
                    'count' => 0,
                    'sum' => 0,
                    'min' => PHP_FLOAT_MAX,
                    'max' => PHP_FLOAT_MIN,
                    'timestamps' => []
                ];
            }

            // Extract numeric values from data
            $numericValues = $this->extractNumericValues($data);

            foreach ($numericValues as $key => $value) {
                if (!isset($aggregated[$channel][$key])) {
                    $aggregated[$channel][$key] = [
                        'values' => [],
                        'count' => 0,
                        'sum' => 0,
                        'min' => PHP_FLOAT_MAX,
                        'max' => PHP_FLOAT_MIN
                    ];
                }

                $aggregated[$channel][$key]['values'][] = $value;
                $aggregated[$channel][$key]['count']++;
                $aggregated[$channel][$key]['sum'] += $value;
                $aggregated[$channel][$key]['min'] = min($aggregated[$channel][$key]['min'], $value);
                $aggregated[$channel][$key]['max'] = max($aggregated[$channel][$key]['max'], $value);
            }

            $aggregated[$channel]['count']++;
            $aggregated[$channel]['timestamps'][] = $data['timestamp'] ?? now()->toISOString();
        }

        // Calculate aggregated values
        $result = [];

        foreach ($aggregated as $channel => $channelData) {
            $channelResult = [
                'channel' => $channel,
                'count' => $channelData['count'],
                'timestamp' => max($channelData['timestamps']),
                'metrics' => []
            ];

            foreach ($channelData as $key => $metricData) {
                if (is_array($metricData) && isset($metricData['count']) && $metricData['count'] > 0) {
                    $channelResult['metrics'][$key] = [
                        'average' => $metricData['sum'] / $metricData['count'],
                        'min' => $metricData['min'],
                        'max' => $metricData['max'],
                        'count' => $metricData['count']
                    ];
                }
            }

            $result[] = $channelResult;
        }

        return $result;
    }

    /**
     * Extract numeric values from data array
     */
    protected function extractNumericValues($data): array
    {
        $numericValues = [];
        $numericKeys = [
            'temperature',
            'humidity',
            'pressure',
            'rainfall',
            'wind_speed',
            'wind_direction',
            'par_sensor',
            'solar_radiation'
        ];

        foreach ($numericKeys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                $numericValues[$key] = (float) $data[$key];
            }
        }

        return $numericValues;
    }

    /**
     * Get broadcasting statistics
     */
    public function getBroadcastingStats(): array
    {
        $stats = [];
        $channels = ['scada-data', 'scada-realtime', 'scada-batch', 'scada-aggregated'];

        foreach ($channels as $channel) {
            $cacheKey = "broadcast_throttle:{$channel}";
            $lastBroadcast = Cache::get($cacheKey);

            $stats[$channel] = [
                'last_broadcast' => $lastBroadcast ? date('Y-m-d H:i:s', $lastBroadcast) : 'Never',
                'is_active' => $lastBroadcast && (now()->timestamp - $lastBroadcast) < 300, // 5 minutes
                'cache_key' => $cacheKey
            ];
        }

        return $stats;
    }

    /**
     * Clear broadcasting cache
     */
    public function clearBroadcastingCache(): bool
    {
        try {
            $channels = ['scada-data', 'scada-realtime', 'scada-batch', 'scada-aggregated'];

            foreach ($channels as $channel) {
                $cacheKey = "broadcast_throttle:{$channel}";
                Cache::forget($cacheKey);
            }

            Log::info('Broadcasting cache cleared successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear broadcasting cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
