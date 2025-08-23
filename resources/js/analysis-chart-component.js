// public/js/analysis-chart-component.js
// Alpine.js Component untuk Analysis Chart dengan WebSocket Integration

// ========================================
// IMMEDIATE FIXES IMPLEMENTATION
// ========================================

// 1. Chart Throttling System
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

// 2. Data Buffering System
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

// 3. WebSocket Connection Manager (Replaces SSE)
class WebSocketManager {
    constructor(config = {}) {
        this.config = {
            host: config.host || "127.0.0.1",
            port: config.port || 6001,
            appKey: config.appKey || "scada_dashboard_key_2024",
            reconnectAttempts: config.reconnectAttempts || 5,
            initialReconnectDelay: config.initialReconnectDelay || 2000,
            maxReconnectDelay: config.maxReconnectDelay || 10000,
            ...config,
        };

        this.websocketClient = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.subscribedChannels = new Set();

        // Event handlers
        this.onMessage = config.onMessage || (() => {});
        this.onConnect = config.onConnect || (() => {});
        this.onError = config.onError || (() => {});
        this.onDisconnect = config.onDisconnect || (() => {});
    }

    connect() {
        try {
            // Create WebSocket client
            this.websocketClient = new ScadaWebSocketClient({
                host: this.config.host,
                port: this.config.port,
                appKey: this.config.appKey,
                reconnectAttempts: this.config.reconnectAttempts,
                reconnectDelay: this.config.initialReconnectDelay,
                maxReconnectDelay: this.config.maxReconnectDelay,
                onConnect: () => {
                    console.log("WebSocket connected successfully");
                    this.isConnected = true;
                    this.reconnectAttempts = 0;
                    this.onConnect();

                    // Resubscribe to channels after reconnection
                    this.resubscribeChannels();
                },
                onMessage: (data) => {
                    this.onMessage(data);
                },
                onError: (error) => {
                    console.error("WebSocket error:", error);
                    this.onError(error);
                },
                onDisconnect: () => {
                    console.log("WebSocket disconnected");
                    this.isConnected = false;
                    this.onDisconnect();
                },
            });

            console.log("WebSocket manager initialized");
        } catch (error) {
            console.error("Failed to initialize WebSocket manager:", error);
            this.onError(error);
        }
    }

    subscribe(channelName, eventName = null, handler = null) {
        if (!this.websocketClient || !this.isConnected) {
            console.warn("WebSocket not connected, cannot subscribe");
            return false;
        }

        try {
            const success = this.websocketClient.subscribe(
                channelName,
                eventName,
                handler
            );
            if (success) {
                this.subscribedChannels.add(channelName);
                console.log(`Subscribed to channel: ${channelName}`);
            }
            return success;
        } catch (error) {
            console.error(
                `Failed to subscribe to channel ${channelName}:`,
                error
            );
            return false;
        }
    }

    unsubscribe(channelName) {
        if (!this.websocketClient) return false;

        try {
            const success = this.websocketClient.unsubscribe(channelName);
            if (success) {
                this.subscribedChannels.delete(channelName);
                console.log(`Unsubscribed from channel: ${channelName}`);
            }
            return success;
        } catch (error) {
            console.error(
                `Failed to unsubscribe from channel ${channelName}:`,
                error
            );
            return false;
        }
    }

    resubscribeChannels() {
        // This will be called after reconnection to restore subscriptions
        console.log("Resubscribing to channels after reconnection");
        // The actual resubscription logic will be handled by the component
    }

    disconnect() {
        if (this.websocketClient) {
            this.websocketClient.disconnect();
            this.websocketClient = null;
        }
        this.isConnected = false;
        this.subscribedChannels.clear();

        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
    }

    getConnectionState() {
        return this.websocketClient
            ? this.websocketClient.getConnectionState()
            : "disconnected";
    }

    isConnectionHealthy() {
        return this.websocketClient
            ? this.websocketClient.isConnectionHealthy()
            : false;
    }
}

// 4. Chart Data Manager
class ChartDataManager {
    constructor(maxPoints = 1000, cleanupInterval = 30000) {
        this.maxPoints = maxPoints;
        this.cleanupInterval = cleanupInterval;
        this.cleanupTimer = null;
        this.startCleanupTimer();
    }

    addData(data) {
        // Add data point
        this.dataPoints.push({
            data: data,
            timestamp: Date.now(),
        });

        // Limit data points
        if (this.dataPoints.length > this.maxPoints) {
            this.dataPoints = this.dataPoints.slice(-this.maxPoints);
        }
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

    cleanup() {
        const now = Date.now();
        const cutoff = now - this.cleanupInterval;

        this.dataPoints = this.dataPoints.filter(
            (point) => point.timestamp > cutoff
        );
    }

    getDataPoints() {
        return this.dataPoints;
    }
}

// 5. Performance Tracker
class PerformanceTracker {
    constructor() {
        this.startTime = Date.now();
        this.metrics = {
            renderCount: 0,
            dataReceived: 0,
            lastRenderTime: 0,
            averageRenderTime: 0,
            memoryUsage: 0,
        };

        // Start monitoring
        this.startMonitoring();
    }

    startMonitoring() {
        this.monitoringInterval = setInterval(() => {
            this.updateMemoryUsage();
            this.checkThresholds();
            this.logMetrics();
        }, 30000); // Every 30 seconds
    }

