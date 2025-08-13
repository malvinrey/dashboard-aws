/**
 * SCADA Chart Manager dengan throttling dan data buffering
 * Mengatasi masalah data firehose dengan implementasi yang efisien
 */
class ScadaChartManager {
    constructor(chartElement, options = {}) {
        this.chartElement = chartElement;
        this.options = {
            maxDataPoints: options.maxDataPoints || 1000,
            updateInterval: options.updateInterval || 100, // Throttle to 10 FPS max
            aggregationEnabled: options.aggregationEnabled || true,
            bufferSize: options.bufferSize || 100,
            ...options,
        };

        this.dataBuffer = [];
        this.chartData = [];
        this.lastUpdateTime = 0;
        this.updateTimer = null;
        this.isUpdating = false;
        this.isInitialized = false;

        // Performance tracking
        this.metrics = {
            updates: 0,
            dataPoints: 0,
            renderTime: 0,
            bufferOverflows: 0,
        };

        this.initializeChart();
        this.startUpdateLoop();
    }

    initializeChart() {
        // Initialize Plotly chart
        if (window.Plotly && this.chartElement) {
            const layout = {
                title: "SCADA Real-time Data",
                xaxis: {
                    title: "Time",
                    type: "date",
                    rangeslider: { visible: false },
                },
                yaxis: { title: "Value" },
                autosize: true,
                margin: { l: 50, r: 50, t: 50, b: 50 },
                template: "plotly_white",
                showlegend: true,
                legend: { orientation: "h", y: 1.1 },
                hovermode: "closest",
                dragmode: "zoom",
            };

            const config = {
                responsive: true,
                displayModeBar: true,
                modeBarButtonsToRemove: ["pan2d", "lasso2d", "select2d"],
                displaylogo: false,
                toImageButtonOptions: {
                    format: "png",
                    filename: "scada_chart",
                    height: 600,
                    width: 800,
                    scale: 1,
                },
            };

            Plotly.newPlot(this.chartElement, [], layout, config);
            this.isInitialized = true;

            console.log("Chart initialized successfully");
        } else {
            console.warn("Plotly not available or chart element not found");
        }
    }

    addData(data) {
        const timestamp = Date.now();

        // Add data to buffer with throttling
        this.dataBuffer.push({
            ...data,
            timestamp: timestamp,
        });

        // Prevent buffer overflow
        if (this.dataBuffer.length > this.options.bufferSize * 2) {
            this.dataBuffer = this.dataBuffer.slice(-this.options.bufferSize);
            this.metrics.bufferOverflows++;
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

        // Throttle updates based on interval
        const now = Date.now();
        if (now - this.lastUpdateTime < this.options.updateInterval) {
            return;
        }

        this.isUpdating = true;
        this.lastUpdateTime = now;
        const startTime = performance.now();

        try {
            // Get data from buffer
            const dataToProcess = [...this.dataBuffer];
            this.dataBuffer = [];

            // Aggregate data if enabled
            let processedData = dataToProcess;
            if (this.options.aggregationEnabled && dataToProcess.length > 1) {
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
            const channel = item.channel || item.nama_group || "unknown";

            if (!aggregated[channel]) {
                aggregated[channel] = {
                    values: [],
                    timestamps: [],
                    count: 0,
                    sum: 0,
                    min: null,
                    max: null,
                };
            }

            const value =
                item.value ||
                item.temperature ||
                item.humidity ||
                item.pressure ||
                0;
            if (value !== null && value !== undefined) {
                aggregated[channel].values.push(parseFloat(value));
                aggregated[channel].timestamps.push(item.timestamp);
                aggregated[channel].count++;
                aggregated[channel].sum += parseFloat(value);

                if (
                    aggregated[channel].min === null ||
                    value < aggregated[channel].min
                ) {
                    aggregated[channel].min = value;
                }
                if (
                    aggregated[channel].max === null ||
                    value > aggregated[channel].max
                ) {
                    aggregated[channel].max = value;
                }
            }
        });

        // Calculate aggregated values
        const result = [];

        Object.keys(aggregated).forEach((channel) => {
            const data = aggregated[channel];

            if (data.count > 0) {
                result.push({
                    channel: channel,
                    value: data.sum / data.count, // Average
                    timestamp: Math.max(...data.timestamps),
                    count: data.count,
                    min: data.min,
                    max: data.max,
                    raw_values: data.values,
                });
            }
        });

        return result;
    }

    updateChart(data) {
        if (!this.isInitialized || !window.Plotly || !this.chartElement) return;

        try {
            // Prepare data for Plotly
            const traces = this.prepareTraces(data);

            // Update chart with new data
            Plotly.react(this.chartElement, traces, {
                title: "SCADA Real-time Data",
                xaxis: {
                    title: "Time",
                    type: "date",
                    rangeslider: { visible: false },
                },
                yaxis: { title: "Value" },
                showlegend: true,
                legend: { orientation: "h", y: 1.1 },
            });

            // Update statistics
            this.updateStatistics(data);
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
                    marker: { size: 4 },
                    connectgaps: false,
                    hovertemplate:
                        "<b>%{fullData.name}</b><br>" +
                        "Time: %{x}<br>" +
                        "Value: %{y:.2f}<br>" +
                        "<extra></extra>",
                };
            }

            traces[channel].x.push(new Date(item.timestamp));
            traces[channel].y.push(item.value);
        });

        return Object.values(traces);
    }

    updateStatistics(data) {
        // Update UI statistics
        const totalDataPointsElement =
            document.getElementById("totalDataPoints");
        const activeChannelsElement = document.getElementById("activeChannels");
        const updateRateElement = document.getElementById("updateRate");

        if (totalDataPointsElement) {
            totalDataPointsElement.textContent = this.metrics.dataPoints;
        }

        if (activeChannelsElement) {
            const uniqueChannels = new Set(data.map((item) => item.channel))
                .size;
            activeChannelsElement.textContent = uniqueChannels;
        }

        if (updateRateElement) {
            const rate =
                this.metrics.updates > 0
                    ? (this.metrics.dataPoints / this.metrics.updates).toFixed(
                          1
                      )
                    : "0";
            updateRateElement.textContent = `${rate}/s`;
        }
    }

    // Performance monitoring
    getMetrics() {
        return {
            ...this.metrics,
            bufferSize: this.dataBuffer.length,
            chartDataSize: this.chartData.length,
            isUpdating: this.isUpdating,
            lastUpdateTime: this.lastUpdateTime,
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
        this.isInitialized = false;

        console.log("Chart manager destroyed");
    }

    // Public methods for external control
    pauseUpdates() {
        this.isUpdating = true;
        console.log("Chart updates paused");
    }

    resumeUpdates() {
        this.isUpdating = false;
        console.log("Chart updates resumed");
    }

    clearData() {
        this.dataBuffer = [];
        this.chartData = [];
        this.metrics.dataPoints = 0;
        console.log("Chart data cleared");
    }

    // Export functionality
    exportChart(format = "png") {
        if (!this.isInitialized || !window.Plotly) {
            console.error("Chart not initialized");
            return;
        }

        try {
            Plotly.downloadImage(this.chartElement, {
                format: format,
                filename: `scada_chart_${new Date()
                    .toISOString()
                    .slice(0, 19)
                    .replace(/:/g, "-")}`,
                height: 600,
                width: 800,
                scale: 1,
            });
        } catch (error) {
            console.error("Failed to export chart:", error);
        }
    }
}
