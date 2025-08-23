# WebSocket Reverb Implementation untuk SCADA Dashboard

## 🚀 Overview

Implementasi WebSocket real-time menggunakan Laravel Reverb untuk halaman Analysis (Graph Real-time) pada SCADA Dashboard. Sistem ini menggantikan implementasi Soketi sebelumnya dengan teknologi yang lebih modern dan performant.

## ✨ Fitur Utama

### 1. Real-time Data Streaming

-   **WebSocket Connection**: Koneksi real-time menggunakan Laravel Reverb
-   **Data Broadcasting**: Broadcasting data SCADA ke multiple channels
-   **Throttling & Buffering**: Sistem throttling untuk mencegah data firehose
-   **Auto-reconnection**: Reconnection otomatis dengan exponential backoff

### 2. Analysis Chart Features

-   **Real-time Metrics Dashboard**: Display metrics real-time (current, min, max, avg, trend)
-   **Interactive Charts**: Chart Plotly dengan update real-time
-   **Channel Selection**: Pemilihan channel data yang akan ditampilkan
-   **Performance Monitoring**: Monitoring performa WebSocket dan chart

### 3. WebSocket Management

-   **Connection Status**: Indikator status koneksi real-time
-   **Performance Metrics**: Latency, messages/sec, error count
-   **Channel Management**: Subscribe/unsubscribe ke channels
-   **Connection Toggle**: Enable/disable WebSocket connection

## 🏗️ Architecture

### Backend Components

```
app/
├── Events/
│   └── ScadaDataReceived.php          # Event untuk broadcasting data
├── Livewire/
│   └── AnalysisChart.php              # Livewire component dengan WebSocket
├── Services/
│   └── ScadaBroadcastingService.php   # Service untuk broadcasting
└── Http/Controllers/
    └── AnalysisController.php         # Controller untuk analysis
```

### Frontend Components

```
public/js/
├── scada-websocket-client.js          # WebSocket client untuk Reverb
├── scada-chart-manager.js             # Chart manager dengan throttling
└── analysis-chart-component.js        # Alpine.js component untuk analysis

resources/views/
└── livewire/
    └── graph-analysis.blade.php       # View dengan WebSocket integration
```

### Configuration Files

```
config/
├── broadcasting.php                    # Broadcasting configuration (Reverb)
└── reverb.php                         # Reverb server configuration
```

## 🔧 Installation & Setup

### 1. Install Dependencies

```bash
composer require laravel/reverb
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider"
```

### 3. Environment Configuration

```env
# Broadcasting Configuration
BROADCAST_DRIVER=reverb

# Reverb Configuration
REVERB_APP_ID=12345
REVERB_APP_KEY=scada_dashboard_key_2024
REVERB_APP_SECRET=scada_dashboard_secret_2024
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Legacy Pusher (untuk kompatibilitas)
PUSHER_APP_ID=12345
PUSHER_APP_KEY=scada_dashboard_key_2024
PUSHER_APP_SECRET=scada_dashboard_secret_2024
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### 4. Start Reverb Server

```bash
# Menggunakan script PowerShell
.\scripts\start-reverb-server.ps1

# Atau manual
php artisan reverb:start --host=127.0.0.1 --port=8080
```

## 🚀 Usage

### 1. Access Analysis Page

```
http://localhost:8000/analysis
```

### 2. WebSocket Connection

-   Halaman akan otomatis connect ke Reverb server
-   Status koneksi ditampilkan di header
-   Real-time metrics akan mulai update

### 3. Channel Management

-   **scada-data**: Channel utama untuk data SCADA
-   **scada-realtime**: Channel khusus untuk real-time updates
-   **scada-analysis**: Channel untuk analysis data

### 4. Real-time Features

-   **Play/Pause**: Kontrol real-time updates
-   **Channel Selection**: Pilih channel yang akan ditampilkan
-   **Export Data**: Export data ke CSV
-   **Performance Monitoring**: Monitor WebSocket performance

## 📊 Real-time Metrics

### Metrics yang Ditampilkan

-   **Current Value**: Nilai terkini dari setiap sensor
-   **Min/Max**: Nilai minimum dan maksimum
-   **Average**: Rata-rata nilai
-   **Trend**: Indikator trend (rising, falling, stable)
-   **Last Update**: Timestamp update terakhir

### Performance Metrics

-   **Connection Status**: Status koneksi WebSocket
-   **Latency**: Latency koneksi dalam ms
-   **Messages/sec**: Jumlah pesan per detik
-   **Error Count**: Jumlah error yang terjadi

## 🔌 WebSocket Client API

### Basic Usage

```javascript
const wsClient = new ScadaWebSocketClient({
    host: "127.0.0.1",
    port: 8080,
    appKey: "scada_dashboard_key_2024",
    cluster: "mt1",
    onConnect: () => console.log("Connected"),
    onMessage: (data) => console.log("Message:", data),
    onError: (error) => console.error("Error:", error),
    onDisconnect: () => console.log("Disconnected"),
});
```

### Subscribe to Channel

```javascript
wsClient.subscribe("scada-data", "scada.data.received", (data) => {
    console.log("SCADA data received:", data);
});
```

### Send Message

```javascript
wsClient.sendMessage("scada-data", "test.event", {
    temperature: 25.5,
    humidity: 60.2,
});
```

### Connection Management

```javascript
// Check connection status
const isConnected = wsClient.isConnected();

