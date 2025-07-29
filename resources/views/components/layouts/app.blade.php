<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire Chart Analysis</title>
    {{-- Menggunakan Google Fonts untuk tipografi yang lebih baik --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <style>
        /* ---------------------------------- */
        /* Variabel Global & Gaya Dasar      */
        /* ---------------------------------- */
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
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .dashboard-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ---------------------------------- */
        /* Header & Tab                       */
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

        /* ---------------------------------- */
        /* Kontainer Utama & Filter           */
        /* ---------------------------------- */
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

        /* Gaya konsisten untuk semua input, select, dan tombol */
        .filter-group input[type="date"],
        .interval-buttons button,
        .selected-metrics-display,
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
        .interval-buttons button:focus,
        .selected-metrics-display:focus-within {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        /* Tombol Interval */
        .interval-buttons {
            display: flex;
            gap: -1px;
            /* Membuat tombol saling menempel */
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

        /* Tombol Aksi Utama */
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

        /* ---------------------------------- */
        /* Pemilih Metrik Kustom              */
        /* ---------------------------------- */
        .metric-selector {
            position: relative;
            min-width: 250px;
        }

        .selected-metrics-display {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .selected-metrics-display.open {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .display-tags-container {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            overflow: hidden;
        }

        .selected-metrics-display .placeholder {
            color: var(--text-secondary);
        }

        .metric-pill {
            display: inline-flex;
            align-items: center;
            background-color: #e0e7ff;
            color: #4338ca;
            padding: 4px 8px;
            border-radius: 1rem;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }

        .more-tags-indicator {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        .caret {
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid var(--text-secondary);
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }

        .caret.open {
            transform: rotate(180deg);
        }

        .metric-overlay {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: none;
        }

        .metric-overlay.show {
            display: block;
        }

        .tag-checkboxes {
            max-height: 220px;
            overflow-y: auto;
            padding: 8px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: var(--radius-md);
            cursor: pointer;
        }

        .checkbox-item:hover {
            background-color: var(--bg-light);
        }

        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: var(--primary-color);
        }

        .checkbox-item label {
            font-size: 14px;
            color: var(--text-primary);
            cursor: pointer;
            margin: 0;
            flex: 1;
            font-weight: 400;
            /* Reset bobot font */
        }

        /* ---------------------------------- */
        /* Kontainer Chart & Overlay Loading  */
        /* ---------------------------------- */
        .chart-container {
            position: relative;
            width: 100%;
            height: 450px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            background-color: var(--bg-white);
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

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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

    {{-- Chart.js dan dependencies --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom/dist/chartjs-plugin-zoom.min.js"></script>

    {{-- Livewire Scripts --}}
    @livewireScripts

    <script>
        // Menjalankan skrip setelah DOM atau navigasi Livewire selesai
        document.addEventListener('livewire:navigated', () => {
            const ctx = document.getElementById('historicalChart')?.getContext('2d');
            if (!ctx) return;

            let historicalChart;

            // Fungsi untuk membuat atau memperbarui chart
            function createOrUpdateChart(chartData) {
                const chartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'minute', // Unit bisa dinamis berdasarkan interval
                                tooltipFormat: 'PPpp'
                            },
                            title: {
                                display: true,
                                text: 'Timestamp'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Value'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        zoom: {
                            pan: {
                                enabled: true,
                                mode: 'x',
                            },
                            zoom: {
                                wheel: {
                                    enabled: true
                                },
                                pinch: {
                                    enabled: true
                                },
                                mode: 'x',
                            }
                        }
                    }
                };

                if (historicalChart) {
                    historicalChart.data = chartData;
                    historicalChart.update('none'); // Update tanpa animasi agar lebih cepat
                } else {
                    historicalChart = new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: chartOptions
                    });
                }
            }

            // Listener untuk event BERAT: Mengganti seluruh data grafik
            document.addEventListener('chart-data-updated', event => {
                const chartData = event.detail.chartData;
                createOrUpdateChart(chartData);
            });

            // Listener untuk event RINGAN: Menambahkan satu titik data baru
            document.addEventListener('new-data-point', event => {
                if (!historicalChart || !historicalChart.data.labels || historicalChart.data.labels
                    .length === 0) return;

                const newData = event.detail.data;
                const lastLabel = historicalChart.data.labels[historicalChart.data.labels.length - 1];

                // Hindari duplikasi data
                if (lastLabel && new Date(newData.timestamp) <= new Date(lastLabel)) {
                    return;
                }

                historicalChart.data.labels.push(newData.timestamp);
                historicalChart.data.datasets.forEach(dataset => {
                    // Gunakan null jika metrik tidak ada untuk timestamp ini
                    dataset.data.push(newData.metrics[dataset.label] ?? null);
                });

                // Batasi jumlah titik data untuk performa
                const maxDataPoints = 120;
                if (historicalChart.data.labels.length > maxDataPoints) {
                    historicalChart.data.labels.shift();
                    historicalChart.data.datasets.forEach(dataset => dataset.data.shift());
                }

                historicalChart.update('none');
            });
        });
    </script>
</body>

</html>
