# WebSocket Services Startup Script
# Script ini digunakan untuk menjalankan semua service yang diperlukan untuk WebSocket

param(
    [string]$Environment = "local",
    [switch]$Background
)

# Global variables for tracking process IDs
$Global:ServicePIDs = @{}
$Global:PIDFile = ".\temp\websocket-services.pid"

# =============================================================================
# Function to load service PIDs from file (THIS IS THE FIX)
# =============================================================================
function Load-ServicePIDs {
    try {
        if (Test-Path $Global:PIDFile) {
            $fileContent = Get-Content -Path $Global:PIDFile -Raw
            if ($fileContent) {
                $Global:ServicePIDs = $fileContent | ConvertFrom-Json
                Write-Host "📂 Loaded service PIDs from: $Global:PIDFile" -ForegroundColor Cyan
                return $true
            }
        }
    } catch {
        Write-Host "⚠️  Warning: Could not load or parse service PIDs from file. It might be corrupt." -ForegroundColor Yellow
        # Corrupt file, so we should clean it up to prevent future errors
        Remove-Item $Global:PIDFile -Force -ErrorAction SilentlyContinue
    }
    return $false
}

Write-Host "🚀 Starting SCADA WebSocket Services..." -ForegroundColor Green

# Check if services are already running and load PIDs
if (Load-ServicePIDs) {
    Write-Host "📂 Found existing service PIDs, checking if services are still running..." -ForegroundColor Cyan

    $existingServices = @{}
    foreach ($service in $Global:ServicePIDs.GetEnumerator()) {
        if ($service.Value -and (Get-Process -Id $service.Value -ErrorAction SilentlyContinue)) {
            $existingServices[$service.Key] = $service.Value
            Write-Host "✅ $($service.Key) already running (PID: $($service.Value))" -ForegroundColor Green
        } else {
            Write-Host "⚠️  $($service.Key) process with PID $($service.Value) not found. It may have been stopped." -ForegroundColor Yellow
        }
    }

    # Update global PIDs with only running services
    $Global:ServicePIDs = $existingServices
}

# Set environment variables
$env:BROADCAST_DRIVER = "pusher"
$env:QUEUE_CONNECTION = "redis"
$env:CACHE_DRIVER = "redis"
$env:SESSION_DRIVER = "redis"
$env:PUSHER_APP_ID = "12345"
$env:PUSHER_APP_KEY = "scada_dashboard_key_2024"
$env:PUSHER_APP_SECRET = "scada_dashboard_secret_2024"
$env:PUSHER_APP_CLUSTER = "mt1"
$env:PUSHER_HOST = "127.0.0.1"
$env:PUSHER_PORT = "6001"
$env:PUSHER_SCHEME = "http"
$env:PUSHER_APP_ENCRYPTED = "false"

# Function to check if port is available
function Test-Port {
    param([int]$Port)
    try {
        $connection = New-Object System.Net.Sockets.TcpClient
        $connection.Connect("127.0.0.1", $Port)
        $connection.Close()
        return $true
    }
    catch {
        return $false
    }
}

# Function to check if Soketi is responding
function Test-Soketi {
    try {
        # First check if port is listening
        if (-not (Test-Port 6001)) {
            return $false
        }

        # Then try a simple HTTP request
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:6001" -TimeoutSec 3 -UseBasicParsing -ErrorAction SilentlyContinue
        return $response.StatusCode -eq 200
    }
    catch {
        return (Test-Port 6001)
    }
}

# Function to save service PIDs to file
function Save-ServicePIDs {
    try {
        if (-not (Test-Path ".\temp")) {
            New-Item -ItemType Directory -Path ".\temp" -Force | Out-Null
        }
        $Global:ServicePIDs | ConvertTo-Json | Out-File -FilePath $Global:PIDFile -Encoding UTF8
        Write-Host "💾 Service PIDs saved to: $Global:PIDFile" -ForegroundColor Cyan
    } catch {
        Write-Host "⚠️  Warning: Could not save service PIDs to file" -ForegroundColor Yellow
    }
}

