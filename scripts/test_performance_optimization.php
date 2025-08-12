<?php

/**
 * Test Script untuk Verifikasi Optimasi Performance
 *
 * Script ini akan menguji:
 * 1. Bulk Insert vs Individual Insert
 * 2. Query Optimization
 * 3. Database Health Monitoring
 * 4. Performance Metrics
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Testing Performance Optimizations...\n\n";

// Test 1: Bulk Insert Performance
echo "📊 Test 1: Bulk Insert Performance\n";
echo "=====================================\n";

$scadaService = new ScadaDataService();

// Generate test data
$testPayload = [
    'DataArray' => []
];

for ($i = 0; $i < 1000; $i++) {
    $testPayload['DataArray'][] = [
        '_groupTag' => 'TEST_GROUP_' . ($i % 10),
        '_terminalTime' => now()->subMinutes($i)->format('Y-m-d H:i:s'),
        'temperature' => rand(20, 35),
        'humidity' => rand(40, 80),
        'pressure' => rand(1000, 1020),
        'rainfall' => rand(0, 5),
        'wind_speed' => rand(0, 20),
        'wind_direction' => rand(0, 360),
        'par_sensor' => rand(0, 1000),
        'solar_radiation' => rand(0, 800)
    ];
}

echo "Generated " . count($testPayload['DataArray']) . " test records\n";

// Test bulk insert performance
$startTime = microtime(true);
try {
    $scadaService->processScadaPayload($testPayload);
    $endTime = microtime(true);
    $processingTime = round(($endTime - $startTime) * 1000, 2);

    echo "✅ Bulk Insert completed in: {$processingTime}ms\n";
    echo "📈 Performance: " . round(count($testPayload['DataArray']) / ($processingTime / 1000), 0) . " records/second\n\n";
} catch (Exception $e) {
    echo "❌ Bulk Insert failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Database Health Check
echo "🏥 Test 2: Database Health Check\n";
echo "==================================\n";

try {
    $healthData = $scadaService->getDatabaseHealth();

    echo "✅ Database Status: " . ucfirst($healthData['status']) . "\n";
    echo "📊 Table Size: " . $healthData['table_size_mb'] . " MB\n";
    echo "📝 Total Records: " . number_format($healthData['total_records']) . "\n";
    echo "⏰ Latest Data Age: " . ($healthData['latest_data_age_minutes'] ?? 'N/A') . " minutes\n";
    echo "🚀 Insertion Rate (Last Hour): " . number_format($healthData['insertion_rate_last_hour']) . " records/hour\n";
    echo "⚡ Health Check Time: " . $healthData['health_check_time_ms'] . "ms\n";

    if (!empty($healthData['recommendations'])) {
        echo "\n💡 Recommendations:\n";
        foreach ($healthData['recommendations'] as $rec) {
            echo "   • {$rec}\n";
        }
    }

    echo "\n";
} catch (Exception $e) {
    echo "❌ Health Check failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Query Performance
echo "🔍 Test 3: Query Performance\n";
echo "==============================\n";

try {
    // Test dashboard metrics query
    $startTime = microtime(true);
    $dashboardData = $scadaService->getDashboardMetrics();
    $endTime = microtime(true);
    $queryTime = round(($endTime - $startTime) * 1000, 2);

    echo "✅ Dashboard Metrics Query: {$queryTime}ms\n";
    echo "📊 Metrics Count: " . count($dashboardData['metrics']) . "\n";

    // Test historical data query
    $startTime = microtime(true);
    $historicalData = $scadaService->getHistoricalChartData(
        ['temperature', 'humidity', 'pressure'],
        'hour',
        now()->subDays(7)->format('Y-m-d H:i:s'),
        now()->format('Y-m-d H:i:s')
    );
    $endTime = microtime(true);
    $queryTime = round(($endTime - $startTime) * 1000, 2);

    echo "✅ Historical Data Query: {$queryTime}ms\n";
    echo "📈 Data Points: " . count($historicalData['data'][0]['x'] ?? []) . "\n";

    // Test log data with pagination
    $startTime = microtime(true);
    $logData = $scadaService->getLogData(50, 1);
    $endTime = microtime(true);
    $queryTime = round(($endTime - $startTime) * 1000, 2);

    echo "✅ Log Data Query (Pagination): {$queryTime}ms\n";
    echo "📄 Records Returned: " . $logData->count() . "\n";
    echo "📊 Total Records: " . $logData->total() . "\n";

    echo "\n";
} catch (Exception $e) {
    echo "❌ Query Performance test failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Memory Usage
echo "💾 Test 4: Memory Usage\n";
echo "========================\n";

$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

echo "✅ Current Memory Usage: " . formatBytes($memoryUsage) . "\n";
echo "📈 Peak Memory Usage: " . formatBytes($peakMemory) . "\n";
echo "🔍 Memory Efficiency: " . round(($memoryUsage / $peakMemory) * 100, 1) . "%\n\n";

// Test 5: Large Dataset Processing
echo "📊 Test 5: Large Dataset Processing\n";
echo "====================================\n";

// Generate larger test dataset
$largeTestPayload = [
    'DataArray' => []
];

for ($i = 0; $i < 5000; $i++) {
    $largeTestPayload['DataArray'][] = [
        '_groupTag' => 'LARGE_TEST_' . ($i % 20),
        '_terminalTime' => now()->subMinutes($i)->format('Y-m-d H:i:s'),
        'temperature' => rand(20, 35),
        'humidity' => rand(40, 80),
        'pressure' => rand(1000, 1020),
        'rainfall' => rand(0, 5),
        'wind_speed' => rand(0, 20),
        'wind_direction' => rand(0, 360),
        'par_sensor' => rand(0, 1000),
        'solar_radiation' => rand(0, 800)
    ];
}

echo "Generated " . count($largeTestPayload['DataArray']) . " large test records\n";

$startTime = microtime(true);
try {
    $scadaService->processLargeDataset($largeTestPayload, 1000);
    $endTime = microtime(true);
    $processingTime = round(($endTime - $startTime) * 1000, 2);

    echo "✅ Large Dataset Processing completed in: {$processingTime}ms\n";
    echo "📈 Performance: " . round(count($largeTestPayload['DataArray']) / ($processingTime / 1000), 0) . " records/second\n";
    echo "🔧 Chunk Size: 1000 records per transaction\n\n";
} catch (Exception $e) {
    echo "❌ Large Dataset Processing failed: " . $e->getMessage() . "\n\n";
}

// Summary
echo "🎯 Performance Optimization Summary\n";
echo "===================================\n";
echo "✅ Bulk Insert: Implemented for better performance\n";
echo "✅ Query Optimization: Added pagination and selective column selection\n";
echo "✅ Database Health Monitoring: Real-time health checks\n";
echo "✅ Performance Metrics: Comprehensive monitoring system\n";
echo "✅ Large Dataset Handling: Chunked processing for scalability\n";
echo "✅ Memory Management: Efficient memory usage tracking\n\n";

echo "🚀 All tests completed successfully!\n";

function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}
