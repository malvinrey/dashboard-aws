# Immediate Fixes Implementation Guide

## Solusi Cepat untuk Data Firehose Problem

### 1. Frontend Throttling Implementation

#### 1.1 Update analysis-chart-component.js

```javascript
// Tambahkan di bagian atas file
class ChartThrottler {
    constructor(throttleMs = 100) {
        this.lastUpdateTime = 0;
        this.throttleMs = throttleMs;
        this.pendingData = null;
        this.isProcessing = false;
    }

    throttleUpdate(data, updateFunction) {
        const now = Date.now();

        if (now - this.lastUpdateTime >= this.throttleMs) {
            // Update immediately
            this.lastUpdateTime = now;
            this.pendingData = null;
            updateFunction(data);
        } else {
            // Store for later update
            this.pendingData = data;

            if (!this.isProcessing) {
                this.isProcessing = true;
                setTimeout(() => {
                    if (this.pendingData) {
                        this.lastUpdateTime = Date.now();
                        updateFunction(this.pendingData);
                        this.pendingData = null;
                    }
                    this.isProcessing = false;
                }, this.throttleMs - (now - this.lastUpdateTime));
            }
        }
    }
}

// Inisialisasi throttler
const chartThrottler = new ChartThrottler(100); // 100ms throttle
```

#### 1.2 Implementasi di SSE Handler

```javascript
// Ganti fungsi update yang ada dengan throttled version
function handleSSEMessage(event) {
    try {
        const data = JSON.parse(event.data);

        // Gunakan throttler untuk update chart
        chartThrottler.throttleUpdate(data, (throttledData) => {
            console.log("Updating chart with throttled data:", throttledData);
            updateChartWithData(throttledData);
        });
    } catch (error) {
        console.error("Error parsing SSE data:", error);
    }
}
```

### 2. Data Buffering System

#### 2.1 Implementasi Data Buffer

```javascript
class DataBuffer {
    constructor(maxSize = 100, flushInterval = 1000) {
        this.buffer = [];
        this.maxSize = maxSize;
        this.flushInterval = flushInterval;
        this.flushTimer = null;
        this.onFlush = null;
    }

    addData(data) {
        this.buffer.push({
            data: data,
            timestamp: Date.now(),
        });

        // Flush jika buffer penuh
        if (this.buffer.length >= this.maxSize) {
            this.flush();
        }

        // Set timer untuk flush otomatis
        if (!this.flushTimer) {
            this.flushTimer = setTimeout(() => {
                this.flush();
            }, this.flushInterval);
        }
    }

    flush() {
        if (this.buffer.length > 0 && this.onFlush) {
            const dataToProcess = [...this.buffer];
            this.buffer = [];

            if (this.flushTimer) {
                clearTimeout(this.flushTimer);
                this.flushTimer = null;
            }

            this.onFlush(dataToProcess);
        }
    }

    setFlushCallback(callback) {
        this.onFlush = callback;
    }
}

// Inisialisasi buffer
const dataBuffer = new DataBuffer(50, 1000); // 50 items atau 1 detik
```

#### 2.2 Integrasi dengan Chart Update

```javascript
// Set callback untuk buffer flush
dataBuffer.setFlushCallback((bufferedData) => {
    console.log(`Processing ${bufferedData.length} buffered data points`);

    // Aggregate data jika diperlukan
    const aggregatedData = aggregateData(bufferedData);

    // Update chart dengan data yang sudah di-aggregate
    chartThrottler.throttleUpdate(aggregatedData, updateChartWithData);
});

// Tambahkan data ke buffer dari SSE
function handleSSEMessage(event) {
    try {
        const data = JSON.parse(event.data);
        dataBuffer.addData(data);
    } catch (error) {
        console.error("Error parsing SSE data:", error);
    }
}
```

### 3. SSE Connection Resilience

#### 3.1 Robust Reconnection Logic

