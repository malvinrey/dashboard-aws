# WebSocket Implementation untuk SCADA Dashboard

## 🚀 Overview

Implementasi WebSocket pada SCADA Dashboard memungkinkan real-time data streaming dari server ke client browser. Sistem ini menggunakan Laravel Broadcasting dengan Pusher driver untuk WebSocket communication yang handal dan scalable.

## 🏗️ Architecture

```
[SCADA Data Source] → [ReceiverController] → [ScadaBroadcastingService] → [Event] → [WebSocket Server] → [Browser Client]
```

### Components

1. **ScadaBroadcastingService** - Service untuk broadcasting data dengan throttling dan batch processing
2. **ScadaDataReceived Event** - Event yang di-broadcast ke WebSocket clients
3. **WebSocket Client** - JavaScript client untuk browser dengan auto-reconnection
4. **Broadcasting Configuration** - Konfigurasi Pusher/WebSocket server
5. **Queue System** - Background processing untuk broadcasting

## 📋 Prerequisites

-   PHP 8.1+
-   Laravel 10+
-   Composer
-   Node.js & NPM
-   MySQL/PostgreSQL
-   Redis (optional, untuk caching)

## 🛠️ Installation

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer require pusher/pusher-php-server
composer require beyondcode/laravel-websockets

# Install JavaScript dependencies
npm install laravel-echo pusher-js
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=12345
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 4. Publish WebSocket Configuration

```bash
# Publish WebSocket configuration
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"

# Run WebSocket migrations
php artisan migrate
```

## 🚀 Quick Start

### 1. Start Services

```bash
# Option 1: Use batch file (Windows)
start-websocket-services.bat

# Option 2: Use PowerShell script
.\scripts\start-websocket-services.ps1 -Environment local -Background

# Option 3: Manual start
php artisan websockets:serve
php artisan queue:work
php artisan serve
```

### 2. Test WebSocket

```bash
# Run test script
php scripts/test_websocket_implementation.php

# Run data simulation
php scripts/test_websocket_data_simulation.php

# Open browser
http://localhost:8000/websocket-test
```

### 3. Send Test Data

```bash
# Via API endpoint
curl -X POST http://localhost:8000/api/receiver \
  -H "Content-Type: application/json" \
  -d '{"temperature": 25.5, "humidity": 65.2, "pressure": 1013.25}'
```

## 📁 File Structure

```
dashboard-aws/
├── app/
│   ├── Services/
│   │   └── ScadaBroadcastingService.php    # Broadcasting service
│   ├── Events/
│   │   └── ScadaDataReceived.php           # WebSocket event
│   ├── Http/Controllers/Api/
│   │   └── ReceiverController.php          # API endpoint
│   └── Providers/
│       └── AppServiceProvider.php          # Broadcasting configuration
├── config/
│   └── broadcasting.php                    # Broadcasting config
├── public/js/
│   └── scada-websocket-client.js          # WebSocket client
├── resources/views/
│   └── websocket-test.blade.php           # Test page
├── routes/
│   └── web.php                            # WebSocket routes
├── scripts/
│   ├── start-websocket-services.ps1       # PowerShell startup
│   ├── test_websocket_implementation.php  # Implementation test
│   └── test_websocket_data_simulation.php # Data simulation
├── start-websocket-services.bat            # Batch startup
├── stop-websocket-services.bat             # Batch shutdown
└── docs/
    └── WEBSOCKET_IMPLEMENTATION.md        # Detailed documentation
```

## 🔧 Configuration

### Broadcasting Configuration

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

### WebSocket Client Configuration

```javascript
const wsClient = new ScadaWebSocketClient({
    serverUrl: "ws://localhost:6001",
    channel: "scada-data",
    onConnect: () => console.log("Connected"),
    onMessage: (data) => updateDashboard(data),
    onError: (error) => console.error("Error:", error),
});
```

## 📊 Usage Examples

### 1. Broadcasting Data

```php
// Via Service
$broadcastingService = app(ScadaBroadcastingService::class);
$broadcastingService->broadcastData($scadaData);

// Via Event
event(new ScadaDataReceived($scadaData, 'scada-data'));

// Batch broadcasting
$broadcastingService->broadcastBatchData($dataArray);

// Throttled broadcasting
$broadcastingService->broadcastAggregatedData($data, 'scada-data', 100);
```

### 2. Client Connection

