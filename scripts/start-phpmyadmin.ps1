# Start phpMyAdmin for SCADA Dashboard
# This script starts phpMyAdmin with proper configuration

Write-Host "Starting phpMyAdmin for SCADA Dashboard..." -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green

# Change to the project directory
Set-Location "D:\dashboard-aws"

# Check if PHP is available
try {
    $phpVersion = & php -v 2>&1 | Select-String "PHP" | Select-Object -First 1
    Write-Host "PHP Version: $phpVersion" -ForegroundColor Cyan
} catch {
    Write-Host "Error: PHP not found in PATH" -ForegroundColor Red
    Write-Host "Please ensure PHP is installed and added to PATH" -ForegroundColor Red
    exit 1
}

# Check if phpMyAdmin directory exists
$phpMyAdminDir = ".\phpmyadmin"
if (-not (Test-Path $phpMyAdminDir)) {
    Write-Host "phpMyAdmin directory not found. Creating basic setup..." -ForegroundColor Yellow

    # Create phpMyAdmin directory
    New-Item -ItemType Directory -Path $phpMyAdminDir -Force | Out-Null

    # Create basic index.php for phpMyAdmin
    $indexContent = @'
<?php
/**
 * Simple phpMyAdmin-like interface for SCADA Dashboard
 * This is a basic interface to manage MySQL database
 */

// Database connection settings
$host = '127.0.0.1';
$port = '3306';
$username = 'root';
$password = 'belaanjing12'; // Update dengan password yang benar
$database = 'scada_dashboard';

// Test database connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connectionStatus = "Connected successfully to database: $database";
} catch(PDOException $e) {
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
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #0056b3; }
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
                <a href="http://127.0.0.1:80" class="btn">🏠 SCADA Dashboard</a>
                <a href="http://127.0.0.1:80/api/aws/receiver" class="btn">📡 API Endpoint</a>
                <a href="http://127.0.0.1:80/log-data" class="btn">📊 Log Data</a>
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
'@

    Set-Content -Path "$phpMyAdminDir\index.php" -Value $indexContent
    Write-Host "✓ Basic phpMyAdmin interface created" -ForegroundColor Green
}

# Start PHP built-in server for phpMyAdmin
Write-Host "Starting PHP server for phpMyAdmin on port 8080..." -ForegroundColor Yellow

try {
    $phpMyAdminProcess = Start-Process -FilePath "php.exe" -ArgumentList "-S", "127.0.0.1:8080", "-t", $phpMyAdminDir -WindowStyle Minimized -PassThru

    if ($phpMyAdminProcess) {
        Write-Host "✓ phpMyAdmin started successfully!" -ForegroundColor Green
        Write-Host "  Process ID: $($phpMyAdminProcess.Id)" -ForegroundColor Cyan
        Write-Host "  URL: http://127.0.0.1:8080" -ForegroundColor White
        Write-Host "  Document Root: $phpMyAdminDir" -ForegroundColor White

        # Wait a moment for server to start
        Start-Sleep -Seconds 3

        # Test if server is responding
        try {
            $response = Invoke-WebRequest -Uri "http://127.0.0.1:8080" -TimeoutSec 5 -ErrorAction Stop
            if ($response.StatusCode -eq 200) {
                Write-Host "✓ phpMyAdmin is responding on port 8080" -ForegroundColor Green
            }
        } catch {
            Write-Host "⚠ phpMyAdmin might still be starting up..." -ForegroundColor Yellow
        }

        Write-Host ""
        Write-Host "To stop phpMyAdmin:" -ForegroundColor Cyan
        Write-Host "  Stop-Process -Id $($phpMyAdminProcess.Id)" -ForegroundColor White
        Write-Host ""
        Write-Host "phpMyAdmin is now running at: http://127.0.0.1:8080" -ForegroundColor Green

    } else {
        Write-Host "✗ Failed to start phpMyAdmin" -ForegroundColor Red
    }

} catch {
    Write-Host "Error starting phpMyAdmin: $_" -ForegroundColor Red
    exit 1
}
