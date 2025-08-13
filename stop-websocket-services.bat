@echo off
title Stop WebSocket Services

echo ========================================
echo    Stop WebSocket Services Script
echo ========================================
echo.

echo Stopping WebSocket Services...
echo.

REM Stop Laravel WebSocket server
echo [1/4] Stopping Laravel WebSocket Server...
taskkill /f /im php.exe /fi "WINDOWTITLE eq WebSocket Server*" >nul 2>&1
echo   WebSocket Server stopped.

REM Stop Queue Worker
echo [2/4] Stopping Queue Worker...
taskkill /f /im php.exe /fi "WINDOWTITLE eq Queue Worker*" >nul 2>&1
echo   Queue Worker stopped.

REM Stop Laravel Application
echo [3/4] Stopping Laravel Application...
taskkill /f /im php.exe /fi "WINDOWTITLE eq Laravel App*" >nul 2>&1
echo   Laravel Application stopped.

REM Stop Nginx
echo [4/4] Stopping Nginx...
taskkill /f /im nginx.exe >nul 2>&1
if exist "nginx\stop-nginx.bat" (
    call "nginx\stop-nginx.bat" >nul 2>&1
)
echo   Nginx stopped.

echo.
echo ========================================
echo    All Services Stopped Successfully!
echo ========================================
echo.
echo Services stopped:
echo - Laravel WebSocket Server
echo - Queue Worker
echo - Laravel Application
echo - Nginx
echo.
echo Press any key to exit...
pause >nul
