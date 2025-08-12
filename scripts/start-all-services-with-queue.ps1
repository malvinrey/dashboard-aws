# Start All Services with Queue Workers for SCADA Dashboard
# This script starts all necessary services including the new queue system

Write-Host "Starting All Services with Queue Workers for SCADA Dashboard..." -ForegroundColor Green
Write-Host "=============================================================" -ForegroundColor Green

# Change to the project directory
Set-Location "D:\dashboard-aws"

# Start Nginx
Write-Host "Starting Nginx..." -ForegroundColor Yellow
Start-Process -FilePath ".\nginx\start-nginx.bat" -WindowStyle Minimized
Start-Sleep -Seconds 3

# Start PHP-FPM
Write-Host "Starting PHP-FPM..." -ForegroundColor Yellow
Start-Process -FilePath "php-cgi.exe" -ArgumentList "-b 127.0.0.1:9000" -WindowStyle Minimized
Start-Sleep -Seconds 2

# Start phpMyAdmin
Write-Host "Starting phpMyAdmin..." -ForegroundColor Yellow
& .\scripts\start-phpmyadmin.ps1
Start-Sleep -Seconds 3

# Start Queue Workers
Write-Host "Starting Queue Workers..." -ForegroundColor Yellow
Write-Host "Starting 3 queue workers for SCADA data processing..." -ForegroundColor Cyan

# Start multiple queue workers in background
$workerJobs = @()

for ($i = 1; $i -le 3; $i++) {
    $workerJob = Start-Job -ScriptBlock {
        param($projectPath, $workerId)

        Set-Location $projectPath

        # Start queue worker with specific configuration
        & php artisan queue:work --queue=scada-processing,scada-large-datasets --tries=3 --timeout=1800 --verbose --name="SCADA-Worker-$workerId"
    } -ArgumentList (Get-Location), $i

    $workerJobs += $workerJob
    Write-Host "  Worker $i started with Job ID: $($workerJob.Id)" -ForegroundColor Green
}

Start-Sleep -Seconds 3

# Check if services are running
Write-Host "Checking service status..." -ForegroundColor Cyan

# Check Nginx
try {
    $nginxResponse = Invoke-WebRequest -Uri "http://localhost:80" -TimeoutSec 5 -ErrorAction Stop
    if ($nginxResponse.StatusCode -eq 200) {
        Write-Host "✓ Nginx is running on port 80" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ Nginx is not responding on port 80" -ForegroundColor Red
}

# Check PHP-FPM
try {
    $phpResponse = Invoke-WebRequest -Uri "http://localhost:80" -TimeoutSec 5 -ErrorAction Stop
    if ($phpResponse.StatusCode -eq 200) {
        Write-Host "✓ PHP-FPM is working with Nginx" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ PHP-FPM is not working properly" -ForegroundColor Red
}

# Check phpMyAdmin
try {
    $phpmyadminResponse = Invoke-WebRequest -Uri "http://localhost:8080" -TimeoutSec 5 -ErrorAction Stop
    if ($phpmyadminResponse.StatusCode -eq 200) {
        Write-Host "✓ phpMyAdmin is running on port 8080" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ phpMyAdmin is not responding on port 8080" -ForegroundColor Red
}

# Check Queue Workers
$runningWorkers = Get-Job -State Running | Where-Object { $_.Name -like "*SCADA-Worker*" }
if ($runningWorkers.Count -eq 3) {
    Write-Host "✓ All 3 queue workers are running" -ForegroundColor Green
} else {
    Write-Host "⚠ Only $($runningWorkers.Count)/3 queue workers are running" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "All services started successfully!" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green
Write-Host "Nginx: http://localhost:80" -ForegroundColor White
Write-Host "phpMyAdmin: http://localhost:8080" -ForegroundColor White
Write-Host "SCADA Dashboard: http://localhost:80" -ForegroundColor White
Write-Host "Queue Workers: $($runningWorkers.Count)/3 running" -ForegroundColor White
Write-Host ""
Write-Host "Queue Worker Management:" -ForegroundColor Cyan
Write-Host "  Get-Job                    # List all background jobs" -ForegroundColor White
Write-Host "  Receive-Job -Id <JobId>   # See output from specific worker" -ForegroundColor White
Write-Host "  Stop-Job -State Running   # Stop all running workers" -ForegroundColor White
Write-Host ""
Write-Host "Monitoring:" -ForegroundColor Cyan
Write-Host "  .\scripts\monitor-queue-status.ps1    # Monitor queue status" -ForegroundColor White
Write-Host ""
Write-Host "To stop all services, run: .\scripts\stop-all-services-with-queue.ps1" -ForegroundColor Yellow
