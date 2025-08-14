@echo off
echo === Redis Status Check ===
echo.

echo 1. Checking Redis Service Status:
sc query Redis >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Redis service found
    sc query Redis | findstr "STATE"
) else (
    echo ❌ Redis service not found
)
echo.

echo 2. Checking Redis Port 6379:
netstat -an | findstr :6379 >nul
if %errorlevel% equ 0 (
    echo ✅ Redis port 6379 is accessible
) else (
    echo ❌ Redis port 6379 is NOT accessible
)
echo.

echo 3. Checking PHP Redis Extension:
php -m 2>nul | findstr redis >nul
if %errorlevel% equ 0 (
    echo ✅ Redis extension is loaded in PHP
) else (
    echo ❌ Redis extension is NOT loaded in PHP
)
echo.

echo 4. PHP Version:
php -v 2>nul
echo.

echo 5. PHP Configuration:
php --ini 2>nul | findstr "Loaded Configuration File"
echo.

echo === End of Check ===
echo.
echo Next steps:
echo 1. Open http://127.0.0.1:8000/test-redis.php in your browser
echo 2. Check the detailed error messages above
echo 3. Install Redis extension if needed
echo.
pause
