<?php

/**
 * Test Script for Queue Implementation
 * This script tests the new queue-based SCADA data processing system
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__ . '/..')
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Queue Implementation for SCADA Data Processing ===\n\n";

// Test 1: Small dataset (normal processing)
echo "Test 1: Small Dataset (100 records)\n";
echo "-----------------------------------\n";

$smallPayload = [
    'DataArray' => []
];

// Generate 100 test records
for ($i = 0; $i < 100; $i++) {
    $smallPayload['DataArray'][] = [
        '_groupTag' => 'TEST_GROUP_' . ($i % 5),
        '_terminalTime' => date('Y-m-d H:i:s', time() - ($i * 60)),
        'temperature' => rand(20, 35),
        'humidity' => rand(40, 80),
        'pressure' => rand(1000, 1100),
        'rainfall' => rand(0, 10),
        'wind_speed' => rand(0, 20),
        'wind_direction' => rand(0, 360),
        'par_sensor' => rand(100, 1000),
        'solar_radiation' => rand(200, 800)
    ];
}

echo "Generated payload with " . count($smallPayload['DataArray']) . " records\n";

try {
    // Dispatch job to queue
    $job = new \App\Jobs\ProcessScadaDataJob($smallPayload);
    dispatch($job);

    echo "✓ Small dataset job dispatched successfully\n";
    echo "  Queue: scada-processing\n";
    echo "  Expected processing time: 1-2 minutes\n\n";
} catch (Exception $e) {
    echo "✗ Error dispatching small dataset job: " . $e->getMessage() . "\n\n";
}

// Test 2: Large dataset (chunked processing)
echo "Test 2: Large Dataset (7,500 records)\n";
echo "-------------------------------------\n";

$largePayload = [
    'DataArray' => []
];

// Generate 7,500 test records
for ($i = 0; $i < 7500; $i++) {
    $largePayload['DataArray'][] = [
        '_groupTag' => 'LARGE_TEST_GROUP_' . ($i % 10),
        '_terminalTime' => date('Y-m-d H:i:s', time() - ($i * 30)),
        'temperature' => rand(15, 40),
        'humidity' => rand(30, 90),
        'pressure' => rand(950, 1150),
        'rainfall' => rand(0, 25),
        'wind_speed' => rand(0, 30),
        'wind_direction' => rand(0, 360),
        'par_sensor' => rand(50, 1200),
        'solar_radiation' => rand(100, 1000)
    ];
}

echo "Generated payload with " . count($largePayload['DataArray']) . " records\n";

try {
    // Dispatch large dataset job
    $largeJob = new \App\Jobs\ProcessLargeScadaDatasetJob($largePayload, 1000);
    dispatch($largeJob);

    echo "✓ Large dataset job dispatched successfully\n";
    echo "  Queue: scada-large-datasets\n";
    echo "  Chunk size: 1000\n";
    echo "  Expected processing time: 5-10 minutes\n\n";
} catch (Exception $e) {
    echo "✗ Error dispatching large dataset job: " . $e->getMessage() . "\n\n";
}

// Test 3: Check queue status
echo "Test 3: Queue Status Check\n";
echo "--------------------------\n";

try {
    // Check jobs table
    $jobCount = \Illuminate\Support\Facades\DB::table('jobs')->count();
    $failedJobCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

    echo "Jobs in queue: $jobCount\n";
    echo "Failed jobs: $failedJobCount\n";

    if ($jobCount > 0) {
        echo "✓ Jobs are queued successfully\n";
    } else {
        echo "⚠ No jobs found in queue\n";
    }

    if ($failedJobCount === 0) {
        echo "✓ No failed jobs\n";
    } else {
        echo "⚠ Found $failedJobCount failed jobs\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking queue status: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "1. Small dataset (100 records): " . (isset($job) ? "✓ Dispatched" : "✗ Failed") . "\n";
echo "2. Large dataset (7,500 records): " . (isset($largeJob) ? "✓ Dispatched" : "✗ Failed") . "\n";
echo "3. Queue status: " . (isset($jobCount) ? "✓ Checked" : "✗ Failed") . "\n";

echo "\nNext steps:\n";
echo "1. Start queue workers: scripts/start-queue-worker.ps1\n";
echo "2. Monitor progress: scripts/monitor-queue-status.ps1\n";
echo "3. Check logs for processing updates\n";

echo "\nTest completed!\n";
