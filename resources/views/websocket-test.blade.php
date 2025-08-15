<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA WebSocket Test</title>

    <!-- Include Pusher and SCADA WebSocket Client -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
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
        let dataCount = 0;
        let chartData = [];
        let maxChartPoints = 50;

        // Initialize WebSocket client
        function initializeWebSocket() {
            wsClient = new ScadaWebSocketClient({
                serverUrl: 'ws://127.0.0.1:6001',
                appKey: '{{ env('PUSHER_APP_KEY', 'your_app_key_here') }}',
                appId: '{{ env('PUSHER_APP_ID', '12345') }}',
                cluster: '{{ env('PUSHER_APP_CLUSTER', 'mt1') }}',
                encrypted: false,
                onConnect: handleConnect,
                onMessage: handleMessage,
                onError: handleError,
                onDisconnect: handleDisconnect
            });
        }

        // Handle WebSocket connection
        function handleConnect() {
            updateStatus('connected', 'Connected');
            document.getElementById('connectBtn').disabled = true;
            document.getElementById('disconnectBtn').disabled = false;
            document.getElementById('subscribeBtn').disabled = false;
            document.getElementById('testDataBtn').disabled = false;
            addLog('Connected to WebSocket server', 'success');
        }

        // Handle WebSocket messages
        function handleMessage(data) {
            dataCount++;
            document.getElementById('dataCount').textContent = dataCount;

            // Update real-time values
            if (data.temperature !== undefined) {
                document.getElementById('temperatureValue').textContent = data.temperature.toFixed(1);
            }
            if (data.humidity !== undefined) {
                document.getElementById('humidityValue').textContent = data.humidity.toFixed(1);
            }
            if (data.pressure !== undefined) {
                document.getElementById('pressureValue').textContent = data.pressure.toFixed(1);
            }

            // Add to chart data
            addChartData(data);

            // Log message
            addLog(`Data received: ${JSON.stringify(data)}`, 'info');
        }

        // Handle WebSocket errors
        function handleError(error) {
            updateStatus('error', 'Error');
            addLog(`WebSocket error: ${error}`, 'error');
        }

        // Handle WebSocket disconnection
        function handleDisconnect() {
            updateStatus('disconnected', 'Disconnected');
            document.getElementById('connectBtn').disabled = false;
            document.getElementById('disconnectBtn').disabled = true;
            document.getElementById('subscribeBtn').disabled = true;
            document.getElementById('testDataBtn').disabled = true;
            addLog('Disconnected from WebSocket server', 'warning');
        }

        // Update connection status
        function updateStatus(status, text) {
            const indicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');

            indicator.className = `status-indicator status-${status}`;
            statusText.textContent = text;
        }

        // Add log entry
        function addLog(message, type = 'info') {
            const log = document.getElementById('messageLog');
            const timestamp = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.innerHTML = `<span class="timestamp">[${timestamp}]</span> ${message}`;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }

        // Clear logs
        function clearLogs() {
            document.getElementById('messageLog').innerHTML = '';
        }

        // Connect to WebSocket
        function connectWebSocket() {
            if (!wsClient) {
                initializeWebSocket();
            }
            wsClient.connect();
            updateStatus('connecting', 'Connecting...');
            addLog('Attempting to connect...', 'info');
        }

        // Disconnect from WebSocket
        function disconnectWebSocket() {
            if (wsClient) {
                wsClient.disconnect();
            }
        }

        // Subscribe to channels
        function subscribeChannels() {
            if (wsClient) {
                wsClient.subscribe('scada-data');
                wsClient.subscribe('scada-batch');
                wsClient.subscribe('scada-aggregated');
                updateChannelsInfo();
                addLog('Subscribed to all channels', 'success');
            }
        }

        // Update channels info
        function updateChannelsInfo() {
            if (wsClient) {
                const channels = wsClient.getSubscribedChannels();
                document.getElementById('channelsInfo').textContent = channels.join(', ') || 'None';
            }
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

        // Add data to chart
        function addChartData(data) {
            const timestamp = new Date().getTime();
            chartData.push({
                timestamp: timestamp,
                temperature: data.temperature || 0,
                humidity: data.humidity || 0,
                pressure: data.pressure || 0
            });

            // Keep only last N points
            if (chartData.length > maxChartPoints) {
                chartData.shift();
            }

            // Update chart display (simple text representation for now)
            updateChartDisplay();
        }

        // Update chart display
        function updateChartDisplay() {
            const chart = document.getElementById('realtimeChart');
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            addLog('Page loaded, ready to connect', 'info');
            updateStatus('disconnected', 'Disconnected');
        });
    </script>
</body>

</html>
