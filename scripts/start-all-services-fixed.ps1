# Start All Services Fixed - PowerShell Script
# This script starts all necessary services for WebSocket and Laravel to work properly

Write-Host "Starting All Services for WebSocket Fix..." -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green

# Function to check if a process is running
function Test-ProcessRunning {
    param([string]$ProcessName)
    return Get-Process -Name $ProcessName -ErrorAction SilentlyContinue
}

# Function to start a service and wait for it
function Start-ServiceAndWait {
    param(
        [string]$ServiceName,
        [string]$Command,
        [string]$WorkingDirectory = $PWD
    )

    Write-Host "Starting $ServiceName..." -ForegroundColor Yellow

    if (Test-ProcessRunning $ServiceName) {
        Write-Host "$ServiceName is already running" -ForegroundColor Green
        return $true
    }

    try {
        Start-Process -FilePath "cmd" -ArgumentList "/c", $Command -WorkingDirectory $WorkingDirectory -WindowStyle Minimized
        Start-Sleep -Seconds 3

        # Wait for process to start
        $attempts = 0
        while ($attempts -lt 10) {
            if (Test-ProcessRunning $ServiceName) {
                Write-Host "$ServiceName started successfully" -ForegroundColor Green
                return $true
            }
            Start-Sleep -Seconds 1
            $attempts++
        }

        Write-Host "Failed to start $ServiceName after 10 attempts" -ForegroundColor Red
        return $false
    }
    catch {
        Write-Host "Error starting $ServiceName: $_" -ForegroundColor Red
        return $false
    }
}

# Function to check if a port is listening
function Test-PortListening {
    param([int]$Port)

    try {
        $connection = Test-NetConnection -ComputerName "127.0.0.1" -Port $Port -InformationLevel Quiet -WarningAction SilentlyContinue
        return $connection.TcpTestSucceeded
    }
    catch {
        return $false
    }
}

# Stop any existing services first
Write-Host "Stopping existing services..." -ForegroundColor Yellow
Get-Process -Name "php", "nginx", "redis-server", "soketi" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

# 1. Start Redis Server
Write-Host "`n1. Starting Redis Server..." -ForegroundColor Cyan
if (Test-ProcessRunning "redis-server") {
    Write-Host "Redis is already running" -ForegroundColor Green
} else {
    $redisStarted = Start-ServiceAndWait "redis-server" "redis-server"
    if (-not $redisStarted) {
        Write-Host "Warning: Redis may not have started properly" -ForegroundColor Yellow
    }
}

# Wait for Redis to be ready
Write-Host "Waiting for Redis to be ready..." -ForegroundColor Yellow
$redisReady = $false
$attempts = 0
while (-not $redisReady -and $attempts -lt 30) {
    try {
        $redisTest = Test-NetConnection -ComputerName "127.0.0.1" -Port 6379 -InformationLevel Quiet -WarningAction SilentlyContinue
        if ($redisTest.TcpTestSucceeded) {
            $redisReady = $true
            Write-Host "Redis is ready on port 6379" -ForegroundColor Green
        }
    }
    catch {
        # Ignore errors
    }

    if (-not $redisReady) {
        Start-Sleep -Seconds 1
        $attempts++
        Write-Host "Waiting for Redis... ($attempts/30)" -ForegroundColor Yellow
    }
}

if (-not $redisReady) {
    Write-Host "Warning: Redis may not be fully ready" -ForegroundColor Yellow
}

# 2. Start PHP-FPM
Write-Host "`n2. Starting PHP-FPM..." -ForegroundColor Cyan
$phpStarted = Start-ServiceAndWait "php-cgi" "php-cgi -b 127.0.0.1:9000"
if (-not $phpStarted) {
    Write-Host "Warning: PHP-FPM may not have started properly" -ForegroundColor Yellow
}

# 3. Start Nginx
Write-Host "`n3. Starting Nginx..." -ForegroundColor Cyan
$nginxStarted = Start-ServiceAndWait "nginx" "nginx\nginx.exe -c nginx\config\nginx.conf"
if (-not $nginxStarted) {
    Write-Host "Warning: Nginx may not have started properly" -ForegroundColor Yellow
}

# Wait for Nginx to be ready
Write-Host "Waiting for Nginx to be ready..." -ForegroundColor Yellow
$nginxReady = $false
$attempts = 0
while (-not $nginxReady -and $attempts -lt 30) {
    try {
        $nginxTest = Test-NetConnection -ComputerName "127.0.0.1" -Port 80 -InformationLevel Quiet -WarningAction SilentlyContinue
        if ($nginxTest.TcpTestSucceeded) {
            $nginxReady = $true
            Write-Host "Nginx is ready on port 80" -ForegroundColor Green
        }
    }
    catch {
        # Ignore errors
    }

    if (-not $nginxReady) {
        Start-Sleep -Seconds 1
        $attempts++
        Write-Host "Waiting for Nginx... ($attempts/30)" -ForegroundColor Yellow
    }
}

