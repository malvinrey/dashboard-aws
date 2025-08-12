<?php

/**
 * Test script untuk method getAggregatedHistoricalData yang baru
 *
 * Script ini akan menguji:
 * 1. Agregasi per detik (raw data)
 * 2. Agregasi per menit
 * 3. Agregasi per jam
 * 4. Agregasi per hari
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;

echo "=== Testing getAggregatedHistoricalData Method ===\n\n";

$scadaService = new ScadaDataService();

// Test data - using actual data range from database
$tags = ['temperature', 'humidity', 'pressure'];
$startDate = '2025-08-11 00:00:00';  // Date with 2650 records
$endDate = '2025-08-12 00:00:00';    // Date with 1507 records

echo "Test Parameters:\n";
echo "- Tags: " . implode(', ', $tags) . "\n";
echo "- Start Date: $startDate\n";
echo "- End Date: $endDate\n\n";

// Test 1: Second level (raw data)
echo "1. Testing SECOND level aggregation:\n";
try {
    $startTime = microtime(true);
    $result = $scadaService->getAggregatedHistoricalData($tags, $startDate, $endDate, 'second');
    $endTime = microtime(true);

    echo "   - Execution time: " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
    echo "   - Records returned: " . $result->count() . "\n";

    if ($result->count() > 0) {
        $firstRecord = $result->first();
        $recordData = $firstRecord->toArray();
        echo "   - First record columns: " . implode(', ', array_keys($recordData)) . "\n";

        // Show sample values for key columns
        if (isset($recordData['timestamp_device'])) {
            echo "   - Sample timestamp: " . $recordData['timestamp_device'] . "\n";
        }
        if (isset($recordData['time_bucket'])) {
            echo "   - Sample time_bucket: " . $recordData['time_bucket'] . "\n";
        }
        if (isset($recordData['temperature'])) {
            echo "   - Sample temperature: " . $recordData['temperature'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Minute level
echo "2. Testing MINUTE level aggregation:\n";
try {
    $startTime = microtime(true);
    $result = $scadaService->getAggregatedHistoricalData($tags, $startDate, $endDate, 'minute');
    $endTime = microtime(true);

    echo "   - Execution time: " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
    echo "   - Records returned: " . $result->count() . "\n";

    if ($result->count() > 0) {
        $firstRecord = $result->first();
        echo "   - First record columns: " . implode(', ', array_keys((array) $firstRecord)) . "\n";
        echo "   - Sample time_bucket: " . ($firstRecord->time_bucket ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Hour level
echo "3. Testing HOUR level aggregation:\n";
try {
    $startTime = microtime(true);
    $result = $scadaService->getAggregatedHistoricalData($tags, $startDate, $endDate, 'hour');
    $endTime = microtime(true);

    echo "   - Execution time: " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
    echo "   - Records returned: " . $result->count() . "\n";

    if ($result->count() > 0) {
        $firstRecord = $result->first();
        echo "   - First record columns: " . implode(', ', array_keys((array) $firstRecord)) . "\n";
        echo "   - Sample time_bucket: " . ($firstRecord->time_bucket ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Day level
echo "4. Testing DAY level aggregation:\n";
try {
    $startTime = microtime(true);
    $result = $scadaService->getAggregatedHistoricalData($tags, $startDate, $endDate, 'day');
    $endTime = microtime(true);

    echo "   - Execution time: " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
    echo "   - Records returned: " . $result->count() . "\n";

    if ($result->count() > 0) {
        $firstRecord = $result->first();
        echo "   - First record columns: " . implode(', ', array_keys((array) $firstRecord)) . "\n";
        echo "   - Sample time_bucket: " . ($firstRecord->time_bucket ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Performance comparison
echo "5. Performance Comparison:\n";
$aggregationLevels = ['second', 'minute', 'hour', 'day'];
$performanceData = [];

foreach ($aggregationLevels as $level) {
    try {
        $startTime = microtime(true);
        $result = $scadaService->getAggregatedHistoricalData($tags, $startDate, $endDate, $level);
        $endTime = microtime(true);

        $performanceData[$level] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'records' => $result->count()
        ];
    } catch (Exception $e) {
        $performanceData[$level] = ['error' => $e->getMessage()];
    }
}

foreach ($performanceData as $level => $data) {
    if (isset($data['error'])) {
        echo "   - $level: ERROR - " . $data['error'] . "\n";
    } else {
        echo "   - $level: " . $data['execution_time'] . "ms, " . $data['records'] . " records\n";
    }
}

echo "\n=== Test Completed ===\n";
