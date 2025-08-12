# Start Services Script
# This script starts PHP-FPM and Nginx services

Write-Host "Starting PHP-FPM..." -ForegroundColor Green
Start-Process -FilePath "C:\php\php-cgi.exe" -ArgumentList "-b", "127.0.0.1:9000", "-c", "D:\dashboard-aws\php-fpm.ini" -WindowStyle Normal

Write-Host "Starting Nginx..." -ForegroundColor Green
Start-Process -FilePath "C:\nginx\nginx.exe" -ArgumentList "-c", "D:\dashboard-aws\nginx\config\nginx.conf", "-p", "D:\dashboard-aws" -WindowStyle Normal

Write-Host "Services started successfully!" -ForegroundColor Green
Write-Host "PHP-FPM running on 127.0.0.1:9000" -ForegroundColor Yellow
Write-Host "Nginx started with config from D:\dashboard-aws\nginx\config\nginx.conf" -ForegroundColor Yellow
