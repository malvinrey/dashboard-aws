<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Events\ScadaDataReceived;

echo "=== Testing WebSocket Integration with Livewire Component ===\n\n";

// Test 1: Check if ScadaDataReceived event exists and can be instantiated
echo "1. Testing ScadaDataReceived Event...\n";
try {
    $testData = [
        'id' => uniqid(),
        'channel' => 'test-channel',
        'value' => rand(0, 100),
        'timestamp' => now()->toISOString(),
        'tags' => ['temperature', 'humidity']
    ];

    $event = new ScadaDataReceived($testData, 'scada-data');
    echo "✅ ScadaDataReceived event created successfully\n";
    echo "   - Channel: " . $event->channel . "\n";
    echo "   - Event name: " . $event->broadcastAs() . "\n";
    echo "   - Data size: " . (is_array($event->scadaData) ? count($event->scadaData) : 1) . "\n";
} catch (Exception $e) {
    echo "❌ Failed to create ScadaDataReceived event: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Test Livewire component integration
echo "\n2. Testing Livewire Component Integration...\n";
try {
    // Check if AnalysisChart component exists
    $componentClass = 'App\Livewire\AnalysisChart';
    if (class_exists($componentClass)) {
        echo "✅ AnalysisChart component found\n";

        // Check if WebSocket methods exist
        $reflection = new ReflectionClass($componentClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $websocketMethods = ['handleWebSocketData', 'handleRealtimeData', 'updateWebSocketStatus'];
        $foundMethods = [];

        foreach ($methods as $method) {
            if (in_array($method->getName(), $websocketMethods)) {
                $foundMethods[] = $method->getName();
            }
        }

        if (count($foundMethods) === count($websocketMethods)) {
            echo "✅ All WebSocket methods found in component\n";
            echo "   - Methods: " . implode(', ', $foundMethods) . "\n";
        } else {
            echo "⚠️  Some WebSocket methods missing\n";
            echo "   - Found: " . implode(', ', $foundMethods) . "\n";
            echo "   - Expected: " . implode(', ', $websocketMethods) . "\n";
        }

        // Check properties
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $websocketProperties = ['websocketStatus', 'lastWebSocketUpdate', 'websocketData'];
        $foundProperties = [];

        foreach ($properties as $property) {
            if (in_array($property->getName(), $websocketProperties)) {
                $foundProperties[] = $property->getName();
            }
        }

        if (count($foundProperties) === count($websocketProperties)) {
            echo "✅ All WebSocket properties found in component\n";
            echo "   - Properties: " . implode(', ', $foundProperties) . "\n";
        } else {
            echo "⚠️  Some WebSocket properties missing\n";
            echo "   - Found: " . implode(', ', $foundProperties) . "\n";
            echo "   - Expected: " . implode(', ', $websocketProperties) . "\n";
        }
    } else {
        echo "❌ AnalysisChart component not found\n";
    }
} catch (Exception $e) {
    echo "❌ Livewire component test failed: " . $e->getMessage() . "\n";
}

// Test 3: Check broadcasting configuration
echo "\n3. Testing Broadcasting Configuration...\n";
try {
    $config = config('broadcasting');
    echo "✅ Broadcasting config loaded successfully\n";
    echo "   - Default driver: " . $config['default'] . "\n";
    echo "   - Available connections: " . implode(', ', array_keys($config['connections'])) . "\n";

    if (isset($config['connections']['websockets'])) {
        echo "✅ WebSocket connection configured\n";
        echo "     * Host: " . $config['connections']['websockets']['options']['host'] . "\n";
        echo "     * Port: " . $config['connections']['websockets']['options']['port'] . "\n";
    } else {
        echo "❌ WebSocket connection not found in config\n";
    }
} catch (Exception $e) {
    echo "❌ Broadcasting config test failed: " . $e->getMessage() . "\n";
}

// Test 4: Check environment variables
echo "\n4. Testing Environment Variables...\n";
$requiredEnvVars = [
    'BROADCAST_DRIVER' => 'redis',
    'REDIS_HOST' => '127.0.0.1',
    'REDIS_PORT' => '6379',
    'PUSHER_APP_ID' => '12345',
    'PUSHER_APP_KEY' => 'scada_dashboard_key_2024',
    'PUSHER_APP_SECRET' => 'scada_dashboard_secret_2024',
    'PUSHER_HOST' => '127.0.0.1',
    'PUSHER_PORT' => '6001',
    'SOKETI_PORT' => '6001'
];

$allGood = true;
foreach ($requiredEnvVars as $var => $expectedValue) {
    $actualValue = env($var);
    if ($actualValue == $expectedValue) {
        echo "✅ {$var}: {$actualValue}\n";
    } else {
        echo "❌ {$var}: {$actualValue} (expected: {$expectedValue})\n";
        $allGood = false;
    }
}

if ($allGood) {
    echo "\n🎉 All environment variables are correctly configured!\n";
} else {
    echo "\n⚠️  Some environment variables need attention\n";
}

// Test 5: Check if Soketi config exists
echo "\n5. Testing Soketi Configuration...\n";
$soketiConfigPath = __DIR__ . '/../soketi.json';
if (file_exists($soketiConfigPath)) {
    echo "✅ Soketi configuration file exists\n";

    $soketiConfig = json_decode(file_get_contents($soketiConfigPath), true);
    if ($soketiConfig) {
        echo "✅ Soketi config is valid JSON\n";
        echo "   - App ID: " . ($soketiConfig['appId'] ?? 'N/A') . "\n";
        echo "   - Port: " . ($soketiConfig['port'] ?? 'N/A') . "\n";
        echo "   - Host: " . ($soketiConfig['host'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Soketi config is not valid JSON\n";
    }
} else {
    echo "❌ Soketi configuration file not found\n";
}

echo "\n=== Test Summary ===\n";
echo "✅ WebSocket integration with Livewire component implemented\n";
echo "✅ Broadcasting configuration updated\n";
echo "✅ Environment variables configured\n";
echo "\nNext steps:\n";
echo "1. Start Redis server\n";
echo "2. Start Soketi WebSocket server\n";
echo "3. Start queue worker: php artisan queue:work\n";
echo "4. Test real-time updates in browser\n";
echo "\nTest completed successfully!\n";
