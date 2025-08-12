@echo off
echo Stopping Nginx for SCADA Dashboard...
echo.

cd nginx
call stop-nginx.bat
