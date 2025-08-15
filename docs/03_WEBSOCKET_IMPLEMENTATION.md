# 03. WebSocket Implementation - Status & Setup

## 🔌 WebSocket Implementation Status

### Overview

WebSocket infrastructure sudah diimplementasi di level Laravel (events, services, configuration) tetapi **Soketi server belum running**. Ini menyebabkan error `WebSocket connection to 'ws://127.0.0.1:6001/... failed`.

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

#### 4. WebSocket Client JavaScript

```javascript
// public/js/scada-websocket-client.js
class ScadaWebSocketClient {
    constructor(options = {}) {
        this.options = {
            url: "ws://127.0.0.1:6001/app/scada_dashboard_key_2024",
            reconnectAttempts: 10,
            reconnectDelay: 1000,
            heartbeatInterval: 30000,
        };
    }

    connect() {
        /* ... */
    }
    subscribe(channel) {
        /* ... */
    }
    send(message) {
        /* ... */
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

-   **Soketi Package**: ✅ Installed (`npm install @soketi/soketi`)
-   **Soketi Server**: ❌ Not running
-   **Port 6001**: ❌ Not listening
-   **WebSocket Connection**: ❌ Failed

#### Error Message

```
WebSocket connection to 'ws://127.0.0.1:6001/app/scada_dashboard_key_2024' failed
```

### 🚀 **SOLUTION: START SOKETI SERVER**

#### Step 1: Use Fixed Startup Scripts

```cmd
# Option 1: Simple WebSocket Services
start-websocket-services.bat

# Option 2: All Services (Recommended)
start-all-services-fixed.bat

# Option 3: PowerShell
.\scripts\start-all-services-fixed.ps1
```

#### Step 2: Verify Soketi is Running

```cmd
# Check if port 6001 is listening
netstat -an | findstr :6001

# Check if Soketi process is running
tasklist | findstr node
```

#### Step 3: Test WebSocket Connection

Buka browser dan akses:

```
http://localhost:8000/test-websocket-client.html
```

### 🔧 **CONFIGURATION FILES**

#### 1. Soketi Configuration

```json
// soketi.json
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
    }
}
```

#### 2. Environment Variables

```env
# .env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=12345
PUSHER_APP_KEY=scada_dashboard_key_2024
PUSHER_APP_SECRET=scada_dashboard_secret_2024
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_ENCRYPTED=false
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
cd node_modules/.bin
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

### 🚨 **TROUBLESHOOTING**

#### 1. Soketi Not Starting

```bash
# Check if Soketi is installed
ls node_modules/.bin/soketi*

# Reinstall if needed
npm install @soketi/soketi

# Check Node.js version (requires Node.js 18+)
node --version
```

#### 2. Port Already in Use

```bash
# Check what's using port 6001
netstat -ano | findstr :6001

# Kill the process
taskkill /f /pid <PID>
```

#### 3. Redis Connection Issues

```bash
# Check Redis status
redis-cli ping

# Start Redis if needed
redis-server --port 6379
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

---

**Status**: 🟡 **PARTIALLY IMPLEMENTED** - Infrastructure ready, server needs to start
**Next Step**: Run `start-all-services-fixed.bat` to start Soketi server
**Last Updated**: January 2025
**Version**: 0.9.0
