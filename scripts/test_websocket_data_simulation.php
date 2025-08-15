<?php

/**
 * WebSocket Data Simulation Test Script
 *
 * Script ini digunakan untuk simulasi data SCADA dan testing WebSocket broadcasting
 * Meliputi pengiriman data real-time, batch data, dan performance testing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaBroadcastingService;
use App\Events\ScadaDataReceived;
use App\Models\ScadaDataWide;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== WebSocket Data Simulation Test ===\n\n";

// Configuration
$config = [
    'total_iterations' => 100,
    'interval_ms' => 1000, // 1 second
    'batch_size' => 10,
    'enable_logging' => true,
    'enable_database' => true,
    'enable_queue' => true
];

echo "Configuration:\n";
echo "- Total iterations: {$config['total_iterations']}\n";
echo "- Interval: {$config['interval_ms']} ms\n";
echo "- Batch size: {$config['batch_size']}\n";
echo "- Logging: " . ($config['enable_logging'] ? 'Enabled' : 'Disabled') . "\n";
echo "- Database: " . ($config['enable_database'] ? 'Enabled' : 'Disabled') . "\n";
echo "- Queue: " . ($config['enable_queue'] ? 'Enabled' : 'Disabled') . "\n\n";

// Initialize services
$broadcastingService = app(ScadaBroadcastingService::class);
$startTime = microtime(true);
$totalMessages = 0;
$successCount = 0;
$errorCount = 0;

// Data simulation functions
function generateScadaData($iteration)
{
    $baseTime = time();
    $timestamp = date('Y-m-d H:i:s', $baseTime + $iteration);

    return [
        'batch_id' => uniqid(),
        'nama_group' => 'test_group_' . ($iteration % 5 + 1), // Generate 5 different groups
        'timestamp_device' => $timestamp,
        'temperature' => 20 + (sin($iteration * 0.1) * 10) + (rand(-5, 5) * 0.1),
        'humidity' => 60 + (cos($iteration * 0.15) * 20) + (rand(-10, 10) * 0.5),
        'pressure' => 1013 + (sin($iteration * 0.05) * 5) + (rand(-2, 2) * 0.1),
        'wind_speed' => 5 + (sin($iteration * 0.2) * 15) + (rand(-3, 3) * 0.5),
        'rainfall' => max(0, (sin($iteration * 0.3) * 2) + (rand(0, 5) * 0.1)),
        'solar_radiation' => max(0, 800 + (sin($iteration * 0.1) * 200) + (rand(-50, 50) * 2)),
        'iteration' => $iteration
    ];
}

function generateBatchData($startIteration, $batchSize)
{
    $batchData = [];
    for ($i = 0; $i < $batchSize; $i++) {
        $batchData[] = generateScadaData($startIteration + $i);
    }
    return $batchData;
}

function logMessage($message, $type = 'info')
{
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";

    switch ($type) {
        case 'error':
            echo "\033[31m{$logMessage}\033[0m\n";
            break;
        case 'success':
            echo "\033[32m{$logMessage}\033[0m\n";
            break;
        case 'warning':
            echo "\033[33m{$logMessage}\033[0m\n";
            break;
        default:
            echo "{$logMessage}\n";
    }
}

// Test 1: Single Data Broadcasting
echo "1. Testing Single Data Broadcasting...\n";
for ($i = 0; $i < min(10, $config['total_iterations']); $i++) {
    $data = generateScadaData($i);

    try {
        $result = $broadcastingService->broadcastData($data);
        if ($result) {
            $successCount++;
            logMessage("Data broadcasted successfully: Temperature={$data['temperature']}°C, Humidity={$data['humidity']}%", 'success');
        } else {
            $errorCount++;
            logMessage("Data broadcast failed", 'error');
        }
        $totalMessages++;

        // Save to database if enabled
        if ($config['enable_database']) {
            try {
                ScadaDataWide::create($data);
            } catch (Exception $e) {
                logMessage("Database save failed: " . $e->getMessage(), 'warning');
            }
        }

        usleep($config['interval_ms'] * 1000); // Convert to microseconds
    } catch (Exception $e) {
        $errorCount++;
        logMessage("Error broadcasting data: " . $e->getMessage(), 'error');
    }
}

echo "\n";

// Test 2: Batch Data Broadcasting
echo "2. Testing Batch Data Broadcasting...\n";
$batchCount = 0;
for ($i = 10; $i < min(30, $config['total_iterations']); $i += $config['batch_size']) {
    $batchData = generateBatchData($i, $config['batch_size']);

    try {
        $result = $broadcastingService->broadcastBatchData($batchData);
        if ($result) {
            $successCount++;
            logMessage("Batch data broadcasted successfully: " . count($batchData) . " records", 'success');
        } else {
            $errorCount++;
            logMessage("Batch data broadcast failed", 'error');
        }
        $totalMessages += count($batchData);
        $batchCount++;

        // Save batch to database if enabled
        if ($config['enable_database']) {
            try {
                foreach ($batchData as $data) {
                    ScadaDataWide::create($data);
                }
            } catch (Exception $e) {
                logMessage("Batch database save failed: " . $e->getMessage(), 'warning');
            }
        }

        usleep($config['interval_ms'] * 1000);
    } catch (Exception $e) {
        $errorCount++;
        logMessage("Error broadcasting batch data: " . $e->getMessage(), 'error');
    }
}

echo "\n";

// Test 3: Aggregated Data Broadcasting with Throttling
echo "3. Testing Aggregated Data Broadcasting with Throttling...\n";
for ($i = 30; $i < min(60, $config['total_iterations']); $i++) {
    $data = generateScadaData($i);

    try {
        // Use throttled broadcasting (100ms throttle)
        $result = $broadcastingService->broadcastAggregatedData($data, 'scada-aggregated', 100);
        if ($result) {
            $successCount++;
            logMessage("Aggregated data broadcasted: Pressure={$data['pressure']} hPa", 'success');
        } else {
            logMessage("Data throttled (rate limiting)", 'warning');
        }
        $totalMessages++;

        // Save to database if enabled
        if ($config['enable_database']) {
            try {
                ScadaDataWide::create($data);
            } catch (Exception $e) {
                logMessage("Database save failed: " . $e->getMessage(), 'warning');
            }
        }

        usleep($config['interval_ms'] * 1000);
    } catch (Exception $e) {
        $errorCount++;
        logMessage("Error broadcasting aggregated data: " . $e->getMessage(), 'error');
    }
}

echo "\n";

// Test 4: High-Frequency Data Broadcasting
echo "4. Testing High-Frequency Data Broadcasting...\n";
$highFreqStart = microtime(true);
for ($i = 60; $i < min(80, $config['total_iterations']); $i++) {
    $data = generateScadaData($i);

    try {
        $result = $broadcastingService->broadcastData($data, 'scada-high-freq');
        if ($result) {
            $successCount++;
            if ($i % 5 == 0) { // Log every 5th message to avoid spam
                logMessage("High-frequency data: Wind={$data['wind_speed']} m/s, Solar={$data['solar_radiation']} W/m²", 'success');
            }
        } else {
            $errorCount++;
            logMessage("High-frequency broadcast failed", 'error');
        }
        $totalMessages++;

        // Save to database if enabled
        if ($config['enable_database']) {
            try {
                ScadaDataWide::create($data);
            } catch (Exception $e) {
                logMessage("Database save failed: " . $e->getMessage(), 'warning');
            }
        }

        usleep(100000); // 100ms for high frequency
    } catch (Exception $e) {
        $errorCount++;
        logMessage("Error in high-frequency broadcasting: " . $e->getMessage(), 'error');
    }
}
$highFreqEnd = microtime(true);
$highFreqDuration = ($highFreqEnd - $highFreqStart) * 1000; // Convert to milliseconds

echo "\n";

// Test 5: Event-Based Broadcasting
echo "5. Testing Event-Based Broadcasting...\n";
for ($i = 80; $i < $config['total_iterations']; $i++) {
    $data = generateScadaData($i);

    try {
        // Dispatch event directly
        event(new ScadaDataReceived($data, 'scada-event'));
        $successCount++;
        $totalMessages++;

        if ($i % 10 == 0) { // Log every 10th message
            logMessage("Event dispatched: Rainfall={$data['rainfall']} mm", 'success');
        }

        // Save to database if enabled
        if ($config['enable_database']) {
            try {
                ScadaDataWide::create($data);
            } catch (Exception $e) {
                logMessage("Database save failed: " . $e->getMessage(), 'warning');
            }
        }

        usleep($config['interval_ms'] * 1000);
    } catch (Exception $e) {
        $errorCount++;
        logMessage("Error dispatching event: " . $e->getMessage(), 'error');
    }
}

echo "\n";

// Performance Analysis
$endTime = microtime(true);
$totalDuration = ($endTime - $startTime) * 1000; // Convert to milliseconds
$avgMessageTime = $totalDuration / $totalMessages;
$messagesPerSecond = $totalMessages / ($totalDuration / 1000);

echo "=== Performance Analysis ===\n";
echo "Total execution time: " . number_format($totalDuration, 2) . " ms\n";
echo "Total messages processed: {$totalMessages}\n";
echo "Successful broadcasts: {$successCount}\n";
echo "Failed broadcasts: {$errorCount}\n";
echo "Success rate: " . number_format(($successCount / $totalMessages) * 100, 2) . "%\n";
echo "Average time per message: " . number_format($avgMessageTime, 2) . " ms\n";
echo "Messages per second: " . number_format($messagesPerSecond, 2) . "\n";
echo "High-frequency test duration: " . number_format($highFreqDuration, 2) . " ms\n";

// Queue status if enabled
if ($config['enable_queue']) {
    echo "\n=== Queue Status ===\n";
    try {
        $queueSize = Queue::size();
        echo "Current queue size: {$queueSize}\n";

        if ($queueSize > 0) {
            echo "Note: Some jobs may still be in queue. Monitor with: php artisan queue:work\n";
        }
    } catch (Exception $e) {
        echo "Queue status check failed: " . $e->getMessage() . "\n";
    }
}

// Database status if enabled
if ($config['enable_database']) {
    echo "\n=== Database Status ===\n";
    try {
        $totalRecords = ScadaDataWide::count();
        echo "Total records in database: {$totalRecords}\n";

        $recentRecords = ScadaDataWide::where('created_at', '>=', now()->subMinutes(5))->count();
        echo "Records created in last 5 minutes: {$recentRecords}\n";
    } catch (Exception $e) {
        echo "Database status check failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Summary ===\n";
echo "WebSocket data simulation test completed successfully!\n";
echo "Check your WebSocket client for real-time updates.\n\n";

echo "Next steps:\n";
echo "1. Open WebSocket test page: /websocket-test\n";
echo "2. Monitor browser console for connection status\n";
echo "3. Check real-time chart updates\n";
echo "4. Verify data flow in message log\n\n";

echo "Monitoring commands:\n";
echo "- Check WebSocket connections: php artisan websockets:serve --debug\n";
echo "- Monitor queue: php artisan queue:work --verbose\n";
echo "- View logs: tail -f storage/logs/laravel.log\n";
echo "- Check database: php artisan tinker\n";
