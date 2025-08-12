@echo off
echo Gracefully stopping Nginx for SCADA Dashboard...
echo.

rem Pindah ke direktori instalasi Nginx
cd /d "C:\nginx"

rem Kirim sinyal stop dengan path prefix yang benar
nginx.exe -p "D:\dashboard-aws" -s stop

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ Nginx stop signal sent successfully.
) else (
    echo.
    echo ✗ Failed to send stop signal!
    echo Check if Nginx is running from this configuration.
)

echo.
pause
