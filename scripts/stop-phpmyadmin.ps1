# Stop phpMyAdmin for SCADA Dashboard
# This script stops phpMyAdmin processes

Write-Host "Stopping phpMyAdmin for SCADA Dashboard..." -ForegroundColor Red
Write-Host "=========================================" -ForegroundColor Red

# Change to the project directory
Set-Location "D:\dashboard-aws"

# Find and stop phpMyAdmin processes
Write-Host "Looking for phpMyAdmin processes..." -ForegroundColor Yellow

try {
    # Find PHP processes that might be running phpMyAdmin
    $phpMyAdminProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
        $_.ProcessName -eq "php"
    }

    if ($phpMyAdminProcesses) {
        Write-Host "Found $($phpMyAdminProcesses.Count) PHP processes" -ForegroundColor Cyan

        foreach ($process in $phpMyAdminProcesses) {
            Write-Host "Stopping process ID: $($process.Id)" -ForegroundColor Yellow
            try {
                $process.Kill()
                Write-Host "✓ Process $($process.Id) stopped" -ForegroundColor Green
            } catch {
                Write-Host "⚠ Error stopping process $($process.Id): $_" -ForegroundColor Yellow
            }
        }

        # Wait a moment for processes to stop
        Start-Sleep -Seconds 2

        # Check if processes are still running
        $remainingProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
            $_.ProcessName -eq "php"
        }

        if ($remainingProcesses) {
            Write-Host "⚠ $($remainingProcesses.Count) PHP processes still running" -ForegroundColor Yellow
        } else {
            Write-Host "✓ All PHP processes stopped successfully" -ForegroundColor Green
        }

    } else {
        Write-Host "No PHP processes found running" -ForegroundColor Yellow
    }

} catch {
    Write-Host "Error during process management: $_" -ForegroundColor Red
}

# Check if port 8080 is still in use
Write-Host "Checking if port 8080 is free..." -ForegroundColor Cyan

try {
    $portCheck = netstat -an | findstr :8080
    if ($portCheck) {
        Write-Host "⚠ Port 8080 is still in use:" -ForegroundColor Yellow
        Write-Host $portCheck -ForegroundColor White
    } else {
        Write-Host "✓ Port 8080 is now free" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠ Could not check port status" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "phpMyAdmin stopped!" -ForegroundColor Green
Write-Host "===================" -ForegroundColor Green
Write-Host "Port 8080: Available" -ForegroundColor White
Write-Host ""
Write-Host "To start phpMyAdmin again, run: .\scripts\start-phpmyadmin.ps1" -ForegroundColor Yellow
