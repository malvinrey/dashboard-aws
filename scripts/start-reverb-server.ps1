# Start Reverb WebSocket Server
# Script untuk menjalankan Laravel Reverb server

Write-Host "🚀 Starting Laravel Reverb WebSocket Server..." -ForegroundColor Green

# Check if PHP is available
try {
    $phpVersion = php -v 2>&1 | Select-String "PHP" | Select-Object -First 1
    Write-Host "✅ PHP found: $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ PHP not found. Please install PHP and add it to PATH." -ForegroundColor Red
    exit 1
}

# Check if Composer is available
try {
    $composerVersion = composer -V 2>&1 | Select-String "Composer" | Select-Object -First 1
    Write-Host "✅ Composer found: $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Composer not found. Please install Composer and add it to PATH." -ForegroundColor Red
    exit 1
}

# Navigate to project directory
$projectDir = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $projectDir

Write-Host "📁 Project directory: $projectDir" -ForegroundColor Blue

# Check if .env file exists
if (-not (Test-Path ".env")) {
    Write-Host "⚠️  .env file not found. Creating from .env.example..." -ForegroundColor Yellow

    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "✅ .env file created from .env.example" -ForegroundColor Green
    } else {
        Write-Host "❌ .env.example not found. Please create .env file manually." -ForegroundColor Red
        exit 1
    }
}

# Check if vendor directory exists
if (-not (Test-Path "vendor")) {
    Write-Host "📦 Installing dependencies..." -ForegroundColor Yellow
    composer install --no-dev --optimize-autoloader
}

# Check if Reverb is installed
try {
    $reverbInstalled = php artisan list | Select-String "reverb:start"
    if (-not $reverbInstalled) {
        Write-Host "📦 Installing Laravel Reverb..." -ForegroundColor Yellow
        composer require laravel/reverb
        php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider"
    }
} catch {
    Write-Host "⚠️  Could not check Reverb installation. Continuing..." -ForegroundColor Yellow
}

# Create Reverb configuration if not exists
$reverbConfigPath = "config/reverb.php"
if (-not (Test-Path $reverbConfigPath)) {
    Write-Host "⚙️  Creating Reverb configuration..." -ForegroundColor Yellow

    $reverbConfig = @"
<?php

return [
    'servers' => [
        'default' => [
            'host' => env('REVERB_HOST', '127.0.0.1'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'options' => [
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
                'verify' => false,
            ],
        ],
    ],

    'apps' => [
        [
            'id' => env('REVERB_APP_ID', '12345'),
            'key' => env('REVERB_APP_KEY', 'scada_dashboard_key_2024'),
            'secret' => env('REVERB_APP_SECRET', 'scada_dashboard_secret_2024'),
            'enable_client_messages' => true,
            'enable_insights' => true,
        ],
    ],

    'max_request_size' => 10 * 1024 * 1024, // 10MB

    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST'],
        'allowed_headers' => ['*'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => false,
    ],
];
"@

    $reverbConfig | Out-File -FilePath $reverbConfigPath -Encoding UTF8
    Write-Host "✅ Reverb configuration created" -ForegroundColor Green
}

# Check if broadcasting is configured
$broadcastingConfig = Get-Content "config/broadcasting.php" -Raw
if ($broadcastingConfig -notmatch "reverb") {
    Write-Host "⚠️  Broadcasting not configured for Reverb. Please check config/broadcasting.php" -ForegroundColor Yellow
}

# Start Reverb server
Write-Host "🚀 Starting Reverb server on port 8080..." -ForegroundColor Green
Write-Host "📡 WebSocket URL: ws://127.0.0.1:8080" -ForegroundColor Blue
Write-Host "🌐 HTTP URL: http://127.0.0.1:8080" -ForegroundColor Blue
Write-Host "⏹️  Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

try {
    # Start Reverb server
    php artisan reverb:start --host=127.0.0.1 --port=8080
} catch {
    Write-Host "❌ Failed to start Reverb server: $_" -ForegroundColor Red

    # Alternative: try to start with different method
    Write-Host "🔄 Trying alternative method..." -ForegroundColor Yellow
    try {
        php artisan reverb:start
    } catch {
        Write-Host "❌ Alternative method also failed: $_" -ForegroundColor Red
        Write-Host "💡 Please check:" -ForegroundColor Yellow
        Write-Host "   1. Laravel Reverb is properly installed" -ForegroundColor White
        Write-Host "   2. Broadcasting configuration is correct" -ForegroundColor White
        Write-Host "   3. Port 8080 is not in use" -ForegroundColor White
        Write-Host "   4. You have proper permissions" -ForegroundColor White
    }
}

Write-Host ""
Write-Host "🏁 Reverb server stopped." -ForegroundColor Green
