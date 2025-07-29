<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire Chart Analysis</title>
    {{-- Menggunakan Google Fonts untuk tipografi yang lebih baik --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <style>
        /* Semua gaya CSS dari versi sebelumnya tetap sama */
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
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-frame {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .title-status-container {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .main-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 28px;
            margin: 0;
        }

        .status-indicator {
            display: flex;
            padding: 8px 16px;
            align-items: center;
            gap: 8px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background-color: var(--bg-white);
            box-shadow: var(--shadow-sm);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--green-color);
        }

        .status-text {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
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

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .filter-group input[type="date"],
        .filter-group select,
        .interval-buttons button,
        .btn-primary {
            height: 42px;
            padding: 0 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background-color: var(--bg-white);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .filter-group input[type="date"]:focus,
        .filter-group select:focus,
        .interval-buttons button:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .interval-buttons {
            display: flex;
            gap: -1px;
        }

        .interval-buttons button {
            cursor: pointer;
            white-space: nowrap;
        }

        .interval-buttons button:not(:last-child) {
            border-right: none;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .interval-buttons button:not(:first-child) {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .interval-buttons button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            z-index: 1;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover-color);
        }

        /* Gaya khusus untuk dropdown metric */
        .metric-dropdown {
            min-width: 200px;
            cursor: pointer;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        /* Icon loading kecil untuk data real-time */
        .realtime-loading-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .realtime-loading-indicator[wire\\:loading] {
            opacity: 1;
            transform: scale(1);
        }

        /* Tooltip untuk icon loading */
        .realtime-loading-indicator:hover::after {
            content: attr(title);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: 8px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1001;
        }

        /* Status indicator untuk real-time data */
        .realtime-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .status-dot-green {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
            animation: pulse 2s infinite;
        }

        /* Tooltip untuk status indicator */
        .realtime-status:hover::after {
            content: attr(title);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: 8px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1001;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
            }

            50% {
                box-shadow: 0 0 16px rgba(16, 185, 129, 0.8);
            }

            100% {
                box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
            }
        }

        .realtime-spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid #10b981;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Gaya untuk single chart */
        .single-chart-container {
            position: relative;
            width: 100%;
            height: 450px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
        }
    </style>
    @livewireStyles
</head>

<body>
    <div class="dashboard-container">
        <div class="dashboard-title">
            <div class="header-frame">
                <div class="title-status-container">
                    <h1 class="main-title">Monitoring AWS - Analysis</h1>
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <div class="status-text">System Connected</div>
                    </div>
                </div>
            </div>
            <div class="tabs">
                <div class="tab-item">
                    <div class="tab-text"><a href="{{ url('/') }}"
                            class="{{ request()->is('/') ? 'active' : '' }}">Overview</a></div>
                    <div class="tab-text"><a href="{{ url('/log-data') }}"
                            class="{{ request()->is('log-data') ? 'active' : '' }}">Log Data</a></div>
                    <div class="tab-text"><a href="{{ url('/analysis') }}"
                            class="{{ request()->is('analysis') ? 'active' : '' }}">Analysis Chart</a></div>
                </div>
            </div>
        </div>

        <div class="chart-page-container">
            {{ $slot }}
        </div>
    </div>

    {{-- KUNCI: Hammer.js HARUS dimuat SEBELUM Chart.js untuk dukungan gestur mobile --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom/dist/chartjs-plugin-zoom.min.js"></script>

    {{-- Livewire Scripts --}}
    @livewireScripts
</body>

</html>
