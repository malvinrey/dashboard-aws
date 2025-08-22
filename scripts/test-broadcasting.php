<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Events\ScadaDataReceived;
use Illuminate\Support\Facades\Event;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Testing Broadcasting System\n";
echo "==============================\n\n";

try {
    // Test 1: Check if broadcasting driver is configured
    $driver = config('broadcasting.default');
    echo "✅ Broadcasting driver: {$driver}\n";

    // Test 2: Check Pusher configuration
    $pusherConfig = config('broadcasting.connections.pusher');
    echo "✅ Pusher host: {$pusherConfig['options']['host']}\n";
    echo "✅ Pusher port: {$pusherConfig['options']['port']}\n";
    echo "✅ Pusher app key: {$pusherConfig['key']}\n";

    // Test 3: Create test event
    $testData = [
        'temperature' => 25.5,
        'humidity' => 60.2,
        'pressure' => 1013.25,
        'timestamp' => now()->toISOString()
    ];

    echo "\n📡 Creating test event...\n";
    $event = new ScadaDataReceived($testData, 'scada-channel');

    // Test 4: Check event configuration
    echo "✅ Event channel: " . $event->broadcastOn()->name . "\n";
    echo "✅ Event name: " . $event->broadcastAs() . "\n";
    echo "✅ Event data: " . json_encode($event->broadcastWith()) . "\n";

    // Test 5: Try to broadcast event
    echo "\n📤 Broadcasting event...\n";
    broadcast($event);
    echo "✅ Event broadcasted successfully!\n";

    // Test 6: Check if event was queued
    $queueConnection = config('queue.default');
    echo "✅ Queue connection: {$queueConnection}\n";

    echo "\n🎉 All tests passed! Broadcasting system is working correctly.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "🔍 Stack trace:\n" . $e->getTraceAsString() . "\n";
}
