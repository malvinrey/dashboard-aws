# WebSocket Implementation untuk SCADA Dashboard

## Overview

Implementasi WebSocket pada SCADA Dashboard memungkinkan real-time data streaming dari server ke client browser. Sistem ini menggunakan Laravel Broadcasting dengan Pusher driver untuk WebSocket communication.

## Architecture

```
[SCADA Data Source] → [ReceiverController] → [ScadaBroadcastingService] → [Event] → [WebSocket Server] → [Browser Client]
```

### Components

1. **ScadaBroadcastingService** - Service untuk broadcasting data
2. **ScadaDataReceived Event** - Event yang di-broadcast
3. **WebSocket Client** - JavaScript client untuk browser
4. **Broadcasting Configuration** - Konfigurasi Pusher/WebSocket
5. **Queue System** - Background processing untuk broadcasting

## Implementation Details

### 1. ScadaBroadcastingService

Service ini bertanggung jawab untuk broadcasting SCADA data ke WebSocket clients.

```php
class ScadaBroadcastingService
{
    // Broadcast single data point
    public function broadcastData($data, $channel = 'scada-data'): bool

    // Broadcast batch data with aggregation
    public function broadcastBatchData($dataArray, $channel = 'scada-batch'): bool

    // Broadcast aggregated data with throttling
    public function broadcastAggregatedData($data, $channel = 'scada-aggregated', $throttleMs = 100): bool
}
```

**Features:**

-   Throttling untuk mencegah firehose
-   Batch processing dengan aggregation
-   Error handling dan logging
-   Channel management

### 2. ScadaDataReceived Event

Event yang di-broadcast ke WebSocket clients.

```php
class ScadaDataReceived implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $data;
    public $channel;

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

**Channels:**

-   `scada-data` - Real-time data updates
-   `scada-batch` - Batch data updates
-   `scada-aggregated` - Aggregated data updates

### 3. WebSocket Client

JavaScript client untuk koneksi WebSocket.

```javascript
class ScadaWebSocketClient {
    constructor(config) {
        this.serverUrl = config.serverUrl;
        this.channel = config.channel;
        this.onConnect = config.onConnect;
        this.onDisconnect = config.onDisconnect;
        this.onMessage = config.onMessage;
        this.onError = config.onError;
    }

    connect() {
        /* ... */
    }
    disconnect() {
        /* ... */
    }
    subscribe(channel) {
        /* ... */
    }
    unsubscribe(channel) {
        /* ... */
    }
}
```

**Features:**

-   Auto-reconnection
-   Channel subscription management
-   Event handling
-   Error handling

### 4. Broadcasting Configuration

Konfigurasi untuk Pusher WebSocket server.

```php
// config/broadcasting.php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'encrypted' => true,
        'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusherapp.com',
        'port' => env('PUSHER_PORT', 443),
        'scheme' => env('PUSHER_SCHEME', 'https')
    ],
],
```

## Setup Instructions

### 1. Environment Configuration

```bash
# .env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

### 2. Install Dependencies

```bash
composer require pusher/pusher-php-server
npm install laravel-echo pusher-js
```

### 3. Start Services

```bash
# Start Laravel WebSocket server
php artisan websockets:serve

# Start queue worker
php artisan queue:work

# Start Laravel application
php artisan serve
```

### 4. Test WebSocket

```bash
# Run test script
php scripts/test_websocket_implementation.php

# Open browser
http://localhost:8000/websocket-test
```

## Usage Examples

### 1. Broadcasting Data

```php
// Via Service
$broadcastingService = app(ScadaBroadcastingService::class);
$broadcastingService->broadcastData($scadaData);

// Via Event
event(new ScadaDataReceived($scadaData, 'scada-data'));
```

### 2. Client Connection

```javascript
const wsClient = new ScadaWebSocketClient({
    serverUrl: "ws://localhost:6001",
    channel: "scada-data",
    onConnect: () => console.log("Connected"),
    onMessage: (data) => updateDashboard(data),
    onError: (error) => console.error("Error:", error),
});

wsClient.connect();
```

### 3. Channel Subscription

```javascript
// Subscribe to specific channel
wsClient.subscribe("scada-data");

// Listen for specific events
Echo.channel("scada-data").listen("ScadaDataReceived", (e) => {
    console.log("Data received:", e.data);
});
```

## Performance Optimization

