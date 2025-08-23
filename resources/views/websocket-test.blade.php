<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SCADA WebSocket Test - Laravel Reverb</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
            <h1>🔌 SCADA WebSocket Test - Laravel Reverb</h1>
            <p>Real-time data streaming via Laravel Reverb WebSocket server</p>
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
                    <strong>Server:</strong> <span id="serverInfo">ws://127.0.0.1:8080</span><br>
                    <strong>Channels:</strong> <span id="channelsInfo">None</span><br>
                    <strong>Broadcaster:</strong> <span id="broadcasterInfo">Laravel Reverb</span>
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

            <!-- Message Logs -->
            <div class="logs-section">
                <h3>📝 Message Logs</h3>
                <div id="messageLog"></div>
                <button class="btn btn-warning clear-logs" onclick="clearLogs()">Clear Logs</button>
            </div>
        </div>
    </div>

    <script>
        let echo = null;
        let isConnected = false;
        let dataCount = 0;

        // Fungsi untuk menambahkan log ke UI
        function addLog(message, type = 'info') {
            const log = document.getElementById('messageLog');
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.innerHTML = `<span class="timestamp">${new Date().toLocaleTimeString()}</span> - ${message}`;
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

            // Update button states
            const connectBtn = document.getElementById('connectBtn');
            const disconnectBtn = document.getElementById('disconnectBtn');
            const subscribeBtn = document.getElementById('subscribeBtn');
            const testDataBtn = document.getElementById('testDataBtn');

            if (state === 'connected') {
                connectBtn.disabled = true;
                disconnectBtn.disabled = false;
                subscribeBtn.disabled = false;
                testDataBtn.disabled = false;
            } else {
                connectBtn.disabled = false;
                disconnectBtn.disabled = true;
                subscribeBtn.disabled = true;
                testDataBtn.disabled = true;
            }
        }

        // Fungsi untuk menginisialisasi Laravel Echo
        function initializeEcho() {
            addLog('Initializing Laravel Echo with Reverb...', 'info');

            try {
                // Echo sudah diinisialisasi di bootstrap.js
                if (window.Echo) {
                    echo = window.Echo;
                    addLog('Laravel Echo initialized successfully', 'success');
                    updateStatus('connected', 'Connected to Reverb');
                    isConnected = true;

                    // Update server info
                    document.getElementById('serverInfo').textContent = 'ws://127.0.0.1:8080';
                    document.getElementById('broadcasterInfo').textContent = 'Laravel Reverb';

                    return true;
                } else {
                    addLog('Error: Laravel Echo not found. Check bootstrap.js configuration.', 'error');
                    updateStatus('error', 'Echo not found');
                    return false;
                }
            } catch (error) {
                addLog(`Error initializing Echo: ${error.message}`, 'error');
                updateStatus('error', 'Initialization failed');
                return false;
            }
        }

        // Fungsi untuk memulai koneksi dari tombol
        function connectWebSocket() {
            if (isConnected) {
                addLog('Already connected.', 'warn');
                return;
            }

            if (initializeEcho()) {
                addLog('Successfully connected to Laravel Reverb!', 'success');
            }
        }

        // Fungsi untuk memutus koneksi dari tombol
        function disconnectWebSocket() {
            if (echo) {
                try {
                    // Unsubscribe dari semua channel
                    echo.disconnect();
                    echo = null;
                    isConnected = false;
                    updateStatus('disconnected', 'Disconnected by user');
                    addLog('Disconnected from Reverb.', 'info');
                } catch (error) {
                    addLog(`Error disconnecting: ${error.message}`, 'error');
                }
            } else {
                addLog('Not connected.', 'warn');
            }
        }

        // Fungsi untuk subscribe ke channels
        function subscribeChannels() {
            if (!echo || !isConnected) {
                addLog('Not connected. Please connect first.', 'warn');
                return;
            }

            try {
                // Subscribe ke channel SCADA
                const channel = echo.channel('scada-channel');

                channel.listen('ScadaDataReceived', (e) => {
                    addLog(`Received SCADA data: ${JSON.stringify(e)}`, 'success');
                    processData(e);
                });

                // Subscribe ke private channel jika diperlukan
                const privateChannel = echo.private('scada-private');

                privateChannel.listen('ScadaDataReceived', (e) => {
                    addLog(`Received private SCADA data: ${JSON.stringify(e)}`, 'success');
                    processData(e);
                });

                addLog('Subscribed to SCADA channels successfully', 'success');
                document.getElementById('channelsInfo').textContent = 'scada-channel, scada-private';

            } catch (error) {
                addLog(`Error subscribing to channels: ${error.message}`, 'error');
            }
        }

        // Proses data yang diterima
        function processData(data) {
            dataCount++;
            document.getElementById('dataCount').textContent = dataCount;

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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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

        // Clear logs
        function clearLogs() {
            const log = document.getElementById('messageLog');
            if (log) log.innerHTML = '';
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            addLog('Page loaded, ready to connect to Laravel Reverb.', 'info');
            updateStatus('disconnected', 'Disconnected');

            // Auto-connect jika Echo tersedia
            setTimeout(() => {
                if (window.Echo) {
                    addLog('Auto-connecting to Reverb...', 'info');
                    connectWebSocket();
                } else {
                    addLog('Laravel Echo not available. Check console for errors.', 'warning');
                }
            }, 1000);
        });
    </script>
</body>

</html>
