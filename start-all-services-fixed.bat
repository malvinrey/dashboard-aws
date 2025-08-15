@echo off
chcp 65001 >nul
title Start All Services for WebSocket Fix

echo.
echo =============================================
echo Starting All Services for WebSocket Fix...
echo =============================================
echo.

REM Stop any existing services first
echo Stopping existing services...
taskkill /f /im php.exe >nul 2>&1
taskkill /f /im nginx.exe >nul 2>&1
taskkill /f /im redis-server.exe >nul 2>&1
taskkill /f /im soketi.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM 1. Start Redis Server
echo.
echo 1. Starting Redis Server...
if not exist "redis-server.exe" (
    echo Warning: redis-server.exe not found in current directory
    echo Please ensure Redis is installed and accessible
) else (
    start "Redis Server" /min redis-server.exe
    timeout /t 3 /nobreak >nul
)

REM 2. Start PHP-FPM
echo.
echo 2. Starting PHP-FPM...
start "PHP-FPM" /min php-cgi.exe -b 127.0.0.1:9000
timeout /t 3 /nobreak >nul

REM 3. Start Nginx
echo.
echo 3. Starting Nginx...
if exist "nginx\nginx.exe" (
    start "Nginx" /min nginx\nginx.exe -c nginx\config\nginx.conf
    timeout /t 3 /nobreak >nul
) else (
    echo Warning: nginx\nginx.exe not found
)

REM 4. Start Laravel Queue Worker
echo.
echo 4. Starting Laravel Queue Worker...
start "Laravel Queue" /min php.exe artisan queue:work --sleep=3 --tries=3 --max-time=3600
timeout /t 3 /nobreak >nul

REM 5. Start Soketi WebSocket Server
echo.
echo 5. Starting Soketi WebSocket Server...
if exist "soketi.exe" (
    start "Soketi" /min soketi.exe start --config=soketi.json
    timeout /t 3 /nobreak >nul
) else (
    echo Warning: soketi.exe not found in current directory
    echo Please ensure Soketi is installed and accessible
)

REM 6. Start Laravel Development Server
echo.
echo 6. Starting Laravel Development Server...
start "Laravel Server" /min php.exe artisan serve --host=0.0.0.0 --port=8000
timeout /t 3 /nobreak >nul

REM Wait for services to start
echo.
echo Waiting for services to start...
timeout /t 10 /nobreak >nul

REM Show service status
echo.
echo =============================================
echo Service Status Summary:
echo =============================================

REM Check Redis
netstat -an | findstr ":6379" >nul
if %errorlevel% equ 0 (
    echo Redis:        [32m✅ RUNNING[0m
) else (
    echo Redis:        [31m❌ NOT RUNNING[0m
)

REM Check PHP-FPM
netstat -an | findstr ":9000" >nul
if %errorlevel% equ 0 (
    echo PHP-FPM:      [32m✅ RUNNING[0m
) else (
    echo PHP-FPM:      [31m❌ NOT RUNNING[0m
)

REM Check Nginx
netstat -an | findstr ":80" >nul
if %errorlevel% equ 0 (
    echo Nginx:        [32m✅ RUNNING[0m
) else (
    echo Nginx:        [31m❌ NOT RUNNING[0m
)

REM Check Soketi
netstat -an | findstr ":6001" >nul
if %errorlevel% equ 0 (
    echo Soketi:       [32m✅ RUNNING[0m
) else (
    echo Soketi:       [31m❌ NOT RUNNING[0m
)

REM Check Laravel
netstat -an | findstr ":8000" >nul
if %errorlevel% equ 0 (
    echo Laravel:      [32m✅ RUNNING[0m
) else (
    echo Laravel:      [31m❌ NOT RUNNING[0m
)

echo.
echo =============================================
echo All services started!
echo =============================================
echo.
echo You can now:
echo 1. Open http://localhost:8000 in your browser
echo 2. Test WebSocket at http://localhost:8000/test-websocket-fix.html
echo 3. Check WebSocket connection at ws://127.0.0.1:6001
echo.
echo Press any key to stop all services...
echo.

pause >nul

REM Stop all services
echo.
echo Stopping all services...
taskkill /f /im php.exe >nul 2>&1
taskkill /f /im nginx.exe >nul 2>&1
taskkill /f /im redis-server.exe >nul 2>&1
taskkill /f /im soketi.exe >nul 2>&1

echo All services stopped
echo.
pause
