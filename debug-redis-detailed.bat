@echo off
echo === Detailed Redis Debugging ===
echo.

echo 1. What's using port 6379:
netstat -ano | findstr :6379
if %errorlevel% equ 0 (
    echo ✅ Port 6379 is in use
) else (
    echo ❌ Port 6379 is not in use
)
echo.

echo 2. Docker Containers:
docker ps 2>nul | findstr redis
if %errorlevel% equ 0 (
    echo ✅ Redis container found
) else (
    echo ❌ No Redis containers running
)
echo.

echo 3. WSL Processes:
wsl --list --running 2>nul
if %errorlevel% equ 0 (
    echo ✅ WSL instances running
) else (
    echo ❌ No WSL instances running
)
echo.

echo 4. Services with 'redis' in name:
sc query | findstr -i redis
if %errorlevel% equ 0 (
    echo ✅ Redis-related services found
) else (
    echo ❌ No Redis-related services found
)
echo.

echo 5. Redis processes:
tasklist | findstr -i redis
if %errorlevel% equ 0 (
    echo ✅ Redis processes found
) else (
    echo ❌ No Redis processes found
)
echo.

echo 6. Process details for port 6379:
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :6379') do (
    echo Process ID: %%a
    tasklist /FI "PID eq %%a" 2>nul
)
echo.

echo === End of Detailed Debug ===
echo.
echo Summary:
echo If port 6379 is accessible but no Redis service found,
echo Redis is likely running via Docker, WSL, or as a manual process.
echo.
pause
