Write-Host "🔧 Setting up Redis for SCADA Dashboard..." -ForegroundColor Green

# Check if Redis is already running
try {
    $redisTest = New-Object System.Net.Sockets.TcpClient
    $redisTest.Connect("127.0.0.1", 6379)
    $redisTest.Close()
    Write-Host "✅ Redis is already running on port 6379" -ForegroundColor Green
    exit 0
} catch {
    Write-Host "ℹ️  Redis not running, proceeding with setup..." -ForegroundColor Yellow
}

# Check if Redis is installed via Chocolatey
if (Get-Command "redis-server" -ErrorAction SilentlyContinue) {
    Write-Host "✅ Redis is already installed" -ForegroundColor Green
} else {
    Write-Host "📦 Installing Redis..." -ForegroundColor Yellow

    # Check if Chocolatey is installed
    if (Get-Command "choco" -ErrorAction SilentlyContinue) {
        Write-Host "Installing Redis via Chocolatey..." -ForegroundColor Cyan
        choco install redis-64 -y
    } else {
        Write-Host "❌ Chocolatey not found. Please install Redis manually:" -ForegroundColor Red
        Write-Host "   Option 1: Install Chocolatey and run: choco install redis-64" -ForegroundColor Yellow
        Write-Host "   Option 2: Download from https://github.com/microsoftarchive/redis/releases" -ForegroundColor Yellow
        Write-Host "   Option 3: Use Docker: docker run -d -p 6379:6379 redis:alpine" -ForegroundColor Yellow
        exit 1
    }
}

# Start Redis service
Write-Host "🚀 Starting Redis service..." -ForegroundColor Yellow
try {
    Start-Service Redis
    Write-Host "✅ Redis service started successfully" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Could not start Redis service, trying manual start..." -ForegroundColor Yellow
    try {
        Start-Process -FilePath "redis-server" -ArgumentList "--port", "6379" -WindowStyle Hidden
        Start-Sleep -Seconds 3
        Write-Host "✅ Redis server started manually" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to start Redis. Please start manually:" -ForegroundColor Red
        Write-Host "   redis-server --port 6379" -ForegroundColor Yellow
        exit 1
    }
}

# Test Redis connection
Write-Host "🧪 Testing Redis connection..." -ForegroundColor Yellow
Start-Sleep -Seconds 2

try {
    $redisTest = New-Object System.Net.Sockets.TcpClient
    $redisTest.Connect("127.0.0.1", 6379)
    $redisTest.Close()
    Write-Host "✅ Redis connection successful!" -ForegroundColor Green
} catch {
    Write-Host "❌ Redis connection failed" -ForegroundColor Red
    exit 1
}

# Test Redis functionality
Write-Host "🧪 Testing Redis functionality..." -ForegroundColor Yellow
try {
    $redisCli = Get-Command "redis-cli" -ErrorAction SilentlyContinue
    if ($redisCli) {
        $testResult = & redis-cli ping
        if ($testResult -eq "PONG") {
            Write-Host "✅ Redis functionality test passed" -ForegroundColor Green
        } else {
            Write-Host "⚠️  Redis ping test failed" -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "⚠️  Could not test Redis functionality" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎉 Redis setup completed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Redis Status:" -ForegroundColor Cyan
Write-Host "  • Service: Running on port 6379" -ForegroundColor White
Write-Host "  • Connection: 127.0.0.1:6379" -ForegroundColor White
Write-Host "  • Usage: Queue, Cache, Session, Broadcasting" -ForegroundColor White
Write-Host ""
Write-Host "🔧 Management Commands:" -ForegroundColor Cyan
Write-Host "  • Start Redis: Start-Service Redis" -ForegroundColor White
Write-Host "  • Stop Redis: Stop-Service Redis" -ForegroundColor White
Write-Host "  • Redis CLI: redis-cli" -ForegroundColor White
Write-Host "  • Monitor: redis-cli monitor" -ForegroundColor White
Write-Host ""
Write-Host "✅ Your SCADA Dashboard is now ready for high-performance WebSocket operations!" -ForegroundColor Green
