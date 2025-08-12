# Master PowerShell Script untuk Setup Nginx + MySQL + PHP-FPM di Windows
# Run as Administrator

Write-Host "SCADA Dashboard - Nginx + MySQL + PHP-FPM Setup" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
if (!$isAdmin) {
    Write-Host "X This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "> Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

    Write-Host "✓ Running as Administrator" -ForegroundColor Green

# Function to check prerequisites
function Check-Prerequisites {
    Write-Host "Checking prerequisites..." -ForegroundColor Yellow

        # Check PHP
    $phpPath = Get-Command php -ErrorAction SilentlyContinue
    if (!$phpPath) {
        Write-Host "X PHP not found. Please install PHP first." -ForegroundColor Red
        Write-Host "> Download from: https://windows.php.net/download/" -ForegroundColor Yellow
        return $false
    }
    Write-Host "✓ PHP found: $($phpPath.Source)" -ForegroundColor Green

    # Check Composer
    $composerPath = Get-Command composer -ErrorAction SilentlyContinue
    if (!$composerPath) {
        Write-Host "X Composer not found. Please install Composer first." -ForegroundColor Red
        Write-Host "> Download from: https://getcomposer.org/download/" -ForegroundColor Yellow
        return $false
    }
    Write-Host "✓ Composer found: $($composerPath.Source)" -ForegroundColor Green

    # Check MySQL
    $mysqlService = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue
    if (!$mysqlService) {
        Write-Host "X MySQL service not found. Please install MySQL first." -ForegroundColor Red
        Write-Host "> Download from: https://dev.mysql.com/downloads/installer/" -ForegroundColor Yellow
        return $false
    }
    Write-Host "✓ MySQL service found: $($mysqlService.Name)" -ForegroundColor Green

    return $true
}

# Function to install Nginx
function Install-Nginx {
    Write-Host "Installing Nginx..." -ForegroundColor Yellow

    $scriptPath = Join-Path $PSScriptRoot "install_nginx_windows.ps1"
    if (Test-Path $scriptPath) {
        & $scriptPath
        return $LASTEXITCODE -eq 0
    } else {
        Write-Host "X Nginx install script not found: $scriptPath" -ForegroundColor Red
        return $false
    }
}

# Function to setup PHP-FPM
function Setup-PHPFPM {
    Write-Host "Setting up PHP-FPM..." -ForegroundColor Yellow

    $scriptPath = Join-Path $PSScriptRoot "setup_php_fpm_windows.ps1"
    if (Test-Path $scriptPath) {
        & $scriptPath
        return $LASTEXITCODE -eq 0
    } else {
        Write-Host "PHP-FPM setup script not found: $scriptPath" -ForegroundColor Red
        return $false
    }
}

# Function to setup MySQL
function Setup-MySQL {
    Write-Host "Setting up MySQL..." -ForegroundColor Yellow

    $scriptPath = Join-Path $PSScriptRoot "setup_mysql_windows.ps1"
    if (Test-Path $scriptPath) {
        & $scriptPath
        return $LASTEXITCODE -eq 0
    } else {
        Write-Host "X MySQL setup script not found: $scriptPath" -ForegroundColor Red
        return $false
    }
}

# Function to configure Nginx
function Configure-Nginx {
    Write-Host "Configuring Nginx..." -ForegroundColor Yellow

    $nginxConf = "config\nginx-scada-dashboard.conf"
    if (!(Test-Path $nginxConf)) {
        Write-Host "X Nginx configuration not found: $nginxConf" -ForegroundColor Red
        return $false
    }

    $nginxDir = "C:\nginx\nginx"
    if (!(Test-Path $nginxDir)) {
        Write-Host "X Nginx not installed. Please run Nginx installation first." -ForegroundColor Red
        return $false
    }

    # Copy configuration
    $nginxConfDest = Join-Path $nginxDir "conf\nginx.conf"
    try {
        Copy-Item $nginxConf $nginxConfDest -Force
        Write-Host "✓ Nginx configuration copied to: $nginxConfDest" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to copy Nginx configuration: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }

    return $true
}

# Function to create startup scripts
function Create-StartupScripts {
    Write-Host "Creating startup scripts..." -ForegroundColor Yellow

    $startupDir = "scripts\startup"
    if (!(Test-Path $startupDir)) {
        New-Item -ItemType Directory -Path $startupDir -Force | Out-Null
    }

    # Start all services script
    $startAll = Join-Path $startupDir "start-all-services.bat"
    $startAllContent = @"
@echo off
echo Starting SCADA Dashboard Services...
echo.

echo Starting MySQL...
net start MySQL80
if %ERRORLEVEL% NEQ 0 (
    echo Failed to start MySQL. Please check if MySQL service is installed.
    pause
    exit /b 1
)
echo MySQL started successfully.
echo.

echo Starting PHP-FPM...
cd /d "C:\php"
start "PHP-FPM" php-cgi.exe -b 127.0.0.1:9000 -c php-fpm.ini
echo PHP-FPM started on port 9000.
echo.

echo Starting Nginx...
cd /d "C:\nginx\nginx"
start "Nginx" nginx.exe
echo Nginx started on port 80.
echo.

echo All services started successfully!
echo.
echo MySQL: localhost:3306
echo PHP-FPM: 127.0.0.1:9000
echo Nginx: http://localhost
echo.
pause
"@

    try {
        Set-Content -Path $startAll -Value $startAllContent -Encoding ASCII
        Write-Host "✓ Start all services script created: $startAll" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to create start script: $($_.Exception.Message)" -ForegroundColor Red
    }

    # Stop all services script
    $stopAll = Join-Path $startupDir "stop-all-services.bat"
    $stopAllContent = @"
@echo off
echo Stopping SCADA Dashboard Services...
echo.

echo Stopping Nginx...
taskkill /f /im nginx.exe 2>nul
echo Nginx stopped.
echo.

echo Stopping PHP-FPM...
taskkill /f /im php-cgi.exe 2>nul
echo PHP-FPM stopped.
echo.

echo Stopping MySQL...
net stop MySQL80 2>nul
echo MySQL stopped.
echo.

echo All services stopped successfully!
pause
"@

    try {
        Set-Content -Path $stopAll -Value $stopAllContent -Encoding ASCII
        Write-Host "✓ Stop all services script created: $stopAll" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to create stop script: $($_.Exception.Message)" -ForegroundColor Red
    }

    return $true
}

# Function to test setup
function Test-Setup {
    Write-Host "Testing setup..." -ForegroundColor Yellow

    $tests = @()

    # Test MySQL
    try {
        $mysqlService = Get-Service -Name "MySQL*"
        if ($mysqlService.Status -eq "Running") {
            $tests += "✓ MySQL: Running"
        } else {
            $tests += "X MySQL: Not running"
        }
    } catch {
        $tests += "X MySQL: Service not found"
    }

    # Test PHP-FPM
    try {
        $phpFpmProcess = Get-Process -Name "php-cgi" -ErrorAction SilentlyContinue
        if ($phpFpmProcess) {
            $tests += "✓ PHP-FPM: Running on port 9000"
        } else {
            $tests += "X PHP-FPM: Not running"
        }
    } catch {
        $tests += "X PHP-FPM: Process not found"
    }

    # Test Nginx
    try {
        $nginxProcess = Get-Process -Name "nginx" -ErrorAction SilentlyContinue
        if ($nginxProcess) {
            $tests += "✓ Nginx: Running on port 80"
        } else {
            $tests += "X Nginx: Not running"
        }
    } catch {
        $tests += "X Nginx: Process not found"
    }

    # Display test results
    Write-Host "Setup Test Results:" -ForegroundColor Cyan
    foreach ($test in $tests) {
        Write-Host "   $test" -ForegroundColor $(if ($test -like "V*") { "Green" } else { "Red" })
    }

    return $tests -like "V*" | Measure-Object | Select-Object -ExpandProperty Count
}

# Main execution
Write-Host "Starting setup process..." -ForegroundColor Green

# Step 1: Check prerequisites
if (!(Check-Prerequisites)) {
    Write-Host "X Prerequisites check failed. Please install required software first." -ForegroundColor Red
    exit 1
}

# Step 2: Install Nginx
Write-Host "`nStep 1: Installing Nginx..." -ForegroundColor Cyan
if (!(Install-Nginx)) {
    Write-Host "X Nginx installation failed." -ForegroundColor Red
    exit 1
}

# Step 3: Setup PHP-FPM
Write-Host "`nStep 2: Setting up PHP-FPM..." -ForegroundColor Cyan
if (!(Setup-PHPFPM)) {
    Write-Host "X PHP-FPM setup failed." -ForegroundColor Red
    exit 1
}

# Step 4: Setup MySQL
Write-Host "`nStep 3: Setting up MySQL..." -ForegroundColor Cyan
if (!(Setup-MySQL)) {
    Write-Host "X MySQL setup failed." -ForegroundColor Red
    exit 1
}

# Step 5: Configure Nginx
Write-Host "`nStep 4: Configuring Nginx..." -ForegroundColor Cyan
if (!(Configure-Nginx)) {
    Write-Host "X Nginx configuration failed." -ForegroundColor Red
    exit 1
}

# Step 6: Create startup scripts
Write-Host "`nStep 5: Creating startup scripts..." -ForegroundColor Cyan
if (!(Create-StartupScripts)) {
    Write-Host "! Startup scripts creation failed, but setup can continue." -ForegroundColor Yellow
}

# Step 7: Test setup
Write-Host "`nStep 6: Testing setup..." -ForegroundColor Cyan
$testResults = Test-Setup

Write-Host "`nSetup completed!" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green

if ($testResults -eq 3) {
    Write-Host "✓ All services are running successfully!" -ForegroundColor Green
} else {
    Write-Host "! Some services may not be running. Please check manually." -ForegroundColor Yellow
}

Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Copy config\.env.mysql.template to .env" -ForegroundColor White
Write-Host "2. Run: php artisan migrate" -ForegroundColor White
Write-Host "3. Run: php artisan serve (for development)" -ForegroundColor White
Write-Host "4. Or use startup scripts in scripts\startup\" -ForegroundColor White

Write-Host "`nYour SCADA Dashboard should be available at:" -ForegroundColor Cyan
Write-Host "   http://localhost" -ForegroundColor White

Write-Host "`nImportant files:" -ForegroundColor Cyan
Write-Host "   Nginx config: C:\nginx\nginx\conf\nginx.conf" -ForegroundColor White
Write-Host "   PHP-FPM config: C:\php\php-fpm.ini" -ForegroundColor White
Write-Host "   Startup scripts: scripts\startup\" -ForegroundColor White

Write-Host "`nTo start all services, run:" -ForegroundColor Cyan
Write-Host "   scripts\startup\start-all-services.bat" -ForegroundColor White
