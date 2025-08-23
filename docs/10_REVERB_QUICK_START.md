# Laravel Reverb Quick Start Guide

## Overview

Guide ini memberikan langkah cepat untuk menjalankan Laravel Reverb WebSocket server untuk SCADA Dashboard.

## Prerequisites

✅ Laravel Reverb sudah terinstall (`composer require laravel/reverb`)  
✅ File konfigurasi sudah diperbaiki (`.env`, `config/broadcasting.php`, `resources/js/bootstrap.js`)  
✅ Redis server running di port 6379

## Quick Start

### 1. Start Services dengan Script PowerShell

```powershell
# Menggunakan script yang sudah dibuat
.\scripts\start-websocket-services-reverb.ps1

# Atau menggunakan batch file
.\start-websocket-services-reverb.bat
```

### 2. Start Services Manual

Jika ingin menjalankan manual:

```bash
# Terminal 1: Start Redis (jika belum running)
redis-server --port 6379

# Terminal 2: Start Laravel Reverb
php artisan reverb:start --host=127.0.0.1 --port=8080

# Terminal 3: Start Laravel Queue Worker
php artisan queue:work --connection=redis

# Terminal 4: Start Laravel Development Server
php artisan serve --host=127.0.0.1 --port=8000
```

## Verifikasi Services

### 1. Check Reverb Status

```bash
# Check apakah Reverb running
netstat -an | findstr :8080

# Check Reverb logs
tail -f storage/logs/reverb.out.log
tail -f storage/logs/reverb.err.log
```

### 2. Check Redis

```bash
redis-cli ping
# Should return: PONG
```

### 3. Check Laravel

```bash
# Test queue
php artisan queue:work --once

# Check config
php artisan tinker
>>> config('broadcasting.default') // Should return 'reverb'
```

## Test WebSocket Connection

### 1. Buka Browser

-   Navigate ke: `http://127.0.0.1:8000/websocket-test`
-   Check browser console untuk WebSocket connection

### 2. Test Real-time Broadcasting

```bash
# Di terminal Laravel, test event
php artisan tinker
>>> event(new \App\Events\ScadaDataReceived(['test' => 'data']));
```

## Troubleshooting

### Issue 1: Reverb tidak bisa start

```bash
# Check port availability
netstat -an | findstr :8080

# Check Laravel logs
tail -f storage/logs/laravel.log

# Clear config cache
php artisan config:clear
```

### Issue 2: Frontend tidak bisa connect

-   Check browser console untuk error
-   Verify `.env` variables sudah benar
-   Check firewall settings untuk port 8080

### Issue 3: Broadcasting tidak berfungsi

```bash
# Check broadcast driver
php artisan tinker
>>> config('broadcasting.default')

# Test event
php artisan make:event TestEvent
```

## Service Management

### Start Services

```bash
.\start-websocket-services-reverb.bat
```

### Stop Services

```bash
.\stop-websocket-services-reverb.bat
```

### Check Service Status

```bash
# Check running processes
Get-Process | Where-Object {$_.ProcessName -eq "php"}

# Check PID file
Get-Content temp\websocket-services-reverb.pid
```

## Configuration Files

### .env (Key Variables)

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=12345
REVERB_APP_KEY=scada_dashboard_key_2024
REVERB_APP_SECRET=scada_dashboard_secret_2024
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend variables
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### resources/js/bootstrap.js

```javascript
window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: parseInt(import.meta.env.VITE_REVERB_PORT),
    wssPort: parseInt(import.meta.env.VITE_REVERB_PORT),
    enabledTransports: ["ws", "wss"],
});
```

## Performance Tips

### 1. Monitor Memory Usage

```bash
# Check PHP process memory
Get-Process | Where-Object {$_.ProcessName -eq "php"} | Select-Object Id, ProcessName, WorkingSet
```

### 2. Optimize Redis

```bash
# Check Redis memory
redis-cli info memory

# Monitor Redis performance
redis-cli monitor
```

### 3. Laravel Queue Optimization

```bash
# Run multiple queue workers
php artisan queue:work --connection=redis --tries=3 --timeout=60 &
php artisan queue:work --connection=redis --tries=3 --timeout=60 &
```

## Next Steps

1. **Production Deployment**: Update untuk production environment
2. **SSL/TLS**: Implement HTTPS untuk production
3. **Load Balancing**: Setup multiple Reverb instances jika diperlukan
4. **Monitoring**: Implement proper monitoring dan alerting
5. **Backup**: Setup backup strategy untuk konfigurasi

---

**Note**: Guide ini berdasarkan konfigurasi yang sudah diperbaiki. Pastikan semua file konfigurasi sudah diupdate sesuai dengan dokumentasi migrasi.
