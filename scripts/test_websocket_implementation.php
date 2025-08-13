<?php

/**
 * WebSocket Implementation Test Script
 *
 * Script ini digunakan untuk testing implementasi WebSocket pada SCADA Dashboard
 * Meliputi testing broadcasting service, event handling, dan WebSocket client
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaBroadcastingService;
use App\Events\ScadaDataReceived;
use App\Models\ScadaDataWide;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== WebSocket Implementation Test ===\n\n";

// Test 1: Broadcasting Service
echo "1. Testing ScadaBroadcastingService...\n";
try {
    $broadcastingService = app(ScadaBroadcastingService::class);
    echo "   ✓ Broadcasting service instantiated successfully\n";

    // Test broadcast method
    $testData = [
        'temperature' => 25.5,
        'humidity' => 65.2,
        'pressure' => 1013.25,
        'timestamp' => now()->toISOString()
    ];

    $result = $broadcastingService->broadcastData($testData);
    echo "   ✓ Broadcast method executed successfully\n";
    echo "   ✓ Broadcast result: " . ($result ? 'Success' : 'Failed') . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Event Broadcasting
echo "2. Testing ScadaDataReceived Event...\n";
try {
    // Create test data
    $testData = [
        'temperature' => 26.8,
        'humidity' => 68.5,
        'pressure' => 1012.8,
        'timestamp' => now()->toISOString()
    ];

    // Dispatch event
    event(new ScadaDataReceived($testData));
    echo "   ✓ Event dispatched successfully\n";

    // Check if event is broadcastable
    $event = new ScadaDataReceived($testData);
    if (method_exists($event, 'broadcastOn')) {
        echo "   ✓ Event implements broadcastOn method\n";

        $channels = $event->broadcastOn();
        echo "   ✓ Event broadcasts on channels: " . implode(', ', $channels) . "\n";
    } else {
        echo "   ✗ Event does not implement broadcastOn method\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Database Integration
echo "3. Testing Database Integration...\n";
try {
    // Check if ScadaDataWide model exists
    $model = new ScadaDataWide();
    echo "   ✓ ScadaDataWide model instantiated successfully\n";

    // Check table structure
    $tableName = $model->getTable();
    echo "   ✓ Table name: {$tableName}\n";

    // Check if table exists
    $tableExists = \Illuminate\Support\Facades\Schema::hasTable($tableName);
    echo "   ✓ Table exists: " . ($tableExists ? 'Yes' : 'No') . "\n";

    if ($tableExists) {
        // Get table columns
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        echo "   ✓ Table columns: " . implode(', ', $columns) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Broadcasting Configuration
echo "4. Testing Broadcasting Configuration...\n";
try {
    $broadcastDriver = config('broadcasting.default');
    echo "   ✓ Default broadcast driver: {$broadcastDriver}\n";

    $pusherConfig = config('broadcasting.connections.pusher');
    if ($pusherConfig) {
        echo "   ✓ Pusher configuration found\n";
        echo "   ✓ App ID: " . ($pusherConfig['app_id'] ?? 'Not set') . "\n";
        echo "   ✓ App Key: " . ($pusherConfig['app_key'] ?? 'Not set') . "\n";
        echo "   ✓ App Secret: " . (isset($pusherConfig['app_secret']) ? 'Set' : 'Not set') . "\n";
        echo "   ✓ Cluster: " . ($pusherConfig['options']['cluster'] ?? 'Not set') . "\n";
    } else {
        echo "   ✗ Pusher configuration not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Queue Integration
echo "5. Testing Queue Integration...\n";
try {
    $queueConnection = config('queue.default');
    echo "   ✓ Default queue connection: {$queueConnection}\n";

    $queueConfig = config("queue.connections.{$queueConnection}");
    if ($queueConfig) {
        echo "   ✓ Queue configuration found\n";
        echo "   ✓ Driver: " . ($queueConfig['driver'] ?? 'Unknown') . "\n";

        if (isset($queueConfig['host'])) {
            echo "   ✓ Host: " . $queueConfig['host'] . "\n";
        }
        if (isset($queueConfig['port'])) {
            echo "   ✓ Port: " . $queueConfig['port'] . "\n";
        }
    }

    // Check queue status
    $queueSize = \Illuminate\Support\Facades\Queue::size();
    echo "   ✓ Current queue size: {$queueSize}\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: WebSocket Client JavaScript
echo "6. Testing WebSocket Client JavaScript...\n";
try {
    $jsPath = __DIR__ . '/../public/js/scada-websocket-client.js';
    if (file_exists($jsPath)) {
        echo "   ✓ WebSocket client JavaScript file exists\n";

        $jsContent = file_get_contents($jsPath);
        $fileSize = strlen($jsContent);
        echo "   ✓ File size: {$fileSize} bytes\n";

        // Check for key functions
        if (strpos($jsContent, 'class ScadaWebSocketClient') !== false) {
            echo "   ✓ ScadaWebSocketClient class found\n";
        } else {
            echo "   ✗ ScadaWebSocketClient class not found\n";
        }

        if (strpos($jsContent, 'connect()') !== false) {
            echo "   ✓ connect() method found\n";
        } else {
            echo "   ✗ connect() method not found\n";
        }

        if (strpos($jsContent, 'disconnect()') !== false) {
            echo "   ✓ disconnect() method found\n";
        } else {
            echo "   ✗ disconnect() method not found\n";
        }
    } else {
        echo "   ✗ WebSocket client JavaScript file not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 7: Routes
echo "7. Testing Routes...\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $broadcastingRoutes = [];

    foreach ($routes as $route) {
        if (strpos($route->uri(), 'broadcasting') !== false) {
            $broadcastingRoutes[] = $route->uri();
        }
    }

    if (!empty($broadcastingRoutes)) {
        echo "   ✓ Broadcasting routes found:\n";
        foreach ($broadcastingRoutes as $route) {
            echo "     - {$route}\n";
        }
    } else {
        echo "   ✗ No broadcasting routes found\n";
    }

    // Check WebSocket test route
    $websocketRoute = \Illuminate\Support\Facades\Route::getRoutes()->get('websocket-test');
    if ($websocketRoute) {
        echo "   ✓ WebSocket test route found\n";
    } else {
        echo "   ✗ WebSocket test route not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 8: Environment Variables
echo "8. Testing Environment Variables...\n";
try {
    $envVars = [
        'BROADCAST_DRIVER',
        'PUSHER_APP_ID',
        'PUSHER_APP_KEY',
        'PUSHER_APP_SECRET',
        'PUSHER_APP_CLUSTER'
    ];

    foreach ($envVars as $var) {
        $value = env($var);
        if ($value) {
            echo "   ✓ {$var}: Set\n";
        } else {
            echo "   ✗ {$var}: Not set\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 9: Performance Test
echo "9. Performance Test...\n";
try {
    $startTime = microtime(true);

    // Simulate multiple broadcasts
    for ($i = 0; $i < 10; $i++) {
        $testData = [
            'temperature' => 20 + ($i * 0.5),
            'humidity' => 60 + ($i * 1.0),
            'pressure' => 1010 + ($i * 0.1),
            'timestamp' => now()->toISOString()
        ];

        event(new ScadaDataReceived($testData));
    }

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

    echo "   ✓ 10 broadcasts completed in " . number_format($executionTime, 2) . " ms\n";
    echo "   ✓ Average time per broadcast: " . number_format($executionTime / 10, 2) . " ms\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 10: Error Handling
echo "10. Testing Error Handling...\n";
try {
    // Test with invalid data
    $invalidData = null;

    try {
        event(new ScadaDataReceived($invalidData));
        echo "   ✗ Should have thrown an error for invalid data\n";
    } catch (Exception $e) {
        echo "   ✓ Error handling works correctly: " . $e->getMessage() . "\n";
    }

    // Test broadcasting service with invalid data
    try {
        $broadcastingService = app(ScadaBroadcastingService::class);
        $result = $broadcastingService->broadcastData($invalidData);
        echo "   ✓ Broadcasting service handles invalid data gracefully\n";
    } catch (Exception $e) {
        echo "   ✓ Broadcasting service error handling: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "WebSocket implementation testing completed.\n";
echo "Check the results above for any issues that need to be addressed.\n\n";

echo "Next steps:\n";
echo "1. Start Laravel WebSocket server: php artisan websockets:serve\n";
echo "2. Start queue worker: php artisan queue:work\n";
echo "3. Open WebSocket test page: /websocket-test\n";
echo "4. Send test data via API endpoint: POST /api/receiver\n";
echo "5. Monitor real-time updates in the browser\n\n";

echo "For production deployment:\n";
echo "1. Configure proper WebSocket server (Laravel WebSockets or Pusher)\n";
echo "2. Set up SSL certificates for secure WebSocket connections\n";
echo "3. Configure load balancing if needed\n";
echo "4. Set up monitoring and logging\n";
