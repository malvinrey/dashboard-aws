@echo off
echo === Checking Process ID 45936 ===
echo.

echo Process ID 45936 details:
tasklist /FI "PID eq 45936" /V
echo.

echo All processes with 'redis' in name:
tasklist | findstr -i redis
echo.

echo All processes with 'mem' in name (WSL memory):
tasklist | findstr -i mem
echo.

echo All processes with 'wsl' in name:
tasklist | findstr -i wsl
echo.

echo All processes with 'hyper' in name (Hyper-V):
tasklist | findstr -i hyper
echo.

echo === End of Check ===
pause
