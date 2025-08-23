# Migrasi dari Soketi ke Laravel Reverb

## Overview

Dokumen ini menjelaskan langkah-langkah lengkap untuk migrasi dari Soketi (Node.js-based WebSocket server) ke Laravel Reverb (PHP-based WebSocket server) pada project SCADA Dashboard.

## Mengapa Migrasi ke Reverb?

### Keuntungan Laravel Reverb:

✅ **100% PHP Native**: Tidak perlu Node.js, npm, atau package manager tambahan  
✅ **Integrasi Seamless**: Install via Composer seperti package Laravel lainnya  
✅ **Performa Tinggi**: Built on high-performance PHP event loop  
✅ **Pusher Protocol Compatible**: Bekerja dengan existing Laravel Echo dan Pusher.js  
✅ **Maintenance Lebih Mudah**: Satu bahasa pemrograman untuk backend dan WebSocket  
✅ **Dependency Management**: Tidak ada konflik versi Node.js

### Masalah dengan Soketi:

❌ **Node.js Dependency**: Perlu manage npm, node_modules, nvm  
❌ **Environment Issues**: Konflik versi Node.js, dependency hell  
❌ **Setup Complexity**: Perlu setup terpisah untuk WebSocket server  
❌ **Maintenance Overhead**: Dua ecosystem berbeda (PHP + Node.js)

## Prerequisites

Sebelum memulai migrasi, pastikan:

-   Laravel project sudah berjalan dengan baik
-   Redis server sudah terinstall dan running
-   Composer tersedia di sistem
-   PHP 8.1+ dengan extensions yang diperlukan

## Langkah 1: Install Laravel Reverb

### 1.1 Install Package via Composer

```bash
composer require laravel/reverb
```

### 1.2 Install Reverb Configuration

```bash
php artisan reverb:install
```

Command ini akan membuat file `config/reverb.php` dengan konfigurasi default.

## Langkah 2: Update Environment Variables

### 2.1 Update .env File

```env
# Broadcasting Configuration
BROADCAST_DRIVER=reverb

# Laravel Reverb Configuration
REVERB_SERVER=reverb
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Reverb App Configuration
REVERB_APP_ID=12345
REVERB_APP_KEY=scada_dashboard_key_2024
REVERB_APP_SECRET=scada_dashboard_secret_2024

# Legacy Pusher Configuration (untuk kompatibilitas)
PUSHER_APP_ID=12345
PUSHER_APP_KEY=scada_dashboard_key_2024
PUSHER_APP_SECRET=scada_dashboard_secret_2024
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=8080
PUSHER_SCHEME=http
PUSHER_APP_ENCRYPTED=false
```

### 2.2 Perubahan Utama:

-   `BROADCAST_DRIVER` dari `pusher` ke `reverb`
-   Port berubah dari `6001` (Soketi) ke `8080` (Reverb)
-   Tambahan konfigurasi `REVERB_*` variables

## Langkah 3: Update Broadcasting Configuration

### 3.1 Update config/broadcasting.php

```php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST', '127.0.0.1'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
        ],
    ],

    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => env('PUSHER_APP_ENCRYPTED', false),
            'host' => env('PUSHER_HOST', '127.0.0.1'),
            'port' => env('PUSHER_PORT', 8080), // Updated port
            'scheme' => env('PUSHER_SCHEME', 'http'),
            'useTLS' => env('PUSHER_SCHEME', 'http') === 'https',
            'curl_options' => [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ],
        ],
    ],
    // ... other connections
],
```

## Langkah 4: Update Frontend JavaScript

### 4.1 Update resources/js/bootstrap.js

```javascript
window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,

    // Update port dari 6001 ke 8080
    wsHost: window.location.hostname,
    wsPort: 8080, // Port Reverb
    wssPort: 8080, // Port Reverb
    forceTLS: false,
    enabledTransports: ["ws", "wss"],
    disableStats: true,
});
```

### 4.2 Update Vite Environment Variables

Pastikan `VITE_PUSHER_PORT` di `.env` sudah diupdate ke `8080`.

## Langkah 5: Update Service Scripts

### 5.1 Script Baru untuk Reverb

Kami telah membuat script baru khusus untuk Reverb:

-   **Start Script**: `scripts/start-websocket-services-reverb.ps1`
-   **Stop Script**: `scripts/stop-websocket-services-reverb.ps1`
-   **Batch Files**: `start-websocket-services-reverb.bat`, `stop-websocket-services-reverb.bat`

