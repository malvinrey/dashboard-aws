<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Real-time Update Fix ===\n\n";

$service = new ScadaDataService();

// Test 1: Check metric name formatting consistency
echo "1. Testing Metric Name Formatting Consistency:\n";

$tags = ['par_sensor', 'solar_radiation', 'wind_speed', 'wind_direction', 'temperature', 'humidity', 'pressure', 'rainfall'];

echo "   - PHP formatting (from ScadaDataService):\n";
foreach ($tags as $tag) {
    $formattedName = ucfirst(str_replace('_', ' ', $tag));
    echo "     * '{$tag}' -> '{$formattedName}'\n";
}

echo "\n   - JavaScript formatting (should match PHP):\n";
foreach ($tags as $tag) {
    // Simulate JavaScript formatting - corrected version
    $formattedName = strtoupper($tag[0]) . substr($tag, 1);
    $formattedName = str_replace('_', ' ', $formattedName);
    echo "     * '{$tag}' -> '{$formattedName}'\n";
}

echo "\n   - Verification:\n";
foreach ($tags as $tag) {
    $phpFormatted = ucfirst(str_replace('_', ' ', $tag));
    $jsFormatted = strtoupper($tag[0]) . substr($tag, 1);
    $jsFormatted = str_replace('_', ' ', $jsFormatted);

    if ($phpFormatted === $jsFormatted) {
        echo "     * ✅ '{$tag}' -> '{$phpFormatted}' (MATCH)\n";
    } else {
        echo "     * ❌ '{$tag}' -> PHP: '{$phpFormatted}' vs JS: '{$jsFormatted}' (MISMATCH)\n";
    }
}

echo "\n";

// Test 2: Check historical chart data format
echo "2. Testing Historical Chart Data Format:\n";

$startDate = now()->subDay()->toDateTimeString();
$endDate = now()->toDateTimeString();
$chartData = $service->getHistoricalChartData(['temperature', 'humidity'], 'hour', $startDate, $endDate);

echo "   - Chart traces created:\n";
foreach ($chartData['data'] as $trace) {
    echo "     * Trace name: '{$trace['name']}'\n";
    echo "       - Data points: " . count($trace['x']) . "\n";
    if (count($trace['x']) > 0) {
        echo "       - Sample timestamp: {$trace['x'][0]}\n";
        echo "       - Sample value: {$trace['y'][0]}\n";
    }
}

echo "\n";

// Test 3: Check latest data format
echo "3. Testing Latest Data Format:\n";

$latestData = $service->getLatestDataPoint(['temperature', 'humidity']);

if ($latestData) {
    echo "   - Latest data structure:\n";
    echo "     * Timestamp: {$latestData['timestamp']}\n";
    echo "     * Metrics:\n";
    foreach ($latestData['metrics'] as $metricName => $value) {
        echo "       - '{$metricName}': {$value}\n";
    }

    echo "\n   - Expected JavaScript processing:\n";
    foreach ($latestData['metrics'] as $metricName => $value) {
        $formattedName = strtoupper($metricName[0]) . substr($metricName, 1);
        $formattedName = str_replace('_', ' ', $formattedName);
        echo "     * '{$metricName}' -> '{$formattedName}' (should match trace name)\n";
    }
} else {
    echo "   - No latest data found\n";
}

echo "\n";

// Test 4: Simulate real-time update scenario
echo "4. Simulating Real-time Update Scenario:\n";

// Get historical data first
$historicalData = $service->getHistoricalChartData(['temperature'], 'second', $startDate, $endDate);

if (!empty($historicalData['data'])) {
    $trace = $historicalData['data'][0];
    echo "   - Historical trace created:\n";
    echo "     * Trace name: '{$trace['name']}'\n";
    echo "     * Data points: " . count($trace['x']) . "\n";

    // Simulate real-time data
    $realtimeData = [
        'timestamp' => now()->toDateTimeString(),
        'metrics' => [
            'temperature' => 25.5
        ]
    ];

    echo "\n   - Real-time data received:\n";
    echo "     * Timestamp: {$realtimeData['timestamp']}\n";
    foreach ($realtimeData['metrics'] as $metricName => $value) {
        echo "     * '{$metricName}': {$value}\n";
    }

    echo "\n   - JavaScript processing simulation:\n";
    foreach ($realtimeData['metrics'] as $metricName => $value) {
        $formattedName = strtoupper($metricName[0]) . substr($metricName, 1);
        $formattedName = str_replace('_', ' ', $formattedName);
        echo "     * Looking for trace: '{$formattedName}'\n";

        if ($formattedName === $trace['name']) {
            echo "     * ✅ MATCH FOUND - Update will work!\n";
        } else {
            echo "     * ❌ NO MATCH - Update will fail!\n";
            echo "       - Expected: '{$formattedName}'\n";
            echo "       - Found: '{$trace['name']}'\n";
        }
    }
} else {
    echo "   - No historical data available for testing\n";
}

echo "\n";

// Test 5: Performance test for real-time updates
echo "5. Performance Test for Real-time Updates:\n";

$startTime = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $service->getLatestDataPoint(['temperature', 'humidity']);
}
$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);

echo "   - 100 real-time data fetches: {$executionTime}ms\n";
echo "   - Average per fetch: " . round($executionTime / 100, 2) . "ms\n";

echo "\n=== Test Complete ===\n";
