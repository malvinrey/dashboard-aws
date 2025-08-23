# 03. WebSocket Implementation - Status & Setup

## 🔌 WebSocket Implementation Status

### Overview

WebSocket infrastructure sudah diimplementasi di level Laravel (events, services, configuration) dan JavaScript client sudah siap dengan Laravel Echo + Pusher.js, tetapi **Soketi server belum running**. Ini menyebabkan error `WebSocket connection to 'ws://127.0.0.1:6001/... failed`.

### ✅ **IMPLEMENTED COMPONENTS**

#### 1. Laravel Broadcasting Infrastructure

```php
// app/Events/ScadaDataReceived.php
class ScadaDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $scadaData;
    public $timestamp;
    public $channel;
    public $batchId;

    public function broadcastOn()
    {
        return new Channel($this->channel);
    }

    public function broadcastAs()
    {
        return 'scada.data.received';
    }
}
```

#### 2. Broadcasting Service

```php
// app/Services/ScadaBroadcastingService.php
class ScadaBroadcastingService
{
    public function broadcastData($data, $channel = 'scada-data'): bool
    public function broadcastBatchData($dataArray, $channel = 'scada-batch'): bool
    public function broadcastAggregatedData($data, $channel = 'scada-aggregated', $throttleMs = 100): bool
}
```

#### 3. Broadcasting Configuration

```php
// config/broadcasting.php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'host' => env('PUSHER_HOST', '127.0.0.1'),
        'port' => env('PUSHER_PORT', 6001),
        'scheme' => env('PUSHER_SCHEME', 'http'),
    ],
],
```

#### 4. WebSocket Client JavaScript (Updated)

```javascript
// public/js/scada-websocket-client.js
class ScadaWebSocketClient {
    constructor(config = {}) {
        this.config = {
            host: config.host || "127.0.0.1",
            port: config.port || 6001,
            appKey: config.appKey || "scada_dashboard_key_2024",
            cluster: config.cluster || "mt1",
            forceTLS: config.forceTLS || false,
            onConnect: config.onConnect || (() => {}),
            onMessage: config.onMessage || (() => {}),
            onError: config.onError || (() => {}),
            onDisconnect: config.onDisconnect || (() => {}),
        };

        this.initializeEcho(); // Langsung inisialisasi Echo
    }

    initializeEcho() {
        if (typeof Pusher === "undefined" || typeof Echo === "undefined") {
            console.error(
                "Pusher.js atau Laravel Echo tidak termuat. Inisialisasi dibatalkan."
            );
            return;
        }

        // Cek jika Echo sudah ada dan berfungsi, jangan buat lagi.
        if (window.Echo && typeof window.Echo.socketId === "function") {
            console.log("Laravel Echo sudah diinisialisasi.");
            this.bindEvents(); // Cukup ikat event-nya saja
            return;
        }

        console.log("Menginisialisasi instance Laravel Echo baru...");
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
            console.log("Instance Laravel Echo berhasil diinisialisasi.");
        } catch (e) {
            console.error("Gagal menginisialisasi Laravel Echo:", e);
            if (typeof this.config.onError === "function") {
                this.config.onError(e);
            }
        }
    }

    subscribe(channelName, eventName, callback) {
        if (!window.Echo) {
            console.error("Echo belum diinisialisasi. Tidak bisa subscribe.");
            return;
        }

        console.log(
            `Subscribe ke channel: ${channelName}, event: ${eventName}`
        );
        window.Echo.channel(channelName).listen(eventName, callback);
    }
}
```

#### 5. Livewire WebSocket Integration

```php
// app/Livewire/AnalysisChart.php
class AnalysisChart extends Component
{
    public string $websocketStatus = 'disconnected';
    public array $websocketData = [];

    protected $listeners = [
        'echo:scada-data,scada.data.received' => 'handleWebSocketData',
        'echo:scada-realtime,scada.data.received' => 'handleRealtimeData',
        'websocket-status-updated' => 'updateWebSocketStatus'
    ];
}
```

