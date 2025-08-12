<?php

/**
 * Simple phpMyAdmin-like interface for SCADA Dashboard
 * This is a basic interface to manage MySQL database
 */

// Database connection settings
$host = '127.0.0.1';
$port = '3306';
$username = 'root';
$password = 'belaanjing12';
$database = 'scada_dashboard';

// Test database connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connectionStatus = "Connected successfully to database: $database";
} catch (PDOException $e) {
    $connectionStatus = "Connection failed: " . $e->getMessage();
}

// Get database info
$tables = [];
$jobsCount = 0;
$failedJobsCount = 0;

if (isset($pdo)) {
    try {
        // Get tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get queue info
        if (in_array('jobs', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
            $jobsCount = $stmt->fetchColumn();
        }

        if (in_array('failed_jobs', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM failed_jobs");
            $failedJobsCount = $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA Dashboard - Database Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🚀 SCADA Dashboard - Database Management</h1>
            <p>Simple database interface for SCADA data processing</p>
        </div>

        <div class="status <?php echo isset($pdo) ? 'success' : 'error'; ?>">
            <h3>Database Connection Status</h3>
            <p><?php echo $connectionStatus; ?></p>
        </div>

        <?php if (isset($pdo)): ?>
            <div class="status info">
                <h3>Queue System Status</h3>
                <p><strong>Jobs in Queue:</strong> <?php echo $jobsCount; ?></p>
                <p><strong>Failed Jobs:</strong> <?php echo $failedJobsCount; ?></p>
            </div>

            <div class="status info">
                <h3>Database Tables</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($table); ?></td>
                                <td>✅ Available</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="status info">
                <h3>Quick Actions</h3>
                <a href="http://localhost:80" class="btn">🏠 SCADA Dashboard</a>
                <a href="http://localhost:80/api/aws/receiver" class="btn">📡 API Endpoint</a>
                <a href="http://localhost:80/log-data" class="btn">📊 Log Data</a>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="status error">
                <h3>Error Information</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
