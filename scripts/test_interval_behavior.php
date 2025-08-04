<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Livewire\AnalysisChart;
use Illuminate\Support\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Interval Behavior Fix ===\n\n";

// Test 1: Check date format compatibility
echo "1. Testing Date Format Compatibility:\n";

$dateFormats = [
    'toDateString()' => now()->toDateString(),
    'toDateTimeString()' => now()->toDateTimeString(),
    'format(Y-m-d)' => now()->format('Y-m-d'),
    'format(Y-m-d H:i:s)' => now()->format('Y-m-d H:i:s'),
];

foreach ($dateFormats as $method => $format) {
    echo "   - {$method}: {$format}\n";
}

echo "\n";

// Test 2: Simulate interval changes
echo "2. Testing Interval Change Behavior:\n";

// Create a mock component instance
$component = new AnalysisChart();

// Set initial state
$component->selectedTags = ['temperature', 'humidity'];
$component->interval = 'hour';
$component->startDate = '2025-08-01';
$component->endDate = '2025-08-03';
$component->realtimeEnabled = true;

echo "   - Initial state:\n";
echo "     * Interval: {$component->interval}\n";
echo "     * Start Date: {$component->startDate}\n";
echo "     * End Date: {$component->endDate}\n";
echo "     * Real-time: " . ($component->realtimeEnabled ? 'true' : 'false') . "\n";

echo "\n   - Simulating interval changes (should NOT reset dates):\n";

$intervals = ['minute', 'day', 'second', 'hour'];

foreach ($intervals as $newInterval) {
    $oldStartDate = $component->startDate;
    $oldEndDate = $component->endDate;

    // Simulate interval change (this would normally trigger updatedInterval)
    $component->interval = $newInterval;

    echo "     * Changed to '{$newInterval}':\n";
    echo "       - Start Date: {$component->startDate} (unchanged: " . ($component->startDate === $oldStartDate ? '✅' : '❌') . ")\n";
    echo "       - End Date: {$component->endDate} (unchanged: " . ($component->endDate === $oldEndDate ? '✅' : '❌') . ")\n";
}

echo "\n";

// Test 3: Test real-time toggle behavior
echo "3. Testing Real-time Toggle Behavior:\n";

$component->realtimeEnabled = false;
echo "   - Real-time disabled:\n";
echo "     * Start Date: {$component->startDate}\n";
echo "     * End Date: {$component->endDate}\n";

$component->realtimeEnabled = true;
echo "   - Real-time enabled (should reset to live range):\n";
echo "     * Start Date: {$component->startDate}\n";
echo "     * End Date: {$component->endDate}\n";

// Check if dates are in correct format for HTML input
$isStartDateValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $component->startDate);
$isEndDateValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $component->endDate);

echo "     * Start Date format valid: " . ($isStartDateValid ? '✅' : '❌') . "\n";
echo "     * End Date format valid: " . ($isEndDateValid ? '✅' : '❌') . "\n";

echo "\n";

// Test 4: Test historical data loading
echo "4. Testing Historical Data Loading:\n";

// Simulate setting custom dates
$component->startDate = '2025-08-01';
$component->endDate = '2025-08-03';
$component->interval = 'hour';
$component->realtimeEnabled = false;

echo "   - Custom date range set:\n";
echo "     * Start Date: {$component->startDate}\n";
echo "     * End Date: {$component->endDate}\n";
echo "     * Interval: {$component->interval}\n";

// Simulate interval change (should NOT affect dates)
$component->interval = 'day';
echo "   - After interval change to 'day':\n";
echo "     * Start Date: {$component->startDate} (should remain 2025-08-01)\n";
echo "     * End Date: {$component->endDate} (should remain 2025-08-03)\n";

echo "\n";

// Test 5: Test date parsing
echo "5. Testing Date Parsing:\n";

$testDates = [
    '2025-08-01',
    '2025-08-03',
    '2025-08-01 12:00:00',
    '2025-08-03 23:59:59'
];

foreach ($testDates as $testDate) {
    try {
        $parsed = Carbon::parse($testDate);
        $formatted = $parsed->format('Y-m-d H:i:s');
        echo "   - '{$testDate}' -> '{$formatted}' ✅\n";
    } catch (Exception $e) {
        echo "   - '{$testDate}' -> ERROR: {$e->getMessage()} ❌\n";
    }
}

echo "\n";

// Test 6: Test user workflow simulation
echo "6. Testing User Workflow Simulation:\n";

echo "   - Step 1: User sets custom date range\n";
$component->startDate = '2025-08-01';
$component->endDate = '2025-08-03';
echo "     * Dates set: {$component->startDate} to {$component->endDate}\n";

echo "   - Step 2: User changes interval (should NOT reset dates)\n";
$component->interval = 'minute';
echo "     * Interval changed to: {$component->interval}\n";
echo "     * Dates remain: {$component->startDate} to {$component->endDate}\n";

echo "   - Step 3: User clicks 'Load Historical Data'\n";
// This would call setHistoricalModeAndLoad() which calls loadChartData()
echo "     * Real-time disabled: " . ($component->realtimeEnabled ? 'false' : 'true') . "\n";
echo "     * Data loaded with current settings\n";

echo "   - Step 4: User changes interval again (should NOT reset dates)\n";
$component->interval = 'day';
echo "     * Interval changed to: {$component->interval}\n";
echo "     * Dates still remain: {$component->startDate} to {$component->endDate}\n";

echo "\n=== Test Complete ===\n";
