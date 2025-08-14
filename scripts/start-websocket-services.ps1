# WebSocket Services Startup Script
# Script ini digunakan untuk menjalankan semua service yang diperlukan untuk WebSocket

param(
    [string]$Environment = "local",
    [switch]$Background
)

Write-Host "🚀 Starting SCADA WebSocket Services..." -ForegroundColor Green

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

# Function to start service
function Start-Service {
    param(
        [string]$Name,
        [string]$Command,
        [string]$WorkingDirectory = $PWD
    )

    Write-Host "Starting $Name..." -ForegroundColor Yellow

    if ($Background) {
        Start-Process -FilePath "powershell" -ArgumentList "-Command", $Command -WorkingDirectory $WorkingDirectory -WindowStyle Hidden
        Write-Host "$Name started in background" -ForegroundColor Green
    } else {
        Start-Process -FilePath "powershell" -ArgumentList "-Command", $Command -WorkingDirectory $WorkingDirectory
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

# Check if Soketi is installed
try {
    $soketiVersion = soketi --version
    Write-Host "✅ Soketi found: $soketiVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Soketi not found. Installing..." -ForegroundColor Red
    npm install -g @soketi/soketi
}

# Check if Laravel is ready
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Laravel artisan not found. Please run this script from the Laravel project root." -ForegroundColor Red
    exit 1
}

# Check if .env exists
if (-not (Test-Path ".env")) {
    Write-Host "❌ .env file not found. Please create one first." -ForegroundColor Red
    exit 1
}

# Check if database is ready
Write-Host "Checking database connection..." -ForegroundColor Yellow
try {
    php artisan migrate:status --quiet
    Write-Host "✅ Database connection OK" -ForegroundColor Green
} catch {
    Write-Host "❌ Database connection failed. Please check your database configuration." -ForegroundColor Red
    exit 1
}

# Check if queue table exists
if (-not (Test-Path "database/migrations/*_create_jobs_table.php")) {
    Write-Host "❌ Jobs table migration not found. Please run migrations first." -ForegroundColor Red
    exit 1
}

# Start Soketi WebSocket server
Write-Host "Starting Soketi WebSocket server..." -ForegroundColor Yellow
if (Test-Port 6001) {
    Write-Host "⚠️  Port 6001 is already in use. Stopping existing process..." -ForegroundColor Yellow
    Get-Process | Where-Object {$_.ProcessName -eq "node" -and $_.CommandLine -like "*soketi*"} | Stop-Process -Force
    Start-Sleep -Seconds 2
}

Start-Service -Name "Soketi WebSocket Server" -Command "soketi start --config=soketi.json"

# Wait for Soketi to start
Write-Host "Waiting for Soketi to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# Check if Soketi is running
if (Test-Port 6001) {
    Write-Host "✅ Soketi WebSocket server started on port 6001" -ForegroundColor Green
} else {
    Write-Host "❌ Failed to start Soketi WebSocket server" -ForegroundColor Red
    exit 1
}

# Start Laravel queue worker with Redis
Write-Host "Starting Laravel queue worker with Redis..." -ForegroundColor Yellow
Start-Service -Name "Laravel Queue Worker" -Command "php artisan queue:work --tries=3 --timeout=60 --connection=redis"

# Start Laravel development server (if not already running)
if (-not (Test-Port 8000)) {
    Write-Host "Starting Laravel development server..." -ForegroundColor Yellow
    Start-Service -Name "Laravel Development Server" -Command "php artisan serve --host=127.0.0.1 --port=8000"
} else {
    Write-Host "✅ Laravel server already running on port 8000" -ForegroundColor Green
}

# Display status
Write-Host ""
Write-Host "🎉 All services started successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Service Status:" -ForegroundColor Cyan
Write-Host "  • Redis Server: 127.0.0.1:6379" -ForegroundColor White
Write-Host "  • Soketi WebSocket Server: http://127.0.0.1:6001" -ForegroundColor White
Write-Host "  • Laravel Application: http://127.0.0.1:8000" -ForegroundColor White
Write-Host "  • WebSocket Test Page: http://127.0.0.1:8000/websocket-test" -ForegroundColor White
Write-Host ""
Write-Host "🔧 Management Commands:" -ForegroundColor Cyan
Write-Host "  • Monitor Redis: redis-cli monitor" -ForegroundColor White
Write-Host "  • View queue status: php artisan queue:monitor" -ForegroundColor White
Write-Host "  • Check failed jobs: php artisan queue:failed" -ForegroundColor White
Write-Host "  • Monitor WebSocket: soketi status" -ForegroundColor White
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
            $soketiRunning = Test-Port 6001
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

        # Stop services
        Get-Process | Where-Object {$_.ProcessName -eq "php" -and $_.CommandLine -like "*artisan*"} | Stop-Process -Force
        Get-Process | Where-Object {$_.ProcessName -eq "node" -and $_.CommandLine -like "*soketi*"} | Stop-Process -Force
        Get-Process | Where-Object {$_.ProcessName -eq "redis-server"} | Stop-Process -Force

        Write-Host "✅ All services stopped" -ForegroundColor Green
    }
}
