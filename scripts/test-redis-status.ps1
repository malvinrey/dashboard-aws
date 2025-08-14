# Redis Status Check Script
Write-Host "=== Redis Status Check ===" -ForegroundColor Green

# Check if Redis service is running
Write-Host "`n1. Checking Redis Service Status:" -ForegroundColor Yellow
try {
    $redisService = Get-Service -Name "Redis" -ErrorAction SilentlyContinue
    if ($redisService) {
        Write-Host "Redis service found:" -ForegroundColor Green
        Write-Host "  Status: $($redisService.Status)" -ForegroundColor White
        Write-Host "  Start Type: $($redisService.StartType)" -ForegroundColor White
    } else {
        Write-Host "Redis service not found" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking Redis service: $($_.Exception.Message)" -ForegroundColor Red
}

# Check Redis port
Write-Host "`n2. Checking Redis Port 6379:" -ForegroundColor Yellow
try {
    $portCheck = Test-NetConnection -ComputerName 127.0.0.1 -Port 6379 -InformationLevel Quiet
    if ($portCheck) {
        Write-Host "✅ Redis port 6379 is accessible" -ForegroundColor Green
    } else {
        Write-Host "❌ Redis port 6379 is NOT accessible" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking port: $($_.Exception.Message)" -ForegroundColor Red
}

# Check PHP Redis extension
Write-Host "`n3. Checking PHP Redis Extension:" -ForegroundColor Yellow
try {
    $phpOutput = & php -m 2>$null | Select-String "redis"
    if ($phpOutput) {
        Write-Host "✅ Redis extension is loaded in PHP" -ForegroundColor Green
        Write-Host "  Output: $phpOutput" -ForegroundColor White
    } else {
        Write-Host "❌ Redis extension is NOT loaded in PHP" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking PHP extensions: $($_.Exception.Message)" -ForegroundColor Red
}

# Check PHP version
Write-Host "`n4. PHP Version:" -ForegroundColor Yellow
try {
    $phpVersion = & php -v 2>$null | Select-Object -First 1
    Write-Host "PHP Version: $phpVersion" -ForegroundColor White
} catch {
    Write-Host "Error getting PHP version: $($_.Exception.Message)" -ForegroundColor Red
}

# Check PHP ini location
Write-Host "`n5. PHP Configuration:" -ForegroundColor Yellow
try {
    $phpIni = & php --ini 2>$null | Select-String "Loaded Configuration File"
    if ($phpIni) {
        Write-Host "PHP INI Location: $phpIni" -ForegroundColor White
    } else {
        Write-Host "Could not determine PHP INI location" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Error getting PHP INI location: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== End of Check ===" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Open http://127.0.0.1:8000/test-redis.php in your browser" -ForegroundColor White
Write-Host "2. Check the detailed error messages above" -ForegroundColor White
Write-Host "3. Install Redis extension if needed" -ForegroundColor White