```javascript
// Initialize client
const wsClient = new ScadaWebSocketClient({
    serverUrl: "ws://localhost:6001",
    channel: "scada-data",
    onConnect: () => console.log("Connected"),
    onMessage: (data) => updateDashboard(data),
    onError: (error) => console.error("Error:", error),
});

// Connect to WebSocket
wsClient.connect();

// Subscribe to channels
wsClient.subscribe("scada-data");
wsClient.subscribe("scada-batch");
```

### 3. Channel Subscription

```javascript
// Listen for specific events
Echo.channel("scada-data").listen("ScadaDataReceived", (e) => {
    console.log("Data received:", e.data);
    updateMetrics(e.data);
    updateChart(e.data);
});

// Private channels
Echo.private(`scada-private-${userId}`).listen("ScadaDataReceived", (e) => {
    // Handle private data
});
```

## 🧪 Testing

### 1. Implementation Test

```bash
# Test WebSocket implementation
php scripts/test_websocket_implementation.php
```

**Tests include:**

-   Broadcasting service functionality
-   Event dispatching
-   Database integration
-   Configuration validation
-   Performance testing
-   Error handling

### 2. Data Simulation Test

```bash
# Simulate SCADA data
php scripts/test_websocket_data_simulation.php
```

**Features:**

-   Single data broadcasting
-   Batch data processing
-   Throttled broadcasting
-   High-frequency testing
-   Performance analysis
-   Database integration

### 3. Browser Testing

```bash
# Open test page
http://localhost:8000/websocket-test
```

**Test page includes:**

-   Connection status monitoring
-   Real-time data display
-   Interactive charts
-   Message logging
-   Export functionality

## 📈 Performance Optimization

### 1. Throttling

```php
// Prevent firehose with throttling
$broadcastingService->broadcastAggregatedData($data, 'scada-data', 100);
```

### 2. Batch Processing

```php
// Process multiple data points efficiently
$broadcastingService->broadcastBatchData($dataArray);
```

### 3. Caching

```php
// Cache throttle timestamps
Cache::put("broadcast_throttle:{$channel}", now()->timestamp, 60);
```

### 4. Queue Management

```bash
# Monitor queue performance
php artisan queue:monitor

# Check queue size
php artisan queue:size

# Retry failed jobs
php artisan queue:retry all
```

## 🔍 Monitoring & Debugging

### 1. Log Files

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# WebSocket logs
tail -f storage/logs/websockets.log
```

### 2. WebSocket Debug

```bash
# Debug WebSocket connections
php artisan websockets:serve --debug

# Check broadcasting routes
php artisan route:list | grep broadcasting
```

### 3. Queue Monitoring

```bash
# Monitor queue workers
php artisan queue:work --verbose

# Check failed jobs
php artisan queue:failed
```

### 4. Performance Monitoring

```bash
# Check WebSocket connections
php artisan websockets:connections

# Monitor broadcasting performance
php artisan websockets:stats
```

## 🚨 Error Handling

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
# Monitor queue failures
php artisan queue:failed

# Retry specific failed jobs
php artisan queue:retry {id}
```

## 🔒 Security Considerations

### 1. Channel Authorization

```php
// Public channels
Broadcast::channel('scada-data', function ($user) {
    return true;
});

// Private channels
Broadcast::channel('scada-private-{id}', function ($user, $id) {
    return $user->id == $id;
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

## 🚀 Production Deployment

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

### 3. Process Management

```bash
# Use PM2 for process management
pm2 start laravel-websockets
pm2 start "php artisan queue:work"
pm2 monit
```

## 🐛 Troubleshooting

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

# Check queue status
php artisan queue:work --verbose
```

## 📚 API Reference

### ScadaBroadcastingService

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

### ScadaDataReceived Event

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

### WebSocket Client

```javascript
class ScadaWebSocketClient {
    constructor(config)
    connect()
    disconnect()
    subscribe(channel)
    unsubscribe(channel)
    send(message)
}
```

## 🔮 Future Enhancements

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

## 📖 References

-   [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
-   [Pusher WebSocket Documentation](https://pusher.com/docs/channels)
-   [Laravel WebSockets Package](https://github.com/beyondcode/laravel-websockets)
-   [WebSocket Protocol Specification](https://tools.ietf.org/html/rfc6455)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

If you encounter any issues or have questions:

1. Check the troubleshooting section
2. Review the logs
3. Run the test scripts
4. Create an issue on GitHub

---

**Happy WebSocketing! 🚀**
