<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Redis Connection Test</h2>";

// Check PHP Redis extension first
echo "<h3>1. PHP Redis Extension Status:</h3>";
if (extension_loaded('redis')) {
    echo "✅ Redis extension is loaded<br>";
    echo "Version: " . phpversion('redis') . "<br>";
} else {
    echo "❌ Redis extension is NOT loaded<br>";
    echo "This is the main issue - you need to install Redis extension first<br>";
    echo "<br><strong>Solution:</strong><br>";
    echo "1. Download Redis extension for PHP 8.3 from PECL<br>";
    echo "2. Extract php_redis.dll to your PHP ext folder<br>";
    echo "3. Add 'extension=redis' to your php.ini<br>";
    echo "4. Restart your web server<br>";
    exit;
}

// Check PHP version
echo "<h3>2. PHP Version:</h3>";
echo "Current PHP version: " . phpversion() . "<br>";

// Check if Redis class exists
echo "<h3>3. Redis Class Availability:</h3>";
if (class_exists('Redis')) {
    echo "✅ Redis class is available<br>";
} else {
    echo "❌ Redis class is NOT available<br>";
    exit;
}

// Test basic Redis connection
echo "<h3>4. Redis Connection Test:</h3>";
try {
    $redis = new Redis();
    echo "✅ Redis object created successfully<br>";

    // Test connection
    $connected = $redis->connect('127.0.0.1', 6379);
    if ($connected) {
        echo "✅ Redis connection successful!<br>";

        // Test basic operations
        $redis->set('test_key', 'Hello Redis!');
        $value = $redis->get('test_key');
        echo "✅ Set/Get test: $value<br>";

        // Test ping
        $ping = $redis->ping();
        echo "✅ Ping response: $ping<br>";

        $redis->close();
        echo "✅ Redis connection closed<br>";
    } else {
        echo "❌ Redis connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Redis error: " . $e->getMessage() . "<br>";
    echo "Error type: " . get_class($e) . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

// Check Redis service status
echo "<h3>5. Redis Service Status:</h3>";
$port_check = @fsockopen('127.0.0.1', 6379, $errno, $errstr, 5);
if ($port_check) {
    echo "✅ Redis port 6379 is accessible<br>";
    fclose($port_check);
} else {
    echo "❌ Redis port 6379 is NOT accessible<br>";
    echo "Error: $errstr (Code: $errno)<br>";
    echo "<br><strong>Possible solutions:</strong><br>";
    echo "1. Start Redis service<br>";
    echo "2. Check if Redis is running on different port<br>";
    echo "3. Check firewall settings<br>";
}

// Show PHP configuration
echo "<h3>6. PHP Configuration:</h3>";
echo "Loaded extensions: " . implode(', ', get_loaded_extensions()) . "<br>";
echo "PHP ini location: " . php_ini_loaded_file() . "<br>";
echo "Extension directory: " . ini_get('extension_dir') . "<br>";
