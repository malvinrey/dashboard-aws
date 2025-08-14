<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Predis Connection Test to Memurai</h2>";

// Check if Predis is available
echo "<h3>1. Predis Availability:</h3>";
if (class_exists('Predis\Client')) {
    echo "✅ Predis class is available<br>";
} else {
    echo "❌ Predis class is NOT available<br>";
    echo "Installing Predis...<br>";

    // Try to install Predis via Composer
    if (file_exists('../composer.json')) {
        echo "Composer.json found, trying to install Predis...<br>";
        echo "Please run: composer require predis/predis<br>";
    } else {
        echo "Composer.json not found<br>";
    }
    exit;
}

// Test connection to Memurai
echo "<h3>2. Connection Test to Memurai:</h3>";
try {
    $redis = new Predis\Client([
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 5
    ]);

    echo "✅ Predis client created successfully<br>";

    // Test connection
    $redis->ping();
    echo "✅ Connection to Memurai successful!<br>";

    // Test basic operations
    $redis->set('test_key', 'Hello Memurai!');
    $value = $redis->get('test_key');
    echo "✅ Set/Get test: $value<br>";

    // Test ping
    $ping = $redis->ping();
    echo "✅ Ping response: $ping<br>";

    // Test info command
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

    $redis->disconnect();
    echo "✅ Connection closed<br>";
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "<br>";
    echo "Error type: " . get_class($e) . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

// Check PHP version
echo "<h3>3. PHP Version:</h3>";
echo "Current PHP version: " . phpversion() . "<br>";

// Show PHP configuration
echo "<h3>4. PHP Configuration:</h3>";
echo "Loaded extensions: " . implode(', ', get_loaded_extensions()) . "<br>";
echo "PHP ini location: " . php_ini_loaded_file() . "<br>";
echo "Extension directory: " . ini_get('extension_dir') . "<br>";
