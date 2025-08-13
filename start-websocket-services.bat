@echo off
title WebSocket Services Startup

echo ========================================
echo    WebSocket Services Startup Script
echo ========================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo Error: Laravel artisan file not found.
    echo Please run this script from the project root.
    pause
    exit /b 1
)

REM Check if .env exists
if not exist ".env" (
    echo Warning: .env file not found.
    if exist ".env.example" (
        echo Creating .env from .env.example...
        copy ".env.example" ".env"
        echo .env file created.
    ) else (
        echo Error: .env.example not found.
        echo Please create .env file manually.
        pause
        exit /b 1
    )
)

echo Starting WebSocket Services...
echo.

REM Start Laravel WebSocket server
echo [1/4] Starting Laravel WebSocket Server...
start "WebSocket Server" cmd /k "php artisan websockets:serve"
timeout /t 3 /nobreak >nul

REM Start Queue Worker
echo [2/4] Starting Queue Worker...
start "Queue Worker" cmd /k "php artisan queue:work --sleep=3 --tries=3"
timeout /t 3 /nobreak >nul

REM Start Laravel Application
echo [3/4] Starting Laravel Application...
start "Laravel App" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"
timeout /t 3 /nobreak >nul

REM Start Nginx if exists
if exist "nginx\start-nginx.bat" (
    echo [4/4] Starting Nginx...
    start "Nginx" cmd /k "nginx\start-nginx.bat"
) else (
    echo [4/4] Nginx not found, skipping...
)

echo.
echo ========================================
echo    Services Started Successfully!
echo ========================================
echo.
echo Access URLs:
echo - Laravel App: http://localhost:8000
echo - WebSocket Test: http://localhost:8000/websocket-test
echo - WebSocket Server: ws://localhost:6001
echo - API Endpoint: http://localhost:8000/api/receiver
echo.
echo Next Steps:
echo 1. Open WebSocket test page in browser
echo 2. Send test data via API endpoint
echo 3. Monitor real-time updates
echo.
echo Press any key to exit...
pause >nul