### 1. Throttling

```php
// Throttle broadcasts to prevent firehose
$broadcastingService->broadcastAggregatedData($data, 'scada-data', 100);
```

### 2. Batch Processing

```php
// Process multiple data points in batch
$broadcastingService->broadcastBatchData($dataArray);
```

### 3. Caching

```php
// Cache throttle timestamps
Cache::put("broadcast_throttle:{$channel}", now()->timestamp, 60);
```

## Error Handling

### 1. Connection Errors

```javascript
wsClient.onError = function (error) {
    console.error("WebSocket error:", error);
    // Implement retry logic
    setTimeout(() => wsClient.connect(), 5000);
};
```

### 2. Broadcasting Errors

```php
try {
    $broadcastingService->broadcastData($data);
} catch (Exception $e) {
    Log::error('Broadcasting failed', ['error' => $e->getMessage()]);
    // Fallback to polling or other methods
}
```

### 3. Queue Failures

```bash
# Monitor queue status
php artisan queue:monitor

# Retry failed jobs
php artisan queue:retry all
```

## Monitoring & Debugging

### 1. Log Files

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# WebSocket logs
tail -f storage/logs/websockets.log
```

### 2. Queue Monitoring

```bash
# Check queue status
php artisan queue:work --verbose

# Monitor queue size
php artisan queue:size
```

### 3. WebSocket Testing

```bash
# Test script
php scripts/test_websocket_implementation.php

# Browser console
# Check WebSocket connection status
# Monitor message flow
```

## Production Deployment

### 1. SSL Configuration

```bash
# Generate SSL certificates
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365

# Update environment
PUSHER_SCHEME=https
PUSHER_PORT=443
```

### 2. Load Balancing

```nginx
# Nginx configuration
upstream websocket {
    server 127.0.0.1:6001;
    server 127.0.0.1:6002;
}

location /app/ {
    proxy_pass http://websocket;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### 3. Monitoring

```bash
# Process monitoring
pm2 start laravel-websockets
pm2 monit

# Performance monitoring
php artisan telescope:install
```

## Troubleshooting

### Common Issues

1. **Connection Refused**

    - Check WebSocket server status
    - Verify port configuration
    - Check firewall settings

2. **Authentication Failed**

    - Verify Pusher credentials
    - Check CSRF token
    - Verify channel permissions

3. **Data Not Broadcasting**

    - Check queue worker status
    - Verify event dispatching
    - Check channel subscription

4. **Performance Issues**
    - Implement throttling
    - Use batch processing
    - Monitor queue size

### Debug Commands

```bash
# Check broadcasting status
php artisan route:list | grep broadcasting

# Test event dispatching
php artisan tinker
event(new App\Events\ScadaDataReceived(['test' => 'data']));

# Monitor WebSocket connections
php artisan websockets:serve --debug
```

## Security Considerations

### 1. Channel Authorization

```php
// Authorize channels
Broadcast::channel('scada-data', function ($user) {
    return true; // Public channel
});

Broadcast::channel('scada-private-{id}', function ($user, $id) {
    return $user->id == $id; // Private channel
});
```

### 2. Data Validation

```php
// Validate incoming data
$validator = Validator::make($data, [
    'temperature' => 'required|numeric|between:-50,100',
    'humidity' => 'required|numeric|between:0,100',
    'pressure' => 'required|numeric|between:800,1200',
]);
```

### 3. Rate Limiting

```php
// Implement rate limiting
RateLimiter::attempt('broadcast:' . $channel, 100, 60);
```

## Future Enhancements

### 1. Advanced Features

-   [ ] Bi-directional communication
-   [ ] File transfer via WebSocket
-   [ ] Real-time collaboration
-   [ ] Mobile app support

### 2. Performance Improvements

-   [ ] WebSocket clustering
-   [ ] Redis pub/sub optimization
-   [ ] Compression algorithms
-   [ ] Connection pooling

### 3. Monitoring & Analytics

-   [ ] Real-time metrics dashboard
-   [ ] Performance analytics
-   [ ] Error tracking
-   [ ] Usage statistics

## References

-   [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
-   [Pusher WebSocket Documentation](https://pusher.com/docs/channels)
-   [Laravel WebSockets Package](https://github.com/beyondcode/laravel-websockets)
-   [WebSocket Protocol Specification](https://tools.ietf.org/html/rfc6455)
