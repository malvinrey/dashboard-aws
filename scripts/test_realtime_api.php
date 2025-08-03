<?php

/**
 * Test script untuk memverifikasi API real-time endpoint
 *
 * Usage: php scripts/test_realtime_api.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;

echo "=== Real-time API Test Script ===\n\n";

// Test 1: Check if service method works
echo "1. Testing ScadaDataService::getLatestAggregatedDataPoint()\n";
$scadaService = new ScadaDataService();

$tags = ['temperature', 'humidity'];
$interval = 'hour';

$latestData = $scadaService->getLatestAggregatedDataPoint($tags, $interval);

if ($latestData) {
    echo "✅ Service method returned data:\n";
    echo "   Timestamp: " . $latestData['timestamp'] . "\n";
    echo "   Metrics: " . json_encode($latestData['metrics']) . "\n";
} else {
    echo "❌ Service method returned null (no data available)\n";
}

echo "\n";

// Test 2: Simulate API endpoint
echo "2. Testing API endpoint simulation\n";

$request = new \Illuminate\Http\Request();
$request->merge([
    'tags' => $tags,
    'interval' => $interval
]);

$controller = new \App\Http\Controllers\AnalysisController($scadaService);

try {
    $response = $controller->getLatestDataApi($request);
    $statusCode = $response->getStatusCode();
    $data = json_decode($response->getContent(), true);

    echo "✅ API endpoint simulation successful:\n";
    echo "   Status Code: " . $statusCode . "\n";
    echo "   Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "❌ API endpoint simulation failed:\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Performance test
echo "3. Performance test (10 iterations)\n";

$startTime = microtime(true);
$successCount = 0;
$totalTime = 0;

for ($i = 0; $i < 10; $i++) {
    $iterStart = microtime(true);

    try {
        $response = $controller->getLatestDataApi($request);
        $successCount++;

        $iterTime = (microtime(true) - $iterStart) * 1000;
        $totalTime += $iterTime;

        echo "   Iteration " . ($i + 1) . ": " . round($iterTime, 2) . "ms\n";
    } catch (\Exception $e) {
        echo "   Iteration " . ($i + 1) . ": FAILED - " . $e->getMessage() . "\n";
    }
}

$endTime = microtime(true);
$totalDuration = ($endTime - $startTime) * 1000;
$avgTime = $totalTime / $successCount;

echo "\nPerformance Summary:\n";
echo "   Total Duration: " . round($totalDuration, 2) . "ms\n";
echo "   Successful Calls: " . $successCount . "/10\n";
echo "   Average Response Time: " . round($avgTime, 2) . "ms\n";
echo "   Performance: " . ($avgTime < 100 ? "✅ EXCELLENT" : ($avgTime < 500 ? "✅ GOOD" : "⚠️ SLOW")) . "\n";

echo "\n";

// Test 4: Database check
echo "4. Database data availability check\n";

$latestRecord = \App\Models\ScadaDataTall::orderBy('timestamp_device', 'desc')->first();

if ($latestRecord) {
    echo "✅ Database has data:\n";
    echo "   Latest record: " . $latestRecord->timestamp_device . "\n";
    echo "   Tag: " . $latestRecord->nama_tag . "\n";
    echo "   Value: " . $latestRecord->nilai_tag . "\n";

    // Check data for selected tags
    $tagCounts = \App\Models\ScadaDataTall::whereIn('nama_tag', $tags)
        ->selectRaw('nama_tag, COUNT(*) as count')
        ->groupBy('nama_tag')
        ->get();

    echo "   Data counts by tag:\n";
    foreach ($tagCounts as $tagCount) {
        echo "     " . $tagCount->nama_tag . ": " . $tagCount->count . " records\n";
    }
} else {
    echo "❌ No data found in database\n";
}

echo "\n";

// Test 5: Route availability
echo "5. Route availability check\n";

$routes = \Illuminate\Support\Facades\Route::getRoutes();
$apiRouteFound = false;

foreach ($routes as $route) {
    if ($route->uri() === 'api/latest-data' && $route->methods()[0] === 'GET') {
        $apiRouteFound = true;
        break;
    }
}

if ($apiRouteFound) {
    echo "✅ API route '/api/latest-data' is registered\n";
} else {
    echo "❌ API route '/api/latest-data' not found\n";
}

echo "\n=== Test Complete ===\n";

// Summary
echo "\nSummary:\n";
echo "1. Service Method: " . ($latestData ? "✅ WORKING" : "⚠️ NO DATA") . "\n";
echo "2. API Endpoint: " . ($statusCode === 200 || $statusCode === 204 ? "✅ WORKING" : "❌ FAILED") . "\n";
echo "3. Performance: " . ($avgTime < 100 ? "✅ EXCELLENT" : ($avgTime < 500 ? "✅ GOOD" : "⚠️ SLOW")) . "\n";
echo "4. Database: " . ($latestRecord ? "✅ HAS DATA" : "❌ NO DATA") . "\n";
echo "5. Route: " . ($apiRouteFound ? "✅ REGISTERED" : "❌ MISSING") . "\n";

echo "\nRecommendations:\n";
if (!$latestData) {
    echo "- Add test data to database for real-time testing\n";
}
if ($avgTime > 500) {
    echo "- Consider optimizing database queries\n";
}
if (!$apiRouteFound) {
    echo "- Check route registration in routes/api.php\n";
}

echo "\nFor real-time testing:\n";
echo "1. Ensure database has recent data\n";
echo "2. Start Laravel server: php artisan serve\n";
echo "3. Open browser and navigate to Analysis Chart\n";
echo "4. Enable real-time toggle and monitor console logs\n";
echo "5. Check Network tab for API calls every 5 seconds\n";
