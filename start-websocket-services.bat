@echo OFF
chcp 65001 >nul
echo 🚀 Starting SCADA WebSocket Services...
echo.

REM Set titles for the command prompt windows
set LARAVEL_TITLE="SCADA Laravel Server (Artisan)"
set SOKETI_TITLE="SCADA WebSocket Server (Soketi)"
set QUEUE_TITLE="SCADA Queue Worker"

REM Start Laravel Artisan Serve
echo [1/3] Starting Laravel development server on http://localhost:8000
start "%LARAVEL_TITLE%" cmd /k "php artisan serve"

REM Start Soketi WebSocket Server using the local executable
echo [2/3] Starting Soketi WebSocket server on port 6001...
if exist "node_modules\.bin\soketi.cmd" (
    start "%SOKETI_TITLE%" cmd /k "node_modules\.bin\soketi.cmd start --config=soketi.json"
) else (
    echo ❌ Soketi not found. Installing...
    npm install @soketi/soketi
    if exist "node_modules\.bin\soketi.cmd" (
        start "%SOKETI_TITLE%" cmd /k "node_modules\.bin\soketi.cmd start --config=soketi.json"
    ) else (
        echo ❌ Failed to install Soketi
        pause
        exit /b 1
    )
)

REM Start Laravel Queue Worker
echo [3/3] Starting Laravel queue worker...
start "%QUEUE_TITLE%" cmd /k "php artisan queue:work"

echo.
echo All services have been started in separate windows.
echo Please keep these windows open.
echo.
echo 📊 Service URLs:
echo   • Laravel App: http://localhost:8000
echo   • WebSocket Test: http://localhost:8000/websocket-test
echo   • Soketi Server: http://localhost:6001
echo.
pause
