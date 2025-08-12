@echo off
echo Starting phpMyAdmin for SCADA Dashboard...
echo.

echo Checking if Nginx is running...
tasklist /FI "IMAGENAME eq nginx.exe" 2>NUL | find /I /N "nginx.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✓ Nginx is already running!
    echo ✓ phpMyAdmin will be available at: http://localhost:8080
) else (
    echo ✗ Nginx is not running!
    echo Please start Nginx first using: start-nginx.bat
    pause
    exit /b 1
)

echo.
echo phpMyAdmin Configuration:
echo - URL: http://localhost:8080
echo - Root: C:\phpmyadmin
echo - Port: 8080
echo.
echo Database Connection:
echo - Host: localhost
echo - Port: 3306
echo - Username: root
echo - Password: anjingbela12
echo - Database: scada_dashboard
echo.
echo To access phpMyAdmin, open: http://localhost:8080
echo.
pause
