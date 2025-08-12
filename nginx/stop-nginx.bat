@echo off
echo Stopping Nginx for SCADA Dashboard...
echo.

echo Checking if Nginx is running...
tasklist /FI "IMAGENAME eq nginx.exe" 2>NUL | find /I /N "nginx.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo Stopping Nginx...
    taskkill /f /im nginx.exe
    echo ✓ Nginx stopped successfully!
) else (
    echo Nginx is not running.
)

echo.
pause
