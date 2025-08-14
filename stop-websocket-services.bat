@echo off
chcp 65001 >nul
echo 🛑 Stopping SCADA WebSocket Services...
echo.

REM Check if PowerShell is available
powershell -Command "Get-Host" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PowerShell not available. Please install PowerShell 7+.
    pause
    exit /b 1
)

REM Run PowerShell stop script
echo Running PowerShell stop script...
powershell -ExecutionPolicy Bypass -File "scripts\stop-websocket-services.ps1"

echo.
echo ✅ WebSocket services stopped successfully.
echo.
echo To restart services, run:
echo   start-websocket-services.bat
echo.
pause
