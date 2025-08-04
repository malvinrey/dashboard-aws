<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaDataService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Log Viewer with Wide Format ===\n\n";

$service = new ScadaDataService();

// Test getLogData
echo "1. Testing getLogData():\n";
$logs = $service->getLogData(10);

echo "   - Total logs retrieved: " . count($logs) . "\n";
echo "   - Sample logs:\n";

foreach ($logs->take(5) as $log) {
    echo "     * ID: {$log->id}\n";
    echo "       - Timestamp: {$log->timestamp_device}\n";
    echo "       - Group: {$log->nama_group}\n";
    echo "       - Tag: {$log->nama_tag}\n";
    echo "       - Value: {$log->nilai_tag}\n";
    echo "       - Batch ID: {$log->batch_id}\n";
    echo "\n";
}

// Test getTotalRecords
echo "2. Testing getTotalRecords():\n";
$totalRecords = $service->getTotalRecords();
echo "   - Total records in wide table: {$totalRecords}\n";

// Test data transformation
echo "3. Testing Data Transformation:\n";
$wideData = \App\Models\ScadaDataWide::orderBy('id', 'desc')->limit(3)->get();
echo "   - Wide format records: " . $wideData->count() . "\n";

foreach ($wideData as $wideRecord) {
    echo "     * Wide Record ID: {$wideRecord->id}\n";
    echo "       - Timestamp: {$wideRecord->timestamp_device}\n";
    echo "       - Available sensors: ";

    $sensors = ['par_sensor', 'solar_radiation', 'wind_speed', 'wind_direction', 'temperature', 'humidity', 'pressure', 'rainfall'];
    $availableSensors = [];

    foreach ($sensors as $sensor) {
        if (!is_null($wideRecord->$sensor)) {
            $availableSensors[] = $sensor;
        }
    }

    echo implode(', ', $availableSensors) . "\n";
    echo "       - Values: ";

    foreach ($availableSensors as $sensor) {
        echo "{$sensor}={$wideRecord->$sensor} ";
    }
    echo "\n\n";
}

echo "=== Test Complete ===\n";