### ❌ **MISSING COMPONENT: SOKETI SERVER**

#### Current Status

-   **Soketi Package**: ✅ Installed (`@soketi/soketi v1.6.1`)
-   **Soketi Server**: ❌ Not running
-   **Port 6001**: ❌ Not listening
-   **WebSocket Connection**: ❌ Failed

#### Error Message

```
WebSocket connection to 'ws://127.0.0.1:6001/app/scada_dashboard_key_2024' failed
```

### 🚨 **PERMASALAHAN SOKETI YANG DITEMUKAN**

#### 1. Port 6001 Tidak Terbaca

**Problem**: Meskipun konfigurasi `soketi.json` sudah benar dengan port 6001, server Soketi tidak bisa diakses di port tersebut.

**Root Cause Analysis**:

-   Soketi server tidak running
-   Port 6001 tidak listening
-   Service startup script tidak berhasil menjalankan Soketi

#### 2. Dependensi Package

**Current Dependencies** (dari `package.json`):

```json
{
    "dependencies": {
        "@soketi/soketi": "^1.6.1",
        "laravel-echo": "^1.15.3",
        "pusher-js": "^8.4.0"
    }
}
```

**Status**: ✅ **COMPLETE** - Semua package sudah terinstall dengan versi terbaru

#### 3. Konfigurasi Soketi

**File**: `soketi.json`

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
    }
}
```

**Status**: ✅ **CORRECT** - Konfigurasi sudah benar

### 🚀 **SOLUTION: START SOKETI SERVER**

#### Step 1: Use Fixed Startup Scripts

```cmd
# Option 1: All Services (Recommended)
start-all-services-fixed.bat

# Option 2: WebSocket Services Only
start-websocket-services.bat

# Option 3: PowerShell
.\scripts\start-all-services-fixed.ps1
```

#### Step 2: Verify Soketi is Running

```cmd
# Check if port 6001 is listening
netstat -an | findstr :6001

# Check if Soketi process is running
tasklist | findstr node

# Check Soketi executable
dir node_modules\.bin\soketi*
```

#### Step 3: Test WebSocket Connection

Buka browser dan akses:

```
http://localhost:8000/test-websocket-client.html
```

### 🔧 **TROUBLESHOOTING SOKETI**

#### 1. Soketi Not Starting

**Problem**: Server Soketi tidak bisa dijalankan

**Debug Steps**:

```bash
# Check if Soketi is installed
ls node_modules\.bin\soketi*

# Reinstall if needed
npm install @soketi/soketi

# Check Node.js version (requires Node.js 18+)
node --version

# Try manual start
cd node_modules\.bin
./soketi start --config=../../soketi.json
```

#### 2. Port Already in Use

**Problem**: Port 6001 sudah digunakan oleh aplikasi lain

**Debug Steps**:

```bash
# Check what's using port 6001
netstat -ano | findstr :6001

# Kill the process
taskkill /f /pid <PID>

# Alternative: Use different port
# Edit soketi.json and change port to 6002
```

#### 3. Redis Connection Issues

**Problem**: Soketi tidak bisa connect ke Redis

**Debug Steps**:

```bash
# Check Redis status
redis-cli ping

# Start Redis if needed
redis-server --port 6379

# Check Redis connection from Soketi
redis-cli -h 127.0.0.1 -p 6379 ping
```

#### 4. Permission Issues

**Problem**: Soketi tidak bisa bind ke port 6001

**Debug Steps**:

```bash
# Run as Administrator
# Check Windows Firewall
# Check antivirus blocking

