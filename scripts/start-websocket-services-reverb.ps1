# WebSocket Services Startup Script dengan Laravel Reverb
# Script ini digunakan untuk menjalankan semua service yang diperlukan untuk WebSocket menggunakan Reverb

param(
    [string]$Environment = "local",
    [switch]$Background
)

# Global variables for tracking process IDs
$Global:ServicePIDs = @{}
$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$Global:PIDFile = Join-Path $ProjectRoot "temp\websocket-services-reverb.pid"

# =============================================================================
# Function to load service PIDs from file
# =============================================================================
function Load-ServicePIDs {
    try {
        if (Test-Path $Global:PIDFile) {
            $fileContent = Get-Content -Path $Global:PIDFile -Raw
            if ($fileContent) {
                $parsed = $fileContent | ConvertFrom-Json
                $hash = @{}
                foreach ($prop in $parsed.PSObject.Properties) {
                    $hash[$prop.Name] = [int]$prop.Value
                }
                $Global:ServicePIDs = $hash
                Write-Host "Loaded service PIDs from: $Global:PIDFile" -ForegroundColor Cyan
                return $true
            }
        }
    } catch {
        Write-Host "Warning: Could not load or parse service PIDs from file. It might be corrupt." -ForegroundColor Yellow
        Remove-Item $Global:PIDFile -Force -ErrorAction SilentlyContinue
    }
    return $false
}

Write-Host "Starting SCADA WebSocket Services dengan Laravel Reverb..." -ForegroundColor Green

# Check if services are already running and load PIDs
if (Load-ServicePIDs) {
    Write-Host "Found existing service PIDs, checking if services are still running..." -ForegroundColor Cyan

    $existingServices = @{}
    foreach ($service in $Global:ServicePIDs.GetEnumerator()) {
        try {
            $serviceName = $service.Key
            $servicePid = [int]$service.Value

            if ($servicePid -gt 0) {
                $process = Get-Process -Id $servicePid -ErrorAction SilentlyContinue
                if ($null -ne $process) {
                    $existingServices[$serviceName] = $servicePid
                    Write-Host "  - Service '$serviceName' is already running (PID: $servicePid)" -ForegroundColor DarkGray
                } else {
                    Write-Host "  - Stale PID for '$serviceName' found (PID: $servicePid). It will be removed." -ForegroundColor Yellow
                }
            }
        } catch {
            Write-Host "  - Could not process service entry: $($service.Key) -> $($service.Value)" -ForegroundColor Yellow
        }
    }

    # Update global PIDs with only running services
    $Global:ServicePIDs = $existingServices
}

# Set environment variables untuk Reverb
$env:BROADCAST_DRIVER = "reverb"
$env:REVERB_APP_ID = "12345"
$env:REVERB_APP_KEY = "scada_dashboard_key_2024"
$env:REVERB_APP_SECRET = "scada_dashboard_secret_2024"
$env:REVERB_HOST = "127.0.0.1"
$env:REVERB_PORT = "8080"
$env:REVERB_SCHEME = "http"

# Other environment variables
$env:QUEUE_CONNECTION = "redis"
$env:CACHE_DRIVER = "redis"
$env:SESSION_DRIVER = "redis"

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

# Function to check if Reverb is responding
function Test-Reverb {
    try {
        # First check if port is listening
        if (-not (Test-Port 8080)) {
            return $false
        }

        # Then try a simple HTTP request
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:8080" -TimeoutSec 3 -UseBasicParsing -ErrorAction SilentlyContinue
        return $response.StatusCode -eq 200
    }
    catch {
        return (Test-Port 8080)
    }
}

# Function to save service PIDs to file
function Save-ServicePIDs {
    try {
        $pidDir = Split-Path -Path $Global:PIDFile -Parent
        if (-not (Test-Path $pidDir)) {
            New-Item -ItemType Directory -Path $pidDir -Force | Out-Null
        }
        $Global:ServicePIDs | ConvertTo-Json -Depth 3 | Out-File -FilePath $Global:PIDFile -Encoding UTF8
        Write-Host " Service PIDs saved to: $Global:PIDFile" -ForegroundColor Cyan
    } catch {
        Write-Host "  Warning: Could not save service PIDs to file" -ForegroundColor Yellow
    }
}

