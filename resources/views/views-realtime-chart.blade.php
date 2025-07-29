<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire Chart Analysis - Real-time</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <style>
        /* Semua gaya CSS umum bisa diletakkan di sini */
        :root {
            --border-color: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --primary-color: #3b82f6;
            --primary-hover-color: #2563eb;
            --green-color: #10b981;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: var(--bg-light);
            color: var(--text-primary);
        }

        .dashboard-container {
            padding: 24px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .header-frame {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .main-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 28px;
            margin: 0;
        }

        .tabs {
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .tab-item {
            display: inline-flex;
            gap: 24px;
        }

        .tab-text a {
            padding: 12px 4px;
            display: inline-block;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 16px;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .tab-text a.active,
        .tab-text a:hover {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .chart-page-container {
            background-color: var(--bg-white);
            padding: 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
        }

        .debug-info {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 16px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
    @livewireStyles
</head>

<body>
    <div class="dashboard-container">
        <div class="dashboard-title">
            <div class="header-frame">
                <h1 class="main-title">Monitoring AWS - Real-time</h1>
            </div>
            <div class="tabs">
                <div class="tab-item">
                    <div class="tab-text"><a href="{{ url('/analysis') }}"
                            class="{{ request()->is('analysis') ? 'active' : '' }}">Historical Analysis</a></div>
                    <div class="tab-text"><a href="{{ url('/realtime') }}"
                            class="{{ request()->is('realtime') ? 'active' : '' }}">Real-time Streaming</a></div>
                </div>
            </div>
        </div>

        <div class="chart-page-container">
            {{-- Debug info untuk development --}}
            @if (config('app.debug'))
                <div class="debug-info">
                    <strong>Debug Info:</strong><br>
                    - Chart.js loaded: <span id="chartjs-status">Checking...</span><br>
                    - Streaming plugin loaded: <span id="streaming-status">Checking...</span><br>
                    - Date adapter loaded: <span id="date-adapter-status">Checking...</span><br>
                    - Livewire ready: <span id="livewire-status">Checking...</span>
                </div>
            @endif

            {{-- KUNCI: Memuat komponen Livewire di sini --}}
            <livewire:realtime-chart />
        </div>
    </div>

    {{-- Chart.js dan dependencies - URUTAN PENTING! --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>

    {{-- Streaming plugin - menggunakan versi yang kompatibel --}}
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-streaming@2.0.0/dist/chartjs-plugin-streaming.min.js"></script>

    {{-- Alternatif: Jika streaming plugin masih bermasalah, gunakan versi yang lebih stabil --}}
    <script>
        // Fallback untuk streaming plugin
        if (typeof Chart !== 'undefined' && !Chart.registry.controllers.streaming) {
            console.log('Loading streaming plugin fallback...');
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chartjs-plugin-streaming@1.8.0/dist/chartjs-plugin-streaming.min.js';
            script.onload = function() {
                console.log('Streaming plugin fallback loaded');
                document.getElementById('streaming-status').textContent = '✓ Loaded (Fallback)';
            };
            script.onerror = function() {
                console.error('Streaming plugin fallback failed');
                // Try alternative CDN
                const altScript = document.createElement('script');
                altScript.src = 'https://unpkg.com/chartjs-plugin-streaming@2.0.0/dist/chartjs-plugin-streaming.min.js';
                altScript.onload = function() {
                    console.log('Streaming plugin alternative CDN loaded');
                    document.getElementById('streaming-status').textContent = '✓ Loaded (Alt CDN)';
                };
                altScript.onerror = function() {
                    console.error('All streaming plugin sources failed');
                    document.getElementById('streaming-status').textContent = '✗ Failed (All)';
                };
                document.head.appendChild(altScript);
            };
            document.head.appendChild(script);
        }
    </script>

    {{-- Debug script untuk memeriksa loading --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');

            // Check if Chart.js is loaded
            if (typeof Chart !== 'undefined') {
                document.getElementById('chartjs-status').textContent = '✓ Loaded';
                console.log('Chart.js loaded successfully');
            } else {
                document.getElementById('chartjs-status').textContent = '✗ Failed';
                console.error('Chart.js not loaded');
            }

            // Check if streaming plugin is loaded - improved detection
            function checkStreamingPlugin() {
                if (typeof Chart !== 'undefined') {
                    // Check multiple ways the plugin might be registered
                    const hasStreaming = (
                        (Chart.registry && Chart.registry.controllers.streaming) ||
                        (Chart.defaults && Chart.defaults.scales && Chart.defaults.scales.realtime) ||
                        (Chart.controllers && Chart.controllers.streaming)
                    );

                    if (hasStreaming) {
                        document.getElementById('streaming-status').textContent = '✓ Loaded';
                        console.log('Streaming plugin loaded successfully');
                        return true;
                    } else {
                        document.getElementById('streaming-status').textContent = '✗ Failed';
                        console.error('Streaming plugin not loaded');
                        return false;
                    }
                }
                return false;
            }

            // Check streaming plugin after a short delay to allow fallback to load
            setTimeout(checkStreamingPlugin, 1000);

            // Check if date adapter is loaded
            if (typeof Chart !== 'undefined' && Chart.defaults && Chart.defaults.scales && Chart.defaults.scales
                .time) {
                document.getElementById('date-adapter-status').textContent = '✓ Loaded';
                console.log('Date adapter loaded successfully');
            } else {
                document.getElementById('date-adapter-status').textContent = '✗ Failed';
                console.error('Date adapter not loaded');
            }
        });
    </script>

    {{-- Livewire Scripts --}}
    @livewireScripts

    <script>
        document.addEventListener('livewire:init', function() {
            document.getElementById('livewire-status').textContent = '✓ Ready';
            console.log('Livewire initialized');
        });
    </script>
</body>

</html>
