<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Interval Aggregation ===\n\n";

$service = new ScadaDataService();

// Check available data first
echo "0. Checking Available Data:\n";
$totalRecords = \App\Models\ScadaDataWide::count();
echo "   - Total records in database: {$totalRecords}\n";

if ($totalRecords > 0) {
    $firstRecord = \App\Models\ScadaDataWide::orderBy('timestamp_device', 'asc')->first();
    $lastRecord = \App\Models\ScadaDataWide::orderBy('timestamp_device', 'desc')->first();

    echo "   - First record: {$firstRecord->timestamp_device}\n";
    echo "   - Last record: {$lastRecord->timestamp_device}\n";

    // Use actual data range
    $startDate = $firstRecord->timestamp_device->subDays(1)->toDateTimeString();
    $endDate = $lastRecord->timestamp_device->toDateTimeString();

    echo "   - Using date range: {$startDate} to {$endDate}\n";
} else {
    echo "   - No data found in database\n";
    exit;
}

echo "\n";

// Test data untuk berbagai interval
$intervals = ['second', 'minute', 'hour', 'day'];
$tags = ['temperature', 'humidity'];

foreach ($intervals as $interval) {
    echo "1. Testing Interval: {$interval}\n";
    echo "   - Date Range: {$startDate} to {$endDate}\n";

    $startTime = microtime(true);
    $chartData = $service->getHistoricalChartData($tags, $interval, $startDate, $endDate);
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);

    echo "   - Execution time: {$executionTime}ms\n";
    echo "   - Chart traces: " . count($chartData['data']) . "\n";

    foreach ($chartData['data'] as $trace) {
        $dataPoints = count($trace['x']);
        echo "     * Trace '{$trace['name']}': {$dataPoints} data points\n";

        // Show sample data points
        if ($dataPoints > 0) {
            echo "       - Sample timestamps: ";
            $sampleTimestamps = array_slice($trace['x'], 0, 3);
            echo implode(', ', $sampleTimestamps);
            if ($dataPoints > 3) {
                echo " ... (and " . ($dataPoints - 3) . " more)";
            }
            echo "\n";

            echo "       - Sample values: ";
            $sampleValues = array_slice($trace['y'], 0, 3);
            echo implode(', ', $sampleValues);
            if ($dataPoints > 3) {
                echo " ... (and " . ($dataPoints - 3) . " more)";
            }
            echo "\n";
        }
    }
    echo "\n";
}

// Test performance comparison
echo "2. Performance Comparison:\n";
$performanceData = [];

foreach ($intervals as $interval) {
    $startTime = microtime(true);
    $chartData = $service->getHistoricalChartData($tags, $interval, $startDate, $endDate);
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);

    $totalDataPoints = 0;
    foreach ($chartData['data'] as $trace) {
        $totalDataPoints += count($trace['x']);
    }

    $performanceData[$interval] = [
        'execution_time' => $executionTime,
        'data_points' => $totalDataPoints,
        'points_per_ms' => $totalDataPoints > 0 ? round($totalDataPoints / $executionTime, 2) : 0
    ];
}

// Sort by execution time
asort($performanceData);

echo "   - Performance Ranking (fastest to slowest):\n";
foreach ($performanceData as $interval => $data) {
    echo "     * {$interval}: {$data['execution_time']}ms, {$data['data_points']} points, {$data['points_per_ms']} points/ms\n";
}

echo "\n";

// Test data aggregation logic
echo "3. Data Aggregation Logic Test:\n";
$testIntervals = ['hour', 'day'];

foreach ($testIntervals as $interval) {
    echo "   - Testing {$interval} aggregation:\n";

    $chartData = $service->getHistoricalChartData($tags, $interval, $startDate, $endDate);

    if (!empty($chartData['data'])) {
        $trace = $chartData['data'][0]; // Take first trace
        $dataPoints = count($trace['x']);

        echo "     * Data points: {$dataPoints}\n";

        if ($dataPoints > 0) {
            // Check if timestamps are properly grouped
            $firstTimestamp = Carbon::parse($trace['x'][0]);
            $lastTimestamp = Carbon::parse($trace['x'][$dataPoints - 1]);

            echo "     * Time range: {$firstTimestamp->format('Y-m-d H:i:s')} to {$lastTimestamp->format('Y-m-d H:i:s')}\n";

            // Check interval spacing
            if ($dataPoints > 1) {
                $secondTimestamp = Carbon::parse($trace['x'][1]);
                $intervalSpacing = $firstTimestamp->diffInSeconds($secondTimestamp);

                $expectedSpacing = match ($interval) {
                    'hour' => 3600, // 1 hour in seconds
                    'day' => 86400, // 1 day in seconds
                    default => 0
                };

                echo "     * Interval spacing: {$intervalSpacing}s (expected: {$expectedSpacing}s)\n";

                if (abs($intervalSpacing - $expectedSpacing) <= 60) { // Allow 1 minute tolerance
                    echo "     * ✅ Interval spacing is correct\n";
                } else {
                    echo "     * ❌ Interval spacing is incorrect\n";
                }
            }
        }
    } else {
        echo "     * No data found\n";
    }
    echo "\n";
}

echo "=== Test Complete ===\n";
