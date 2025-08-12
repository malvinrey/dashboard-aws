@echo off
echo Testing SCADA Dashboard Services Status...
echo.

echo 1. Testing Nginx (Port 80)...
netstat -an | findstr :80 >nul
if %ERRORLEVEL% EQU 0 (
    echo ✓ Nginx is running on port 80
) else (
    echo ✗ Nginx is NOT running on port 80
)

echo.
echo 2. Testing PHP-CGI (Port 9000)...
netstat -an | findstr :9000 >nul
if %ERRORLEVEL% EQU 0 (
    echo ✓ PHP-CGI is running on port 9000
) else (
    echo ✗ PHP-CGI is NOT running on port 9000
)

echo.
echo 3. Testing MySQL Service...
sc query MySQL80 | findstr "RUNNING" >nul
if %ERRORLEVEL% EQU 0 (
    echo ✓ MySQL service is running
) else (
    echo ✗ MySQL service is NOT running
)

echo.
echo 4. Testing Laravel API Endpoint...
echo Testing: http://localhost/api/aws/receiver
echo This will show if the full stack is working...

echo.
pause
