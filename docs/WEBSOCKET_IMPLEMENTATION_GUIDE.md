# WebSocket Implementation Guide

## Solusi Jangka Panjang untuk Data Firehose Problem

### 1. Overview Arsitektur Baru

#### 1.1 Komponen Utama

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Laravel App  │    │     Redis       │    │  WebSocket     │
│                 │    │   Pub/Sub       │    │   Server       │
│  - Jobs        │───▶│                 │───▶│  (Soketi)      │
│  - Events      │    │                 │    │                 │
│  - Broadcasting│    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                       │
                                │                       │
                                ▼                       ▼
                       ┌─────────────────┐    ┌─────────────────┐
                       │   Frontend      │    │   Browser       │
                       │   WebSocket     │◀───│   Clients       │
                       │   Client        │    │                 │
                       └─────────────────┘    └─────────────────┘
```

#### 1.2 Keuntungan Arsitektur Baru

-   **Decoupling**: Laravel fokus pada business logic, WebSocket server fokus pada real-time
-   **Scalability**: Mampu handle ribuan koneksi simultan
-   **Performance**: Latency rendah, throughput tinggi
-   **Reliability**: Connection management yang robust
-   **Bidirectional**: Support untuk komunikasi dua arah

### 2. Backend Implementation

#### 2.1 Install Dependencies

```bash
# Install Laravel WebSockets atau Soketi
composer require beyondcode/laravel-websockets

# Atau untuk Soketi (recommended untuk production)
npm install -g @soketi/soketi

# Install Redis untuk Laravel
composer require predis/predis
```

#### 2.1.1 Konfigurasi Broadcasting

```php
// config/broadcasting.php
'default' => env('BROADCAST_DRIVER', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => [
            'connection' => 'default',
        ],
    ],

    'websockets' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => true,
            'host' => '127.0.0.1',
            'port' => 6001,
            'scheme' => 'http'
        ]
    ],
],
```

#### 2.2 Event Class Implementation

```php
// app/Events/ScadaDataReceived.php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScadaDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $scadaData;
    public $timestamp;
    public $channel;

    public function __construct($scadaData, $channel = null)
    {
        $this->scadaData = $scadaData;
        $this->timestamp = now();
        $this->channel = $channel ?? 'scada-data';
    }

    public function broadcastOn()
    {
        return new Channel($this->channel);
    }

    public function broadcastAs()
    {
        return 'scada.data.received';
    }

    public function broadcastWith()
    {
        return [
            'data' => $this->scadaData,
            'timestamp' => $this->timestamp->toISOString(),
            'channel' => $this->channel
        ];
    }
}
```

#### 2.3 Update ProcessScadaDataJob

```php
// app/Jobs/ProcessScadaDataJob.php
<?php

namespace App\Jobs;

use App\Events\ScadaDataReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScadaDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        try {
            // Process dan simpan data ke database
            $processedData = $this->processScadaData();

            // Broadcast event untuk real-time update
            if ($processedData) {
                ScadaDataReceived::dispatch($processedData, 'scada-realtime');

                // Log successful broadcast
                \Log::info('ScadaDataReceived event dispatched', [
                    'data_id' => $processedData->id ?? 'unknown',
                    'timestamp' => now()
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error processing SCADA data', [
                'error' => $e->getMessage(),
                'payload' => $this->payload
            ]);

            throw $e;
        }
    }

    protected function processScadaData()
    {
        // Implementasi processing data yang sudah ada
        // ... existing code ...

        return $processedData;
    }
}
```

#### 2.4 Broadcasting Service

```php
// app/Services/ScadaBroadcastingService.php
<?php

namespace App\Services;

use App\Events\ScadaDataReceived;
use Illuminate\Support\Facades\Log;

class ScadaBroadcastingService
{
    public function broadcastData($data, $channel = 'scada-data')
    {
        try {
            ScadaDataReceived::dispatch($data, $channel);

            Log::info('Data broadcasted successfully', [
                'channel' => $channel,
                'data_size' => is_array($data) ? count($data) : 1
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast data', [
                'error' => $e->getMessage(),
                'channel' => $channel
            ]);

            return false;
        }
    }

