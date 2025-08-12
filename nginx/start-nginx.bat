@echo off
echo Starting Nginx for SCADA Dashboard...
echo.

echo Checking if Nginx is already running...
tasklist /FI "IMAGENAME eq nginx.exe" 2>NUL | find /I /N "nginx.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo Nginx is already running!
    echo Stopping existing Nginx...
    taskkill /f /im nginx.exe
    timeout /t 2 /nobreak >nul
)

echo.
echo Starting Nginx with project configuration...
echo Config file: %CD%\nginx.conf
echo.

cd /d "C:\nginx"
nginx.exe -c "D:\dashboard-aws\nginx\config\nginx.conf" -p "D:\dashboard-aws"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ Nginx started successfully!
    echo ✓ SCADA Dashboard available at: http://localhost
    echo ✓ phpMyAdmin available at: http://localhost:8080
    echo.
    echo To stop Nginx, run: stop-nginx.bat
    echo To check status: tasklist | findstr nginx
) else (
    echo.
    echo ✗ Failed to start Nginx!
    echo Check the error messages above.
)

echo.
pause
