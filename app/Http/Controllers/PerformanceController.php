<?php

namespace App\Http\Controllers;

use App\Services\ScadaDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PerformanceController extends Controller
{
    public function __construct(protected ScadaDataService $scadaDataService) {}

    /**
     * Display performance dashboard
     */
    public function index()
    {
        // Cache health data for 5 minutes to avoid excessive database queries
        $healthData = Cache::remember('database_health', 300, function () {
            return $this->scadaDataService->getDatabaseHealth();
        });

        return view('performance.dashboard', compact('healthData'));
    }

    /**
     * API endpoint for real-time performance metrics
     */
    public function getMetrics(Request $request)
    {
        try {
            $metrics = Cache::remember('performance_metrics', 60, function () {
                return [
                    'database_health' => $this->scadaDataService->getDatabaseHealth(),
                    'system_info' => [
                        'php_version' => PHP_VERSION,
                        'laravel_version' => app()->version(),
                        'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                        'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
                        'uptime' => $this->getSystemUptime(),
                    ]
                ];
            });

            return response()->json($metrics);
        } catch (\Exception $e) {
            Log::error('Performance metrics fetch failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to fetch performance metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint for database optimization recommendations
     */
    public function getOptimizationRecommendations()
    {
        try {
            $health = $this->scadaDataService->getDatabaseHealth();

            $recommendations = [
                'immediate' => [],
                'short_term' => [],
                'long_term' => []
            ];

            // Immediate actions
            if ($health['status'] === 'unhealthy') {
                $recommendations['immediate'][] = 'Database connection issues detected. Check database server status.';
            }

            if ($health['latest_data_age_minutes'] > 60) {
                $recommendations['immediate'][] = 'Data pipeline appears to be down. Investigate immediately.';
            }

            // Short term optimizations
            if ($health['table_size_mb'] > 100) {
                $recommendations['short_term'][] = 'Consider adding database indexes on timestamp_device and nama_group columns.';
            }

            if ($health['insertion_rate_last_hour'] > 5000) {
                $recommendations['short_term'][] = 'High insertion rate detected. Consider implementing batch processing.';
            }

            // Long term optimizations
            if ($health['total_records'] > 1000000) {
                $recommendations['long_term'][] = 'Large dataset detected. Consider implementing data partitioning or archiving.';
            }

            return response()->json([
                'health_status' => $health['status'],
                'recommendations' => $recommendations,
                'last_updated' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            Log::error('Optimization recommendations fetch failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to fetch optimization recommendations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear performance cache
     */
    public function clearCache()
    {
        try {
            Cache::forget('database_health');
            Cache::forget('performance_metrics');

            Log::info('Performance cache cleared manually');

            return response()->json([
                'status' => 'success',
                'message' => 'Performance cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cache clearing failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear cache'
            ], 500);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get system uptime information
     */
    private function getSystemUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return 'Load: ' . round($load[0], 2) . ' (1m), ' . round($load[1], 2) . ' (5m), ' . round($load[2], 2) . ' (15m)';
        }

        return 'Load information not available';
    }
}
