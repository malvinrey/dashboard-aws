<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ScadaDataService;

echo "=== Testing Aggregated Values ===\n\n";

$scadaService = new ScadaDataService();

// Test parameters
$tags = ['temperature', 'humidity', 'pressure'];
$startDate = '2025-08-11 00:00:00';
$endDate = '2025-08-12 00:00:00';

echo "Test Parameters:\n";
echo "- Tags: " . implode(', ', $tags) . "\n";
echo "- Start Date: $startDate\n";
echo "- End Date: $endDate\n\n";

// Test each aggregation level
$levels = ['second', 'minute', 'hour', 'day'];

foreach ($levels as $level) {
    echo "=== $level LEVEL AGGREGATION ===\n";

    try {
        $startTime = microtime(true);
        $result = $scadaService->getAggregatedHistoricalData($tags, $startDate, $endDate, $level);
        $endTime = microtime(true);

        echo "Execution time: " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
        echo "Records returned: " . $result->count() . "\n";

        if ($result->count() > 0) {
            $firstRecord = $result->first();

            // Handle both Eloquent models and stdClass objects
            if (method_exists($firstRecord, 'toArray')) {
                $recordData = $firstRecord->toArray();
            } else {
                $recordData = (array) $firstRecord;
            }

            echo "First record structure: " . implode(', ', array_keys($recordData)) . "\n";

            // Show sample values
            if ($level === 'second') {
                echo "Sample data:\n";
                echo "  - Timestamp: " . $recordData['timestamp_device'] . "\n";
                echo "  - Group: " . $recordData['nama_group'] . "\n";
                echo "  - Temperature: " . $recordData['temperature'] . "\n";
                echo "  - Humidity: " . $recordData['humidity'] . "\n";
                echo "  - Pressure: " . $recordData['pressure'] . "\n";
            } else {
                echo "Sample aggregated data:\n";
                echo "  - Time Bucket: " . $recordData['time_bucket'] . "\n";
                echo "  - Temperature (avg/max/min): " .
                    round($recordData['avg_temperature'], 2) . " / " .
                    round($recordData['max_temperature'], 2) . " / " .
                    round($recordData['min_temperature'], 2) . "\n";
                echo "  - Humidity (avg/max/min): " .
                    round($recordData['avg_humidity'], 2) . " / " .
                    round($recordData['max_humidity'], 2) . " / " .
                    round($recordData['min_humidity'], 2) . "\n";
                echo "  - Pressure (avg/max/min): " .
                    round($recordData['avg_pressure'], 2) . " / " .
                    round($recordData['max_pressure'], 2) . " / " .
                    round($recordData['min_pressure'], 2) . "\n";
            }

            // Show last record for comparison
            if ($result->count() > 1) {
                $lastRecord = $result->last();

                if (method_exists($lastRecord, 'toArray')) {
                    $lastData = $lastRecord->toArray();
                } else {
                    $lastData = (array) $lastRecord;
                }

                if ($level === 'second') {
                    echo "Last record:\n";
                    echo "  - Timestamp: " . $lastData['timestamp_device'] . "\n";
                    echo "  - Temperature: " . $lastData['temperature'] . "\n";
                } else {
                    echo "Last record:\n";
                    echo "  - Time Bucket: " . $lastData['time_bucket'] . "\n";
                    echo "  - Temperature (avg): " . round($lastData['avg_temperature'], 2) . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "=== Performance Analysis ===\n";
$performanceData = [];

foreach ($levels as $level) {
    try {
        $startTime = microtime(true);
        $result = $scadaService->getAggregatedHistoricalData($tags, $startDate, $endDate, $level);
        $endTime = microtime(true);

        $performanceData[$level] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'records' => $result->count(),
            'efficiency' => round($result->count() / (($endTime - $startTime) * 1000), 2)
        ];
    } catch (Exception $e) {
        $performanceData[$level] = ['error' => $e->getMessage()];
    }
}

foreach ($performanceData as $level => $data) {
    if (isset($data['error'])) {
        echo "$level: ERROR - " . $data['error'] . "\n";
    } else {
        echo "$level: {$data['execution_time']}ms, {$data['records']} records, {$data['efficiency']} records/ms\n";
    }
}

echo "\n=== Test Completed ===\n";
