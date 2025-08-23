# 08. WebSocket Soketi Troubleshooting

## 🚨 Critical Issue: Port 6001 Tidak Terbaca

### Problem Description

```
WebSocket connection to 'ws://127.0.0.1:6001/app/scada_dashboard_key_2024' failed
```

### Root Cause

-   ✅ Konfigurasi `soketi.json` sudah benar
-   ✅ Package `@soketi/soketi` sudah terinstall (v1.6.1)
-   ❌ **Soketi server tidak running**
-   ❌ **Port 6001 tidak listening**

### Solution: Enhanced Startup Script

**File**: `start-all-services-fixed.bat`

```batch
@echo off
chcp 65001 >nul
title Start All Services for WebSocket Fix

echo Starting All Services for WebSocket Fix...

REM Stop existing services first
taskkill /f /im php.exe >nul 2>&1
taskkill /f /im nginx.exe >nul 2>&1
taskkill /f /im redis-server.exe >nul 2>&1
taskkill /f /im node.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM Start Redis
start "Redis Server" /min redis-server.exe
timeout /t 3 /nobreak >nul

REM Start PHP-FPM
start "PHP-FPM" /min php-cgi.exe -b 127.0.0.1:9000
timeout /t 3 /nobreak >nul

REM Start Nginx
if exist "nginx\nginx.exe" (
    start "Nginx" /min nginx\nginx.exe -c nginx\config\nginx.conf
    timeout /t 3 /nobreak >nul
)

REM Start Laravel Queue
start "Laravel Queue" /min php.exe artisan queue:work --sleep=3 --tries=3 --max-time=3600
timeout /t 3 /nobreak >nul

REM Start Soketi WebSocket Server
if exist "node_modules\.bin\soketi.cmd" (
    start "Soketi" /min node_modules\.bin\soketi.cmd start --config=soketi.json
    timeout /t 3 /nobreak >nul
)

REM Start Laravel Server
start "Laravel Server" /min php.exe artisan serve --host=0.0.0.0 --port=8000
timeout /t 10 /nobreak >nul

REM Show service status
echo Service Status Summary:
netstat -an | findstr ":6379" >nul && echo Redis: ✅ RUNNING || echo Redis: ❌ NOT RUNNING
netstat -an | findstr ":6001" >nul && echo Soketi: ✅ RUNNING || echo Soketi: ❌ NOT RUNNING
netstat -an | findstr ":8000" >nul && echo Laravel: ✅ RUNNING || echo Laravel: ❌ NOT RUNNING

echo All services started!
pause
```

### Troubleshooting Steps

#### 1. Pre-Startup Verification

```bash
# Check Node.js version (requires 18+)
node --version

# Check Soketi package
npm list @soketi/soketi

# Check Redis status
redis-cli ping

# Check port availability
netstat -an | findstr :6001
```

#### 2. Manual Soketi Start

```bash
cd node_modules\.bin
soketi start --config=../../soketi.json
```

#### 3. Test WebSocket

```
http://localhost:8000/test-websocket-client.html
```

### Common Errors & Solutions

#### Error: "Soketi executable not found"

```bash
npm uninstall @soketi/soketi
npm install @soketi/soketi
```

#### Error: "Port 6001 already in use"

```bash
netstat -ano | findstr :6001
taskkill /f /pid <PID>
```

#### Error: "Redis connection failed"

```bash
redis-server --port 6379
redis-cli ping
```

### Enhanced WebSocket Client

**File**: `public/js/scada-websocket-client.js`

