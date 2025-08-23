# Stop WebSocket Services dengan Laravel Reverb
# Script ini digunakan untuk menghentikan semua service yang berjalan

$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$PIDFile = Join-Path $ProjectRoot "temp\websocket-services-reverb.pid"

Write-Host "Stopping SCADA WebSocket Services dengan Laravel Reverb..." -ForegroundColor Red

# Load service PIDs from file
if (Test-Path $PIDFile) {
    try {
        $fileContent = Get-Content -Path $PIDFile -Raw
        if ($fileContent) {
            $servicePIDs = $fileContent | ConvertFrom-Json
            Write-Host "Found service PIDs file: $PIDFile" -ForegroundColor Cyan

            # Stop each service
            foreach ($service in $servicePIDs.PSObject.Properties) {
                $serviceName = $service.Name
                $servicePID = [int]$service.Value

                try {
                    if (Get-Process -Id $servicePID -ErrorAction SilentlyContinue) {
                        Write-Host "Stopping $serviceName (PID: $servicePID)..." -ForegroundColor Yellow
                        Stop-Process -Id $servicePID -Force
                        Write-Host "  $serviceName stopped successfully" -ForegroundColor Green
                    } else {
                        Write-Host "  $serviceName (PID: $servicePID) is not running" -ForegroundColor DarkGray
                    }
                } catch {
                    Write-Host "  Could not stop $serviceName (PID: $servicePID): $($_.Exception.Message)" -ForegroundColor Yellow
                }
            }

            # Clean up PID file
            Remove-Item $PIDFile -Force
            Write-Host "PID file cleaned up" -ForegroundColor Cyan
        }
    } catch {
        Write-Host "Error reading PID file: $($_.Exception.Message)" -ForegroundColor Red
        # Clean up corrupt PID file
        if (Test-Path $PIDFile) {
            Remove-Item $PIDFile -Force
            Write-Host "Corrupt PID file removed" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "No PID file found. Using fallback process stopping..." -ForegroundColor Yellow

    # Fallback: stop processes by name
    try {
        Get-Process | Where-Object {$_.ProcessName -eq "php"} | Stop-Process -Force
        Write-Host "PHP processes stopped" -ForegroundColor Green
    } catch {
        Write-Host "Could not stop PHP processes" -ForegroundColor Yellow
    }
}

# Stop Redis if running
try {
    Get-Process | Where-Object {$_.ProcessName -eq "redis-server"} | Stop-Process -Force
    Write-Host "Redis server stopped" -ForegroundColor Green
} catch {
    Write-Host "Could not stop Redis server" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "All services stopped successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "You can now start services again using:" -ForegroundColor Cyan
Write-Host "  • PowerShell: .\scripts\start-websocket-services-reverb.ps1" -ForegroundColor White
Write-Host "  • Batch file: .\start-websocket-services-reverb.bat" -ForegroundColor White
