<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ScadaDataWide;
use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Wide Format Migration ===\n\n";

// Test 1: Check record counts
echo "1. Record Counts:\n";
$tallCount = \App\Models\ScadaDataTall::count();
$wideCount = ScadaDataWide::count();
echo "   - ScadaDataTall: {$tallCount} records\n";
echo "   - ScadaDataWide: {$wideCount} records\n";
echo "   - Compression ratio: " . round(($tallCount / $wideCount), 2) . "x\n\n";

// Test 2: Check sample data structure
echo "2. Sample Data Structure:\n";
$sampleWide = ScadaDataWide::first();
if ($sampleWide) {
    echo "   - Sample record ID: {$sampleWide->id}\n";
    echo "   - Timestamp: {$sampleWide->timestamp_device}\n";
    echo "   - Available sensors: ";
    $sensors = ['par_sensor', 'solar_radiation', 'wind_speed', 'wind_direction', 'temperature', 'humidity', 'pressure', 'rainfall'];
    $availableSensors = [];
    foreach ($sensors as $sensor) {
        if (!is_null($sampleWide->$sensor)) {
            $availableSensors[] = $sensor;
        }
    }
    echo implode(', ', $availableSensors) . "\n";
    echo "   - Sample values:\n";
    foreach ($availableSensors as $sensor) {
        echo "     * {$sensor}: {$sampleWide->$sensor}\n";
    }
} else {
    echo "   - No data found in wide table\n";
}
echo "\n";

// Test 3: Test ScadaDataService methods
echo "3. Testing ScadaDataService:\n";
$service = new ScadaDataService();

// Test getDashboardMetrics
echo "   - Testing getDashboardMetrics():\n";
$metrics = $service->getDashboardMetrics();
echo "     * Found " . count($metrics['metrics']) . " metrics\n";
foreach ($metrics['metrics'] as $tag => $metric) {
    echo "     * {$tag}: {$metric['value']} {$metric['unit']}\n";
}
echo "\n";

// Test getUniqueTags
echo "   - Testing getUniqueTags():\n";
$tags = $service->getUniqueTags();
echo "     * Available tags: " . implode(', ', $tags->toArray()) . "\n";
echo "\n";

// Test getHistoricalChartData
echo "   - Testing getHistoricalChartData():\n";
$startDate = now()->subDay()->toDateTimeString();
$endDate = now()->toDateTimeString();
$chartData = $service->getHistoricalChartData(['temperature', 'humidity'], 'hour', $startDate, $endDate);
echo "     * Chart data traces: " . count($chartData['data']) . "\n";
foreach ($chartData['data'] as $trace) {
    echo "     * Trace '{$trace['name']}': " . count($trace['x']) . " data points\n";
}
echo "\n";

// Test getLatestDataPoint
echo "   - Testing getLatestDataPoint():\n";
$latestData = $service->getLatestDataPoint(['temperature', 'humidity']);
if ($latestData) {
    echo "     * Latest timestamp: {$latestData['timestamp']}\n";
    foreach ($latestData['metrics'] as $tag => $value) {
        echo "     * {$tag}: {$value}\n";
    }
} else {
    echo "     * No latest data found\n";
}
echo "\n";

echo "=== Test Complete ===\n";