```javascript
class ScadaWebSocketClient {
    constructor(config = {}) {
        this.config = {
            host: config.host || "127.0.0.1",
            port: config.port || 6001,
            appKey: config.appKey || "scada_dashboard_key_2024",
            cluster: config.cluster || "mt1",
            forceTLS: config.forceTLS || false,
            onConnect: config.onConnect || (() => {}),
            onError: config.onError || (() => {}),
        };

        this.initializeEcho();
    }

    initializeEcho() {
        if (typeof Pusher === "undefined" || typeof Echo === "undefined") {
            console.error("Pusher.js atau Laravel Echo tidak termuat.");
            return;
        }

        try {
            window.Echo = new Echo({
                broadcaster: "pusher",
                key: this.config.appKey,
                cluster: this.config.cluster,
                wsHost: this.config.host,
                wsPort: this.config.port,
                wssPort: this.config.port,
                forceTLS: this.config.forceTLS,
                enabledTransports: ["ws", "wss"],
                disableStats: true,
            });

            this.bindEvents();
            console.log("Laravel Echo berhasil diinisialisasi.");
        } catch (e) {
            console.error("Gagal menginisialisasi Laravel Echo:", e);
        }
    }

    bindEvents() {
        const pusher = window.Echo.connector.pusher;

        pusher.connection.bind("connected", () => {
            console.log("✅ Berhasil terhubung ke WebSocket via Echo/Pusher.");
            if (typeof this.config.onConnect === "function") {
                this.config.onConnect();
            }
        });

        pusher.connection.bind("error", (error) => {
            console.error("Error koneksi WebSocket:", error);
            if (typeof this.config.onError === "function") {
                this.config.onError(error);
            }
        });
    }

    subscribe(channelName, eventName, callback) {
        if (!window.Echo) {
            console.error("Echo belum diinisialisasi.");
            return;
        }

        console.log(
            `Subscribe ke channel: ${channelName}, event: ${eventName}`
        );
        window.Echo.channel(channelName).listen(eventName, callback);
    }
}
```

### Configuration Files

#### soketi.json

```json
{
    "appId": "12345",
    "appKey": "scada_dashboard_key_2024",
    "appSecret": "scada_dashboard_secret_2024",
    "port": 6001,
    "host": "0.0.0.0",
    "database": {
        "redis": {
            "host": "127.0.0.1",
            "port": 6379,
            "password": null,
            "db": 0
        }
    },
    "cors": {
        "origin": ["http://localhost:8000", "http://127.0.0.1:8000"],
        "methods": ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
        "allowedHeaders": [
            "Content-Type",
            "X-Requested-With",
            "Authorization",
            "X-CSRF-TOKEN"
        ]
    },
    "debug": true,
    "metrics": {
        "enabled": true,
        "driver": "memory"
    }
}
```

#### config/broadcasting.php

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'encrypted' => env('PUSHER_APP_ENCRYPTED', false),
        'host' => env('PUSHER_HOST', '127.0.0.1'),
        'port' => env('PUSHER_PORT', 6001),
        'scheme' => env('PUSHER_SCHEME', 'http'),
        'useTLS' => env('PUSHER_SCHEME', 'http') === 'https',
    ],
],
```

### Package Dependencies

**package.json**:

```json
{
    "dependencies": {
        "@soketi/soketi": "^1.6.1",
        "laravel-echo": "^1.15.3",
        "pusher-js": "^8.4.0"
    }
}
```

### Quick Test Commands

```bash
# 1. Start all services
start-all-services-fixed.bat

# 2. Check service status
netstat -an | findstr ":6001"

# 3. Test WebSocket
# Open: http://localhost:8000/test-websocket-client.html

# 4. Check browser console
# Should see: "✅ Berhasil terhubung ke WebSocket via Echo/Pusher."
```

### Status Summary

| Component            | Status           | Notes                          |
| -------------------- | ---------------- | ------------------------------ |
| **Soketi Package**   | ✅ Installed     | v1.6.1                         |
| **Configuration**    | ✅ Correct       | soketi.json + broadcasting.php |
| **WebSocket Client** | ✅ Ready         | Laravel Echo + Pusher.js       |
| **Soketi Server**    | ❌ Not Running   | Need startup script            |
| **Port 6001**        | ❌ Not Listening | Server not started             |

### Next Actions

1. **Immediate**: Run `start-all-services-fixed.bat`
2. **Verify**: Check port 6001 listening
3. **Test**: WebSocket connection working
4. **Monitor**: Real-time data flow

---

**Last Updated**: January 2025
**Version**: 1.0.0
**Critical Issue**: 🚨 **SOKETI SERVER NOT RUNNING**
**Solution**: ✅ **IMPLEMENTED** - Enhanced startup scripts
**Status**: 🟡 **READY FOR TESTING**