# Alternative: Use higher port (>1024)
# Edit soketi.json: "port": 8080
```

### 📊 **WEB SOCKET CHANNELS**

#### 1. Available Channels

-   **`scada-data`**: Real-time data updates
-   **`scada-batch`**: Batch data updates
-   **`scada-aggregated`**: Aggregated data updates
-   **`scada-realtime`**: Live dashboard updates

#### 2. Event Types

-   **`scada.data.received`**: New data received
-   **`scada.batch.processed`**: Batch processing completed
-   **`scada.aggregated.updated`**: Aggregated data updated

### 🔄 **REAL-TIME DATA FLOW**

#### 1. Data Ingestion Flow

```
[SCADA Data] → [ReceiverController] → [ScadaBroadcastingService] → [Event] → [Soketi] → [Browser]
```

#### 2. Broadcasting Flow

```php
// 1. Data received
$broadcastingService->broadcastData($scadaData, 'scada-data');

// 2. Event dispatched
ScadaDataReceived::dispatch($scadaData, 'scada-data');

// 3. Event broadcasted via Soketi
// 4. Browser receives update via WebSocket
```

### 🧪 **TESTING WEB SOCKET**

#### 1. Manual Testing

```bash
# Start Soketi manually
cd node_modules\.bin
./soketi start --config=../../soketi.json
```

#### 2. Test Page

```
http://localhost:8000/test-websocket-client.html
```

#### 3. Browser Console

```javascript
// Test WebSocket connection
const ws = new WebSocket("ws://127.0.0.1:6001/app/scada_dashboard_key_2024");
ws.onopen = () => console.log("Connected!");
ws.onmessage = (event) => console.log("Message:", event.data);
```

#### 4. Laravel Echo Test

```javascript
// Test Laravel Echo
if (window.Echo) {
    console.log("Echo available:", window.Echo);
    window.Echo.channel("scada-data").listen("scada.data.received", (e) => {
        console.log("Data received:", e);
    });
}
```

### 📈 **PERFORMANCE FEATURES**

#### 1. Throttling

-   **Broadcast Throttling**: 100ms minimum interval
-   **Batch Processing**: Efficient handling of large datasets
-   **Connection Pooling**: Multiple WebSocket connections

#### 2. Scalability

-   **Horizontal Scaling**: Multiple Soketi instances
-   **Load Balancing**: Redis-based pub/sub
-   **Memory Management**: Efficient data handling

### 🔮 **FUTURE ENHANCEMENTS**

#### 1. Planned Features

-   **SSL/TLS Support**: Secure WebSocket connections
-   **Authentication**: User-based channel subscriptions
-   **Metrics Dashboard**: WebSocket performance monitoring

#### 2. Production Ready

-   **PM2 Integration**: Process management
-   **Logging**: Comprehensive WebSocket logging
-   **Health Checks**: Connection health monitoring

### 📋 **CHECKLIST STARTUP SOKETI**

#### Pre-Startup Checks

-   [ ] Node.js 18+ installed
-   [ ] Redis server running on port 6379
-   [ ] `@soketi/soketi` package installed
-   [ ] `soketi.json` configuration correct
-   [ ] Port 6001 available

#### Startup Steps

-   [ ] Run `start-all-services-fixed.bat`
-   [ ] Check Soketi process running
-   [ ] Verify port 6001 listening
-   [ ] Test WebSocket connection
-   [ ] Verify Laravel Echo working

#### Post-Startup Verification

-   [ ] WebSocket test page accessible
-   [ ] Browser console shows "Connected!"
-   [ ] Real-time data flowing
-   [ ] No connection errors
-   [ ] Performance monitoring active

---

**Status**: 🟡 **PARTIALLY IMPLEMENTED** - Infrastructure ready, server needs to start
**Next Step**: Run `start-all-services-fixed.bat` to start Soketi server
**Last Updated**: January 2025
**Version**: 0.9.0
**Package Dependencies**: ✅ **COMPLETE** - @soketi/soketi v1.6.1, laravel-echo v1.15.3, pusher-js v8.4.0
**Critical Issue**: 🚨 **SOKETI SERVER NOT RUNNING** - Port 6001 not accessible
**Solution**: Use startup script `start-all-services-fixed.bat`
