# WebSocket Services Startup Script
# Script ini digunakan untuk menjalankan semua service yang diperlukan untuk WebSocket

param(
    [switch]$Background,
    [switch]$Verbose,
    [string]$Environment = "local"
)

Write-Host "=== WebSocket Services Startup Script ===" -ForegroundColor Green
Write-Host "Environment: $Environment" -ForegroundColor Yellow
Write-Host ""

# Function untuk menampilkan status service
function Show-ServiceStatus {
    param($ServiceName, $Status, $Details = "")

    $color = if ($Status -eq "Running") { "Green" } else { "Red" }
    Write-Host "[$Status] $ServiceName" -ForegroundColor $color
    if ($Details) {
        Write-Host "  $Details" -ForegroundColor Gray
    }
}

# Function untuk menjalankan command
function Invoke-CommandWithLog {
    param($Command, $Description, $Background = $false)

    Write-Host "Starting: $Description..." -ForegroundColor Cyan

    if ($Background) {
        Start-Process -FilePath "powershell" -ArgumentList "-Command", $Command -WindowStyle Minimized
        Start-Sleep -Seconds 2
        Write-Host "  Started in background" -ForegroundColor Green
    } else {
        Invoke-Expression $Command
    }
}

# Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "Error: Laravel artisan file not found. Please run this script from the project root." -ForegroundColor Red
    exit 1
}

# Check Laravel installation
Write-Host "Checking Laravel installation..." -ForegroundColor Cyan
if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
    Write-Host "Error: PHP not found in PATH" -ForegroundColor Red
    exit 1
}

# Check Composer dependencies
Write-Host "Checking Composer dependencies..." -ForegroundColor Cyan
if (-not (Test-Path "vendor")) {
    Write-Host "Installing Composer dependencies..." -ForegroundColor Yellow
    composer install
}

# Check if .env exists
if (-not (Test-Path ".env")) {
    Write-Host "Warning: .env file not found. Creating from .env.example..." -ForegroundColor Yellow
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "Created .env file from .env.example" -ForegroundColor Green
    } else {
        Write-Host "Error: .env.example not found. Please create .env file manually." -ForegroundColor Red
        exit 1
    }
}

# Generate application key if not set
Write-Host "Checking application key..." -ForegroundColor Cyan
$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch "APP_KEY=base64:") {
    Write-Host "Generating application key..." -ForegroundColor Yellow
    php artisan key:generate
}

# Check broadcasting configuration
Write-Host "Checking broadcasting configuration..." -ForegroundColor Cyan
$broadcastDriver = (Get-Content ".env" | Where-Object { $_ -match "BROADCAST_DRIVER" }) -replace "BROADCAST_DRIVER=", ""
if (-not $broadcastDriver) {
    Write-Host "Setting BROADCAST_DRIVER=pusher..." -ForegroundColor Yellow
    Add-Content ".env" "`nBROADCAST_DRIVER=pusher"
}

