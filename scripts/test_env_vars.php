<?php

echo "=== Testing Environment Variables ===\n\n";

// Load Laravel environment
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing direct env() function:\n";
echo "BROADCAST_DRIVER: " . env('BROADCAST_DRIVER') . "\n";
echo "REDIS_HOST: " . env('REDIS_HOST') . "\n";
echo "REDIS_PORT: " . env('REDIS_PORT') . "\n";
echo "PUSHER_APP_ID: " . env('PUSHER_APP_ID') . "\n";
echo "PUSHER_APP_KEY: " . env('PUSHER_APP_KEY') . "\n";
echo "PUSHER_HOST: " . env('PUSHER_HOST') . "\n";
echo "PUSHER_PORT: " . env('PUSHER_PORT') . "\n";
echo "SOKETI_PORT: " . env('SOKETI_PORT') . "\n";

echo "\nTesting config() function:\n";
echo "BROADCAST_DRIVER from config: " . config('broadcasting.default') . "\n";
echo "REDIS_HOST from config: " . config('database.redis.default.host') . "\n";
echo "REDIS_PORT from config: " . config('database.redis.default.port') . "\n";

echo "\nTesting .env file directly:\n";
$envContent = file_get_contents(__DIR__ . '/../.env');
$lines = explode("\n", $envContent);

foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, 'BROADCAST_DRIVER=') === 0) {
        echo "BROADCAST_DRIVER from file: " . substr($line, strlen('BROADCAST_DRIVER=')) . "\n";
    }
    if (strpos($line, 'REDIS_HOST=') === 0) {
        echo "REDIS_HOST from file: " . substr($line, strlen('REDIS_HOST=')) . "\n";
    }
    if (strpos($line, 'REDIS_PORT=') === 0) {
        echo "REDIS_PORT from file: " . substr($line, strlen('REDIS_PORT=')) . "\n";
    }
}
