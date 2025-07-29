<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring AWS</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <style>
        /* ---------------------------------- */
        /* General Layout            */
        /* ---------------------------------- */
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

        /* ---------------------------------- */
        /* Header Section            */
        /* ---------------------------------- */
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

        /* ---------------------------------- */
        /* Tabs Navigation          */
        /* ---------------------------------- */
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

        /* ---------------------------------- */
        /* Main Content Section        */
        /* ---------------------------------- */
        .section-title {
            color: #343a40;
            font-weight: 500;
            font-size: 24px;
            margin-bottom: 16px;
        }

        /* ---------------------------------- */
        /* Metric Cards Styling         */
        /* ---------------------------------- */
        .metric-grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .metric-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-white);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease,
                transform 0.3s ease;
        }


        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-bottom: 16px;
        }

        .card-title {
            color: #495057;
            font-weight: 500;
            font-size: 16px;
            text-align: center;
        }

        .card-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .card-value {
            color: #212529;
            font-weight: 700;
            font-size: 36px;
            line-height: 1.2;
        }

        .card-unit {
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
            margin-left: 4px;
        }

        .card-change {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            justify-content: center;
        }

        .change-indicator {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
        }

        .change-indicator.positive {
            color: #28a745;
        }

        .change-indicator.negative {
            color: #dc3545;
        }

        .card-timestamp {
            font-size: 12px;
            color: #adb5bd;
        }

        /* ---------------------------------- */
        /* Compass Card Styling (Compass & Thermo)*/
        /* ---------------------------------- */
        .compass {
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }

        .wind-details {
            display: flex;
            justify-content: space-around;
            width: 100%;
            text-align: center;
            margin-top: 16px;
        }

        .wind-speed-value,
        .wind-direction-value {
            font-size: 20px;
            font-weight: 500;
        }

        .thermometer-card .card-details {
            align-items: stretch;
            justify-content: center;
        }

        .thermometer-content-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            gap: 30px;
        }

        .thermometer-text-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .thermometer {
            width: 40px;
            height: 120px;
        }

        .thermometer-fill {
            transform-origin: bottom;
            transition: transform 0.5s ease;
        }

        .thermometer-card .card-value {
            font-size: 36px;
            font-weight: 700;
            color: #212529;
        }

        .humidity-card .card-details {
            justify-content: space-between;
        }

        .humidity-gauge {
            width: 130px;
            height: 130px;
            transform: rotate(-90deg);
        }

        .gauge-track {
            fill: none;
            stroke: #e9ecef;
        }

        .gauge-progress {
            fill: none;
            stroke: var(--primary-color);
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease;
        }

        .humidity-gauge .gauge-text {
            font-size: 24px;
            font-weight: 700;
            text-anchor: middle;
            fill: var(--text-primary);
            transform: rotate(90deg);
            transform-origin: 50% 50%;
        }

        .rainfall-card .card-details {
            align-items: stretch;
            justify-content: center;
        }

        .rainfall-content-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 20px;
        }

        .rainfall-text-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .rainfall-gauge {
            width: 50px;
            height: 120px;
        }

        .pressure-card .card-details {
            justify-content: center;
            gap: 16px;
            text-align: center;
        }

        .pressure-gauge {
            width: 120px;
            height: 120px;
        }

        .pressure-gauge .gauge-needle {
            stroke: var(--red-color);
            stroke-width: 2;
            stroke-linecap: round;
        }

        .pressure-gauge .gauge-needle-center {
            fill: var(--text-primary);
            stroke: var(--bg-white);
            stroke-width: 1;
        }

        .pressure-text-group {
            margin-top: 0;
            text-align: center;
        }

        .pressure-text-group .card-value {
            font-size: 28px;
            text-align: center;
        }

        /* ---------------------------------- */
        /* Responsive Design          */
        /* ---------------------------------- */
        @media (max-width: 1200px) {
            .metric-grid-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .metric-grid-container {
                grid-template-columns: 1fr;
            }

            .header-frame {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }

        /* CSS BARU UNTUK KARTU INFORMASI */
        .info-card .card-details {
            align-items: flex-start;
            justify-content: center;
            gap: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 14px;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-right: 10px;
        }

        .info-value {
            font-weight: 500;
            color: var(--text-primary);
            text-align: right;
        }

        .info-value.batch-id {
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }

        .no-payload-text {
            color: var(--text-secondary);
        }

        /* Peningkatan untuk Kartu Informasi Payload Terakhir */
        .info-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-white);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .info-card .card-details {
            align-items: flex-start;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding-top: 10px;
        }

        .info-row {
            display: flex;
            justify-content: flex-start;
            width: 100%;
            font-size: 15px;
            line-height: 1.5;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-primary);
            text-align: left;
            word-break: break-word;
            flex-grow: 1;
        }

        .info-value.batch-id {
            font-family: 'Roboto Mono', monospace;
            font-size: 13px;
            word-break: break-all;
        }

        .no-payload-text {
            color: var(--text-secondary);
            font-style: italic;
            text-align: center;
            width: 100%;
            padding: 20px 0;
        }
    </style>
    @livewireStyles
</head>

<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-title">
            <div class="header-frame">
                <div class="title-status-container">
                    <h1 class="main-title">Monitoring AWS</h1>
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <div class="status-text">System Connected</div>
                    </div>
                </div>
                {{-- Tombol log bisa ditambahkan di sini jika perlu --}}
            </div>
            <div class="tabs">
                <div class="tab-item">
                    <div class="tab-text">
                        {{-- Menambahkan class 'active' jika URL-nya cocok --}}
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

        <!-- Main Content -->
        <div class="main-container">
            <div class="main-content">
                <div class="metrics-section">
                    <!-- Livewire Component Integration -->
                    <livewire:dashboard />
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>

</html>