    public function broadcastBatchData($dataArray, $channel = 'scada-batch')
    {
        try {
            // Aggregate data untuk batch processing
            $aggregatedData = $this->aggregateBatchData($dataArray);

            ScadaDataReceived::dispatch($aggregatedData, $channel);

            Log::info('Batch data broadcasted successfully', [
                'channel' => $channel,
                'original_count' => count($dataArray),
                'aggregated_count' => count($aggregatedData)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast batch data', [
                'error' => $e->getMessage(),
                'channel' => $channel
            ]);

            return false;
        }
    }

    protected function aggregateBatchData($dataArray)
    {
        // Implementasi aggregation logic
        // Contoh: average, min, max untuk setiap channel
        $aggregated = [];

        foreach ($dataArray as $data) {
            $channel = $data['channel'] ?? 'unknown';

            if (!isset($aggregated[$channel])) {
                $aggregated[$channel] = [
                    'values' => [],
                    'count' => 0,
                    'sum' => 0,
                    'min' => PHP_FLOAT_MAX,
                    'max' => PHP_FLOAT_MIN
                ];
            }

            $value = $data['value'] ?? 0;
            $aggregated[$channel]['values'][] = $value;
            $aggregated[$channel]['count']++;
            $aggregated[$channel]['sum'] += $value;
            $aggregated[$channel]['min'] = min($aggregated[$channel]['min'], $value);
            $aggregated[$channel]['max'] = max($aggregated[$channel]['max'], $value);
        }

        // Calculate averages
        foreach ($aggregated as $channel => &$data) {
            $data['average'] = $data['sum'] / $data['count'];
        }

        return $aggregated;
    }
}
```

### 3. WebSocket Server Setup

#### 3.1 Soketi Configuration

```javascript
// soketi.config.js
module.exports = {
    appManager: {
        driver: "array",
        apps: [
            {
                id: process.env.PUSHER_APP_ID || "scada-app",
                key: process.env.PUSHER_APP_KEY || "scada-key",
                secret: process.env.PUSHER_APP_SECRET || "scada-secret",
                enableClientMessages: false,
                enableStatistics: true,
                maxConnectionsPerApp: 1000,
            },
        ],
    },

    server: {
        host: "0.0.0.0",
        port: process.env.SOKETI_PORT || 6001,
        cors: {
            origin: ["http://localhost", "http://localhost:8000"],
            methods: ["GET", "POST"],
            credentials: true,
        },
    },

    database: {
        driver: "redis",
        redis: {
            host: process.env.REDIS_HOST || "127.0.0.1",
            port: process.env.REDIS_PORT || 6379,
            password: process.env.REDIS_PASSWORD || null,
            db: process.env.REDIS_DB || 0,
        },
    },

    metrics: {
        enabled: true,
        driver: "redis",
        redis: {
            host: process.env.REDIS_HOST || "127.0.0.1",
            port: process.env.REDIS_PORT || 6379,
            password: process.env.REDIS_PASSWORD || null,
            db: process.env.REDIS_DB || 0,
        },
    },
};
```

#### 3.2 Start Soketi Server

```bash
# Start Soketi dengan config custom
soketi start --config=soketi.config.js