```javascript
class SSEManager {
    constructor(url, options = {}) {
        this.url = url;
        this.options = {
            maxReconnectAttempts: 5,
            initialReconnectDelay: 1000,
            maxReconnectDelay: 30000,
            ...options,
        };

        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.isConnecting = false;
        this.onMessage = null;
        this.onError = null;
        this.onConnect = null;
    }

    connect() {
        if (this.isConnecting) return;

        this.isConnecting = true;
        console.log(`Connecting to SSE: ${this.url}`);

        try {
            this.eventSource = new EventSource(this.url);
            this.setupEventHandlers();
        } catch (error) {
            console.error("Failed to create EventSource:", error);
            this.handleConnectionError();
        }
    }

    setupEventHandlers() {
        this.eventSource.onopen = () => {
            console.log("SSE connection established");
            this.isConnecting = false;
            this.reconnectAttempts = 0;

            if (this.onConnect) {
                this.onConnect();
            }
        };

        this.eventSource.onmessage = (event) => {
            if (this.onMessage) {
                this.onMessage(event);
            }
        };

        this.eventSource.onerror = (event) => {
            console.error("SSE connection error:", event);
            this.handleConnectionError();
        };
    }

    handleConnectionError() {
        this.isConnecting = false;

        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }

        if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.scheduleReconnect();
        } else {
            console.error("Max reconnection attempts reached");
            if (this.onError) {
                this.onError(new Error("Max reconnection attempts reached"));
            }
        }
    }

    scheduleReconnect() {
        const delay = Math.min(
            this.options.initialReconnectDelay *
                Math.pow(2, this.reconnectAttempts),
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

    disconnect() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }

        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }

        this.isConnecting = false;
    }

    setMessageHandler(handler) {
        this.onMessage = handler;
    }

    setErrorHandler(handler) {
        this.onError = handler;
    }

    setConnectHandler(handler) {
        this.onConnect = handler;
    }
}
```

#### 3.2 Implementasi di Component

```javascript
// Inisialisasi SSE Manager
const sseManager = new SSEManager("/api/sse/scada-data", {
    maxReconnectAttempts: 10,
    initialReconnectDelay: 1000,
    maxReconnectDelay: 30000,
});

// Set handlers
sseManager.setMessageHandler(handleSSEMessage);
sseManager.setErrorHandler((error) => {
    console.error("SSE connection failed:", error);
    // Tampilkan pesan error ke user
    showConnectionError(error.message);
});
sseManager.setConnectHandler(() => {
    console.log("SSE reconnected successfully");
    hideConnectionError();
});

// Start connection
sseManager.connect();
```

### 4. Memory Management

#### 4.1 Chart Data Cleanup

```javascript
class ChartDataManager {
    constructor(maxDataPoints = 1000, cleanupInterval = 30000) {
        this.maxDataPoints = maxDataPoints;
        this.cleanupInterval = cleanupInterval;
        this.chartData = [];
        this.cleanupTimer = null;
        this.startCleanupTimer();
    }

    addData(data) {
        this.chartData.push({
            ...data,
            timestamp: Date.now(),
        });

        // Batasi jumlah data points
        if (this.chartData.length > this.maxDataPoints) {
            this.chartData = this.chartData.slice(-this.maxDataPoints);
        }
    }

    getData() {
        return this.chartData;
    }

    cleanup() {
        const now = Date.now();
        const maxAge = 5 * 60 * 1000; // 5 menit

        this.chartData = this.chartData.filter(
            (item) => now - item.timestamp < maxAge
        );

        console.log(
            `Cleaned up chart data. Remaining: ${this.chartData.length} points`
        );
    }

    startCleanupTimer() {
        this.cleanupTimer = setInterval(() => {
            this.cleanup();
        }, this.cleanupInterval);
    }

    stopCleanupTimer() {
        if (this.cleanupTimer) {
            clearInterval(this.cleanupTimer);
            this.cleanupTimer = null;
        }
    }
}

// Inisialisasi data manager
const chartDataManager = new ChartDataManager(1000, 30000);
```

### 5. Performance Monitoring

#### 5.1 Real-time Performance Tracking