# 4. Start Laravel Queue Worker
Write-Host "`n4. Starting Laravel Queue Worker..." -ForegroundColor Cyan
$queueStarted = Start-ServiceAndWait "php" "php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
if (-not $queueStarted) {
    Write-Host "Warning: Queue worker may not have started properly" -ForegroundColor Yellow
}

# 5. Start Soketi WebSocket Server
Write-Host "`n5. Starting Soketi WebSocket Server..." -ForegroundColor Cyan
$soketiStarted = Start-ServiceAndWait "soketi" "soketi start --config=soketi.json"
if (-not $soketiStarted) {
    Write-Host "Warning: Soketi may not have started properly" -ForegroundColor Yellow
}

# Wait for Soketi to be ready
Write-Host "Waiting for Soketi to be ready..." -ForegroundColor Yellow
$soketiReady = $false
$attempts = 0
while (-not $soketiReady -and $attempts -lt 30) {
    try {
        $soketiTest = Test-NetConnection -ComputerName "127.0.0.1" -Port 6001 -InformationLevel Quiet -WarningAction SilentlyContinue
        if ($soketiTest.TcpTestSucceeded) {
            $soketiReady = $true
            Write-Host "Soketi is ready on port 6001" -ForegroundColor Green
        }
    }
    catch {
        # Ignore errors
    }

    if (-not $soketiReady) {
        Start-Sleep -Seconds 1
        $attempts++
        Write-Host "Waiting for Soketi... ($attempts/30)" -ForegroundColor Yellow
    }
}

# 6. Start Laravel Development Server (if needed)
Write-Host "`n6. Starting Laravel Development Server..." -ForegroundColor Cyan
$laravelStarted = Start-ServiceAndWait "php" "php artisan serve --host=0.0.0.0 --port=8000"
if (-not $laravelStarted) {
    Write-Host "Warning: Laravel server may not have started properly" -ForegroundColor Yellow
}

# Wait for Laravel to be ready
Write-Host "Waiting for Laravel to be ready..." -ForegroundColor Yellow
$laravelReady = $false
$attempts = 0
while (-not $laravelReady -and $attempts -lt 30) {
    try {
        $laravelTest = Test-NetConnection -ComputerName "127.0.0.1" -Port 8000 -InformationLevel Quiet -WarningAction SilentlyContinue
        if ($laravelTest.TcpTestSucceeded) {
            $laravelReady = $true
            Write-Host "Laravel is ready on port 8000" -ForegroundColor Green
        }
    }
    catch {
        # Ignore errors
    }

    if (-not $laravelReady) {
        Start-Sleep -Seconds 1
        $attempts++
        Write-Host "Waiting for Laravel... ($attempts/30)" -ForegroundColor Yellow
    }
}

# Final status check
Write-Host "`n=============================================" -ForegroundColor Green
Write-Host "Service Status Summary:" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green

$services = @(
    @{Name="Redis"; Port=6379; Process="redis-server"},
    @{Name="PHP-FPM"; Port=9000; Process="php-cgi"},
    @{Name="Nginx"; Port=80; Process="nginx"},
    @{Name="Laravel Queue"; Port=0; Process="php"},
    @{Name="Soketi"; Port=6001; Process="soketi"},
    @{Name="Laravel Server"; Port=8000; Process="php"}
)

foreach ($service in $services) {
    $processRunning = Test-ProcessRunning $service.Process
    $portListening = $true

    if ($service.Port -gt 0) {
        $portListening = Test-PortListening $service.Port
    }

    $status = if ($processRunning -and $portListening) { "✅ RUNNING" } else { "❌ NOT RUNNING" }
    $color = if ($processRunning -and $portListening) { "Green" } else { "Red" }

    Write-Host "$($service.Name): $status" -ForegroundColor $color
}

Write-Host "`n=============================================" -ForegroundColor Green
Write-Host "All services started!" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host "`nYou can now:" -ForegroundColor Yellow
Write-Host "1. Open http://localhost:8000 in your browser" -ForegroundColor White
Write-Host "2. Test WebSocket at http://localhost:8000/test-websocket-fix.html" -ForegroundColor White
Write-Host "3. Check WebSocket connection at ws://127.0.0.1:6001" -ForegroundColor White
Write-Host "`nPress Ctrl+C to stop all services" -ForegroundColor Cyan

# Keep script running and monitor services
try {
    while ($true) {
        Start-Sleep -Seconds 30

        # Check if any critical services stopped
        $criticalServices = @("nginx", "php-cgi", "redis-server")
        $stoppedServices = @()

        foreach ($service in $criticalServices) {
            if (-not (Test-ProcessRunning $service)) {
                $stoppedServices += $service
            }
        }

        if ($stoppedServices.Count -gt 0) {
            Write-Host "Warning: Critical services stopped: $($stoppedServices -join ', ')" -ForegroundColor Red
            Write-Host "Consider restarting the script" -ForegroundColor Yellow
        }
    }
}
catch {
    Write-Host "`nStopping all services..." -ForegroundColor Yellow

    # Stop all services
    Get-Process -Name "php", "nginx", "redis-server", "soketi" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

    Write-Host "All services stopped" -ForegroundColor Green
}
