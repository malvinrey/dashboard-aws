# Stop Services Script
# This script stops PHP-FPM and Nginx services

Write-Host "Stopping Nginx..." -ForegroundColor Yellow
# Stop Nginx gracefully
try {
    $nginxProcesses = Get-Process -Name "nginx" -ErrorAction SilentlyContinue
    if ($nginxProcesses) {
        foreach ($process in $nginxProcesses) {
            Write-Host "Stopping Nginx process ID: $($process.Id)" -ForegroundColor Yellow
            Stop-Process -Id $process.Id -Force
        }
        Write-Host "Nginx stopped successfully" -ForegroundColor Green
    } else {
        Write-Host "No Nginx processes found" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Error stopping Nginx: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "Stopping PHP-FPM..." -ForegroundColor Yellow
# Stop PHP-FPM processes
try {
    $phpProcesses = Get-Process -Name "php-cgi" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        foreach ($process in $phpProcesses) {
            Write-Host "Stopping PHP-FPM process ID: $($process.Id)" -ForegroundColor Yellow
            Stop-Process -Id $process.Id -Force
        }
        Write-Host "PHP-FPM stopped successfully" -ForegroundColor Green
    } else {
        Write-Host "No PHP-FPM processes found" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Error stopping PHP-FPM: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "All services stopped!" -ForegroundColor Green
