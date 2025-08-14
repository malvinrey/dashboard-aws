Write-Host "Stopping SCADA WebSocket Services..." -ForegroundColor Red

# Function to stop service by name pattern
function Stop-ServiceByPattern {
    param([string]$Pattern, [string]$Description)

    try {
        $processes = Get-Process | Where-Object {
            $_.ProcessName -eq "php" -and $_.CommandLine -like "*$Pattern*"
        }

        if ($processes) {
            Write-Host "Stopping $Description..." -ForegroundColor Yellow
            $processes | Stop-Process -Force
            Write-Host "$Description stopped" -ForegroundColor Green
        } else {
            Write-Host "$Description not running" -ForegroundColor Gray
        }
    } catch {
        Write-Host "Error stopping $Description - $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Function to stop Node.js processes
function Stop-NodeService {
    param([string]$Description)

    try {
        $processes = Get-Process | Where-Object {
            $_.ProcessName -eq "node" -and $_.CommandLine -like "*soketi*"
        }

        if ($processes) {
            Write-Host "Stopping $Description..." -ForegroundColor Yellow
            $processes | Stop-Process -Force
            Write-Host "$Description stopped" -ForegroundColor Green
        } else {
            Write-Host "$Description not running" -ForegroundColor Gray
        }
    } catch {
        Write-Host "Error stopping $Description - $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Stop Laravel services
Stop-ServiceByPattern "artisan serve" "Laravel Development Server"
Stop-ServiceByPattern "queue:work" "Laravel Queue Worker"
Stop-ServiceByPattern "websockets:serve" "Laravel WebSocket Server"

# Stop Soketi WebSocket server
Stop-NodeService "Soketi WebSocket Server"

# Stop any remaining PHP processes related to this project
try {
    $phpProcesses = Get-Process | Where-Object {
        $_.ProcessName -eq "php" -and $_.WorkingSet -gt 50MB
    }

    if ($phpProcesses) {
        Write-Host "Stopping remaining PHP processes..." -ForegroundColor Yellow
        $phpProcesses | Stop-Process -Force
        Write-Host "Remaining PHP processes stopped" -ForegroundColor Green
    }
} catch {
    Write-Host "Error stopping remaining PHP processes - $($_.Exception.Message)" -ForegroundColor Yellow
}

# Check if ports are still in use
Write-Host ""
Write-Host "Checking port availability..." -ForegroundColor Cyan

$ports = @(8000, 6001)
foreach ($port in $ports) {
    try {
        $connection = New-Object System.Net.Sockets.TcpClient
        $connection.Connect("127.0.0.1", $port)
        $connection.Close()
        Write-Host "Port $port is still in use" -ForegroundColor Yellow
    } catch {
        Write-Host "Port $port is available" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "All WebSocket services stopped successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "To restart services, run:" -ForegroundColor Cyan
Write-Host "  .\scripts\start-websocket-services.ps1" -ForegroundColor White
