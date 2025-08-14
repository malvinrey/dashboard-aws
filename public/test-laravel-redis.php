<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Laravel Redis Connection Test</h2>";

// Bootstrap Laravel
require_once '../vendor/autoload.php';
$app = require_once '../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "<h3>1. Laravel Bootstrap Status:</h3>";
echo "✅ Laravel bootstrapped successfully<br>";

echo "<h3>2. Redis Connection Test:</h3>";
try {
    // Get Redis connection
    $redis = Illuminate\Support\Facades\Redis::connection();
    echo "✅ Redis connection obtained<br>";

    // Test ping
    $ping = $redis->ping();
    echo "✅ Ping response: $ping<br>";

    // Test basic operations
    $redis->set('test_key', 'Hello Redis from Laravel!');
    $value = $redis->get('test_key');
    echo "✅ Set/Get test: $value<br>";

    // Test info
    $info = $redis->info();
    echo "✅ Server info retrieved<br>";

    // Show some info
    if (isset($info['redis_version'])) {
        echo "Redis Version: " . $info['redis_version'] . "<br>";
    }
    if (isset($info['os'])) {
        echo "OS: " . $info['os'] . "<br>";
    }
    if (isset($info['process_id'])) {
        echo "Process ID: " . $info['process_id'] . "<br>";
    }

    echo "✅ All Redis tests passed!<br>";
} catch (Exception $e) {
    echo "❌ Redis error: " . $e->getMessage() . "<br>";
    echo "Error type: " . get_class($e) . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

echo "<h3>3. PHP Configuration:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Redis Extension: " . (extension_loaded('redis') ? '✅ Loaded' : '❌ Not Loaded') . "<br>";
if (extension_loaded('redis')) {
    echo "Redis Extension Version: " . phpversion('redis') . "<br>";
}

echo "<h3>4. Laravel Configuration:</h3>";
echo "Cache Driver: " . config('cache.default') . "<br>";
echo "Session Driver: " . config('session.driver') . "<br>";
echo "Queue Connection: " . config('queue.default') . "<br>";
echo "Redis Host: " . config('database.redis.default.host') . "<br>";
echo "Redis Port: " . config('database.redis.default.port') . "<br>";
echo "Redis Database: " . config('database.redis.default.database') . "<br>";
