<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaDataService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Log Viewer with Wide Format ===\n\n";

$service = new ScadaDataService();

// Test getLogData with wide format
echo "1. Testing getLogData() (Wide Format):\n";
$logs = $service->getLogData(5);

echo "   - Total logs retrieved: " . count($logs) . "\n";
echo "   - Sample logs:\n";

foreach ($logs as $log) {
    echo "     * ID: {$log->id}\n";
    echo "       - Timestamp: {$log->timestamp_device}\n";
    echo "       - Group: {$log->nama_group}\n";
    echo "       - PAR Sensor: " . ($log->par_sensor ?? 'null') . "\n";
    echo "       - Solar Radiation: " . ($log->solar_radiation ?? 'null') . "\n";
    echo "       - Wind Speed: " . ($log->wind_speed ?? 'null') . "\n";
    echo "       - Wind Direction: " . ($log->wind_direction ?? 'null') . "\n";
    echo "       - Temperature: " . ($log->temperature ?? 'null') . "\n";
    echo "       - Humidity: " . ($log->humidity ?? 'null') . "\n";
    echo "       - Pressure: " . ($log->pressure ?? 'null') . "\n";
    echo "       - Rainfall: " . ($log->rainfall ?? 'null') . "\n";
    echo "\n";
}

// Test getTotalRecords
echo "2. Testing getTotalRecords():\n";
$totalRecords = $service->getTotalRecords();
echo "   - Total records in wide table: {$totalRecords}\n";

// Test performance comparison
echo "3. Performance Comparison:\n";
$startTime = microtime(true);
$logs = $service->getLogData(50);
$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);

echo "   - Time to load 50 wide records: {$executionTime}ms\n";
echo "   - Records per millisecond: " . round(50 / $executionTime, 2) . "\n";

// Test data density
echo "4. Data Density Analysis:\n";
$totalSensors = 0;
$nonNullSensors = 0;

foreach ($logs as $log) {
    $sensors = ['par_sensor', 'solar_radiation', 'wind_speed', 'wind_direction', 'temperature', 'humidity', 'pressure', 'rainfall'];
    $totalSensors += count($sensors);

    foreach ($sensors as $sensor) {
        if (!is_null($log->$sensor)) {
            $nonNullSensors++;
        }
    }
}

$dataDensity = round(($nonNullSensors / $totalSensors) * 100, 1);
echo "   - Total sensor slots: {$totalSensors}\n";
echo "   - Non-null sensor values: {$nonNullSensors}\n";
echo "   - Data density: {$dataDensity}%\n";

echo "\n=== Test Complete ===\n";
