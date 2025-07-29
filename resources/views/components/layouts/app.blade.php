<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'AWS Dashboard - Analysis' }}</title>

    {{-- Livewire Styles --}}
    @livewireStyles

    {{-- Chart.js dan dependencies --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom/dist/chartjs-plugin-zoom.min.js"></script>

    <style>
        /* CSS dari views-graph-analysis.blade.php */
        :root {
            --border-color: #e9ecef;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --bg-light: #f4f6f9;
            --bg-white: #fff;
            --primary-color: #007bff;
            --green-color: #28a745;
            --red-color: #dc3545;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: var(--bg-light);
            color: var(--text-primary);
        }

        .dashboard-container {
            padding: 24px;
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
            color: #1f2937;
            font-weight: 700;
            font-size: 32px;
            margin: 0;
        }

        .status-indicator {
            display: flex;
            padding: 8px 16px;
            justify-content: center;
            align-items: center;
            gap: 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-white);
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
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
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

        .filter-group input,
        .filter-group select,
        .interval-buttons button {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-white);
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            height: 40px;
            box-sizing: border-box;
        }

        .filter-group input:focus,
        .filter-group select:focus,
        .interval-buttons button:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .interval-buttons button {
            height: 40px;
            flex-grow: 1;
            cursor: pointer;
            white-space: nowrap;
        }

        .interval-buttons button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .filter-group button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            padding: 10px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            height: 40px;
            box-sizing: border-box;
        }

        .filter-group button:hover {
            background-color: #0056b3;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
        }

        /* Metric selector styles */
        .metric-selector {
            position: relative;
            min-width: 250px;
        }

        .selected-metrics-display {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-white);
            cursor: pointer;
            min-height: 40px;
            display: flex;
            align-items: center;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            gap: 6px;
            overflow: hidden;
        }

        .selected-metrics-display.open {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .selected-metrics-display .placeholder {
            color: var(--text-secondary);
            font-style: italic;
            flex-grow: 1;
        }

        .selected-metrics-display .metric-tag {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 4px;
            margin-bottom: 2px;
        }

        .selected-metrics-display .metric-tag .remove-tag {
            margin-left: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }

        .selected-metrics-display .metric-tag .remove-tag:hover {
            opacity: 0.8;
        }

        .metric-overlay {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            margin-top: 4px;
        }

        .metric-overlay.show {
            display: block;
        }

        .overlay-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-light);
            border-radius: 8px 8px 0 0;
        }

        .overlay-header span {
            font-weight: 500;
            color: var(--text-primary);
        }

        .close-overlay {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .close-overlay:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        .tag-checkboxes {
            max-height: 200px;
            overflow-y: auto;
            padding: 12px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
        }

        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
        }

        .checkbox-item label {
            font-size: 14px;
            color: var(--text-primary);
            cursor: pointer;
            margin: 0;
            flex: 1;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Header Section -->
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
                    <div class="tab-text">
                        <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">Overview</a>
                    </div>
                    <div class="tab-text">
                        <a href="{{ url('/log-data') }}" class="{{ request()->is('log-data') ? 'active' : '' }}">Log
                            Data</a>
                    </div>
                    <div class="tab-text">
                        <a href="{{ url('/analysis') }}"
                            class="{{ request()->is('analysis') ? 'active' : '' }}">Analysis Chart</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Section --}}
        <div class="chart-page-container">
            {{ $slot }}
        </div>
    </div>

    {{-- Livewire Scripts --}}
    @livewireScripts
</body>

</html>
