# Monitor Laravel Queue Status for SCADA Data Processing
# This script provides real-time monitoring of queue status, job counts, and failed jobs

Write-Host "Laravel Queue Status Monitor for SCADA Data Processing" -ForegroundColor Green
Write-Host "=====================================================" -ForegroundColor Green

# Change to the project directory
Set-Location "D:\dashboard-aws"

# Check if Laravel is available
if (-not (Test-Path "artisan")) {
    Write-Host "Error: Laravel artisan file not found" -ForegroundColor Red
    Write-Host "Please run this script from the Laravel project root directory" -ForegroundColor Red
    exit 1
}

# Function to display queue statistics
function Show-QueueStats {
    Write-Host "`n=== Queue Statistics ===" -ForegroundColor Cyan

    try {
        # Get queue statistics using Laravel artisan
        $stats = & php artisan queue:monitor 2>&1

        if ($LASTEXITCODE -eq 0) {
            Write-Host $stats -ForegroundColor White
        } else {
            Write-Host "Queue monitoring not available. Checking basic status..." -ForegroundColor Yellow

            # Check if queue workers are running
            $workers = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.ProcessName -eq "php" }

            if ($workers) {
                Write-Host "PHP processes running: $($workers.Count)" -ForegroundColor Green
                foreach ($worker in $workers) {
                    Write-Host "  PID: $($worker.Id), CPU: $([math]::Round($worker.CPU, 2))s" -ForegroundColor White
                }
            } else {
                Write-Host "No PHP processes found running" -ForegroundColor Red
            }
        }
    } catch {
        Write-Host "Error checking queue statistics: $_" -ForegroundColor Red
    }
}

# Function to check failed jobs
function Show-FailedJobs {
    Write-Host "`n=== Failed Jobs ===" -ForegroundColor Red

    try {
        $failedJobs = & php artisan queue:failed 2>&1

        if ($LASTEXITCODE -eq 0) {
            if ($failedJobs -match "No failed jobs") {
                Write-Host "No failed jobs found" -ForegroundColor Green
            } else {
                Write-Host $failedJobs -ForegroundColor Red
            }
        } else {
            Write-Host "Failed jobs command not available" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "Error checking failed jobs: $_" -ForegroundColor Red
    }
}

# Function to check database queue table
function Show-DatabaseQueueStatus {
    Write-Host "`n=== Database Queue Status ===" -ForegroundColor Cyan

    try {
        # Check if jobs table exists and has data
        $jobCount = & php artisan tinker --execute="echo 'Jobs in queue: ' . DB::table('jobs')->count(); echo 'Failed jobs: ' . DB::table('failed_jobs')->count();" 2>&1

        if ($LASTEXITCODE -eq 0) {
            Write-Host $jobCount -ForegroundColor White
        } else {
            Write-Host "Database queue status not available" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "Error checking database queue status: $_" -ForegroundColor Red
    }
}

# Function to show system resources
function Show-SystemResources {
    Write-Host "`n=== System Resources ===" -ForegroundColor Cyan

    try {
        $cpu = Get-Counter '\Processor(_Total)\% Processor Time' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
        $memory = Get-Counter '\Memory\Available MBytes' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
        $disk = Get-WmiObject -Class Win32_LogicalDisk -Filter "DeviceID='C:'" | Select-Object @{Name="FreeGB";Expression={[math]::Round($_.FreeSpace/1GB,2)}}, @{Name="TotalGB";Expression={[math]::Round($_.Size/1GB,2)}}

        Write-Host "CPU Usage: $([math]::Round($cpu, 1))%" -ForegroundColor White
        Write-Host "Available Memory: $([math]::Round($memory, 0)) MB" -ForegroundColor White
        Write-Host "C: Drive - Free: $($disk.FreeGB) GB / Total: $($disk.TotalGB) GB" -ForegroundColor White
    } catch {
        Write-Host "Error checking system resources: $_" -ForegroundColor Red
    }
}

# Main monitoring loop
Write-Host "Starting queue monitoring... Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

try {
    while ($true) {
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        Write-Host "`n[$timestamp] === Queue Status Update ===" -ForegroundColor Magenta

        Show-QueueStats
        Show-FailedJobs
        Show-DatabaseQueueStatus
        Show-SystemResources

        Write-Host "`nNext update in 30 seconds..." -ForegroundColor Gray
        Start-Sleep -Seconds 30

        # Clear screen for better readability
        Clear-Host
        Write-Host "Laravel Queue Status Monitor for SCADA Data Processing" -ForegroundColor Green
        Write-Host "=====================================================" -ForegroundColor Green
    }
} catch {
    Write-Host "`nMonitoring stopped: $_" -ForegroundColor Red
}

Write-Host "`nQueue monitoring completed." -ForegroundColor Green
