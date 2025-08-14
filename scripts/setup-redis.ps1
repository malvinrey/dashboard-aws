Write-Host "Setting up Redis for SCADA Dashboard..."

# Check if Redis is already running
try {
    $redisTest = New-Object System.Net.Sockets.TcpClient
    $redisTest.Connect("127.0.0.1", 6379)
    $redisTest.Close()
    Write-Host "Redis is already running on port 6379"
    exit 0
} catch {
    Write-Host "Redis not running, proceeding with setup..."
}

# Check if Redis is installed via Chocolatey
if (Get-Command "redis-server" -ErrorAction SilentlyContinue) {
    Write-Host "Redis is already installed"
} else {
    Write-Host "Installing Redis..."

    # Check if Chocolatey is installed
    if (Get-Command "choco" -ErrorAction SilentlyContinue) {
        Write-Host "Installing Redis via Chocolatey..."
        choco install redis-64 -y
    } else {
        Write-Host "Chocolatey not found. Please install Redis manually:"
        Write-Host "   Option 1: Install Chocolatey and run: choco install redis-64"
        Write-Host "   Option 2: Download from https://github.com/microsoftarchive/redis/releases"
        Write-Host "   Option 3: Use Docker: docker run -d -p 6379:6379 redis:alpine"
        exit 1
    }
}

# Start Redis service
Write-Host "Starting Redis service..."
try {
    Start-Service Redis
    Write-Host "Redis service started successfully"
} catch {
    Write-Host "Could not start Redis service, trying manual start..."
    try {
        Start-Process -FilePath "redis-server" -ArgumentList "--port", "6379" -WindowStyle Hidden
        Start-Sleep -Seconds 3
        Write-Host "Redis server started manually"
    } catch {
        Write-Host "Failed to start Redis. Please start manually:"
        Write-Host "   redis-server --port 6379"
        exit 1
    }
}

# Test Redis connection
Write-Host "Testing Redis connection..."
Start-Sleep -Seconds 2

try {
    $redisTest = New-Object System.Net.Sockets.TcpClient
    $redisTest.Connect("127.0.0.1", 6379)
    $redisTest.Close()
    Write-Host "Redis connection successful!"
} catch {
    Write-Host "Redis connection failed"
    exit 1
}

# Test Redis functionality
Write-Host "Testing Redis functionality..."
try {
    $redisCli = Get-Command "redis-cli" -ErrorAction SilentlyContinue
    if ($redisCli) {
        $testResult = & redis-cli ping
        if ($testResult -eq "PONG") {
            Write-Host "Redis functionality test passed"
        } else {
            Write-Host "Redis ping test failed"
        }
    }
} catch {
    Write-Host "Could not test Redis functionality"
}

Write-Host ""
Write-Host "Redis setup completed successfully!"
Write-Host ""
Write-Host "Redis Status:"
Write-Host "  • Service: Running on port 6379"
Write-Host "  • Connection: 127.0.0.1:6379"
Write-Host "  • Usage: Queue, Cache, Session, Broadcasting"
Write-Host ""
Write-Host "Management Commands:"
Write-Host "  • Start Redis: Start-Service Redis"
Write-Host "  • Stop Redis: Stop-Service Redis"
Write-Host "  • Redis CLI: redis-cli"
Write-Host "  • Monitor: redis-cli monitor"
Write-Host ""
Write-Host "Your SCADA Dashboard is now ready for high-performance WebSocket operations!"