### 5.2 Perbedaan dengan Script Soketi:

-   Menggunakan `php artisan reverb:start` bukan `soketi start`
-   Port monitoring berubah dari `6001` ke `8080`
-   Environment variables disesuaikan untuk Reverb
-   PID file terpisah: `websocket-services-reverb.pid`

## Langkah 6: Testing dan Verifikasi

### 6.1 Start Services

```bash
# Menggunakan PowerShell
.\scripts\start-websocket-services-reverb.ps1

# Atau menggunakan batch file
.\start-websocket-services-reverb.bat
```

### 6.2 Verifikasi Services

```bash
# Check Reverb status
php artisan reverb:status

# Check Redis
redis-cli ping

# Check Laravel
php artisan queue:work --once
```

### 6.3 Test WebSocket Connection

-   Buka `http://127.0.0.1:8000/websocket-test`
-   Pastikan connection ke port 8080 berhasil
-   Test real-time data broadcasting

## Langkah 7: Cleanup dan Maintenance

### 7.1 Hapus Soketi Dependencies

```bash
# Uninstall Soketi (optional)
npm uninstall @soketi/soketi

# Hapus file konfigurasi Soketi
rm soketi.json
```

### 7.2 Update Documentation

-   Update semua referensi port 6001 ke 8080
-   Update troubleshooting guides
-   Update deployment scripts

## Troubleshooting

### Common Issues dan Solutions:

#### 1. Reverb tidak bisa start

```bash
# Check logs
tail -f storage/logs/reverb.err.log

# Check port availability
netstat -an | findstr :8080

# Restart dengan verbose mode
php artisan reverb:start --verbose
```

#### 2. Frontend tidak bisa connect

-   Pastikan port 8080 tidak diblokir firewall
-   Check browser console untuk error WebSocket
-   Verify environment variables di frontend

#### 3. Broadcasting tidak berfungsi

```bash
# Check broadcast driver
php artisan tinker
>>> config('broadcasting.default') // Should return 'reverb'

# Test event broadcasting
php artisan make:event TestEvent
```

#### 4. Port conflicts

```bash
# Check what's using port 8080
netstat -ano | findstr :8080

# Kill process if necessary
taskkill /PID <PID> /F
```

## Performance Comparison

### Before (Soketi):

-   WebSocket Server: Node.js process
-   Memory Usage: ~50-100MB per instance
-   Startup Time: 2-5 seconds
-   Dependencies: Node.js + npm packages

### After (Reverb):

-   WebSocket Server: PHP process
-   Memory Usage: ~30-60MB per instance
-   Startup Time: 1-3 seconds
-   Dependencies: PHP only

## Migration Checklist

-   [ ] Install Laravel Reverb package
-   [ ] Update environment variables
-   [ ] Update broadcasting configuration
-   [ ] Update frontend JavaScript
-   [ ] Create new service scripts
-   [ ] Test WebSocket connection
-   [ ] Verify real-time broadcasting
-   [ ] Update documentation
-   [ ] Clean up old Soketi files
-   [ ] Performance testing
-   [ ] Rollback plan preparation

## Rollback Plan

Jika ada masalah dengan Reverb, Anda bisa rollback ke Soketi:

1. Restore `.env` file lama
2. Restore `config/broadcasting.php` lama
3. Restore `resources/js/bootstrap.js` lama
4. Restart services dengan script Soketi lama

## Best Practices

### 1. Environment Management

-   Gunakan environment-specific configs
-   Test di staging sebelum production
-   Backup konfigurasi lama

### 2. Monitoring

-   Monitor memory usage Reverb
-   Check WebSocket connection count
-   Monitor queue performance

### 3. Security

-   Restrict Reverb port access jika diperlukan
-   Use HTTPS di production
-   Implement proper authentication

## Conclusion

Migrasi dari Soketi ke Laravel Reverb memberikan solusi yang lebih terintegrasi dan mudah di-maintain. Dengan menghilangkan dependency Node.js, development dan deployment menjadi lebih straightforward.

### Next Steps:

1. Test di development environment
2. Performance benchmarking
3. Production deployment planning
4. Team training untuk maintenance

---

**Note**: Dokumentasi ini akan diupdate sesuai dengan feedback dan experience menggunakan Laravel Reverb di production environment.
