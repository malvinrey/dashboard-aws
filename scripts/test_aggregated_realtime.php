<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Aggregated Real-time Updates ===\n\n";

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
    echo "   - Data span: " . $firstRecord->timestamp_device->diffInMinutes($lastRecord->timestamp_device) . " minutes\n";
} else {
    echo "   - No data found in database\n";
    exit;
}

echo "\n";

// Test 1: Test different intervals
echo "1. Testing Aggregated Data Points for Different Intervals:\n";

$intervals = ['second', 'minute', 'hour', 'day'];
$tags = ['temperature', 'humidity'];

foreach ($intervals as $interval) {
    echo "   - Testing interval: {$interval}\n";

    $startTime = microtime(true);
    $aggregatedData = $service->getLatestAggregatedDataPoint($tags, $interval);
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);

    if ($aggregatedData) {
        echo "     * Timestamp: {$aggregatedData['timestamp']}\n";
        echo "     * Execution time: {$executionTime}ms\n";

        foreach ($aggregatedData['metrics'] as $metricName => $value) {
            echo "     * {$metricName}: {$value}\n";
        }

        // Verify timestamp format based on interval
        $timestamp = Carbon::parse($aggregatedData['timestamp']);
        $expectedFormat = match ($interval) {
            'second' => 'Y-m-d H:i:s',
            'minute' => 'Y-m-d H:i:00',
            'hour'   => 'Y-m-d H:00:00',
            'day'    => 'Y-m-d 00:00:00',
        };

        $expectedTimestamp = $timestamp->format($expectedFormat);
        $actualTimestamp = $aggregatedData['timestamp'];

        if ($actualTimestamp === $expectedTimestamp) {
            echo "     * ✅ Timestamp format correct for {$interval}\n";
        } else {
            echo "     * ❌ Timestamp format incorrect for {$interval}\n";
            echo "       - Expected: {$expectedTimestamp}\n";
            echo "       - Actual: {$actualTimestamp}\n";
        }
    } else {
        echo "     * ❌ No aggregated data found\n";
    }
    echo "\n";
}

// Test 2: Compare with historical data
echo "2. Comparing Aggregated vs Historical Data:\n";

$testIntervals = ['hour', 'day'];
$startDate = $lastRecord->timestamp_device->subDays(1)->toDateTimeString();
$endDate = $lastRecord->timestamp_device->toDateTimeString();

foreach ($testIntervals as $interval) {
    echo "   - Testing {$interval} interval:\n";

    // Get historical aggregated data
    $historicalData = $service->getHistoricalChartData($tags, $interval, $startDate, $endDate);

    if (!empty($historicalData['data'])) {
        $trace = $historicalData['data'][0];
        $lastHistoricalPoint = end($trace['x']);
        $lastHistoricalValue = end($trace['y']);

        echo "     * Last historical point: {$lastHistoricalPoint} = {$lastHistoricalValue}\n";

        // Get latest aggregated data
        $latestAggregated = $service->getLatestAggregatedDataPoint($tags, $interval);

        if ($latestAggregated) {
            $latestTimestamp = $latestAggregated['timestamp'];
            $latestValue = $latestAggregated['metrics'][$tags[0]];

            echo "     * Latest aggregated point: {$latestTimestamp} = {$latestValue}\n";

            // Check if timestamps match (they should for the same time bucket)
            if ($lastHistoricalPoint === $latestTimestamp) {
                echo "     * ✅ Timestamps match - Real-time update will work correctly!\n";
            } else {
                echo "     * ❌ Timestamps don't match - Real-time update may fail\n";
                echo "       - Historical: {$lastHistoricalPoint}\n";
                echo "       - Latest: {$latestTimestamp}\n";
            }
        }
    } else {
        echo "     * No historical data available\n";
    }
    echo "\n";
}

// Test 3: Performance test
echo "3. Performance Test:\n";

$performanceData = [];

foreach ($intervals as $interval) {
    $startTime = microtime(true);
    for ($i = 0; $i < 10; $i++) {
        $service->getLatestAggregatedDataPoint($tags, $interval);
    }
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    $avgTime = round($executionTime / 10, 2);

    $performanceData[$interval] = $avgTime;
    echo "   - {$interval}: {$avgTime}ms average per call\n";
}

// Sort by performance
asort($performanceData);
echo "\n   - Performance ranking (fastest to slowest):\n";
foreach ($performanceData as $interval => $time) {
    echo "     * {$interval}: {$time}ms\n";
}

echo "\n";

// Test 4: Data consistency test
echo "4. Data Consistency Test:\n";

$testInterval = 'hour';
echo "   - Testing data consistency for {$testInterval} interval:\n";

// Get multiple calls to ensure consistency
$results = [];
for ($i = 0; $i < 5; $i++) {
    $result = $service->getLatestAggregatedDataPoint($tags, $testInterval);
    if ($result) {
        $results[] = $result;
    }
}

if (count($results) > 1) {
    $firstResult = $results[0];
    $consistent = true;

    foreach ($results as $i => $result) {
        if ($result['timestamp'] !== $firstResult['timestamp']) {
            $consistent = false;
            echo "     * ❌ Inconsistent timestamp at call {$i}: {$result['timestamp']}\n";
        }
    }

    if ($consistent) {
        echo "     * ✅ All timestamps consistent: {$firstResult['timestamp']}\n";
    }

    // Check metric values consistency
    foreach ($tags as $tag) {
        $values = array_column($results, 'metrics');
        $tagValues = array_column($values, $tag);

        if (count(array_unique($tagValues)) === 1) {
            echo "     * ✅ {$tag} values consistent: {$tagValues[0]}\n";
        } else {
            echo "     * ❌ {$tag} values inconsistent: " . implode(', ', $tagValues) . "\n";
        }
    }
} else {
    echo "     * No data available for consistency test\n";
}

echo "\n=== Test Complete ===\n";