// Get connection stats
const stats = wsClient.getConnectionStats();

// Disconnect
wsClient.disconnect();

// Reconnect
wsClient.reconnect();
```

## 📈 Chart Integration

### Chart Manager

```javascript
const chartManager = new ScadaChartManager(chartElement, {
    maxDataPoints: 1000,
    updateInterval: 100, // 10 FPS max
    aggregationEnabled: true,
    bufferSize: 100,
});

// Add data
chartManager.addData(scadaData);

// Control updates
chartManager.pauseUpdates();
chartManager.resumeUpdates();
```

### Real-time Updates

-   Data diterima dari WebSocket
-   Diproses melalui buffer dan throttling
-   Update chart dengan Plotly.react()
-   Auto-cleanup data lama untuk performance

## 🧪 Testing

### 1. Test WebSocket Connection

```
http://localhost:8000/test-websocket-reverb.html
```

### 2. Test Analysis Page

```
http://localhost:8000/analysis
```

### 3. Monitor Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Reverb logs (jika enabled)
php artisan reverb:start --verbose
```

## 🔍 Troubleshooting

### Common Issues

#### 1. Connection Failed

```
❌ WebSocket connection failed
```

**Solution:**

-   Pastikan Reverb server berjalan di port 8080
-   Check firewall settings
-   Verify environment variables

#### 2. Chart Not Updating

```
⚠️ Chart updates not working
```

**Solution:**

-   Check WebSocket connection status
-   Verify channel subscription
-   Check browser console untuk errors

#### 3. High Memory Usage

```
⚠️ High memory usage detected
```

**Solution:**

-   Reduce maxDataPoints di ChartManager
-   Enable data cleanup
-   Check for memory leaks di event listeners

### Debug Commands

```bash
# Check Reverb status
php artisan reverb:status

# Clear broadcasting cache
php artisan broadcasting:clear

# Test broadcasting
php artisan tinker
>>> event(new App\Events\ScadaDataReceived(['test' => 'data']));
```

## 📚 API Reference

### Events

-   `scada.data.received`: Data SCADA diterima
-   `scada.data.updated`: Data SCADA diupdate
-   `scada.analysis.completed`: Analysis selesai
-   `scada.anomaly.detected`: Anomali terdeteksi
-   `scada.trend.updated`: Trend diupdate

### Channels

-   `scada-data`: Channel utama
-   `scada-realtime`: Real-time updates
-   `scada-analysis`: Analysis data
-   `scada-batch`: Batch processing
-   `scada-aggregated`: Aggregated data

### Methods

-   `broadcastData()`: Broadcast single data point
-   `broadcastBatchData()`: Broadcast batch data
-   `broadcastAggregatedData()`: Broadcast aggregated data

## 🚀 Performance Optimization

### 1. Throttling

-   Chart updates dibatasi maksimal 10 FPS
-   Data buffering untuk batch processing
-   Aggregation untuk mengurangi data points

### 2. Memory Management

-   Auto-cleanup data lama
-   Limiting data points per chart
-   Efficient data structures

### 3. Connection Management

-   Connection pooling
-   Auto-reconnection dengan backoff
-   Heartbeat monitoring

## 🔮 Future Enhancements

### Planned Features

-   **Authentication**: User authentication untuk private channels
-   **Presence**: User presence tracking
-   **Scaling**: Horizontal scaling dengan Redis
-   **Analytics**: Advanced analytics dan insights
-   **Mobile Support**: Mobile-optimized WebSocket client

### Performance Improvements

-   **Compression**: Data compression untuk bandwidth
-   **Caching**: Redis caching untuk historical data
-   **Load Balancing**: Load balancing untuk multiple Reverb instances

## 📝 Changelog

### v1.0.0 (Current)

-   ✅ Laravel Reverb integration
-   ✅ Real-time WebSocket communication
-   ✅ Analysis chart dengan real-time updates
-   ✅ Performance monitoring
-   ✅ Auto-reconnection
-   ✅ Data throttling dan buffering

### v0.9.0 (Previous - Soketi)

-   ✅ Soketi WebSocket integration
-   ✅ Basic real-time functionality
-   ✅ Chart updates

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Implement changes
4. Add tests
5. Submit pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

Untuk support dan pertanyaan:

-   Create issue di GitHub
-   Check documentation
-   Review troubleshooting guide

---

**Note**: Implementasi ini menggantikan sistem Soketi sebelumnya dengan Laravel Reverb yang lebih modern dan performant. Pastikan untuk migrate dengan hati-hati dan test thoroughly sebelum production deployment.
