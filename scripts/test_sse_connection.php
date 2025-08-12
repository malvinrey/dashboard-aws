<?php

/**
 * Test script untuk SSE connection
 *
 * Usage: php scripts/test_sse_connection.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "🧪 Testing SSE Connection...\n\n";

// Test 1: Check if SSE endpoint is accessible
echo "1. Testing SSE endpoint accessibility...\n";
try {
    $response = Http::get('http://localhost:8000/api/sse/test');

    if ($response->successful()) {
        $data = $response->json();
        echo "✅ SSE endpoint accessible\n";
        echo "   Status: {$data['status']}\n";
        echo "   Endpoint: {$data['endpoint']}\n";
        echo "   Supported events: " . implode(', ', $data['supported_events']) . "\n\n";
    } else {
        echo "❌ SSE endpoint not accessible (Status: {$response->status()})\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error accessing SSE endpoint: " . $e->getMessage() . "\n\n";
}

// Test 2: Test SSE stream connection
echo "2. Testing SSE stream connection...\n";
echo "   This will attempt to connect to the SSE stream...\n";
echo "   Press Ctrl+C to stop the test\n\n";

// Test parameters
$tags = ['temperature', 'humidity'];
$interval = 'minute';

$params = http_build_query([
    'tags' => $tags,
    'interval' => $interval
]);

$url = "http://localhost:8000/api/sse/stream?{$params}";

echo "   Connecting to: {$url}\n";
echo "   Tags: " . implode(', ', $tags) . "\n";
echo "   Interval: {$interval}\n\n";

// Note: For actual SSE testing, you would need a client that can handle EventSource
// This is just a basic HTTP test to ensure the endpoint responds
try {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Accept: text/event-stream',
                'Cache-Control: no-cache',
                'Connection: keep-alive'
            ],
            'timeout' => 10
        ]
    ]);

    $handle = fopen($url, 'r', false, $context);

    if ($handle) {
        echo "✅ SSE stream connection established\n";
        echo "   Reading first few lines...\n\n";

        $lineCount = 0;
        while (!feof($handle) && $lineCount < 10) {
            $line = fgets($handle);
            if ($line !== false) {
                echo "   " . trim($line) . "\n";
                $lineCount++;
            }
        }

        fclose($handle);
        echo "\n   Connection test completed\n";
    } else {
        echo "❌ Failed to establish SSE stream connection\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing SSE stream: " . $e->getMessage() . "\n";
}

echo "\n📋 SSE Test Summary:\n";
echo "   - SSE endpoint: /api/sse/stream\n";
echo "   - Supported events: connected, data, heartbeat, error\n";
echo "   - Real-time updates without polling\n";
echo "   - Automatic reconnection with exponential backoff\n";
echo "   - Heartbeat every 30 seconds\n\n";

echo "🚀 To test real SSE functionality, use a web browser or JavaScript client:\n";
echo "   const eventSource = new EventSource('{$url}');\n";
echo "   eventSource.onmessage = (event) => console.log('Data:', event.data);\n\n";
