@echo off
echo Stopping SCADA WebSocket Services dengan Laravel Reverb...
echo.

REM Check if PowerShell is available
powershell -Command "Get-Host" >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: PowerShell is not available
    pause
    exit /b 1
)

REM Run the PowerShell stop script
powershell -ExecutionPolicy Bypass -File "scripts\stop-websocket-services-reverb.ps1"

echo.
echo Script completed.
pause
