<?php

/**
 * Quick Test for Chart Fixes
 * Tests if the immediate fixes implementation is working
 */

echo "=== Testing Chart Fixes ===\n\n";

// Test 1: Check if the fixed test page exists
$testPage = 'public/test-immediate-fixes.html';
if (file_exists($testPage)) {
    echo "✓ Test page exists: {$testPage}\n";

    // Check if the fixes are properly implemented
    $content = file_get_contents($testPage);

    $fixes = [
        'ChartThrottler' => 'ChartThrottler class reference',
        'DataBuffer' => 'DataBuffer class reference',
        'SSEManager' => 'SSEManager class reference',
        'ChartDataManager' => 'ChartDataManager class reference',
        'PerformanceTracker' => 'PerformanceTracker class reference',
        'resetChart()' => 'Chart reset function',
        'updateTestStatus' => 'Test status updates',
        'Plotly.newPlot' => 'Chart initialization',
        'Plotly.extendTraces' => 'Chart data updates'
    ];

    echo "\nImplementation Check:\n";
    foreach ($fixes as $search => $description) {
        if (strpos($content, $search) !== false) {
            echo "✓ {$description}\n";
        } else {
            echo "✗ {$description} - NOT FOUND\n";
        }
    }
} else {
    echo "✗ Test page not found: {$testPage}\n";
}

// Test 2: Check if the main JS file has the fixes
$jsFile = 'public/js/analysis-chart-component.js';
if (file_exists($jsFile)) {
    echo "\n✓ Main JS file exists: {$jsFile}\n";

    $jsContent = file_get_contents($jsFile);

    $jsFixes = [
        'class ChartThrottler' => 'ChartThrottler class definition',
        'class DataBuffer' => 'DataBuffer class definition',
        'class SSEManager' => 'SSEManager class definition',
        'class ChartDataManager' => 'ChartDataManager class definition',
        'class PerformanceTracker' => 'PerformanceTracker class definition',
        'initImmediateFixes()' => 'Initialization method',
        'handleSSEMessage' => 'SSE message handler',
        'aggregateData' => 'Data aggregation method'
    ];

    echo "\nJavaScript Implementation Check:\n";
    foreach ($jsFixes as $search => $description) {
        if (strpos($jsContent, $search) !== false) {
            echo "✓ {$description}\n";
        } else {
            echo "✗ {$description} - NOT FOUND\n";
        }
    }
} else {
    echo "✗ Main JS file not found: {$jsFile}\n";
}

echo "\n=== Test Instructions ===\n";
echo "1. Start your web server\n";
echo "2. Open: http://localhost/public/test-immediate-fixes.html\n";
echo "3. Check that all classes show as available (green checkmarks)\n";
echo "4. Click 'Start Throttling Test' - should see smooth chart updates\n";
echo "5. Click 'Start Buffer Test' - should see batch processing\n";
echo "6. Click 'Start Memory Test' - should see memory stabilization\n";
echo "7. Monitor console for performance metrics\n";

echo "\n=== Expected Behavior ===\n";
echo "- Chart should initialize with empty trace\n";
echo "- Updates should be throttled to 100ms intervals\n";
echo "- Data should be buffered in groups of 50\n";
echo "- Memory should be managed automatically\n";
echo "- No more 'indices must be valid indices' errors\n";

echo "\n=== Test Complete ===\n";