# Check Pusher configuration
$pusherAppId = (Get-Content ".env" | Where-Object { $_ -match "PUSHER_APP_ID" }) -replace "PUSHER_APP_ID=", ""
if (-not $pusherAppId) {
    Write-Host "Warning: PUSHER_APP_ID not set. Using default values..." -ForegroundColor Yellow
    Add-Content ".env" @"
`n# Pusher Configuration
PUSHER_APP_ID=12345
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
"@
}

Write-Host ""

# Start services based on environment
switch ($Environment.ToLower()) {
    "local" {
        Write-Host "Starting Local Environment Services..." -ForegroundColor Green

        # 1. Start Laravel WebSocket server
        Show-ServiceStatus "Laravel WebSocket Server" "Starting" "Port 6001"
        if ($Background) {
            Invoke-CommandWithLog "cd '$PWD'; php artisan websockets:serve" "Laravel WebSocket Server" $true
        } else {
            Start-Process -FilePath "powershell" -ArgumentList "-Command", "cd '$PWD'; php artisan websockets:serve" -WindowStyle Normal
        }

        Start-Sleep -Seconds 3

        # 2. Start Queue Worker
        Show-ServiceStatus "Queue Worker" "Starting" "Processing background jobs"
        if ($Background) {
            Invoke-CommandWithLog "cd '$PWD'; php artisan queue:work --sleep=3 --tries=3" "Queue Worker" $true
        } else {
            Start-Process -FilePath "powershell" -ArgumentList "-Command", "cd '$PWD'; php artisan queue:work --sleep=3 --tries=3" -WindowStyle Normal
        }

        Start-Sleep -Seconds 3

        # 3. Start Laravel Application
        Show-ServiceStatus "Laravel Application" "Starting" "Port 8000"
        if ($Background) {
            Invoke-CommandWithLog "cd '$PWD'; php artisan serve --host=0.0.0.0 --port=8000" "Laravel Application" $true
        } else {
            Start-Process -FilePath "powershell" -ArgumentList "-Command", "cd '$PWD'; php artisan serve --host=0.0.0.0 --port=8000" -WindowStyle Normal
        }

        Start-Sleep -Seconds 3

        # 4. Start Nginx (if exists)
        if (Test-Path "nginx/start-nginx.bat") {
            Show-ServiceStatus "Nginx" "Starting" "Web server"
            if ($Background) {
                Start-Process -FilePath "nginx/start-nginx.bat" -WindowStyle Minimized
            } else {
                Start-Process -FilePath "nginx/start-nginx.bat" -WindowStyle Normal
            }
        }

        # 5. Start PHP-FPM (if exists)
        if (Test-Path "php-fpm.ini") {
            Show-ServiceStatus "PHP-FPM" "Starting" "FastCGI Process Manager"
            Write-Host "  Note: PHP-FPM configuration found. Start manually if needed." -ForegroundColor Gray
        }
    }

    "production" {
        Write-Host "Starting Production Environment Services..." -ForegroundColor Green

        # Production services
        Show-ServiceStatus "Queue Worker" "Starting" "Production queue processing"
        Invoke-CommandWithLog "cd '$PWD'; php artisan queue:work --sleep=3 --tries=3 --max-time=3600" "Queue Worker" $true

        Show-ServiceStatus "WebSocket Server" "Starting" "Production WebSocket server"
        Invoke-CommandWithLog "cd '$PWD'; php artisan websockets:serve --host=0.0.0.0 --port=6001" "WebSocket Server" $true

        Write-Host "Production services started in background" -ForegroundColor Green
        Write-Host "Use 'pm2 start' for process management in production" -ForegroundColor Yellow
    }

    "testing" {
        Write-Host "Starting Testing Environment Services..." -ForegroundColor Green

        # Run tests
        Show-ServiceStatus "WebSocket Tests" "Running" "Testing implementation"
        php scripts/test_websocket_implementation.php

        Show-ServiceStatus "PHPUnit Tests" "Running" "Unit and feature tests"
        php artisan test

        Write-Host "Testing completed" -ForegroundColor Green
    }

    default {
        Write-Host "Unknown environment: $Environment" -ForegroundColor Red
        Write-Host "Available environments: local, production, testing" -ForegroundColor Yellow
        exit 1
    }
}

Write-Host ""

# Show service status
Write-Host "=== Service Status ===" -ForegroundColor Green

# Check Laravel WebSocket server
try {
    $wsProcess = Get-Process | Where-Object { $_.ProcessName -eq "php" -and $_.CommandLine -like "*websockets:serve*" }
    if ($wsProcess) {
        Show-ServiceStatus "Laravel WebSocket Server" "Running" "PID: $($wsProcess.Id)"
    } else {
        Show-ServiceStatus "Laravel WebSocket Server" "Not Running"
    }
} catch {
    Show-ServiceStatus "Laravel WebSocket Server" "Status Unknown"
}

# Check Queue Worker
try {
    $queueProcess = Get-Process | Where-Object { $_.ProcessName -eq "php" -and $_.CommandLine -like "*queue:work*" }
    if ($queueProcess) {
        Show-ServiceStatus "Queue Worker" "Running" "PID: $($queueProcess.Id)"
    } else {
        Show-ServiceStatus "Queue Worker" "Not Running"
    }
} catch {
    Show-ServiceStatus "Queue Worker" "Status Unknown"
}

# Check Laravel Application
try {
    $laravelProcess = Get-Process | Where-Object { $_.ProcessName -eq "php" -and $_.CommandLine -like "*artisan serve*" }
    if ($laravelProcess) {
        Show-ServiceStatus "Laravel Application" "Running" "PID: $($laravelProcess.Id)"
    } else {
        Show-ServiceStatus "Laravel Application" "Not Running"
    }
} catch {
    Show-ServiceStatus "Laravel Application" "Status Unknown"
}

# Check Nginx
try {
    $nginxProcess = Get-Process | Where-Object { $_.ProcessName -eq "nginx" }
    if ($nginxProcess) {
        Show-ServiceStatus "Nginx" "Running" "PID: $($nginxProcess.Id)"
    } else {
        Show-ServiceStatus "Nginx" "Not Running"
    }
} catch {
    Show-ServiceStatus "Nginx" "Status Unknown"
}

Write-Host ""

# Show URLs
Write-Host "=== Access URLs ===" -ForegroundColor Green
Write-Host "Laravel Application: http://localhost:8000" -ForegroundColor Cyan
Write-Host "WebSocket Test Page: http://localhost:8000/websocket-test" -ForegroundColor Cyan
Write-Host "WebSocket Server: ws://localhost:6001" -ForegroundColor Cyan
Write-Host "API Endpoint: http://localhost:8000/api/receiver" -ForegroundColor Cyan

if (Test-Path "nginx/start-nginx.bat") {
    Write-Host "Nginx: http://localhost (if configured)" -ForegroundColor Cyan
}

Write-Host ""

# Show monitoring commands
Write-Host "=== Monitoring Commands ===" -ForegroundColor Green
Write-Host "Check WebSocket connections: php artisan websockets:serve --debug" -ForegroundColor Gray
Write-Host "Monitor queue: php artisan queue:monitor" -ForegroundColor Gray
Write-Host "Check queue size: php artisan queue:size" -ForegroundColor Gray
Write-Host "View logs: tail -f storage/logs/laravel.log" -ForegroundColor Gray
Write-Host "Test WebSocket: php scripts/test_websocket_implementation.php" -ForegroundColor Gray

Write-Host ""

# Show next steps
Write-Host "=== Next Steps ===" -ForegroundColor Green
Write-Host "1. Open WebSocket test page in browser" -ForegroundColor Yellow
Write-Host "2. Send test data via API endpoint" -ForegroundColor Yellow
Write-Host "3. Monitor real-time updates" -ForegroundColor Yellow
Write-Host "4. Check browser console for WebSocket status" -ForegroundColor Yellow

Write-Host ""
Write-Host "=== WebSocket Services Started Successfully ===" -ForegroundColor Green

if ($Background) {
    Write-Host "All services are running in background." -ForegroundColor Yellow
    Write-Host "Use Task Manager or 'Get-Process | Where-Object { \$_.ProcessName -eq 'php' }' to monitor." -ForegroundColor Gray
}