# Function to start service
function Start-Service {
    param(
        [string]$Name,
        [string]$Command,
        [string]$WorkingDirectory = $PWD
    )

    Write-Host "Starting $Name..." -ForegroundColor Yellow

    try {
        $process = Start-Process -FilePath "powershell" -ArgumentList "-Command", $Command -WorkingDirectory $WorkingDirectory -WindowStyle Hidden -PassThru
        if ($process) {
            Write-Host "$Name started successfully (PID: $($process.Id))" -ForegroundColor Green
            return $process.Id
        } else {
            Write-Host "❌ Failed to start $Name" -ForegroundColor Red
            return $null
        }
    } catch {
        Write-Host "❌ Error starting $Name`: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

# Check Redis installation and status
Write-Host "Checking Redis installation..." -ForegroundColor Cyan
if (-not (Get-Command "redis-server" -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Redis not found. Running Redis setup..." -ForegroundColor Red
    & "$PSScriptRoot\setup-redis.ps1"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Redis setup failed. Please install Redis manually." -ForegroundColor Red
        exit 1
    }
}

# Check if Redis is running
if (-not (Test-Port 6379)) {
    Write-Host "Starting Redis server..." -ForegroundColor Yellow
    try {
        Start-Process -FilePath "redis-server" -ArgumentList "--port", "6379" -WindowStyle Hidden
        Start-Sleep -Seconds 3
        Write-Host "✅ Redis server started" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to start Redis server" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✅ Redis is already running on port 6379" -ForegroundColor Green
}

# Check if Soketi is available locally
$soketiPath = ".\node_modules\.bin\soketi"
if (-not (Test-Path $soketiPath)) {
    Write-Host "❌ Soketi not found in node_modules. Installing..." -ForegroundColor Red
    npm install @soketi/soketi
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Failed to install Soketi" -ForegroundColor Red
        exit 1
    }
}

Write-Host "✅ Soketi found at: $soketiPath" -ForegroundColor Green

# Check if Laravel is ready
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Laravel artisan not found. Please run this script from the Laravel project root." -ForegroundColor Red
    exit 1
}

# Check database connection
Write-Host "Checking database connection..." -ForegroundColor Yellow
try {
    php artisan db:monitor --quiet
    Write-Host "✅ Database connection OK" -ForegroundColor Green
} catch {
    Write-Host "❌ Database connection failed. Please check your database configuration." -ForegroundColor Red
    exit 1
}

# Start Soketi WebSocket server
if ($Global:ServicePIDs.ContainsKey("Soketi")) {
    Write-Host "✅ Soketi already running (PID: $($Global:ServicePIDs['Soketi']))" -ForegroundColor Green
} else {
    Write-Host "Starting Soketi WebSocket Server..." -ForegroundColor Yellow
    $soketiPID = Start-Service -Name "Soketi WebSocket Server" -Command "& '$soketiPath' start --config=soketi.json"
    if ($soketiPID) {
        $Global:ServicePIDs["Soketi"] = $soketiPID
    } else {
        Write-Host "❌ Failed to start Soketi" -ForegroundColor Red
        exit 1
    }
}

# Wait for Soketi to start
Write-Host "Waiting for Soketi to start..." -ForegroundColor Yellow
$maxAttempts = 30
$attempt = 0
$soketiStarted = $false
while ($attempt -lt $maxAttempts -and -not $soketiStarted) {
    Start-Sleep -Seconds 1
    $attempt++
    if (Test-Soketi) {
        $soketiStarted = $true
        Write-Host "✅ Soketi WebSocket server started and responding on port 6001 (attempt $attempt)" -ForegroundColor Green
    }
}
if (-not $soketiStarted) {
    Write-Host "❌ Failed to start Soketi WebSocket server after $maxAttempts attempts" -ForegroundColor Red
    exit 1
}

# Start Laravel queue worker with Redis
if ($Global:ServicePIDs.ContainsKey("QueueWorker")) {
    Write-Host "✅ Queue Worker already running (PID: $($Global:ServicePIDs['QueueWorker']))" -ForegroundColor Green
} else {
    Write-Host "Starting Laravel Queue Worker..." -ForegroundColor Yellow
    $queueWorkerPID = Start-Service -Name "Laravel Queue Worker" -Command "php artisan queue:work --tries=3 --timeout=60 --connection=redis"
    if ($queueWorkerPID) {
        $Global:ServicePIDs["QueueWorker"] = $queueWorkerPID
    } else {
        Write-Host "❌ Failed to start Queue Worker" -ForegroundColor Red
        exit 1
    }
}

# Start Laravel development server
if (-not (Test-Port 8000)) {
    if ($Global:ServicePIDs.ContainsKey("Laravel")) {
         Write-Host "✅ Laravel Server already running (PID: $($Global:ServicePIDs['Laravel']))" -ForegroundColor Green
    } else {
        Write-Host "Starting Laravel Development Server..." -ForegroundColor Yellow
        $laravelPID = Start-Service -Name "Laravel Development Server" -Command "php artisan serve --host=127.0.0.1 --port=8000"
        if ($laravelPID) {
            $Global:ServicePIDs["Laravel"] = $laravelPID
        } else {
            Write-Host "❌ Failed to start Laravel server" -ForegroundColor Red
            exit 1
        }
    }
} else {
    Write-Host "✅ Laravel server already running on port 8000" -ForegroundColor Green
}

# Display status
Write-Host ""
Write-Host "🎉 All services started successfully!" -ForegroundColor Green
Save-ServicePIDs
Write-Host "📊 Service Status:" -ForegroundColor Cyan
Write-Host "  • Redis Server: 127.0.0.1:6379" -ForegroundColor White
Write-Host "  • Soketi WebSocket Server: http://127.0.0.1:6001 (PID: $($Global:ServicePIDs['Soketi']))" -ForegroundColor White
Write-Host "  • Laravel Application: http://127.0.0.1:8000 (PID: $($Global:ServicePIDs['Laravel']))" -ForegroundColor White
Write-Host "  • Queue Worker: PID $($Global:ServicePIDs['QueueWorker'])" -ForegroundColor White
Write-Host "  • WebSocket Test Page: http://127.0.0.1:8000/websocket-test" -ForegroundColor White
Write-Host ""
Write-Host "🔧 Management Commands:" -ForegroundColor Cyan
Write-Host "  • Monitor Redis: redis-cli monitor" -ForegroundColor White
Write-Host "  • View queue status: php artisan queue:monitor" -ForegroundColor White
Write-Host "  • Check failed jobs: php artisan queue:failed" -ForegroundColor White
Write-Host "  • Monitor WebSocket: & '$soketiPath' status" -ForegroundColor White
Write-Host "  • Service PIDs file: $Global:PIDFile" -ForegroundColor White
Write-Host ""
Write-Host "⚡ Performance Features:" -ForegroundColor Cyan
Write-Host "  • Redis Queue: Ultra-fast job processing" -ForegroundColor White
Write-Host "  • Redis Cache: In-memory caching" -ForegroundColor White
Write-Host "  • Redis Session: Fast session handling" -ForegroundColor White
Write-Host ""
Write-Host "⚠️  Note: Keep this terminal open to monitor services" -ForegroundColor Yellow
Write-Host "   Or use the stop script to stop all services" -ForegroundColor Yellow

# Keep running if not in background
if (-not $Background) {
    Write-Host ""
    Write-Host "Press Ctrl+C to stop all services..." -ForegroundColor Red

    try {
        while ($true) {
            Start-Sleep -Seconds 10

            # Check service status
            $redisRunning = Test-Port 6379
            $soketiRunning = Test-Soketi
            $laravelRunning = Test-Port 8000

            if (-not $redisRunning) {
                Write-Host "⚠️  Redis server stopped unexpectedly" -ForegroundColor Yellow
            }

            if (-not $soketiRunning) {
                Write-Host "⚠️  Soketi WebSocket server stopped unexpectedly" -ForegroundColor Yellow
            }

            if (-not $laravelRunning) {
                Write-Host "⚠️  Laravel server stopped unexpectedly" -ForegroundColor Yellow
            }
        }
    }
    catch {
        Write-Host ""
                Write-Host "🛑 Stopping all services..." -ForegroundColor Red

        # Stop services using tracked PIDs
        if ($Global:ServicePIDs.Count -gt 0) {
            Write-Host "Stopping tracked services..." -ForegroundColor Yellow

            foreach ($service in $Global:ServicePIDs.GetEnumerator()) {
                try {
                    if (Get-Process -Id $service.Value -ErrorAction SilentlyContinue) {
                        Stop-Process -Id $service.Value -Force
                        Write-Host "✅ Stopped $($service.Key) (PID: $($service.Value))" -ForegroundColor Green
                    }
                } catch {
                    Write-Host "⚠️  Could not stop $($service.Key) (PID: $($service.Value))" -ForegroundColor Yellow
                }
            }
        } else {
            # Fallback to process name-based stopping
            Write-Host "Using fallback process stopping..." -ForegroundColor Yellow

            try {
                Get-Process | Where-Object {$_.ProcessName -eq "php"} | Stop-Process -Force
            } catch {
                Write-Host "⚠️  Could not stop PHP processes" -ForegroundColor Yellow
            }

            try {
                Get-Process | Where-Object {$_.ProcessName -eq "node"} | Stop-Process -Force
            } catch {
                Write-Host "⚠️  Could not stop Node.js processes" -ForegroundColor Yellow
            }
        }

        try {
            Get-Process | Where-Object {$_.ProcessName -eq "redis-server"} | Stop-Process -Force
        } catch {
            Write-Host "⚠️  Could not stop Redis processes" -ForegroundColor Yellow
        }

        # Clean up PID file
        try {
            if (Test-Path $Global:PIDFile) {
                Remove-Item $Global:PIDFile -Force
                Write-Host "🗑️  PID file cleaned up" -ForegroundColor Cyan
            }
        } catch {
            Write-Host "⚠️  Could not clean up PID file" -ForegroundColor Yellow
        }

        Write-Host "✅ All services stopped" -ForegroundColor Green
    }
}
