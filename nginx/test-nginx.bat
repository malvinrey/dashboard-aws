@echo off
echo Testing Nginx configuration for SCADA Dashboard...
echo.

echo Config file: %CD%\config\nginx.conf
echo.

cd /d "C:\nginx"
nginx.exe -t -c "D:\dashboard-aws\nginx\config\nginx.conf" -p "D:\dashboard-aws"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ Configuration test passed!
    echo ✓ You can now run: start-nginx.bat
) else (
    echo.
    echo ✗ Configuration test failed!
    echo Check the error messages above.
)

echo.
pause
