# Start Laravel Queue Worker for SCADA Data Processing
# This script starts the queue worker to process SCADA data in the background

Write-Host "Starting Laravel Queue Worker for SCADA Data Processing..." -ForegroundColor Green
Write-Host "=======================================================" -ForegroundColor Green

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

# Start queue worker for SCADA processing
Write-Host "Starting queue worker for SCADA data processing..." -ForegroundColor Yellow
Write-Host "Queue: scada-processing, scada-large-datasets" -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop the worker" -ForegroundColor Yellow
Write-Host ""

try {
    # Start the queue worker with specific queues
    & php artisan queue:work --queue=scada-processing,scada-large-datasets --tries=3 --timeout=1800 --verbose
} catch {
    Write-Host "Error starting queue worker: $_" -ForegroundColor Red
    exit 1
}
