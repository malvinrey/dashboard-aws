@echo off
echo Stopping All SCADA Dashboard Services...
echo.

echo 1. Stopping Nginx...
taskkill /f /im nginx.exe 2>nul
if %ERRORLEVEL% EQU 0 (
    echo ✓ Nginx stopped
) else (
    echo - Nginx was not running
)

echo.
echo 2. Stopping PHP-CGI...
taskkill /f /im php-cgi.exe 2>nul
if %ERRORLEVEL% EQU 0 (
    echo ✓ PHP-CGI stopped
) else (
    echo - PHP-CGI was not running
)

echo.
echo 3. MySQL Service Status...
echo Note: MySQL service should be managed through Windows Services
echo To stop MySQL: Stop-Service -Name "MySQL80"
echo To start MySQL: Start-Service -Name "MySQL80"

echo.
echo All services stopped!
echo.
pause
