<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA WebSocket Test</title>

    <!-- Include Pusher, Laravel Echo, and SCADA WebSocket Client -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        window.Pusher = Pusher;
    </script>
    <script src="https://unpkg.com/laravel-echo/dist/echo.iife.js"></script>
    <script src="{{ asset('js/scada-websocket-client.js') }}"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }

        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }

        .content {
            padding: 30px;
        }

        .status-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .status-connected {
            background: #28a745;
        }

        .status-connecting {
            background: #ffc107;
        }

        .status-disconnected {
            background: #dc3545;
        }

        .status-error {
            background: #dc3545;
        }

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #1e7e34;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .data-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .data-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .data-card h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 1.2em;
        }

        .data-value {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .data-unit {
            color: #6c757d;
            font-size: 0.9em;
        }

        .chart-container {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .chart-container h3 {
            margin: 0 0 20px 0;
            color: #495057;
        }

        #realtimeChart {
            width: 100%;
            height: 400px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.1em;
        }

        .logs-section {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .logs-section h3 {
            margin: 0 0 15px 0;
            color: #495057;
        }

        #messageLog {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .log-entry {
            margin-bottom: 8px;
            padding: 5px;
            border-radius: 4px;
        }

        .log-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .log-success {
            background: #d4edda;
            color: #155724;
        }

        .log-warning {
            background: #fff3cd;
            color: #856404;
        }

        .log-error {
            background: #f8d7da;
            color: #721c24;
        }

        .timestamp {
            color: #6c757d;
            font-size: 0.8em;
        }

        .clear-logs {
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
            }

            .data-section {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔌 SCADA WebSocket Test</h1>
            <p>Real-time data streaming via WebSocket connection</p>
        </div>

        <div class="content">
            <!-- Connection Status -->
            <div class="status-section">
                <h3>Connection Status</h3>
                <div>
                    <span class="status-indicator" id="statusIndicator"></span>
                    <span id="statusText">Disconnected</span>
                </div>
                <div style="margin-top: 10px;">
                    <strong>Server:</strong> <span id="serverInfo">ws://127.0.0.1:6001</span><br>
                    <strong>Channels:</strong> <span id="channelsInfo">None</span>
                </div>
            </div>

            <!-- Controls -->
            <div class="controls">
                <button class="btn btn-primary" id="connectBtn" onclick="connectWebSocket()">Connect</button>
                <button class="btn btn-danger" id="disconnectBtn" onclick="disconnectWebSocket()"
                    disabled>Disconnect</button>
                <button class="btn btn-success" id="subscribeBtn" onclick="subscribeChannels()" disabled>Subscribe
                    All</button>
                <button class="btn btn-warning" id="testDataBtn" onclick="sendTestData()" disabled>Send Test
                    Data</button>
                <button class="btn btn-warning" onclick="clearLogs()">Clear Logs</button>
            </div>

            <!-- Real-time Data -->
            <div class="data-section">
                <div class="data-card">
                    <h3>🌡️ Temperature</h3>
                    <div class="data-value" id="temperatureValue">--</div>
                    <div class="data-unit">°C</div>
                </div>

                <div class="data-card">
                    <h3>💧 Humidity</h3>
                    <div class="data-value" id="humidityValue">--</div>
                    <div class="data-unit">%</div>
                </div>

                <div class="data-card">
                    <h3>🌪️ Pressure</h3>
                    <div class="data-value" id="pressureValue">--</div>
                    <div class="data-unit">hPa</div>
                </div>

                <div class="data-card">
                    <h3>📊 Data Count</h3>
                    <div class="data-value" id="dataCount">0</div>
                    <div class="data-unit">messages received</div>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container">
                <h3>📈 Real-time Chart</h3>
                <div id="realtimeChart">
                    Chart will appear here when data is received
                </div>
            </div>

            <!-- Message Logs -->
            <div class="logs-section">
                <h3>📝 Message Logs</h3>
                <div id="messageLog"></div>
                <button class="btn btn-warning clear-logs" onclick="clearLogs()">Clear Logs</button>
            </div>
        </div>
    </div>

    <script>
        let wsClient = null;
        let chartData = [];

        // Fungsi untuk menambahkan log ke UI
        function addLog(message, type = 'log') {
            const log = document.getElementById('messageLog');
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `<span>${new Date().toLocaleTimeString()}</span> - ${message}`;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }

        // Fungsi untuk memperbarui status koneksi di UI
        function updateStatus(state, text) {
            const status = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');

            if (status && statusText) {
                status.className = `status-indicator status-${state}`;
                statusText.textContent = text;
            }
        }

        // Fungsi utama untuk menginisialisasi koneksi WebSocket
        function initializeWebSocket() {
            addLog('Initializing WebSocket...', 'info');

            wsClient = new ScadaWebSocketClient({
                onConnect: () => {
                    updateStatus('connected', 'Connected');
                    addLog('Successfully connected to WebSocket server!', 'success');

                    // Setelah terhubung, subscribe ke channel yang diinginkan
                    const channelName = 'scada-channel';
                    const eventName = 'scada.data.received';
                    addLog(`Subscribing to channel "${channelName}" for event "${eventName}"...`);

                    wsClient.subscribe(channelName, eventName, (data) => {
                        addLog(`Received data: ${JSON.stringify(data)}`, 'data');
                        processData(data);
                    });
                },
                onDisconnect: () => {
                    updateStatus('disconnected', 'Disconnected');
                    addLog('WebSocket connection lost.', 'error');
                },
                onError: (error) => {
                    addLog(`WebSocket error: ${error.message || 'An unknown error occurred'}`, 'error');
                }
            });
        }

        // Fungsi untuk memulai koneksi dari tombol
        function connectWebSocket() {
            if (wsClient) {
                addLog('Already connected or connecting.', 'warn');
                return;
            }
            initializeWebSocket();
        }

        // Fungsi untuk memutus koneksi dari tombol
        function disconnectWebSocket() {
            if (wsClient) {
                wsClient.disconnect();
                wsClient = null;
                updateStatus('disconnected', 'Disconnected by user');
                addLog('Disconnected by user.', 'info');
            } else {
                addLog('Not connected.', 'warn');
            }
        }

        // Proses data yang diterima
        function processData(data) {
            // Update real-time values jika ada
            if (data.temperature !== undefined) {
                const tempElement = document.getElementById('temperatureValue');
                if (tempElement) tempElement.textContent = data.temperature.toFixed(1);
            }
            if (data.humidity !== undefined) {
                const humElement = document.getElementById('humidityValue');
                if (humElement) humElement.textContent = data.humidity.toFixed(1);
            }
            if (data.pressure !== undefined) {
                const pressElement = document.getElementById('pressureValue');
                if (pressElement) pressElement.textContent = data.pressure.toFixed(1);
            }

            // Add to chart data
            addChartData(data);
        }

        // Add data to chart
        function addChartData(data) {
            const timestamp = new Date().getTime();
            chartData.push({
                timestamp: timestamp,
                temperature: data.temperature || 0,
                humidity: data.humidity || 0,
                pressure: data.pressure || 0
            });

            // Keep only last 50 points
            if (chartData.length > 50) {
                chartData.shift();
            }

            // Update chart display
            updateChartDisplay();
        }

        // Update chart display
        function updateChartDisplay() {
            const chart = document.getElementById('realtimeChart');
            if (!chart) return;

            if (chartData.length === 0) {
                chart.innerHTML = 'Chart will appear here when data is received';
                return;
            }

            let chartHtml = '<div style="padding: 20px;">';
            chartHtml += '<h4>Recent Data Points</h4>';
            chartHtml += '<div style="max-height: 300px; overflow-y: auto;">';

            chartData.slice(-10).reverse().forEach((point, index) => {
                const time = new Date(point.timestamp).toLocaleTimeString();
                chartHtml += `
                    <div style="border-bottom: 1px solid #eee; padding: 8px 0;">
                        <strong>${time}</strong><br>
                        T: ${point.temperature}°C | H: ${point.humidity}% | P: ${point.pressure}hPa
                    </div>
                `;
            });

            chartHtml += '</div></div>';
            chart.innerHTML = chartHtml;
        }

        // Clear logs
        function clearLogs() {
            const log = document.getElementById('messageLog');
            if (log) log.innerHTML = '';
        }

        // Send test data via API
        function sendTestData() {
            const testData = {
                temperature: (20 + Math.random() * 20).toFixed(1),
                humidity: (40 + Math.random() * 40).toFixed(1),
                pressure: (1000 + Math.random() * 50).toFixed(1),
                timestamp: new Date().toISOString()
            };

            fetch('/api/receiver', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(testData)
                })
                .then(response => response.json())
                .then(data => {
                    addLog(`Test data sent: ${JSON.stringify(testData)}`, 'success');
                })
                .catch(error => {
                    addLog(`Error sending test data: ${error}`, 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            addLog('Page loaded, ready to connect.', 'info');
            updateStatus('disconnected', 'Disconnected');
        });
    </script>
</body>

</html>