# Atau dengan environment variables
PUSHER_APP_ID=scada-app \
PUSHER_APP_KEY=scada-key \
PUSHER_APP_SECRET=scada-secret \
SOKETI_PORT=6001 \
soketi start
```

#### 3.3 PM2 Configuration (Production)

```javascript
// ecosystem.config.js
module.exports = {
    apps: [
        {
            name: "soketi-server",
            script: "soketi",
            args: "start --config=soketi.config.js",
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: "1G",
            env: {
                NODE_ENV: "production",
                PUSHER_APP_ID: "scada-app",
                PUSHER_APP_KEY: "scada-key",
                PUSHER_APP_SECRET: "scada-secret",
                SOKETI_PORT: 6001,
                REDIS_HOST: "127.0.0.1",
                REDIS_PORT: 6379,
            },
        },
    ],
};
```

### 4. Frontend WebSocket Implementation

#### 4.1 WebSocket Client Class

```javascript
// public/js/scada-websocket-client.js
class ScadaWebSocketClient {
    constructor(options = {}) {
        this.options = {
            url: options.url || "ws://localhost:6001/app/scada-app",
            reconnectAttempts: options.reconnectAttempts || 10,
            reconnectDelay: options.reconnectDelay || 1000,
            maxReconnectDelay: options.maxReconnectDelay || 30000,
            heartbeatInterval: options.heartbeatInterval || 30000,
            ...options,
        };

        this.ws = null;
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.heartbeatTimer = null;
        this.isConnecting = false;
        this.isConnected = false;

        // Event handlers
        this.onMessage = null;
        this.onConnect = null;
        this.onDisconnect = null;
        this.onError = null;

        // Connection state
        this.connectionState = "disconnected";
        this.lastMessageTime = 0;

        // Start connection
        this.connect();
    }

    connect() {
        if (this.isConnecting || this.isConnected) return;

        this.isConnecting = true;
        this.connectionState = "connecting";

        console.log(`Connecting to WebSocket: ${this.options.url}`);

        try {
            this.ws = new WebSocket(this.options.url);
            this.setupEventHandlers();
        } catch (error) {
            console.error("Failed to create WebSocket connection:", error);
            this.handleConnectionError(error);
        }
    }

    setupEventHandlers() {
        this.ws.onopen = () => {
            console.log("WebSocket connection established");
            this.isConnecting = false;
            this.isConnected = true;
            this.connectionState = "connected";
            this.reconnectAttempts = 0;

            // Start heartbeat
            this.startHeartbeat();

            if (this.onConnect) {
                this.onConnect();
            }
        };

        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.lastMessageTime = Date.now();

                if (this.onMessage) {
                    this.onMessage(data);
                }
            } catch (error) {
                console.error("Error parsing WebSocket message:", error);
            }
        };

        this.ws.onclose = (event) => {
            console.log(
                "WebSocket connection closed:",
                event.code,
                event.reason
            );
            this.handleDisconnect(event);
        };

        this.ws.onerror = (error) => {
            console.error("WebSocket error:", error);
            this.handleConnectionError(error);
        };
    }

    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(
                    JSON.stringify({
                        type: "heartbeat",
                        timestamp: Date.now(),
                    })
                );
            }
        }, this.options.heartbeatInterval);
    }

    handleDisconnect(event) {
        this.isConnected = false;
        this.connectionState = "disconnected";

        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }

        if (this.onDisconnect) {
            this.onDisconnect(event);
        }

        // Attempt reconnection
        this.scheduleReconnect();
    }

    handleConnectionError(error) {
        this.isConnecting = false;
        this.connectionState = "error";

        if (this.onError) {
            this.onError(error);
        }

        // Attempt reconnection
        this.scheduleReconnect();
    }

    scheduleReconnect() {
        if (this.reconnectAttempts >= this.options.reconnectAttempts) {
            console.error("Max reconnection attempts reached");
            this.connectionState = "failed";
            return;
        }

        const delay = Math.min(
            this.options.reconnectDelay * Math.pow(2, this.reconnectAttempts),
            this.options.maxReconnectDelay
        );

        console.log(
            `Scheduling reconnection attempt ${
                this.reconnectAttempts + 1
            } in ${delay}ms`
        );

        this.reconnectTimer = setTimeout(() => {
            this.reconnectAttempts++;
            this.connect();
        }, delay);
    }

    send(data) {
        if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
            try {
                this.ws.send(JSON.stringify(data));
                return true;
            } catch (error) {
                console.error("Failed to send data:", error);
                return false;
            }
        } else {
            console.warn("WebSocket not connected, cannot send data");
            return false;
        }
    }

    disconnect() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }

        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }

        if (this.ws) {
            this.ws.close(1000, "Client disconnect");
            this.ws = null;
        }

        this.isConnecting = false;
        this.isConnected = false;
        this.connectionState = "disconnected";
    }

    // Getters
    getConnectionState() {
        return this.connectionState;
    }

    isConnectionHealthy() {
        return (
            this.isConnected &&
            Date.now() - this.lastMessageTime <
                this.options.heartbeatInterval * 2
        );
    }
}
```

#### 4.2 Data Processing & Chart Integration

```javascript
// public/js/scada-chart-manager.js
class ScadaChartManager {
    constructor(chartElement, options = {}) {
        this.chartElement = chartElement;
        this.options = {
            maxDataPoints: options.maxDataPoints || 1000,
            updateInterval: options.updateInterval || 100,
            aggregationEnabled: options.aggregationEnabled || true,
            ...options,
        };

        this.dataBuffer = [];
        this.chartData = [];
        this.lastUpdateTime = 0;
        this.updateTimer = null;
        this.isUpdating = false;

        // Performance tracking
        this.metrics = {
            updates: 0,
            dataPoints: 0,
            renderTime: 0,
        };

        this.initializeChart();
        this.startUpdateLoop();
    }

