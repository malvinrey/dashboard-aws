@echo off
chcp 65001 >nul
echo 🚀 Starting SCADA WebSocket Services...
echo.

REM Check if PowerShell is available
powershell -Command "Get-Host" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PowerShell not available. Please install PowerShell 7+.
    pause
    exit /b 1
)

REM Run PowerShell script
echo Running PowerShell startup script...
powershell -ExecutionPolicy Bypass -File "scripts\start-websocket-services.ps1" -Environment local

echo.
echo ✅ WebSocket services startup completed.
echo.
echo 📊 Service URLs:
echo   • Laravel App: http://127.0.0.1:8000
echo   • WebSocket Test: http://127.0.0.1:8000/websocket-test
echo   • Soketi Server: http://127.0.0.1:6001
echo.
echo Press any key to exit...
pause >nul
