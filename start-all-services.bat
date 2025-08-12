@echo off
echo Starting SCADA Dashboard Services...
echo.

echo 1. Starting PHP-CGI on port 9000...
echo Please keep this terminal open for PHP-CGI to run...
echo.
cd C:\php
start "PHP-CGI" cmd /k "php-cgi.exe -b 127.0.0.1:9000"

echo 2. Starting Nginx...
echo.
cd /d "D:\dashboard-aws\nginx"
call start-nginx.bat

echo.
echo All services started!
echo - PHP-CGI: Running on port 9000
echo - Nginx: Running on port 80
echo - MySQL: Should be running as service
echo.
echo Keep the PHP-CGI terminal open!
echo.
pause