    initializeChart() {
        // Initialize Plotly chart
        if (window.Plotly && this.chartElement) {
            const layout = {
                title: "SCADA Real-time Data",
                xaxis: { title: "Time" },
                yaxis: { title: "Value" },
                autosize: true,
                margin: { l: 50, r: 50, t: 50, b: 50 },
            };

            const config = {
                responsive: true,
                displayModeBar: true,
                modeBarButtonsToRemove: ["pan2d", "lasso2d", "select2d"],
            };

            Plotly.newPlot(this.chartElement, [], layout, config);

            console.log("Chart initialized successfully");
        }
    }

    addData(data) {
        const timestamp = Date.now();

        this.dataBuffer.push({
            ...data,
            timestamp: timestamp,
        });

        // Limit buffer size
        if (this.dataBuffer.length > this.options.maxDataPoints * 2) {
            this.dataBuffer = this.dataBuffer.slice(
                -this.options.maxDataPoints
            );
        }

        this.metrics.dataPoints++;
    }

    startUpdateLoop() {
        const updateLoop = () => {
            if (this.dataBuffer.length > 0 && !this.isUpdating) {
                this.processAndUpdateChart();
            }

            this.updateTimer = requestAnimationFrame(updateLoop);
        };

        this.updateTimer = requestAnimationFrame(updateLoop);
    }

    processAndUpdateChart() {
        if (this.isUpdating) return;

        this.isUpdating = true;
        const startTime = performance.now();

        try {
            // Get data from buffer
            const dataToProcess = [...this.dataBuffer];
            this.dataBuffer = [];

            // Aggregate data if enabled
            let processedData = dataToProcess;
            if (this.options.aggregationEnabled) {
                processedData = this.aggregateData(dataToProcess);
            }

            // Update chart
            this.updateChart(processedData);

            // Update metrics
            this.metrics.updates++;
            this.metrics.renderTime = performance.now() - startTime;
        } catch (error) {
            console.error("Error processing chart data:", error);
        } finally {
            this.isUpdating = false;
        }
    }

    aggregateData(dataArray) {
        if (dataArray.length <= 1) return dataArray;

        // Group by channel and aggregate
        const aggregated = {};

        dataArray.forEach((item) => {
            const channel = item.channel || "unknown";

            if (!aggregated[channel]) {
                aggregated[channel] = {
                    values: [],
                    timestamps: [],
                    count: 0,
                };
            }

            aggregated[channel].values.push(item.value || 0);
            aggregated[channel].timestamps.push(item.timestamp);
            aggregated[channel].count++;
        });

        // Calculate aggregated values
        const result = [];

        Object.keys(aggregated).forEach((channel) => {
            const data = aggregated[channel];

            result.push({
                channel: channel,
                value: this.calculateAverage(data.values),
                timestamp: Math.max(...data.timestamps),
                count: data.count,
                min: Math.min(...data.values),
                max: Math.max(...data.values),
            });
        });

        return result;
    }