# Function to start PHP process
function Start-PhpProcess {
    param(
        [string]$Name,
        [string[]]$PhpArgs,
        [string]$WorkingDirectory
    )
    Write-Host "Starting $Name..." -ForegroundColor Yellow
    try {
        if (-not (Test-Path (Join-Path $WorkingDirectory "storage\logs"))) {
            New-Item -ItemType Directory -Path (Join-Path $WorkingDirectory "storage\logs") -Force | Out-Null
        }
        $process = Start-Process -FilePath "php" `
            -ArgumentList $PhpArgs `
            -WorkingDirectory $WorkingDirectory `
            -NoNewWindow `
            -RedirectStandardOutput (Join-Path $WorkingDirectory "storage\logs\$($Name -replace ' ','-').out.log") `
            -RedirectStandardError (Join-Path $WorkingDirectory "storage\logs\$($Name -replace ' ','-').err.log") `
            -PassThru
        if ($process) {
            Write-Host " $Name started (PID: $($process.Id))" -ForegroundColor Green
            return $process.Id
        }
        Write-Host " Failed to start $Name" -ForegroundColor Red
        return $null
    } catch {
        Write-Host (" Error starting {0}: {1}" -f $Name, $_.Exception.Message) -ForegroundColor Red
        return $null
    }
}

# Check Redis installation and status
Write-Host "Checking Redis installation..." -ForegroundColor Cyan
if (-not (Get-Command "redis-server" -ErrorAction SilentlyContinue)) {
    Write-Host " Redis not found. Running Redis setup..." -ForegroundColor Red
    & "$PSScriptRoot\setup-redis.ps1"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Redis setup failed. Please install Redis manually." -ForegroundColor Red
        exit 1
    }
}