    updateMemoryUsage() {
        if (performance.memory) {
            this.metrics.memoryUsage = performance.memory.usedJSHeapSize;
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

// ========================================
// ALPINE.JS COMPONENT
// ========================================

document.addEventListener("alpine:init", () => {
    Alpine.data("analysisChartComponent", () => ({
        // Properti untuk menyimpan instance dari worker dan chart
        plotlyChart: null,

        // WebSocket manager (replaces SSE)
        websocketManager: null,

        // Immediate fixes components
        chartThrottler: null,
        dataBuffer: null,
        chartDataManager: null,
        performanceTracker: null,

        // Konfigurasi chart
        chartConfig: {
            type: "line",
            colors: [
                "#1f77b4",
                "#ff7f0e",
                "#2ca02c",
                "#d62728",
                "#9467bd",
                "#8c564b",
                "#e377c2",
                "#7f7f7f",
                "#bcbd22",
                "#17becf",
            ],
            lineWidth: 2,
            markerSize: 4,
            showMarkers: false,
            showLegend: true,
            gridColor: "#e5e7eb",
            backgroundColor: "#ffffff",
        },

        // Konfigurasi data
        dataConfig: {
            aggregationInterval: 5, // detik
            maxDataPoints: 1000,
            realtimeEnabled: true,
            historicalEnabled: true,
            gapThreshold: 30000, // 30 detik
            bufferSize: 5000,
        },

        // State management
        state: {
            isPlaying: false,
            isConnected: false,
            lastUpdate: null,
            dataBuffer: [],
            historicalData: [],
            selectedChannels: [], // Will be auto-detected from data
            timeRange: {
                start: null,
                end: null,
            },
        },

        // Fungsi inisialisasi utama yang akan dipanggil oleh x-init
        initComponent() {
            console.log(
                "Initializing Alpine component with WebSocket integration..."
            );

            // Initialize immediate fixes components
            this.initImmediateFixes();

            // Initialize selected channels from backend if available
            if (
                Array.isArray(window.ANALYSIS_DEFAULT_TAGS) &&
                window.ANALYSIS_DEFAULT_TAGS.length > 0
            ) {
                this.state.selectedChannels = [...window.ANALYSIS_DEFAULT_TAGS];
                console.log(
                    "Selected channels from backend:",
                    this.state.selectedChannels
                );
            }

            // Set time range dari input fields
            this.state.timeRange.start =
                document.querySelector('input[name="startDate"]')?.value ||
                new Date(Date.now() - 24 * 60 * 60 * 1000)
                    .toISOString()
                    .slice(0, 16);
            this.state.timeRange.end =
                document.querySelector('input[name="endDate"]')?.value ||
                new Date().toISOString().slice(0, 16);

            this.initPlotlyChart();
            this.setupEventListeners();
            this.startWebSocketConnection();

            // Listener untuk event dari backend Livewire setelah data historis siap
            window.Livewire.on("historicalDataLoaded", (event) => {
                console.log("Historical data received, populating chart...");
                this.populateChartWithInitialData(event.data);
            });

            // Listener untuk menangani error jika pemuatan data gagal
            window.Livewire.on("historicalDataError", (event) => {
                console.error("Failed to load historical data:", event.message);
                this.showError("Error: " + event.message);
            });
        },

        // Initialize immediate fixes components
        initImmediateFixes() {
            // Initialize throttler with 100ms throttle
            this.chartThrottler = new ChartThrottler(100);

            // Initialize data buffer with 50 items or 1 second flush
            this.dataBuffer = new DataBuffer(50, 1000);

            // Initialize WebSocket manager (replaces SSE)
            this.websocketManager = new WebSocketManager({
                host: window.location.hostname || "127.0.0.1",
                port: 8080, // Use Reverb port
                appKey: "scada_dashboard_key_2024",
                reconnectAttempts: 5,
                initialReconnectDelay: 2000,
                maxReconnectDelay: 10000,
                onMessage: this.handleWebSocketMessage.bind(this),
                onConnect: () => {
                    console.log("WebSocket connected successfully");
                    this.state.isConnected = true;
                    this.updateConnectionStatus();
                    this.hideConnectionError();
                },
                onError: (error) => {
                    console.error("WebSocket connection failed:", error);
                    this.state.isConnected = false;
                    this.updateConnectionStatus();
                    this.showError(
                        "WebSocket Connection Error: " + error.message
                    );
                },
                onDisconnect: () => {
                    console.log("WebSocket disconnected");
                    this.state.isConnected = false;
                    this.updateConnectionStatus();
                },
            });

            // Initialize chart data manager with 1000 max points and 30s cleanup
            this.chartDataManager = new ChartDataManager(1000, 30000);

            // Initialize performance tracker
            this.performanceTracker = new PerformanceTracker();

            // Set up chart data cleanup timer (setiap 60 detik)
            this.chartCleanupTimer = setInterval(() => {
                this.cleanupChartData();
            }, 60000); // 60 detik

            // Set up buffer flush callback
            this.dataBuffer.setFlushCallback((bufferedData) => {
                console.log(
                    `Processing ${bufferedData.length} buffered data points`
                );

                // Aggregate data if needed
                const aggregatedData = this.aggregateData(bufferedData);

                // Update chart with throttled aggregated data
                this.chartThrottler.throttleUpdate(
                    aggregatedData,
                    (throttledData) => {
                        this.updateChartWithThrottledData(throttledData);
                    }
                );
            });
        },

        // Handle WebSocket message with throttling and buffering
        handleWebSocketMessage(data) {
            try {
                console.log("Raw WebSocket data received:", data);

                // Extract actual data from the event structure
                let actualData = data;
                if (data && data.data) {
                    actualData = data.data;
                }

                // Record data received for performance tracking
                this.performanceTracker.recordDataReceived();

                // Add data to buffer for processing
                this.dataBuffer.addData(actualData);

                // Add to chart data manager for memory management
                this.chartDataManager.addData(actualData);

                console.log(
                    "WebSocket data processed successfully:",
                    actualData
                );
            } catch (error) {
                console.error("Error processing WebSocket data:", error);
            }
        },

        // Aggregate buffered data
        aggregateData(bufferedData) {
            if (bufferedData.length === 0) return null;

            // Simple aggregation - take the latest value for each channel
            const aggregated = {};
            const channels = this.state.selectedChannels;

            channels.forEach((channel) => {
                let channelData = [];

                // Handle both raw and aggregated data
                bufferedData.forEach((item) => {
                    const data = item.data;
                    let value = null;

                    if (data.time_bucket) {
                        // Aggregated data
                        if (data[`avg_${channel}`] !== undefined) {
                            value = data[`avg_${channel}`];
                        } else if (data[`max_${channel}`] !== undefined) {
                            value = data[`max_${channel}`];
                        } else if (data[`min_${channel}`] !== undefined) {
                            value = data[`min_${channel}`];
                        }
                    } else if (data.timestamp_device) {
                        // Raw data
                        value = data[channel];
                    }

                    if (value !== null) {
                        channelData.push({
                            timestamp:
                                data.time_bucket || data.timestamp_device,
                            value: value,
                        });
                    }
                });

                if (channelData.length > 0) {
                    // Take the latest value
                    const latest = channelData[channelData.length - 1];
                    aggregated[channel] = {
                        timestamp: latest.timestamp,
                        value: latest.value,
                    };
                }
            });

            return aggregated;
        },

        // Update chart with throttled data
        updateChartWithThrottledData(data) {
            if (!this.plotlyChart || !data) return;

            try {
                const updates = {};
                const traces = [];

                Object.keys(data).forEach((channel, index) => {
                    const channelData = data[channel];
                    if (
                        channelData &&
                        channelData.timestamp &&
                        channelData.value !== null
                    ) {
                        updates[`x[${index}]`] = [channelData.timestamp];
                        updates[`y[${index}]`] = [channelData.value];
                        traces.push(index);
                    }
                });

                if (Object.keys(updates).length > 0) {
                    Plotly.extendTraces(this.plotlyChart, updates, traces);
                }
            } catch (error) {
                console.error(
                    "Error updating chart with throttled data:",
                    error
                );
            }
        },

        // Setup event listeners
        setupEventListeners() {
            // Chart type selector
            document.addEventListener("change", (e) => {
                if (e.target.name === "chartType") {
                    this.changeChartType(e.target.value);
                }
            });

            // Aggregation interval selector
            document.addEventListener("change", (e) => {
                if (e.target.name === "aggregationInterval") {
                    this.changeAggregationInterval(parseInt(e.target.value));
                }
            });

            // Channel selector
            document.addEventListener("change", (e) => {
                if (e.target.name === "channelSelect") {
                    this.toggleChannel(e.target.value, e.target.checked);
                }
            });

            // Play/Pause button
            document.addEventListener("click", (e) => {
                if (e.target.id === "playPauseBtn") {
                    this.togglePlayPause();
                }
            });

            // Export button
            document.addEventListener("click", (e) => {
                if (e.target.id === "exportBtn") {
                    this.exportData();
                }
            });

            // Time range picker
            document.addEventListener("change", (e) => {
                if (
                    e.target.name === "startDate" ||
                    e.target.name === "endDate"
                ) {
                    this.updateTimeRange();
                }
            });

            // Cleanup saat halaman ditutup
            window.addEventListener("beforeunload", () => {
                console.log("Page unloading, running safe cleanup...");
                this.safeCleanup();
            });

            // Backup cleanup untuk browser yang tidak mendukung beforeunload
            window.addEventListener("pagehide", () => {
                console.log("Page hiding, running safe cleanup...");
                this.safeCleanup();
            });

            // Triple backup cleanup
            window.addEventListener("unload", () => {
                console.log("Page unloading (unload), running safe cleanup...");
                this.safeCleanup();
            });

            // Pause/resume cleanup saat tab tidak aktif/aktif
            document.addEventListener("visibilitychange", () => {
                if (document.hidden) {
                    // Tab tidak aktif - pause cleanup
                    if (this.chartCleanupTimer) {
                        try {
                            clearInterval(this.chartCleanupTimer);
                            this.chartCleanupTimer = null;
                            console.log(
                                "Chart cleanup paused - tab not active"
                            );
                        } catch (e) {
                            console.warn("Failed to pause chart cleanup:", e);
                        }
                    }
                } else {
                    // Tab aktif kembali - resume cleanup
                    if (!this.chartCleanupTimer) {
                        try {
                            this.chartCleanupTimer = setInterval(() => {
                                this.cleanupChartData();
                            }, 60000); // 60 detik
                            console.log("Chart cleanup resumed - tab active");
                        } catch (e) {
                            console.warn("Failed to resume chart cleanup:", e);
                        }
                    }
                }
            });
        },

        // Hide connection error
        hideConnectionError() {
            const errorEl = document.querySelector(".connection-error");
            if (errorEl) {
                errorEl.style.display = "none";
            }
        },

        // Show connection error
        showConnectionError(message) {
            let errorEl = document.querySelector(".connection-error");
            if (!errorEl) {
                errorEl = document.createElement("div");
                errorEl.className =
                    "connection-error fixed top-4 left-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50";
                document.body.appendChild(errorEl);
            }
            errorEl.textContent = message;
            errorEl.style.display = "block";
        },

        // Fungsi untuk menginisialisasi chart Plotly
        initPlotlyChart() {
            const chartDiv = document.getElementById("analysisChart");
            const layout = {
                title: {
                    text: "Real-time SCADA Data Analysis",
                    font: { size: 18, color: "#374151" },
                },
                xaxis: {
                    title: "Time",
                    gridcolor: this.chartConfig.gridColor,
                    showgrid: true,
                    zeroline: false,
                },
                yaxis: {
                    title: "Value",
                    gridcolor: this.chartConfig.gridColor,
                    showgrid: true,
                    zeroline: false,
                },
                plot_bgcolor: this.chartConfig.backgroundColor,
                paper_bgcolor: this.chartConfig.backgroundColor,
                showlegend: this.chartConfig.showLegend,
                legend: {
                    x: 0.02,
                    y: 0.98,
                    bgcolor: "rgba(255,255,255,0.8)",
                    bordercolor: "#e5e7eb",
                },
                margin: { l: 60, r: 30, t: 60, b: 60 },
                hovermode: "closest",
                dragmode: "zoom",
                modebar: {
                    orientation: "v",
                    bgcolor: "rgba(255,255,255,0.8)",
                    color: "#374151",
                },
            };

            const config = {
                responsive: true,
                displayModeBar: true,
                modeBarButtonsToRemove: ["pan2d", "lasso2d", "select2d"],
            };

            Plotly.newPlot(chartDiv, [], layout, config);
            this.plotlyChart = chartDiv;

            // Bind chart events
            this.plotlyChart.on(
                "plotly_relayout",
                this.handleChartRelayout.bind(this)
            );

            console.log("Plotly chart initialized successfully");
        },

        // Start WebSocket connection (replaces SSE)
        startWebSocketConnection() {
            if (this.websocketManager) {
                // Disconnect existing connection
                this.websocketManager.disconnect();
            }

            // Check if we're in a test environment
            if (
                window.location.pathname.includes("test-immediate-fixes.html")
            ) {
                console.log("WebSocket connection skipped in test environment");
                this.state.isConnected = true; // Simulate connection for testing
                this.updateConnectionStatus();
                return;
            }

            try {
                // Connect to WebSocket
                this.websocketManager.connect();

                // Subscribe to SCADA data channels
                setTimeout(() => {
                    if (this.websocketManager.isConnected) {
                        // Subscribe to multiple channels for better coverage
                        this.websocketManager.subscribe(
                            "scada-data",
                            "scada.data.received",
                            (data) => {
                                console.log(
                                    "SCADA data received via WebSocket (scada-data):",
                                    data
                                );
                                this.handleWebSocketMessage(data);
                            }
                        );

                        this.websocketManager.subscribe(
                            "scada-realtime",
                            "scada.data.received",
                            (data) => {
                                console.log(
                                    "SCADA realtime data received via WebSocket:",
                                    data
                                );
                                this.handleWebSocketMessage(data);
                            }
                        );

                        this.websocketManager.subscribe(
                            "scada-analysis",
                            "scada.data.received",
                            (data) => {
                                console.log(
                                    "SCADA analysis data received via WebSocket:",
                                    data
                                );
                                this.handleWebSocketMessage(data);
                            }
                        );

                        console.log(
                            "WebSocket connection started and subscribed to multiple SCADA channels"
                        );
                    }
                }, 1000);
            } catch (error) {
                console.error("Failed to start WebSocket connection:", error);
                this.showError(
                    "Failed to start WebSocket connection: " + error.message
                );
            }
        },

        // Fungsi untuk mengisi chart dengan data historis awal
        populateChartWithInitialData(initialData) {
            if (!this.plotlyChart || !initialData || initialData.length === 0)
                return;

            console.log(
                "Populating chart with",
                initialData.length,
                "data points"
            );

            // Reset data buffer
            this.state.historicalData = [...initialData];
            this.state.dataBuffer = [];

            // Logika untuk mengubah data mentah menjadi format trace Plotly
            const traces = {};

            // Auto-detect available channels from data
            let channels = this.state.selectedChannels;
            if (initialData.length > 0) {
                const sampleData = initialData[0] || {};
                const availableKeys = Object.keys(sampleData);

                // Handle both raw data and aggregated data
                let availableChannels = [];

                if (availableKeys.includes("time_bucket")) {
                    // Aggregated data (minute/hour/day level)
                    availableChannels = availableKeys.filter(
                        (key) =>
                            key.startsWith("avg_") ||
                            key.startsWith("max_") ||
                            key.startsWith("min_")
                    );

                    // Convert to base channel names (remove avg_, max_, min_ prefix)
                    const baseChannels = [
                        ...new Set(
                            availableChannels.map((ch) => {
                                if (ch.startsWith("avg_"))
                                    return ch.substring(4);
                                if (ch.startsWith("max_"))
                                    return ch.substring(4);
                                if (ch.startsWith("min_"))
                                    return ch.substring(4);
                                return ch;
                            })
                        ),
                    ];

                    channels = baseChannels.slice(0, 5); // Limit to 5 channels
                    console.log("Auto-detected aggregated channels:", channels);
                } else if (availableKeys.includes("timestamp_device")) {
                    // Raw data (second level)
                    availableChannels = availableKeys.filter(
                        (key) =>
                            key !== "timestamp_device" &&
                            key !== "nama_group" &&
                            key !== "id" &&
                            key !== "created_at" &&
                            key !== "updated_at" &&
                            typeof sampleData[key] === "number"
                    );

                    channels = availableChannels.slice(0, 5); // Limit to 5 channels
                    console.log("Auto-detected raw channels:", channels);
                }

                if (channels.length > 0) {
                    this.state.selectedChannels = channels;
                    console.log("Final selected channels:", channels);
                }
            }

            channels.forEach((channel) => {
                traces[channel] = {
                    x: [],
                    y: [],
                    mode: this.chartConfig.showMarkers
                        ? "lines+markers"
                        : "lines",
                    name: channel.toUpperCase(),
                    line: {
                        width: this.chartConfig.lineWidth,
                        color: this.chartConfig.colors[
                            channels.indexOf(channel) %
                                this.chartConfig.colors.length
                        ],
                    },
                    marker: {
                        size: this.chartConfig.markerSize,
                        color: this.chartConfig.colors[
                            channels.indexOf(channel) %
                                this.chartConfig.colors.length
                        ],
                    },
                    type: this.chartConfig.type,
                    fill: "none",
                };
            });

            // Populate traces dengan data historis
            initialData.forEach((d) => {
                channels.forEach((channel) => {
                    let value = null;
                    let timestamp = null;

                    // Handle different data formats
                    if (d.time_bucket) {
                        // Aggregated data
                        timestamp = d.time_bucket;
                        // Try to get average value first, then max, then min
                        if (d[`avg_${channel}`] !== undefined) {
                            value = d[`avg_${channel}`];
                        } else if (d[`max_${channel}`] !== undefined) {
                            value = d[`max_${channel}`];
                        } else if (d[`min_${channel}`] !== undefined) {
                            value = d[`min_${channel}`];
                        }
                    } else if (d.timestamp_device) {
                        // Raw data
                        timestamp = d.timestamp_device;
                        value = d[channel];
                    }

                    if (value !== null && timestamp) {
                        traces[channel].x.push(timestamp);
                        traces[channel].y.push(value);
                    }
                });
            });

            // Update chart
            const traceArray = Object.values(traces);
            Plotly.react(this.plotlyChart, traceArray);

            console.log("Chart populated with historical data");
        },

        // Handle chart relayout (zoom, pan, etc.)
        handleChartRelayout(eventData) {
            if (eventData["xaxis.range[0]"] && eventData["xaxis.range[1]"]) {
                // User melakukan zoom/pan, update time range
                this.state.timeRange.start = new Date(
                    eventData["xaxis.range[0]"]
                ).toISOString();
                this.state.timeRange.end = new Date(
                    eventData["xaxis.range[1]"]
                ).toISOString();
            }
        },

        // Change chart type
        changeChartType(type) {
            this.chartConfig.type = type;

            if (this.plotlyChart) {
                Plotly.restyle(this.plotlyChart, "type", type);
            }
        },

        // Change aggregation interval
        changeAggregationInterval(interval) {
            this.dataConfig.aggregationInterval = interval;

            // Reload historical data dengan interval baru
            if (window.Livewire && window.Livewire.find) {
                const component = window.Livewire.find(
                    document
                        .querySelector("[wire\\:id]")
                        ?.getAttribute("wire:id")
                );
                if (component) {
                    component.call(
                        "loadHistoricalData",
                        this.state.timeRange.start,
                        this.state.timeRange.end,
                        interval
                    );
                }
            }
        },

        // Toggle channel visibility
        toggleChannel(channel, visible) {
            if (visible) {
                if (!this.state.selectedChannels.includes(channel)) {
                    this.state.selectedChannels.push(channel);
                }
            } else {
                const index = this.state.selectedChannels.indexOf(channel);
                if (index > -1) {
                    this.state.selectedChannels.splice(index, 1);
                }
            }

            // Reload chart dengan channels yang dipilih
            this.reloadChartWithSelectedChannels();
        },

        // Reload chart dengan channels yang dipilih
        reloadChartWithSelectedChannels() {
            if (!this.plotlyChart || this.state.historicalData.length === 0)
                return;

            this.populateChartWithInitialData(this.state.historicalData);
        },

        // Toggle play/pause
        togglePlayPause() {
            this.state.isPlaying = !this.state.isPlaying;

            if (this.state.isPlaying) {
                this.startRealtimeUpdates();
            } else {
                this.stopRealtimeUpdates();
            }

            this.updatePlayPauseButton();
        },

        // Start realtime updates
        startRealtimeUpdates() {
            this.dataConfig.realtimeEnabled = true;
            this.startWebSocketConnection();
        },

        // Stop realtime updates
        stopRealtimeUpdates() {
            this.dataConfig.realtimeEnabled = false;
            if (this.websocketManager) {
                this.websocketManager.disconnect();
            }
        },

        // Update play/pause button
        updatePlayPauseButton() {
            const btn = document.getElementById("playPauseBtn");
            if (btn) {
                btn.innerHTML = this.state.isPlaying
                    ? '<i class="fas fa-pause"></i> Pause'
                    : '<i class="fas fa-play"></i> Play';
                btn.className = this.state.isPlaying
                    ? "bg-red-500 hover:bg-red-600"
                    : "bg-green-500 hover:bg-green-600";
            }
        },

        // Update connection status
        updateConnectionStatus() {
            const statusEl = document.getElementById("connectionStatus");
            if (statusEl) {
                statusEl.className = this.state.isConnected
                    ? "text-green-600"
                    : "text-red-600";
                statusEl.textContent = this.state.isConnected
                    ? "Connected"
                    : "Disconnected";
            }
        },

        // Update time range
        updateTimeRange() {
            const startDate = document.querySelector(
                'input[name="startDate"]'
            ).value;
            const endDate = document.querySelector(
                'input[name="endDate"]'
            ).value;

            if (startDate && endDate) {
                this.state.timeRange.start = startDate;
                this.state.timeRange.end = endDate;

                // Reload historical data
                if (window.Livewire && window.Livewire.find) {
                    const component = window.Livewire.find(
                        document
                            .querySelector("[wire\\:id]")
                            ?.getAttribute("wire:id")
                    );
                    if (component) {
                        component.call(
                            "loadHistoricalData",
                            startDate,
                            endDate,
                            this.dataConfig.aggregationInterval
                        );
                    }
                }
            }
        },

        // Export data
        exportData() {
            if (this.state.historicalData.length === 0) {
                this.showError("No data to export");
                return;
            }

            // Prepare CSV data
            const channels = this.state.selectedChannels;
            const headers = ["Time", ...channels.map((ch) => ch.toUpperCase())];

            const csvContent = [
                headers.join(","),
                ...this.state.historicalData.map((row) => {
                    const values = [row.time_bucket || row.terminal_time];
                    channels.forEach((channel) => {
                        values.push(row[channel] || "");
                    });
                    return values.join(",");
                }),
            ].join("\n");

            // Create download link
            const blob = new Blob([csvContent], { type: "text/csv" });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `scada_data_${
                new Date().toISOString().split("T")[0]
            }.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },

        // Show error message
        showError(message) {
            // Create error notification
            const notification = document.createElement("div");
            notification.className =
                "fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50";
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-red-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        },

        // Cleanup function - BULLETPROOF VERSION
        cleanup() {
            console.log("Cleanup function called - BULLETPROOF VERSION");

            try {
                // SUPER SAFE CHECK: Cek setiap properti secara berurutan.
                // Ini adalah cara paling aman untuk menghindari error 'cannot read properties of undefined'.
                if (
                    this.plotlyChart &&
                    this.plotlyChart.data &&
                    Array.isArray(this.plotlyChart.data) &&
                    this.plotlyChart.data.length > 0 &&
                    this.plotlyChart.data[0] &&
                    this.plotlyChart.data[0].x
                ) {
                    const now = new Date().getTime();
                    const maxDataPoints = this.state?.maxDataPoints || 1000;
                    // Set cutoff untuk data yang lebih tua dari 1.5x kapasitas chart
                    const cutoff = now - maxDataPoints * 1500;

                    const pointsToRemove = this.plotlyChart.data[0].x.filter(
                        (x, index) => x < cutoff
                    );

                    if (pointsToRemove.length > 0) {
                        console.log(
                            `Cleanup: Removing ${pointsToRemove.length} old data points.`
                        );

                        // Hapus semua titik data yang sudah tua tanpa me-redraw chart
                        pointsToRemove.forEach((_, index) => {
                            try {
                                // Untuk Plotly, kita perlu membuat data baru tanpa titik yang lama
                                const newX = this.plotlyChart.data[0].x.filter(
                                    (_, i) => i !== index
                                );
                                const newY = this.plotlyChart.data[0].y
                                    ? this.plotlyChart.data[0].y.filter(
                                          (_, i) => i !== index
                                      )
                                    : [];

                                // Update chart data
                                Plotly.restyle(this.plotlyChart, {
                                    x: [newX],
                                    y: [newY],
                                });
                            } catch (e) {
                                // Abaikan jika titik sudah dihapus atau tidak valid
                                console.warn(
                                    "Point removal failed, continuing...",
                                    e
                                );
                            }
                        });
                    }
                } else {
                    // Jika chart belum siap, cukup berikan pesan di konsol dan jangan lakukan apa-apa.
                    console.warn(
                        "Cleanup skipped: Chart object or its data series is not yet initialized."
                    );
                }
            } catch (error) {
                console.error("Error during chart cleanup:", error);
                // Jangan biarkan error ini menghentikan proses cleanup lainnya
            }

            // Cleanup WebSocket manager
            if (this.websocketManager) {
                try {
                    this.websocketManager.disconnect();
                } catch (e) {
                    console.warn("WebSocket cleanup failed:", e);
                }
            }

            // Cleanup chart data manager
            if (this.chartDataManager) {
                try {
                    this.chartDataManager.stopCleanupTimer();
                } catch (e) {
                    console.warn("Chart data manager cleanup failed:", e);
                }
            }

            // Cleanup performance tracker
            if (this.performanceTracker) {
                try {
                    // Stop monitoring
                    clearInterval(this.performanceTracker.monitoringInterval);
                } catch (e) {
                    console.warn("Performance tracker cleanup failed:", e);
                }
            }

            // Cleanup chart data cleanup timer
            if (this.chartCleanupTimer) {
                try {
                    clearInterval(this.chartCleanupTimer);
                    this.chartCleanupTimer = null;
                } catch (e) {
                    console.warn("Chart cleanup timer cleanup failed:", e);
                }
            }

            // Cleanup plotly chart
            if (this.plotlyChart) {
                try {
                    Plotly.purge(this.plotlyChart);
                    this.plotlyChart = null;
                } catch (e) {
                    console.warn("Plotly chart cleanup failed:", e);
                }
            }

            console.log("Component cleanup completed - BULLETPROOF VERSION");
        },

        // Safe chart data cleanup function - BULLETPROOF VERSION
        cleanupChartData() {
            console.log(
                "Attempting to run chart data cleanup - BULLETPROOF VERSION"
            );

            try {
                // SUPER SAFE CHECK: Cek setiap properti secara berurutan
                if (!this.plotlyChart) {
                    console.warn(
                        "Chart data cleanup skipped: plotlyChart is not initialized"
                    );
                    return;
                }

                if (!this.plotlyChart.data) {
                    console.warn(
                        "Chart data cleanup skipped: plotlyChart.data is not available"
                    );
                    return;
                }

                if (!Array.isArray(this.plotlyChart.data)) {
                    console.warn(
                        "Chart data cleanup skipped: plotlyChart.data is not an array"
                    );
                    return;
                }

                if (this.plotlyChart.data.length === 0) {
                    console.warn(
                        "Chart data cleanup skipped: plotlyChart.data is empty"
                    );
                    return;
                }

                if (!this.plotlyChart.data[0]) {
                    console.warn(
                        "Chart data cleanup skipped: plotlyChart.data[0] is not available"
                    );
                    return;
                }

                if (!this.plotlyChart.data[0].x) {
                    console.warn(
                        "Chart data cleanup skipped: plotlyChart.data[0].x is not available"
                    );
                    return;
                }

                if (!Array.isArray(this.plotlyChart.data[0].x)) {
                    console.warn(
                        "Chart data cleanup skipped: plotlyChart.data[0].x is not an array"
                    );
                    return;
                }

                const now = new Date().getTime();
                const maxDataPoints = this.state?.maxDataPoints || 1000;
                const cutoff = now - maxDataPoints * 1.5 * 1000; // 1.5x dari kapasitas maksimal

                // Ambil data series dengan aman
                const chartData = this.plotlyChart.data[0];

                // Filter data yang akan dihapus dengan cara yang aman
                const pointsToRemove = [];
                for (let i = 0; i < chartData.x.length; i++) {
                    try {
                        if (chartData.x[i] < cutoff) {
                            pointsToRemove.push(i);
                        }
                    } catch (e) {
                        console.warn(`Error checking point ${i}:`, e);
                        continue;
                    }
                }

                if (pointsToRemove.length > 0) {
                    console.log(
                        `Chart data cleanup: Removing ${pointsToRemove.length} old data points.`
                    );

                    try {
                        // Buat data baru tanpa titik yang lama
                        const newX = chartData.x.filter(
                            (_, index) => !pointsToRemove.includes(index)
                        );
                        const newY = chartData.y
                            ? chartData.y.filter(
                                  (_, index) => !pointsToRemove.includes(index)
                              )
                            : [];

                        // Update chart data dengan cara yang aman
                        const updateData = {
                            x: [newX],
                            y: [newY],
                        };

                        // Update chart dengan data yang sudah dibersihkan
                        Plotly.restyle(this.plotlyChart, updateData);

                        console.log(
                            `Chart data cleanup completed. Data points reduced from ${chartData.x.length} to ${newX.length}`
                        );

                        // Catat waktu terakhir cleanup
                        this.lastCleanupTime = new Date().toISOString();
                    } catch (error) {
                        console.error("Error updating chart data:", error);
                    }
                } else {
                    console.log(
                        "Chart data cleanup: No old data points to remove."
                    );
                }
            } catch (error) {
                console.error("Error during chart data cleanup:", error);
                // Jangan biarkan error ini menghentikan aplikasi
            }
        },

        // SUPER SAFE CLEANUP - Fungsi cleanup yang paling aman
        safeCleanup() {
            console.log("=== SUPER SAFE CLEANUP STARTED ===");

            try {
                // 1. Cleanup chart data dengan cara yang aman
                this.cleanupChartData();
            } catch (e) {
                console.warn("Chart data cleanup failed, continuing...", e);
            }

            try {
                // 2. Cleanup komponen dengan cara yang aman
                this.cleanup();
            } catch (e) {
                console.warn("Component cleanup failed, continuing...", e);
            }

            try {
                // 3. Cleanup tambahan yang aman
                this.forceCleanup();
            } catch (e) {
                console.warn("Force cleanup failed, continuing...", e);
            }

            console.log("=== SUPER SAFE CLEANUP COMPLETED ===");
        },

        // Force cleanup - Cleanup yang dipaksa untuk kasus ekstrem
        forceCleanup() {
            console.log("Force cleanup started...");

            // Hentikan semua timer yang mungkin ada
            const timers = [
                this.chartCleanupTimer,
                this.performanceTracker?.monitoringInterval,
                this.chartDataManager?.cleanupTimer,
            ];

            timers.forEach((timer) => {
                if (timer) {
                    try {
                        clearInterval(timer);
                        clearTimeout(timer);
                    } catch (e) {
                        console.warn("Timer cleanup failed:", e);
                    }
                }
            });

            // Reset semua properti
            this.chartCleanupTimer = null;
            this.plotlyChart = null;

            console.log("Force cleanup completed");
        },

        // Emergency cleanup - Cleanup darurat untuk kasus yang sangat ekstrem
        emergencyCleanup() {
            console.log("🚨 EMERGENCY CLEANUP STARTED 🚨");

            try {
                // 1. Hentikan semua timer dengan cara yang sangat agresif
                for (let i = 1; i <= 1000; i++) {
                    try {
                        clearInterval(i);
                        clearTimeout(i);
                    } catch (e) {
                        // Abaikan error
                    }
                }

                // 2. Reset semua properti yang mungkin bermasalah
                this.chartCleanupTimer = null;
                this.plotlyChart = null;
                this.websocketManager = null;
                this.chartDataManager = null;
                this.performanceTracker = null;

                // 3. Hapus semua event listener yang mungkin bermasalah
                try {
                    window.removeEventListener(
                        "beforeunload",
                        this.safeCleanup
                    );
                    window.removeEventListener("pagehide", this.safeCleanup);
                    window.removeEventListener("unload", this.safeCleanup);
                    document.removeEventListener(
                        "visibilitychange",
                        this.handleVisibilityChange
                    );
                } catch (e) {
                    console.warn("Event listener removal failed:", e);
                }

                console.log("🚨 EMERGENCY CLEANUP COMPLETED 🚨");
            } catch (e) {
                console.error("Emergency cleanup failed:", e);
            }
        },

        // Manual cleanup function untuk dipanggil dari luar
        manualCleanup() {
            console.log("Manual cleanup requested...");

            // Gunakan safe cleanup yang lebih aman
            this.safeCleanup();

            console.log("Manual cleanup completed");
        },

        // Set cleanup interval secara dinamis
        setCleanupInterval(intervalMs) {
            // Hentikan timer yang ada
            if (this.chartCleanupTimer) {
                clearInterval(this.chartCleanupTimer);
                this.chartCleanupTimer = null;
            }

            // Buat timer baru dengan interval yang diinginkan
            if (intervalMs > 0) {
                this.chartCleanupTimer = setInterval(() => {
                    this.cleanupChartData();
                }, intervalMs);
                console.log(`Chart cleanup interval set to ${intervalMs}ms`);
            }
        },

        // Get cleanup status
        getCleanupStatus() {
            return {
                isActive: !!this.chartCleanupTimer,
                interval: this.chartCleanupTimer ? 60000 : 0, // Default 60 detik
                lastCleanup: this.lastCleanupTime || null,
                chartDataPoints: this.plotlyChart?.data?.[0]?.x?.length || 0,
            };
        },

        // Test function untuk verifikasi throttling
        testThrottling() {
            console.log("Testing throttling implementation...");

            let testCount = 0;
            const testInterval = setInterval(() => {
                testCount++;

                // Simulate high-frequency data
                this.handleWebSocketMessage({
                    timestamp: Date.now(),
                    value: Math.random() * 100,
                    channel: "CH1",
                });

                if (testCount >= 100) {
                    clearInterval(testInterval);
                    console.log("Throttling test completed");
                }
            }, 10); // 10ms interval (100x per second)
        },

        // Handle new data for testing
        handleNewData(data) {
            // Record data received for performance tracking
            this.performanceTracker.recordDataReceived();

            // Add data to buffer for processing
            this.dataBuffer.addData(data);

            // Add to chart data manager for memory management
            this.chartDataManager.addData(data);
        },
    }));
});

// Make component accessible globally for debugging
window.AnalysisChartDebug = {
    getComponent: () => {
        const component = document.querySelector(
            '[x-data="analysisChartComponent"]'
        );
        if (component && component.__x) {
            return component.__x.$data;
        }
        return null;
    },

    safeCleanup: () => {
        const component = window.AnalysisChartDebug.getComponent();
        if (component) {
            component.safeCleanup();
        } else {
            console.warn("Component not found");
        }
    },

    emergencyCleanup: () => {
        const component = window.AnalysisChartDebug.getComponent();
        if (component) {
            component.emergencyCleanup();
        } else {
            console.warn("Component not found");
        }
    },

    getStatus: () => {
        const component = window.AnalysisChartDebug.getComponent();
        if (component) {
            return component.getCleanupStatus();
        } else {
            return { error: "Component not found" };
        }
    },
};

// Test script untuk memverifikasi throttling (run setelah 5 detik)
setTimeout(() => {
    if (window.Alpine && window.Alpine.store) {
        const component = document.querySelector(
            '[x-data="analysisChartComponent"]'
        );
        if (component && component.__x) {
            component.__x.$data.testThrottling();
        }
    }
}, 5000);

// Export untuk ES6 modules
export default {};
