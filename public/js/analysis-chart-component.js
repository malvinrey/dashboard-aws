// public/js/analysis-chart-component.js
// Alpine.js Component untuk Analysis Chart

document.addEventListener("alpine:init", () => {
    Alpine.data("analysisChartComponent", () => ({
        // Properti untuk menyimpan instance dari worker dan chart
        sseWorker: null,
        plotlyChart: null,

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
            selectedChannels: ["ch1", "ch2", "ch3"],
            timeRange: {
                start: null,
                end: null,
            },
        },

        // Fungsi inisialisasi utama yang akan dipanggil oleh x-init
        initComponent() {
            console.log("Initializing Alpine component from external file...");

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
            this.startSseConnection();

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

        // Setup event listeners untuk UI controls
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
                displaylogo: false,
            };

            // Membuat chart Plotly baru dengan data kosong
            Plotly.newPlot(chartDiv, [], layout, config);
            this.plotlyChart = chartDiv;

            // Setup event listeners untuk chart
            chartDiv.on("plotly_relayout", (eventData) => {
                this.handleChartRelayout(eventData);
            });
        },

        // Fungsi untuk memulai koneksi SSE melalui Web Worker
        startSseConnection() {
            if (this.sseWorker) {
                this.sseWorker.terminate(); // Hentikan worker lama jika ada
            }

            try {
                this.sseWorker = new Worker("/js/sse-worker.js");

                // Kirim URL ke worker untuk memulai koneksi
                const params = new URLSearchParams(window.location.search);
                const sseUrl = `/api/sse/stream?${params.toString()}`;
                this.sseWorker.postMessage({ command: "start", url: sseUrl });

                // Terima data dari worker dan update chart
                this.sseWorker.onmessage = (event) => {
                    const data = event.data;
                    if (data && !data.error) {
                        this.updatePlotlyRealtime(data);
                        this.state.isConnected = true;
                        this.state.lastUpdate = new Date();
                        this.updateConnectionStatus();
                    } else if (data && data.error) {
                        console.error("SSE Error:", data.error);
                        this.state.isConnected = false;
                        this.updateConnectionStatus();
                        this.showError("SSE Connection Error: " + data.error);
                    }
                };

                // Handle worker errors
                this.sseWorker.onerror = (error) => {
                    console.error("Worker error:", error);
                    this.state.isConnected = false;
                    this.updateConnectionStatus();
                    this.showError("Worker Error: " + error.message);
                };

                // Auto-reconnect jika koneksi terputus
                setInterval(() => {
                    if (
                        !this.state.isConnected &&
                        this.dataConfig.realtimeEnabled
                    ) {
                        console.log("Attempting to reconnect SSE...");
                        this.startSseConnection();
                    }
                }, 10000); // Coba reconnect setiap 10 detik
            } catch (error) {
                console.error("Failed to create SSE worker:", error);
                this.showError("Failed to create SSE worker: " + error.message);
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
            const channels = this.state.selectedChannels;

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
                    if (d[channel] !== undefined && d[channel] !== null) {
                        traces[channel].x.push(
                            d.time_bucket || d.terminal_time
                        );
                        traces[channel].y.push(d[channel]);
                    }
                });
            });

            // Filter traces yang memiliki data
            const validTraces = Object.values(traces).filter(
                (trace) => trace.x.length > 0
            );

            if (validTraces.length > 0) {
                Plotly.react(this.plotlyChart, validTraces);
                console.log(
                    "Chart populated with",
                    validTraces.length,
                    "traces"
                );
            } else {
                console.warn("No valid data traces found");
            }
        },

        // Fungsi untuk menambahkan data real-time ke chart Plotly
        updatePlotlyRealtime(newData) {
            if (!this.plotlyChart || !this.dataConfig.realtimeEnabled) return;

            // Tambahkan ke buffer
            this.state.dataBuffer.push(newData);

            // Batasi ukuran buffer
            if (this.state.dataBuffer.length > this.dataConfig.bufferSize) {
                this.state.dataBuffer.shift();
            }

            // Update chart dengan data baru
            this.updateChartWithBuffer();
        },

        // Update chart dengan data dari buffer
        updateChartWithBuffer() {
            if (!this.plotlyChart || this.state.dataBuffer.length === 0) return;

            const channels = this.state.selectedChannels;
            const updates = {};
            const traces = [];

            // Siapkan update untuk setiap channel
            channels.forEach((channel, index) => {
                const channelData = this.state.dataBuffer.filter(
                    (d) => d[channel] !== undefined && d[channel] !== null
                );

                if (channelData.length > 0) {
                    updates[`x[${index}]`] = channelData.map(
                        (d) => d.terminal_time
                    );
                    updates[`y[${index}]`] = channelData.map((d) => d[channel]);
                    traces.push(index);
                }
            });

            // Update chart jika ada data
            if (Object.keys(updates).length > 0) {
                Plotly.extendTraces(this.plotlyChart, updates, traces);

                // Auto-scroll jika chart sedang zoom
                const layout = this.plotlyChart.layout;
                if (layout.xaxis && layout.xaxis.range) {
                    // Chart sedang di-zoom, jangan auto-scroll
                } else {
                    // Auto-scroll ke data terbaru
                    Plotly.relayout(this.plotlyChart, {
                        "xaxis.range": [
                            this.state.dataBuffer[0].terminal_time,
                            this.state.dataBuffer[
                                this.state.dataBuffer.length - 1
                            ].terminal_time,
                        ],
                    });
                }
            }
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
            this.startSseConnection();
        },

        // Stop realtime updates
        stopRealtimeUpdates() {
            this.dataConfig.realtimeEnabled = false;
            if (this.sseWorker) {
                this.sseWorker.terminate();
                this.sseWorker = null;
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

        // Cleanup function
        cleanup() {
            if (this.sseWorker) {
                this.sseWorker.terminate();
                this.sseWorker = null;
            }

            if (this.plotlyChart) {
                Plotly.purge(this.plotlyChart);
                this.plotlyChart = null;
            }
        },
    }));
});