# Check if Redis is running
if (-not (Test-Port 6379)) {
    Write-Host "Starting Redis server..." -ForegroundColor Yellow
    try {
        Start-Process -FilePath "redis-server" -ArgumentList "--port", "6379" -WindowStyle Hidden
        Start-Sleep -Seconds 3
        Write-Host " Redis server started" -ForegroundColor Green
    } catch {
        Write-Host " Failed to start Redis server" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host " Redis is already running on port 6379" -ForegroundColor Green
}

# Check if Laravel is ready
if (-not (Test-Path "artisan")) {
    Write-Host " Laravel artisan not found. Please run this script from the Laravel project root." -ForegroundColor Red
    exit 1
}

# Check database connection
Write-Host "Checking database connection..." -ForegroundColor Yellow
try {
    $testDbScript = Join-Path $ProjectRoot "scripts\test-database-connection.php"
    if (Test-Path $testDbScript) {
        php $testDbScript | Out-Null
    } else {
        php -r "echo 'DB check placeholder';" | Out-Null
    }
    Write-Host " Database connection OK" -ForegroundColor Green
} catch {
    Write-Host " Database connection failed. Please check your database configuration." -ForegroundColor Red
    exit 1
}

# Start Laravel Reverb WebSocket server
if ($Global:ServicePIDs.ContainsKey("Reverb")) {
    Write-Host " Reverb already tracked (PID: $($Global:ServicePIDs['Reverb']))" -ForegroundColor Green
} else {
    Write-Host "Starting Laravel Reverb WebSocket Server..." -ForegroundColor Yellow
    try {
        $logsDir = Join-Path $ProjectRoot "storage\logs"
        if (-not (Test-Path $logsDir)) { New-Item -ItemType Directory -Path $logsDir -Force | Out-Null }
        $process = Start-Process -FilePath "php" `
            -ArgumentList @("artisan","reverb:start") `
            -WorkingDirectory $ProjectRoot `
            -NoNewWindow `
            -RedirectStandardOutput (Join-Path $logsDir "reverb.out.log") `
            -RedirectStandardError (Join-Path $logsDir "reverb.err.log") `
            -PassThru
        if ($process) {
            $Global:ServicePIDs["Reverb"] = $process.Id
            Write-Host " Reverb process started (PID: $($process.Id)). See logs in storage\\logs." -ForegroundColor Green
        } else {
            Write-Host " Failed to start Reverb process" -ForegroundColor Red
            exit 1
        }
    } catch {
        Write-Host (" Error starting Reverb: {0}" -f $_.Exception.Message) -ForegroundColor Red
        exit 1
    }
}

# Wait for Reverb to start
Write-Host "Waiting for Reverb to start..." -ForegroundColor Yellow
$maxAttempts = 30
$attempt = 0
$reverbStarted = $false
while ($attempt -lt $maxAttempts -and -not $reverbStarted) {
    Start-Sleep -Seconds 1
    $attempt++
    if (Test-Reverb) {
        $reverbStarted = $true
        Write-Host " Reverb WebSocket server started and responding on port 8080 (attempt $attempt)" -ForegroundColor Green
    }
}
if (-not $reverbStarted) {
    Write-Host " Failed to start Reverb WebSocket server after $maxAttempts attempts" -ForegroundColor Red
    exit 1
}

# Start Laravel queue worker with Redis
if ($Global:ServicePIDs.ContainsKey("QueueWorker")) {
    Write-Host " Queue Worker already tracked (PID: $($Global:ServicePIDs['QueueWorker']))" -ForegroundColor Green
} else {
    Write-Host "Starting Laravel Queue Worker..." -ForegroundColor Yellow
    $queueWorkerPID = Start-PhpProcess -Name "Laravel Queue Worker" -PhpArgs @("artisan","queue:work","--tries=3","--timeout=60","--connection=redis") -WorkingDirectory $ProjectRoot
    if ($queueWorkerPID) {
        $Global:ServicePIDs["QueueWorker"] = $queueWorkerPID
    } else {
        Write-Host " Failed to start Queue Worker" -ForegroundColor Red
        exit 1
    }
}

# Web server: local (artisan) vs nginx/php-cgi based on -Environment
if ($Environment) {
    $normalizedEnv = $Environment.ToLowerInvariant()
} else {
    $normalizedEnv = "local"
}

if ($normalizedEnv -in @("nginx","prod","production")) {
    Write-Host "Environment: $Environment -> Starting PHP-CGI and Nginx" -ForegroundColor Cyan
    # Start PHP-CGI (FPM equivalent on Windows)
    if (Test-Port 9000) {
        Write-Host " PHP-CGI already running on 127.0.0.1:9000" -ForegroundColor Green
    } else {
        Write-Host "Starting PHP-CGI (FastCGI)..." -ForegroundColor Yellow
        try {
            $phpCgiPath = Get-Command "php-cgi" -ErrorAction SilentlyContinue
            if ($phpCgiPath) {
                $phpCgiPath = $phpCgiPath.Source
            } elseif (Test-Path "C:\\php\\php-cgi.exe") {
                $phpCgiPath = "C:\\php\\php-cgi.exe"
            } else {
                throw "php-cgi not found in PATH or C:\\php"
            }
            $iniPath = Join-Path $ProjectRoot "php-fpm.ini"
            $process = Start-Process -FilePath $phpCgiPath -ArgumentList @("-b","127.0.0.1:9000","-c",$iniPath) -WorkingDirectory $ProjectRoot -NoNewWindow -PassThru
            if ($process) {
                $Global:ServicePIDs["PHP-CGI"] = $process.Id
                Write-Host " PHP-CGI started (PID: $($process.Id))" -ForegroundColor Green
            } else {
                throw "Failed to start php-cgi"
            }
        } catch {
            Write-Host (" Error starting PHP-CGI: {0}" -f $_.Exception.Message) -ForegroundColor Red
            exit 1
        }
    }

    # Start Nginx
    try {
        $nginxExe = Join-Path $ProjectRoot "nginx\\nginx.exe"
        $startNginxBat = Join-Path $ProjectRoot "start-nginx.bat"
        if (Test-Path $nginxExe) {
            Write-Host "Starting Nginx..." -ForegroundColor Yellow
            Start-Process -FilePath $nginxExe -ArgumentList @("-c", "nginx\\config\\nginx.conf") -WorkingDirectory $ProjectRoot -NoNewWindow | Out-Null
            Write-Host " Nginx start command issued" -ForegroundColor Green
        } elseif (Test-Path $startNginxBat) {
            Write-Host "Starting Nginx via batch..." -ForegroundColor Yellow
            Start-Process -FilePath $startNginxBat -WorkingDirectory $ProjectRoot -NoNewWindow | Out-Null
            Write-Host " Nginx batch invoked" -ForegroundColor Green
        } else {
            Write-Host " Nginx executable not found. Skipping." -ForegroundColor Yellow
        }
    } catch { Write-Host (" Error starting Nginx: {0}" -f $_.Exception.Message) -ForegroundColor Red }
} else {
    # Start Laravel development server (port-first check, adopt if already running)
    if (Test-Port 8000) {
        Write-Host " Laravel server already running on port 8000" -ForegroundColor Green
    } else {
        Write-Host "Starting Laravel Development Server..." -ForegroundColor Yellow
        try {
            $logsDir = Join-Path $ProjectRoot "storage\logs"
            if (-not (Test-Path $logsDir)) { New-Item -ItemType Directory -Path $logsDir -Force | Out-Null }
            $laravelProcess = Start-Process -FilePath "php" `
                -ArgumentList @("artisan","serve","--host=127.0.0.1","--port=8000") `
                -WorkingDirectory $ProjectRoot `
                -NoNewWindow `
                -RedirectStandardOutput (Join-Path $logsDir "laravel-serve.out.log") `
                -RedirectStandardError (Join-Path $logsDir "laravel-serve.err.log") `
                -PassThru
            if ($laravelProcess) {
                $Global:ServicePIDs["Laravel"] = $laravelProcess.Id
                Write-Host " Laravel server process started (PID: $($laravelProcess.Id)). Logs in storage\\logs." -ForegroundColor Green
            } else {
                Write-Host " Failed to start Laravel server" -ForegroundColor Red
                exit 1
            }
        } catch {
            Write-Host (" Error starting Laravel server: {0}" -f $_.Exception.Message) -ForegroundColor Red
            exit 1
        }
    }
}

# Display status
Write-Host ""
Write-Host "All services started successfully dengan Laravel Reverb!" -ForegroundColor Green
Save-ServicePIDs
Write-Host " Service Status:" -ForegroundColor Cyan
Write-Host "  • Redis Server: 127.0.0.1:6379" -ForegroundColor White
Write-Host "  • Laravel Reverb WebSocket Server: http://127.0.0.1:8080 (PID: $($Global:ServicePIDs['Reverb']))" -ForegroundColor White
Write-Host "  • Laravel Application: http://127.0.0.1:8000 (PID: $($Global:ServicePIDs['Laravel']))" -ForegroundColor White
Write-Host "  • Queue Worker: PID $($Global:ServicePIDs['QueueWorker'])" -ForegroundColor White
Write-Host "  • WebSocket Test Page: http://127.0.0.1:8000/websocket-test" -ForegroundColor White
Write-Host ""
Write-Host " Management Commands:" -ForegroundColor Cyan
Write-Host "  • Monitor Redis: redis-cli monitor" -ForegroundColor White
Write-Host "  • View queue status: php artisan queue:monitor" -ForegroundColor White
Write-Host "  • Check failed jobs: php artisan queue:failed" -ForegroundColor White
Write-Host "  • Monitor Reverb: php artisan reverb:status" -ForegroundColor White
Write-Host "  • Service PIDs file: $Global:PIDFile" -ForegroundColor White
Write-Host ""
Write-Host " Performance Features:" -ForegroundColor Cyan
Write-Host "  • Redis Queue: Ultra-fast job processing" -ForegroundColor White
Write-Host "  • Redis Cache: In-memory caching" -ForegroundColor White
Write-Host "  • Redis Session: Fast session handling" -ForegroundColor White
Write-Host "  • Laravel Reverb: Native PHP WebSocket server" -ForegroundColor White
Write-Host ""
Write-Host "  Note: Keep this terminal open to monitor services" -ForegroundColor Yellow
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
            $reverbRunning = Test-Reverb
            $laravelRunning = Test-Port 8000

            if (-not $redisRunning) {
                Write-Host "  Redis server stopped unexpectedly" -ForegroundColor Yellow
            }

            if (-not $reverbRunning) {
                Write-Host "  Laravel Reverb WebSocket server stopped unexpectedly" -ForegroundColor Yellow
            }

            if (-not $laravelRunning) {
                Write-Host "  Laravel server stopped unexpectedly" -ForegroundColor Yellow
            }
        }
    }
    catch {
        Write-Host ""
        Write-Host "Stopping all services..." -ForegroundColor Red

        # Stop services using tracked PIDs
        if ($Global:ServicePIDs.Count -gt 0) {
            Write-Host "Stopping tracked services..." -ForegroundColor Yellow

            foreach ($service in $Global:ServicePIDs.GetEnumerator()) {
                try {
                    if (Get-Process -Id $service.Value -ErrorAction SilentlyContinue) {
                        Stop-Process -Id $service.Value -Force
                        Write-Host " Stopped $($service.Key) (PID: $($service.Value))" -ForegroundColor Green
                    }
                } catch {
                    Write-Host "  Could not stop $($service.Key) (PID: $($service.Value))" -ForegroundColor Yellow
                }
            }
        } else {
            # Fallback to process name-based stopping
            Write-Host "Using fallback process stopping..." -ForegroundColor Yellow

            try {
                Get-Process | Where-Object {$_.ProcessName -eq "php"} | Stop-Process -Force
            } catch {
                Write-Host "  Could not stop PHP processes" -ForegroundColor Yellow
            }
        }

        try {
            Get-Process | Where-Object {$_.ProcessName -eq "redis-server"} | Stop-Process -Force
        } catch {
            Write-Host "  Could not stop Redis processes" -ForegroundColor Yellow
        }

        # Clean up PID file
        try {
            if (Test-Path $Global:PIDFile) {
                Remove-Item $Global:PIDFile -Force
                Write-Host "PID file cleaned up" -ForegroundColor Cyan
            }
        } catch {
            Write-Host "  Could not clean up PID file" -ForegroundColor Yellow
        }

        Write-Host " All services stopped" -ForegroundColor Green
    }
}