    calculateAverage(values) {
        return values.reduce((sum, val) => sum + val, 0) / values.length;
    }

    updateChart(data) {
        if (!window.Plotly || !this.chartElement) return;

        try {
            // Prepare data for Plotly
            const traces = this.prepareTraces(data);

            // Update chart with new data
            Plotly.react(this.chartElement, traces, {
                title: "SCADA Real-time Data",
                xaxis: { title: "Time" },
                yaxis: { title: "Value" },
            });
        } catch (error) {
            console.error("Error updating chart:", error);
        }
    }

    prepareTraces(data) {
        // Group data by channel for multiple traces
        const traces = {};

        data.forEach((item) => {
            const channel = item.channel || "unknown";

            if (!traces[channel]) {
                traces[channel] = {
                    x: [],
                    y: [],
                    type: "scatter",
                    mode: "lines+markers",
                    name: channel,
                    line: { width: 2 },
                };
            }

            traces[channel].x.push(new Date(item.timestamp));
            traces[channel].y.push(item.value);
        });

        return Object.values(traces);
    }

    // Performance monitoring
    getMetrics() {
        return {
            ...this.metrics,
            bufferSize: this.dataBuffer.length,
            chartDataSize: this.chartData.length,
        };
    }

    // Cleanup
    destroy() {
        if (this.updateTimer) {
            cancelAnimationFrame(this.updateTimer);
            this.updateTimer = null;
        }

        this.dataBuffer = [];
        this.chartData = [];
    }
}
```

### 5. Integration dengan Livewire

#### 5.1 Update AnalysisChart Component

```php
// app/Livewire/AnalysisChart.php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class AnalysisChart extends Component
{
    public $chartData = [];
    public $connectionStatus = 'disconnected';
    public $lastUpdate = null;
    public $performanceMetrics = [];

    protected $listeners = [
        'echo:scada-data,scada.data.received' => 'handleWebSocketData',
        'echo:scada-realtime,scada.data.received' => 'handleRealtimeData'
    ];

    public function mount()
    {
        $this->lastUpdate = now();
    }

    public function handleWebSocketData($event)
    {
        try {
            $data = $event['data'] ?? [];

            if (!empty($data)) {
                $this->chartData[] = $data;

                // Limit data points untuk performance
                if (count($this->chartData) > 1000) {
                    $this->chartData = array_slice($this->chartData, -1000);
                }

                $this->lastUpdate = now();

                // Emit event untuk frontend update
                $this->dispatch('chart-data-updated', $data);
            }
        } catch (\Exception $e) {
            \Log::error('Error handling WebSocket data', [
                'error' => $e->getMessage(),
                'event' => $event
            ]);
        }
    }

    public function handleRealtimeData($event)
    {
        // Handle real-time specific data
        $this->handleWebSocketData($event);
    }

    public function getConnectionStatus()
    {
        return $this->connectionStatus;
    }

    public function getPerformanceMetrics()
    {
        return [
            'data_points' => count($this->chartData),
            'last_update' => $this->lastUpdate,
            'connection_status' => $this->connectionStatus
        ];
    }

    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
```

#### 5.2 Update Blade Template

```blade
{{-- resources/views/livewire/graph-analysis.blade.php --}}
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800">SCADA Data Analysis</h2>
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full {{ $connectionStatus === 'connected' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <span class="text-sm text-gray-600">{{ ucfirst($connectionStatus) }}</span>
            </div>
            <span class="text-sm text-gray-500">Last: {{ $lastUpdate ? $lastUpdate->diffForHumans() : 'Never' }}</span>
        </div>
    </div>

    <div id="scada-chart" class="w-full h-96"></div>

    <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
        <div class="text-center">
            <div class="font-semibold text-gray-700">{{ count($chartData) }}</div>
            <div class="text-gray-500">Data Points</div>
        </div>
        <div class="text-center">
            <div class="font-semibold text-gray-700">{{ $connectionStatus }}</div>
            <div class="text-gray-500">Status</div>
        </div>
        <div class="text-center">
            <div class="font-semibold text-gray-700">{{ $lastUpdate ? $lastUpdate->format('H:i:s') : 'N/A' }}</div>
            <div class="text-gray-500">Last Update</div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script src="{{ asset('js/scada-websocket-client.js') }}"></script>
<script src="{{ asset('js/scada-chart-manager.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize WebSocket client
    const wsClient = new ScadaWebSocketClient({
        url: 'ws://localhost:6001/app/scada-app',
        reconnectAttempts: 10,
        heartbeatInterval: 30000
    });

    // Initialize chart manager
    const chartElement = document.getElementById('scada-chart');
    const chartManager = new ScadaChartManager(chartElement, {
        maxDataPoints: 1000,
        updateInterval: 100,
        aggregationEnabled: true
    });

    // WebSocket event handlers
    wsClient.onConnect = function() {
        console.log('WebSocket connected');
        Livewire.dispatch('connection-status-updated', { status: 'connected' });
    };

    wsClient.onDisconnect = function() {
        console.log('WebSocket disconnected');
        Livewire.dispatch('connection-status-updated', { status: 'disconnected' });
    };

    wsClient.onMessage = function(data) {
        if (data.event === 'scada.data.received') {
            const scadaData = data.data;
            chartManager.addData(scadaData);

            // Update Livewire component
            Livewire.dispatch('chart-data-updated', scadaData);
        }
    };

    wsClient.onError = function(error) {
        console.error('WebSocket error:', error);
        Livewire.dispatch('connection-status-updated', { status: 'error' });
    };

    // Livewire event listeners
    Livewire.on('connection-status-updated', (data) => {
        @this.set('connectionStatus', data.status);
    });

    Livewire.on('chart-data-updated', (data) => {
        @this.set('lastUpdate', new Date().toISOString());
    });
});
</script>
@endpush
```

### 6. Environment Configuration

#### 6.1 .env Configuration

```env
# Broadcasting Configuration
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Pusher/WebSocket Configuration
PUSHER_APP_ID=scada-app
PUSHER_APP_KEY=scada-key
PUSHER_APP_SECRET=scada-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_ENCRYPTED=false

# Soketi Configuration
SOKETI_PORT=6001
SOKETI_HOST=0.0.0.0
```

#### 6.2 Queue Configuration

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

### 7. Performance Monitoring & Optimization

#### 7.1 Redis Monitoring

```bash
# Monitor Redis performance
redis-cli monitor

# Check Redis memory usage
redis-cli info memory

# Monitor Redis connections
redis-cli client list
```

#### 7.2 WebSocket Server Monitoring

```javascript
// soketi.config.js - tambahkan monitoring
module.exports = {
    // ... existing config ...

    monitoring: {
        enabled: true,
        driver: "redis",
        redis: {
            host: process.env.REDIS_HOST || "127.0.0.1",
            port: process.env.REDIS_PORT || 6379,
        },
    },

    statistics: {
        enabled: true,
        model: "redis",
        redis: {
            host: process.env.REDIS_HOST || "127.0.0.1",
            port: process.env.REDIS_PORT || 6379,
        },
    },
};
```

#### 7.3 Performance Metrics Collection

```php
// app/Services/PerformanceMonitoringService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitoringService
{
    public function recordMetrics($operation, $duration, $dataSize = 0)
    {
        $metrics = [
            'operation' => $operation,
            'duration_ms' => $duration,
            'data_size' => $dataSize,
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        // Store metrics in Redis for analysis
        $key = "performance:{$operation}:" . now()->format('Y-m-d:H');
        Cache::put($key, $metrics, 3600);

        // Log if performance is poor
        if ($duration > 1000) { // > 1 second
            Log::warning("Slow operation detected", $metrics);
        }

        return $metrics;
    }

    public function getPerformanceReport($operation, $hours = 24)
    {
        $report = [];

        for ($i = 0; $i < $hours; $i++) {
            $time = now()->subHours($i);
            $key = "performance:{$operation}:" . $time->format('Y-m-d:H');
            $metrics = Cache::get($key);

            if ($metrics) {
                $report[] = $metrics;
            }
        }

        return $report;
    }
}
```

### 8. Testing & Validation

#### 8.1 WebSocket Connection Test

```javascript
// public/js/test-websocket.js
function testWebSocketConnection() {
    const ws = new WebSocket("ws://localhost:6001/app/scada-app");

    ws.onopen = function () {
        console.log("✅ WebSocket connection successful");

        // Test message sending
        ws.send(
            JSON.stringify({
                type: "test",
                message: "Hello WebSocket Server!",
                timestamp: Date.now(),
            })
        );
    };

    ws.onmessage = function (event) {
        console.log("📨 Received message:", event.data);
    };

    ws.onerror = function (error) {
        console.error("❌ WebSocket error:", error);
    };

    ws.onclose = function () {
        console.log("🔌 WebSocket connection closed");
    };

    // Close after 5 seconds
    setTimeout(() => {
        ws.close();
    }, 5000);
}

// Run test
testWebSocketConnection();
```

#### 8.2 Load Testing Script

```php
// scripts/test_websocket_load.php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Events\ScadaDataReceived;

echo "Starting WebSocket load test...\n";

$startTime = microtime(true);
$messageCount = 1000;
$successCount = 0;
$errorCount = 0;

for ($i = 0; $i < $messageCount; $i++) {
    try {
        $testData = [
            'id' => $i,
            'channel' => 'test-channel',
            'value' => rand(0, 100),
            'timestamp' => now()->toISOString()
        ];

        ScadaDataReceived::dispatch($testData, 'load-test');
        $successCount++;

        if ($i % 100 === 0) {
            echo "Sent {$i} messages...\n";
        }

        usleep(1000); // 1ms delay

    } catch (Exception $e) {
        $errorCount++;
        echo "Error sending message {$i}: " . $e->getMessage() . "\n";
    }
}

$endTime = microtime(true);
$duration = $endTime - $startTime;

echo "\n=== Load Test Results ===\n";
echo "Total messages: {$messageCount}\n";
echo "Successful: {$successCount}\n";
echo "Errors: {$errorCount}\n";
echo "Duration: " . number_format($duration, 2) . " seconds\n";
echo "Rate: " . number_format($messageCount / $duration, 2) . " messages/second\n";
```

### 9. Deployment & Production Setup

#### 9.1 Production Environment Variables

```env
# Production .env
APP_ENV=production
APP_DEBUG=false

# Redis Production
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379

# WebSocket Production
PUSHER_APP_ID=your-production-app-id
PUSHER_APP_KEY=your-production-app-key
PUSHER_APP_SECRET=your-production-app-secret
PUSHER_APP_CLUSTER=your-cluster
PUSHER_HOST=your-websocket-host
PUSHER_PORT=6001
PUSHER_SCHEME=https
PUSHER_APP_ENCRYPTED=true
```

#### 9.2 Nginx Configuration for WebSocket

```nginx
# /etc/nginx/sites-available/scada-app
server {
    listen 80;
    server_name your-domain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    # SSL configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;

    # Laravel application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # WebSocket proxy
    location /app/ {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### 9.3 Systemd Service for Soketi

```ini
# /etc/systemd/system/soketi.service
[Unit]
Description=Soketi WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/scada-app
Environment=NODE_ENV=production
Environment=PUSHER_APP_ID=your-app-id
Environment=PUSHER_APP_KEY=your-app-key
Environment=PUSHER_APP_SECRET=your-app-secret
Environment=SOKETI_PORT=6001
Environment=REDIS_HOST=127.0.0.1
Environment=REDIS_PORT=6379
ExecStart=/usr/bin/soketi start --config=soketi.config.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### 10. Troubleshooting & Maintenance

#### 10.1 Common Issues & Solutions

**Issue: WebSocket connection fails**

```bash
# Check if Soketi is running
ps aux | grep soketi

# Check Soketi logs
tail -f /var/log/soketi.log

# Test Redis connection
redis-cli ping
```

**Issue: High memory usage**

```bash
# Monitor memory usage
htop
free -h

# Check Redis memory
redis-cli info memory

# Restart services if needed
sudo systemctl restart soketi
sudo systemctl restart redis
```

**Issue: Slow performance**

```bash
# Check queue status
php artisan queue:work --verbose

# Monitor Redis performance
redis-cli monitor

# Check WebSocket connections
netstat -an | grep :6001
```

#### 10.2 Performance Tuning

```javascript
// soketi.config.js - Performance tuning
module.exports = {
    // ... existing config ...

    server: {
        host: "0.0.0.0",
        port: process.env.SOKETI_PORT || 6001,
        cors: {
            origin: ["https://your-domain.com"],
            methods: ["GET", "POST"],
            credentials: true,
        },
        // Performance tuning
        maxPayload: 1024 * 1024, // 1MB
        perMessageDeflate: false, // Disable compression for better performance
    },

    database: {
        driver: "redis",
        redis: {
            host: process.env.REDIS_HOST || "127.0.0.1",
            port: process.env.REDIS_PORT || 6379,
            password: process.env.REDIS_PASSWORD || null,
            db: process.env.REDIS_DB || 0,
            // Redis performance tuning
            retryDelayOnFailover: 100,
            maxRetriesPerRequest: 3,
            lazyConnect: true,
        },
    },
};
```

### 11. Migration Strategy

#### 11.1 Phase 1: Preparation (Week 1-2)

-   Install and configure Redis
-   Set up WebSocket server (Soketi)
-   Create event classes and broadcasting service
-   Update environment configuration

#### 11.2 Phase 2: Backend Implementation (Week 3-4)

-   Implement ScadaDataReceived event
-   Update ProcessScadaDataJob
-   Test broadcasting functionality
-   Performance testing and optimization

#### 11.3 Phase 3: Frontend Implementation (Week 5-6)

-   Create WebSocket client classes
-   Implement chart manager with throttling
-   Update Livewire components
-   Integration testing

#### 11.4 Phase 4: Deployment & Testing (Week 7-8)

-   Production deployment
-   Load testing
-   Performance monitoring
-   Documentation and training

### 12. Success Metrics

#### 12.1 Performance Improvements

-   **CPU Usage**: Target < 30% (from current 90%+)
-   **Memory Usage**: Target < 2GB (from current 4GB+)
-   **Response Time**: Target < 100ms (from current 500ms+)
-   **Connection Stability**: Target 99.9% uptime

#### 12.2 Scalability Metrics

-   **Concurrent Connections**: Support 1000+ simultaneous users
-   **Data Throughput**: Handle 10,000+ messages/second
-   **Queue Processing**: Process 1000+ jobs/minute
-   **WebSocket Latency**: < 50ms average

### 13. Conclusion

Implementasi WebSocket dengan arsitektur yang diusulkan akan memberikan solusi jangka panjang yang robust untuk masalah data firehose. Pendekatan ini:

1. **Memisahkan concerns** antara business logic dan real-time communication
2. **Meningkatkan scalability** dengan WebSocket server yang dedicated
3. **Mengoptimalkan performance** dengan throttling dan aggregation
4. **Memberikan monitoring** yang komprehensif untuk production
5. **Mendukung migration** yang bertahap dan aman

Dengan implementasi ini, sistem SCADA Anda akan mampu menangani volume data yang tinggi tanpa mengorbankan performance atau stability browser client.
