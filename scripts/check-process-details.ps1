# Check Process Details Script
Write-Host "=== Checking Process Details ===" -ForegroundColor Green

# Check specific process ID 45936
Write-Host "`n1. Process ID 45936 Details:" -ForegroundColor Yellow
try {
    $process = Get-Process -Id 45936 -ErrorAction SilentlyContinue
    if ($process) {
        Write-Host "✅ Process found:" -ForegroundColor Green
        Write-Host "  Name: $($process.ProcessName)" -ForegroundColor White
        Write-Host "  Path: $($process.Path)" -ForegroundColor White
        Write-Host "  Start Time: $($process.StartTime)" -ForegroundColor White
        Write-Host "  CPU Time: $($process.TotalProcessorTime)" -ForegroundColor White
        Write-Host "  Memory: $([math]::Round($process.WorkingSet64/1MB, 2)) MB" -ForegroundColor White
    } else {
        Write-Host "❌ Process ID 45936 not found" -ForegroundColor Red
    }
} catch {
    Write-Host "Error getting process details: $($_.Exception.Message)" -ForegroundColor Red
}

# Check all processes that might be Redis
Write-Host "`n2. All Processes with 'redis' in name:" -ForegroundColor Yellow
try {
    $redisProcesses = Get-Process | Where-Object {$_.ProcessName -like "*redis*"}
    if ($redisProcesses) {
        Write-Host "✅ Redis processes found:" -ForegroundColor Green
        foreach ($proc in $redisProcesses) {
            Write-Host "  $($proc.ProcessName) (PID: $($proc.Id))" -ForegroundColor White
        }
    } else {
        Write-Host "❌ No Redis processes found" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking Redis processes: $($_.Exception.Message)" -ForegroundColor Red
}

# Check WSL and Hyper-V processes
Write-Host "`n3. WSL and Hyper-V Processes:" -ForegroundColor Yellow
try {
    $wslProcesses = Get-Process | Where-Object {$_.ProcessName -like "*wsl*" -or $_.ProcessName -like "*vmmem*" -or $_.ProcessName -like "*hyper*"}
    if ($wslProcesses) {
        Write-Host "✅ WSL/Hyper-V processes found:" -ForegroundColor Green
        foreach ($proc in $wslProcesses) {
            Write-Host "  $($proc.ProcessName) (PID: $($proc.Id)) - Memory: $([math]::Round($proc.WorkingSet64/1MB, 2)) MB" -ForegroundColor White
        }
    } else {
        Write-Host "❌ No WSL/Hyper-V processes found" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking WSL processes: $($_.Exception.Message)" -ForegroundColor Red
}

# Check all processes using port 6379
Write-Host "`n4. All Processes Using Port 6379:" -ForegroundColor Yellow
try {
    $portProcesses = netstat -ano | findstr :6379 | ForEach-Object {
        $parts = $_ -split '\s+'
        $pid = $parts[-1]
        $localAddr = $parts[1]
        $state = $parts[3]

        try {
            $proc = Get-Process -Id $pid -ErrorAction SilentlyContinue
            if ($proc) {
                "  $localAddr -> $state -> PID: $pid -> $($proc.ProcessName)"
            } else {
                "  $localAddr -> $state -> PID: $pid -> [Unknown Process]"
            }
        } catch {
            "  $localAddr -> $state -> PID: $pid -> [Error getting process]"
        }
    }

    if ($portProcesses) {
        Write-Host "✅ Port 6379 processes:" -ForegroundColor Green
        $portProcesses | ForEach-Object { Write-Host $_ -ForegroundColor White }
    } else {
        Write-Host "❌ No processes found using port 6379" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking port processes: $($_.Exception.Message)" -ForegroundColor Red
}

# Check if Redis is running as a Windows service with different name
Write-Host "`n5. All Services (checking for Redis variants):" -ForegroundColor Yellow
try {
    $allServices = Get-Service | Where-Object {$_.Name -like "*cache*" -or $_.Name -like "*memory*" -or $_.Name -like "*data*"}
    if ($allServices) {
        Write-Host "✅ Related services found:" -ForegroundColor Green
        foreach ($service in $allServices) {
            Write-Host "  $($service.Name): $($service.Status)" -ForegroundColor White
        }
    } else {
        Write-Host "❌ No related services found" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking services: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== End of Process Check ===" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Check the process details above" -ForegroundColor White
Write-Host "2. If it's WSL/Hyper-V, Redis is running in Linux subsystem" -ForegroundColor White
Write-Host "3. If it's unknown process, it might be a custom Redis installation" -ForegroundColor White
