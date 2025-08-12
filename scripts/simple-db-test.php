<?php

/**
 * Simple Database Connection Test
 * Test basic MySQL connection without Laravel
 */

echo "=== Simple Database Connection Test ===\n\n";

// Test different connection methods
$hosts = ['127.0.0.1'];
$passwords = ['belaanjing12']; // Test different passwords
$username = 'root';
$database = 'scada_dashboard';

foreach ($hosts as $host) {
    echo "Testing host: $host\n";
    echo "------------------------\n";

    foreach ($passwords as $password) {
        $passwordDisplay = $password ?: '(empty)';
        echo "Testing password: $passwordDisplay\n";

        try {
            // Test connection without database first
            $dsn = "mysql:host=$host;port=3306";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo "✅ Connection successful to MySQL server\n";

            // Test if database exists
            try {
                $pdo->query("USE $database");
                echo "✅ Database '$database' accessible\n";

                // Get tables
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "✅ Tables found: " . count($tables) . "\n";

                if (count($tables) > 0) {
                    echo "   Tables: " . implode(', ', array_slice($tables, 0, 5));
                    if (count($tables) > 5) echo " and " . (count($tables) - 5) . " more";
                    echo "\n";
                }

                echo "\n🎉 SUCCESS! Working configuration found:\n";
                echo "   Host: $host\n";
                echo "   Username: $username\n";
                echo "   Password: $passwordDisplay\n";
                echo "   Database: $database\n\n";

                // Test queue tables specifically
                if (in_array('jobs', $tables)) {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
                    $jobsCount = $stmt->fetchColumn();
                    echo "✅ Queue system ready: $jobsCount jobs in queue\n";
                } else {
                    echo "⚠ Jobs table not found - queue system needs setup\n";
                }

                exit(0); // Exit on success

            } catch (Exception $e) {
                echo "❌ Database '$database' not accessible: " . $e->getMessage() . "\n";
            }
        } catch (PDOException $e) {
            echo "❌ Connection failed: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    echo "\n";
}

echo "❌ No working configuration found!\n";
echo "\nTroubleshooting steps:\n";
echo "1. Check if MySQL service is running\n";
echo "2. Verify MySQL root password\n";
echo "3. Check if database 'scada_dashboard' exists\n";
echo "4. Try creating database: CREATE DATABASE scada_dashboard;\n";
echo "5. Check MySQL user permissions\n";

exit(1);