```javascript
class PerformanceTracker {
    constructor() {
        this.metrics = {
            renderCount: 0,
            dataReceived: 0,
            lastRenderTime: 0,
            averageRenderTime: 0,
            memoryUsage: 0,
        };

        this.startTime = Date.now();
        this.startMonitoring();
    }

    startMonitoring() {
        setInterval(() => {
            this.updateMetrics();
            this.checkThresholds();
            this.logMetrics();
        }, 5000); // Update setiap 5 detik
    }

    updateMetrics() {
        // Update memory usage
        if (performance.memory) {
            this.metrics.memoryUsage = performance.memory.usedJSHeapSize;
        }

        // Calculate render performance
        if (this.metrics.renderCount > 0) {
            this.metrics.averageRenderTime =
                (Date.now() - this.startTime) / this.metrics.renderCount;
        }
    }

    checkThresholds() {
        // Warning jika memory usage tinggi
        if (this.metrics.memoryUsage > 100 * 1024 * 1024) {
            // 100MB
            console.warn(
                "High memory usage detected:",
                Math.round(this.metrics.memoryUsage / 1024 / 1024) + "MB"
            );
        }

        // Warning jika render terlalu sering
        if (this.metrics.renderCount > 100) {
            console.warn(
                "High render count detected:",
                this.metrics.renderCount
            );
        }
    }

    logMetrics() {
        console.log("Performance Metrics:", {
            uptime: Math.round((Date.now() - this.startTime) / 1000) + "s",
            renderCount: this.metrics.renderCount,
            dataReceived: this.metrics.dataReceived,
            memoryUsage:
                Math.round(this.metrics.memoryUsage / 1024 / 1024) + "MB",
            averageRenderTime:
                Math.round(this.metrics.averageRenderTime) + "ms",
        });
    }

    recordRender() {
        this.metrics.renderCount++;
        this.metrics.lastRenderTime = Date.now();
    }

    recordDataReceived() {
        this.metrics.dataReceived++;
    }
}

// Inisialisasi performance tracker
const performanceTracker = new PerformanceTracker();
```

### 6. Integration dengan Alpine.js Component

#### 6.1 Update Component Methods

```javascript
// Di dalam Alpine.js component
function updateChartWithData(data) {
    // Record performance
    performanceTracker.recordRender();

    try {
        // Update chart dengan data baru
        if (window.Plotly && data) {
            // Implementasi update chart yang sudah ada
            // ... existing chart update code ...

            console.log("Chart updated successfully with throttled data");
        }
    } catch (error) {
        console.error("Error updating chart:", error);
    }
}

function handleNewData(data) {
    // Record data received
    performanceTracker.recordDataReceived();

    // Add to buffer
    dataBuffer.addData(data);

    // Add to chart data manager
    chartDataManager.addData(data);
}
```

### 7. Testing Implementation

#### 7.1 Test Script untuk Verifikasi

```javascript
// Test script untuk memverifikasi throttling
function testThrottling() {
    console.log("Testing throttling implementation...");

    let testCount = 0;
    const testInterval = setInterval(() => {
        testCount++;

        // Simulate high-frequency data
        handleNewData({
            timestamp: Date.now(),
            value: Math.random() * 100,
            channel: "CH1",
        });

        if (testCount >= 100) {
            clearInterval(testInterval);
            console.log("Throttling test completed");
        }
    }, 10); // 10ms interval (100x per second)
}

// Run test setelah 5 detik
setTimeout(testThrottling, 5000);
```

### 8. Deployment Checklist

#### 8.1 Pre-deployment

-   [ ] Test throttling dengan data frekuensi tinggi
-   [ ] Verifikasi memory usage tidak meningkat
-   [ ] Test SSE reconnection logic
-   [ ] Monitor CPU usage di browser

#### 8.2 Post-deployment

-   [ ] Monitor console untuk error
-   [ ] Verifikasi throttling berfungsi
-   [ ] Check performance metrics
-   [ ] User acceptance testing

### 9. Expected Results

#### 9.1 Immediate Improvements

-   CPU usage turun dari 100% ke <50%
-   Browser tidak crash lagi
-   Chart updates lebih smooth
-   Memory usage stabil

#### 9.2 Monitoring Points

-   Console log frequency
-   Chart update frequency
-   Memory consumption
-   SSE connection stability

---

**Note**: Implementasi ini memberikan solusi cepat untuk masalah data firehose sambil mempersiapkan infrastruktur untuk solusi jangka panjang yang lebih robust.
