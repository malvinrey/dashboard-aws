# Detailed Redis Debugging Script
Write-Host "=== Detailed Redis Debugging ===" -ForegroundColor Green

# 1. Check what's using port 6379
Write-Host "`n1. What's using port 6379:" -ForegroundColor Yellow
try {
    $portInfo = netstat -ano | findstr :6379
    if ($portInfo) {
        Write-Host "✅ Port 6379 is in use:" -ForegroundColor Green
        Write-Host $portInfo -ForegroundColor White

        # Extract PID and get process details
        $pid = ($portInfo -split '\s+')[-1]
        Write-Host "Process ID: $pid" -ForegroundColor Cyan

        try {
            $process = Get-Process -Id $pid -ErrorAction SilentlyContinue
            if ($process) {
                Write-Host "Process Name: $($process.ProcessName)" -ForegroundColor White
                Write-Host "Process Path: $($process.Path)" -ForegroundColor White
                Write-Host "Start Time: $($process.StartTime)" -ForegroundColor White
            }
        } catch {
            Write-Host "Could not get process details for PID $pid" -ForegroundColor Yellow
        }
    } else {
        Write-Host "❌ Port 6379 is not in use" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking port usage: $($_.Exception.Message)" -ForegroundColor Red
}

# 2. Check Docker containers
Write-Host "`n2. Docker Containers:" -ForegroundColor Yellow
try {
    $dockerOutput = docker ps 2>$null | Select-String "redis"
    if ($dockerOutput) {
        Write-Host "✅ Redis container found:" -ForegroundColor Green
        Write-Host $dockerOutput -ForegroundColor White
    } else {
        Write-Host "❌ No Redis containers running" -ForegroundColor Red
    }
} catch {
    Write-Host "Docker not available or not running" -ForegroundColor Yellow
}

# 3. Check WSL processes
Write-Host "`n3. WSL Processes:" -ForegroundColor Yellow
try {
    $wslOutput = wsl --list --running 2>$null
    if ($wslOutput) {
        Write-Host "✅ WSL instances running:" -ForegroundColor Green
        Write-Host $wslOutput -ForegroundColor White
    } else {
        Write-Host "❌ No WSL instances running" -ForegroundColor Red
    }
} catch {
    Write-Host "WSL not available" -ForegroundColor Yellow
}

# 4. Check all services with "redis" in name
Write-Host "`n4. Services with 'redis' in name:" -ForegroundColor Yellow
try {
    $redisServices = Get-Service | Where-Object {$_.Name -like "*redis*"}
    if ($redisServices) {
        Write-Host "✅ Redis-related services found:" -ForegroundColor Green
        foreach ($service in $redisServices) {
            Write-Host "  $($service.Name): $($service.Status)" -ForegroundColor White
        }
    } else {
        Write-Host "❌ No Redis-related services found" -ForegroundColor Red
    }
} catch {
    Write-Host "Error checking services: $($_.Exception.Message)" -ForegroundColor Red
}

# 5. Check if Redis is running as a process
Write-Host "`n5. Redis processes:" -ForegroundColor Yellow
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
    Write-Host "Error checking processes: $($_.Exception.Message)" -ForegroundColor Red
}

# 6. Test actual Redis connection
Write-Host "`n6. Testing Redis connection:" -ForegroundColor Yellow
try {
    $tcpClient = New-Object System.Net.Sockets.TcpClient
    $result = $tcpClient.BeginConnect("127.0.0.1", 6379, $null, $null)
    $wait = $result.AsyncWaitHandle.WaitOne(3000, $false)

    if ($wait) {
        $tcpClient.EndConnect($result)
        Write-Host "✅ TCP connection to Redis successful" -ForegroundColor Green

        # Try to send a simple Redis command
        $stream = $tcpClient.GetStream()
        $command = "*1`r`n`$4`r`nPING`r`n"
        $bytes = [System.Text.Encoding]::ASCII.GetBytes($command)
        $stream.Write($bytes, 0, $bytes.Length)

        $buffer = New-Object byte[] 1024
        $bytesRead = $stream.Read($buffer, 0, $buffer.Length)
        $response = [System.Text.Encoding]::ASCII.GetString($buffer, 0, $bytesRead)
        Write-Host "Redis response: $response" -ForegroundColor White

        $tcpClient.Close()
    } else {
        Write-Host "❌ TCP connection to Redis failed" -ForegroundColor Red
    }
} catch {
    Write-Host "Error testing Redis connection: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== End of Detailed Debug ===" -ForegroundColor Green
Write-Host "`nSummary:" -ForegroundColor Cyan
Write-Host "If port 6379 is accessible but no Redis service found," -ForegroundColor White
Write-Host "Redis is likely running via Docker, WSL, or as a manual process." -ForegroundColor White
