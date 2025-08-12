# PowerShell Script untuk Setup PHP-FPM di Windows
# Run as Administrator

Write-Host "Setting up PHP-FPM for Windows..." -ForegroundColor Green

# Check if PHP is installed
$phpPath = Get-Command php -ErrorAction SilentlyContinue
if (!$phpPath) {
    Write-Host "X PHP not found. Please install PHP first." -ForegroundColor Red
    Write-Host "> You can download PHP from: https://windows.php.net/download/" -ForegroundColor Yellow
    exit 1
}

$phpDir = Split-Path $phpPath.Source -Parent
        Write-Host "✓ PHP found at: $phpDir" -ForegroundColor Green

# Check if php-fpm is available
$phpFpmPath = Join-Path $phpDir "php-cgi.exe"
    if (!(Test-Path $phpFpmPath)) {
        Write-Host "X php-cgi.exe not found. Please install PHP with CGI support." -ForegroundColor Red
        exit 1
    }

        Write-Host "✓ php-cgi.exe found at: $phpFpmPath" -ForegroundColor Green

# Create PHP-FPM configuration
$phpFpmIni = Join-Path $phpDir "php-fpm.ini"
$phpFpmIniContent = @"
[global]
pid = php-fpm.pid
error_log = php-fpm.log
daemonize = no

[www]
user = $env:USERNAME
group = $env:USERNAME
listen = 127.0.0.1:9000
listen.owner = $env:USERNAME
listen.group = $env:USERNAME
listen.mode = 0666

pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3

php_admin_value[error_log] = php-fpm.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_time] = 300
php_admin_value[post_max_size] = 100M
php_admin_value[upload_max_filesize] = 100M

# SSE specific settings
php_admin_value[max_execution_time] = 0
php_admin_value[output_buffering] = Off
php_admin_value[implicit_flush] = On
"@

try {
    Set-Content -Path $phpFpmIni -Value $phpFpmIniContent -Encoding UTF8
            Write-Host "✓ PHP-FPM configuration created: $phpFpmIni" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to create PHP-FPM config: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Create batch file to start PHP-FPM
$startPhpFpm = Join-Path $phpDir "start-php-fpm.bat"
$startPhpFpmContent = @"
@echo off
echo Starting PHP-FPM...
cd /d "$phpDir"
php-cgi.exe -b 127.0.0.1:9000 -c php-fpm.ini
pause
"@

try {
    Set-Content -Path $startPhpFpm -Value $startPhpFpmContent -Encoding ASCII
            Write-Host "✓ Start script created: $startPhpFpm" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to create start script: $($_.Exception.Message)" -ForegroundColor Red
}

# Create batch file to stop PHP-FPM
$stopPhpFpm = Join-Path $phpDir "stop-php-fpm.bat"
$stopPhpFpmContent = @"
@echo off
echo Stopping PHP-FPM...
taskkill /f /im php-cgi.exe
echo PHP-FPM stopped.
pause
"@

try {
    Set-Content -Path $stopPhpFpm -Value $stopPhpFpmContent -Encoding ASCII
            Write-Host "✓ Stop script created: $stopPhpFpm" -ForegroundColor Green
    } catch {
        Write-Host "X Failed to create stop script: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "PHP-FPM setup completed!" -ForegroundColor Green
Write-Host "Configuration file: $phpFpmIni" -ForegroundColor Cyan
Write-Host "To start PHP-FPM, run: $startPhpFpm" -ForegroundColor Cyan
Write-Host "To stop PHP-FPM, run: $stopPhpFpm" -ForegroundColor Cyan
Write-Host "PHP-FPM will listen on: 127.0.0.1:9000" -ForegroundColor Cyan
