<?php

/**
 * Test Database Connection and Queue Tables
 * This script tests if the database is accessible and queue tables exist
 */

echo "=== Testing Database Connection and Queue Tables ===\n\n";

// Database connection settings
$host = 'localhost';
$port = '3306';
$username = 'root';
$password = 'belaanjing12';
$database = 'scada_dashboard';

echo "Connecting to database: $database on $host:$port\n";
echo "Username: $username\n\n";

try {
    // Test PDO connection
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Database connection successful!\n\n";

    // Get database info
    echo "=== Database Information ===\n";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $version['version'] . "\n";

    // Get tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Total Tables: " . count($tables) . "\n";
    echo "Tables: " . implode(', ', $tables) . "\n\n";

    // Check queue tables specifically
    echo "=== Queue System Check ===\n";

    if (in_array('jobs', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
        $jobsCount = $stmt->fetchColumn();
        echo "✅ Jobs table exists with $jobsCount records\n";

        // Show recent jobs
        if ($jobsCount > 0) {
            $stmt = $pdo->query("SELECT id, queue, attempts, created_at FROM jobs ORDER BY created_at DESC LIMIT 5");
            $recentJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "Recent Jobs:\n";
            foreach ($recentJobs as $job) {
                echo "  ID: {$job['id']}, Queue: {$job['queue']}, Attempts: {$job['attempts']}, Created: {$job['created_at']}\n";
            }
        }
    } else {
        echo "❌ Jobs table not found\n";
    }

    if (in_array('failed_jobs', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM failed_jobs");
        $failedJobsCount = $stmt->fetchColumn();
        echo "✅ Failed jobs table exists with $failedJobsCount records\n";

        if ($failedJobsCount > 0) {
            $stmt = $pdo->query("SELECT id, queue, failed_at, exception FROM failed_jobs ORDER BY failed_at DESC LIMIT 3");
            $failedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "Recent Failed Jobs:\n";
            foreach ($failedJobs as $job) {
                echo "  ID: {$job['id']}, Queue: {$job['queue']}, Failed: {$job['failed_at']}\n";
                echo "  Exception: " . substr($job['exception'], 0, 100) . "...\n";
            }
        }
    } else {
        echo "❌ Failed jobs table not found\n";
    }

    // Check SCADA data tables
    echo "\n=== SCADA Data Tables Check ===\n";

    if (in_array('scada_data_wides', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM scada_data_wides");
        $scadaCount = $stmt->fetchColumn();
        echo "✅ SCADA data table exists with $scadaCount records\n";

        if ($scadaCount > 0) {
            $stmt = $pdo->query("SELECT MAX(timestamp_device) as latest, MIN(timestamp_device) as earliest FROM scada_data_wides");
            $timeRange = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  Data range: {$timeRange['earliest']} to {$timeRange['latest']}\n";
        }
    } else {
        echo "❌ SCADA data table not found\n";
    }

    if (in_array('users', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $usersCount = $stmt->fetchColumn();
        echo "✅ Users table exists with $usersCount records\n";
    } else {
        echo "❌ Users table not found\n";
    }

    echo "\n=== Connection Test Summary ===\n";
    echo "✅ Database: Connected successfully\n";
    echo "✅ Queue Tables: " . (in_array('jobs', $tables) ? 'Available' : 'Missing') . "\n";
    echo "✅ SCADA Tables: " . (in_array('scada_data_wides', $tables) ? 'Available' : 'Missing') . "\n";
    echo "✅ User Tables: " . (in_array('users', $tables) ? 'Available' : 'Missing') . "\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting tips:\n";
    echo "1. Check if MySQL service is running\n";
    echo "2. Verify database credentials\n";
    echo "3. Check if database 'scada_dashboard' exists\n";
    echo "4. Verify MySQL port (default: 3306)\n";

    exit(1);
}

echo "\n=== Test Completed Successfully! ===\n";
echo "Database is ready for queue operations.\n";
echo "Next step: Start queue workers with .\\scripts\\start-multiple-queue-workers.ps1\n";
