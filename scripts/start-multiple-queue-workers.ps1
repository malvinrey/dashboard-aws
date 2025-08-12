# Start Multiple Laravel Queue Workers for SCADA Data Processing
# This script starts multiple queue workers to handle high load and improve performance

Write-Host "Starting Multiple Laravel Queue Workers for SCADA Data Processing..." -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Green

# Change to the project directory
Set-Location "D:\dashboard-aws"

# Check if PHP is available
try {
    $phpVersion = & php -v 2>&1 | Select-String "PHP" | Select-Object -First 1
    Write-Host "PHP Version: $phpVersion" -ForegroundColor Cyan
} catch {
    Write-Host "Error: PHP not found in PATH" -ForegroundColor Red
    Write-Host "Please ensure PHP is installed and added to PATH" -ForegroundColor Red
    exit 1
}

# Check if Laravel is available
if (-not (Test-Path "artisan")) {
    Write-Host "Error: Laravel artisan file not found" -ForegroundColor Red
    Write-Host "Please run this script from the Laravel project root directory" -ForegroundColor Red
    exit 1
}

# Configuration
$workerCount = 3  # Number of workers to start
$scadaQueue = "scada-processing"
$largeDatasetQueue = "scada-large-datasets"

Write-Host "Starting $workerCount queue workers..." -ForegroundColor Yellow
Write-Host "Queues: $scadaQueue, $largeDatasetQueue" -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop all workers" -ForegroundColor Yellow
Write-Host ""

# Start multiple workers
$jobs = @()

for ($i = 1; $i -le $workerCount; $i++) {
    Write-Host "Starting Worker $i..." -ForegroundColor Green

    $job = Start-Job -ScriptBlock {
        param($projectPath, $workerId, $scadaQueue, $largeDatasetQueue)

        Set-Location $projectPath

        # Start queue worker with specific configuration
        & php artisan queue:work --queue=$scadaQueue,$largeDatasetQueue --tries=3 --timeout=1800 --verbose --name="SCADA-Worker-$workerId"
    } -ArgumentList (Get-Location), $i, $scadaQueue, $largeDatasetQueue

    $jobs += $job

    Write-Host "Worker $i started with Job ID: $($job.Id)" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "All $workerCount workers started successfully!" -ForegroundColor Green
Write-Host "Workers are running in the background" -ForegroundColor Yellow
Write-Host ""
Write-Host "To monitor workers:" -ForegroundColor Cyan
Write-Host "  Get-Job                    # List all background jobs" -ForegroundColor White
Write-Host "  Receive-Job -Id <JobId>   # See output from specific worker" -ForegroundColor White
Write-Host "  Stop-Job -Id <JobId>      # Stop specific worker" -ForegroundColor White
Write-Host "  Stop-Job -State Running   # Stop all running workers" -ForegroundColor White
Write-Host ""

# Wait for user input to stop workers
Write-Host "Press Enter to stop all workers..." -ForegroundColor Red
Read-Host

# Stop all workers
Write-Host "Stopping all workers..." -ForegroundColor Yellow
Stop-Job -State Running
Remove-Job -State Completed

Write-Host "All workers stopped successfully!" -ForegroundColor Green
