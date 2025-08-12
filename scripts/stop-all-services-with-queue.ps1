# Stop All Services with Queue Workers for SCADA Dashboard
# This script stops all services including the queue system

Write-Host "Stopping All Services with Queue Workers for SCADA Dashboard..." -ForegroundColor Red
Write-Host "=============================================================" -ForegroundColor Red

# Change to the project directory
Set-Location "D:\dashboard-aws"

# Stop Queue Workers First
Write-Host "Stopping Queue Workers..." -ForegroundColor Yellow

# Get all running queue worker jobs
$runningWorkers = Get-Job -State Running | Where-Object { $_.Name -like "*SCADA-Worker*" }

if ($runningWorkers.Count -gt 0) {
    Write-Host "Found $($runningWorkers.Count) running queue workers" -ForegroundColor Cyan

    # Stop all running workers
    Stop-Job -State Running
    Remove-Job -State Completed

    Write-Host "✓ All queue workers stopped successfully" -ForegroundColor Green
} else {
    Write-Host "No running queue workers found" -ForegroundColor Yellow
}

# Stop Nginx
Write-Host "Stopping Nginx..." -ForegroundColor Yellow
try {
    & .\nginx\stop-nginx.bat
    Write-Host "✓ Nginx stopped" -ForegroundColor Green
} catch {
    Write-Host "⚠ Error stopping Nginx: $_" -ForegroundColor Yellow
}

# Stop PHP-FPM processes
Write-Host "Stopping PHP-FPM processes..." -ForegroundColor Yellow
try {
    $phpProcesses = Get-Process -Name "php-cgi" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        $phpProcesses | Stop-Process -Force
        Write-Host "✓ PHP-FPM processes stopped" -ForegroundColor Green
    } else {
        Write-Host "No PHP-FPM processes found" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠ Error stopping PHP-FPM: $_" -ForegroundColor Yellow
}

# Stop phpMyAdmin
Write-Host "Stopping phpMyAdmin..." -ForegroundColor Yellow
& .\scripts\stop-phpmyadmin.ps1

# Additional cleanup - stop any remaining PHP processes
Write-Host "Cleaning up remaining PHP processes..." -ForegroundColor Yellow
try {
    $remainingPhpProcesses = Get-Process -Name "php*" -ErrorAction SilentlyContinue
    if ($remainingPhpProcesses) {
        $remainingPhpProcesses | Stop-Process -Force
        Write-Host "✓ Remaining PHP processes cleaned up" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠ Error during cleanup: $_" -ForegroundColor Yellow
}

# Check if services are still running
Write-Host "Verifying services are stopped..." -ForegroundColor Cyan

# Check Nginx
try {
    $nginxResponse = Invoke-WebRequest -Uri "http://localhost:80" -TimeoutSec 3 -ErrorAction Stop
    Write-Host "✗ Nginx is still responding on port 80" -ForegroundColor Red
} catch {
    Write-Host "✓ Nginx is not responding on port 80" -ForegroundColor Green
}

# Check phpMyAdmin
try {
    $phpmyadminResponse = Invoke-WebRequest -Uri "http://localhost:8080" -TimeoutSec 3 -ErrorAction Stop
    Write-Host "✗ phpMyAdmin is still responding on port 8080" -ForegroundColor Red
} catch {
    Write-Host "✓ phpMyAdmin is not responding on port 8080" -ForegroundColor Green
}

# Check Queue Workers
$remainingWorkers = Get-Job -State Running | Where-Object { $_.Name -like "*SCADA-Worker*" }
if ($remainingWorkers.Count -eq 0) {
    Write-Host "✓ All queue workers are stopped" -ForegroundColor Green
} else {
    Write-Host "⚠ $($remainingWorkers.Count) queue workers are still running" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "All services stopped!" -ForegroundColor Green
Write-Host "====================" -ForegroundColor Green
Write-Host "Nginx: Stopped" -ForegroundColor White
Write-Host "PHP-FPM: Stopped" -ForegroundColor White
Write-Host "phpMyAdmin: Stopped" -ForegroundColor White
Write-Host "Queue Workers: Stopped" -ForegroundColor White
Write-Host ""
Write-Host "To start all services again, run: .\scripts\start-all-services-with-queue.ps1" -ForegroundColor Yellow
