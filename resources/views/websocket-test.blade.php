<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Test - SCADA Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .connection-status {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .connected {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .disconnected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .connecting {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .data-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .metric-label {
            color: #6c757d;
            font-size: 14px;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }

        .log-container {
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
        }

        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .timestamp {
            color: #6c757d;
            font-size: 12px;
        }

        .data-value {
            font-family: monospace;
            background: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">WebSocket Test - SCADA Dashboard</h1>

                <!-- Connection Status -->
                <div id="connectionStatus" class="connection-status disconnected">
                    <strong>Status:</strong> <span id="statusText">Disconnected</span>
                    <button id="connectBtn" class="btn btn-primary btn-sm ms-3">Connect</button>
                    <button id="disconnectBtn" class="btn btn-danger btn-sm ms-2" disabled>Disconnect</button>
                </div>

                <!-- Connection Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="data-card">
                            <h5>Connection Info</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="metric-label">Server URL</div>
                                    <div class="metric-value" id="serverUrl">ws://localhost:6001</div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-label">Channel</div>
                                    <div class="metric-value" id="channelName">scada-data</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-card">
                            <h5>Connection Stats</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="metric-label">Messages Received</div>
                                    <div class="metric-value" id="messageCount">0</div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-label">Last Update</div>
                                    <div class="metric-value" id="lastUpdate">Never</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Real-time Data Display -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="data-card">
                            <h5>Temperature</h5>
                            <div class="metric-value" id="tempValue">--</div>
                            <div class="metric-label">°C</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="data-card">
                            <h5>Humidity</h5>
                            <div class="metric-value" id="humidityValue">--</div>
                            <div class="metric-label">%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="data-card">
                            <h5>Pressure</h5>
                            <div class="metric-value" id="pressureValue">--</div>
                            <div class="metric-label">hPa</div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="data-card">
                    <h5>Real-time Chart</h5>
                    <div class="chart-container">
                        <canvas id="realtimeChart"></canvas>
                    </div>
                </div>

                <!-- Message Log -->
                <div class="data-card">
                    <h5>Message Log</h5>
                    <div class="mb-3">
                        <button id="clearLogBtn" class="btn btn-secondary btn-sm">Clear Log</button>
                        <button id="exportLogBtn" class="btn btn-success btn-sm ms-2">Export Log</button>
                    </div>
                    <div id="messageLog" class="log-container">
                        <div class="text-muted">No messages received yet...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- WebSocket Client -->
    <script src="{{ asset('js/scada-websocket-client.js') }}"></script>

    <script>
        // Global variables
        let messageCount = 0;
        let chart;
        let chartData = {
            labels: [],
            temperature: [],
            humidity: [],
            pressure: []
        };

        // Initialize chart
        function initChart() {
            const ctx = document.getElementById('realtimeChart').getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                            label: 'Temperature (°C)',
                            data: chartData.temperature,
                            borderColor: '#ff6384',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Humidity (%)',
                            data: chartData.humidity,
                            borderColor: '#36a2eb',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Pressure (hPa)',
                            data: chartData.pressure,
                            borderColor: '#ffcd56',
                            backgroundColor: 'rgba(255, 205, 86, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Value'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });
        }

        // Update chart with new data
        function updateChart(data) {
            const timestamp = new Date().toLocaleTimeString();

            // Add new data points
            chartData.labels.push(timestamp);
            chartData.temperature.push(data.temperature || 0);
            chartData.humidity.push(data.humidity || 0);
            chartData.pressure.push(data.pressure || 0);

            // Keep only last 50 data points
            if (chartData.labels.length > 50) {
                chartData.labels.shift();
                chartData.temperature.shift();
                chartData.humidity.shift();
                chartData.pressure.shift();
            }

            // Update chart
            chart.data.labels = chartData.labels;
            chart.data.datasets[0].data = chartData.temperature;
            chart.data.datasets[1].data = chartData.humidity;
            chart.data.datasets[2].data = chartData.pressure;
            chart.update('none');
        }

        // Add message to log
        function addToLog(message, data = null) {
            const logContainer = document.getElementById('messageLog');
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';

            const timestamp = new Date().toLocaleTimeString();
            let logContent = `<span class="timestamp">${timestamp}</span> - ${message}`;

            if (data) {
                logContent += ` <span class="data-value">${JSON.stringify(data)}</span>`;
            }

            logEntry.innerHTML = logContent;
            logContainer.appendChild(logEntry);

            // Auto-scroll to bottom
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        // Update connection status
        function updateConnectionStatus(status, text) {
            const statusDiv = document.getElementById('connectionStatus');
            const statusText = document.getElementById('statusText');
            const connectBtn = document.getElementById('connectBtn');
            const disconnectBtn = document.getElementById('disconnectBtn');

            statusDiv.className = `connection-status ${status}`;
            statusText.textContent = text;

            if (status === 'connected') {
                connectBtn.disabled = true;
                disconnectBtn.disabled = false;
            } else {
                connectBtn.disabled = false;
                disconnectBtn.disabled = true;
            }
        }

        // Update metrics display
        function updateMetrics(data) {
            if (data.temperature !== undefined) {
                document.getElementById('tempValue').textContent = data.temperature.toFixed(1);
            }
            if (data.humidity !== undefined) {
                document.getElementById('humidityValue').textContent = data.humidity.toFixed(1);
            }
            if (data.pressure !== undefined) {
                document.getElementById('pressureValue').textContent = data.pressure.toFixed(1);
            }

            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }

        // Initialize WebSocket client
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize chart
            initChart();

            // Initialize WebSocket client
            const wsClient = new ScadaWebSocketClient({
                serverUrl: 'ws://localhost:6001',
                channel: 'scada-data',
                onConnect: function() {
                    updateConnectionStatus('connected', 'Connected');
                    addToLog('WebSocket connected successfully');
                },
                onDisconnect: function() {
                    updateConnectionStatus('disconnected', 'Disconnected');
                    addToLog('WebSocket disconnected');
                },
                onMessage: function(data) {
                    messageCount++;
                    document.getElementById('messageCount').textContent = messageCount;

                    // Update metrics
                    updateMetrics(data);

                    // Update chart
                    updateChart(data);

                    // Add to log
                    addToLog('Data received', data);
                },
                onError: function(error) {
                    addToLog('Error: ' + error.message);
                }
            });

            // Connect button
            document.getElementById('connectBtn').addEventListener('click', function() {
                updateConnectionStatus('connecting', 'Connecting...');
                wsClient.connect();
            });

            // Disconnect button
            document.getElementById('disconnectBtn').addEventListener('click', function() {
                wsClient.disconnect();
            });

            // Clear log button
            document.getElementById('clearLogBtn').addEventListener('click', function() {
                document.getElementById('messageLog').innerHTML =
                    '<div class="text-muted">Log cleared...</div>';
            });

            // Export log button
            document.getElementById('exportLogBtn').addEventListener('click', function() {
                const logEntries = document.querySelectorAll('#messageLog .log-entry');
                let logText = 'WebSocket Message Log\n';
                logText += '=====================\n\n';

                logEntries.forEach(entry => {
                    logText += entry.textContent + '\n';
                });

                const blob = new Blob([logText], {
                    type: 'text/plain'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'websocket-log.txt';
                a.click();
                URL.revokeObjectURL(url);
            });

            // Auto-connect on page load
            setTimeout(() => {
                document.getElementById('connectBtn').click();
            }, 1000);
        });
    </script>
</body>

</html>
