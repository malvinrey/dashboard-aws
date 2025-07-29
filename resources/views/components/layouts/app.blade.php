<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire Chart Analysis</title>
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

        .chart-page-container {
            background-color: var(--bg-white);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* ---------------------------------- */
        /* Chart Filters            */
        /* ---------------------------------- */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            /* Increased gap for better separation */
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
            /* margin-bottom: 4px; */
            /* Sedikit jarak antara label dan input */
            /* white-space: nowrap; */
            /* Pastikan label tidak putus baris */
        }

        .filter-group input,
        .filter-group select,
        .interval-buttons button {
            padding: 10px 14px;
            /* Slightly more padding for better touch targets */
            border: 1px solid var(--border-color);
            border-radius: 8px;
            /* Slightly more rounded corners */
            background-color: var(--bg-white);
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            /* box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03); */
            /* Subtle inner shadow for depth */
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            height: 40px;
            /* Nilai ini mungkin perlu disesuaikan */
            box-sizing: border-box;
            /* Pastikan padding dan border termasuk dalam tinggi */
        }

        .filter-group input:focus,
        .filter-group select:focus,
        .interval-buttons button:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            /* Focus ring */
        }

        .filter-group input[type="date"],
        .filter-group input[type="time"] {
            /* Sesuaikan lebar input tanggal/waktu jika diperlukan */
            min-width: 140px;
            /* Contoh: beri lebar minimum agar tidak terlalu sempit */
        }

        .filter-group select {
            /* Sesuaikan lebar select jika diperlukan */
            min-width: 150px;
            /* Contoh: beri lebar minimum */
        }

        /* Styling untuk metric selector */
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

        /* Styling untuk overlay */
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

        /* Styling untuk checkbox container */
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


        .interval-buttons button {
            /* Pastikan tombol interval juga memiliki tinggi yang sama */
            height: 40px;
            flex-grow: 1;
            /* Biarkan tombol membesar jika ada ruang, atau set width spesifik */
        }

        .interval-buttons button,
        #filter-btn,
        #reset-zoom-btn {
            /* Added reset-zoom-btn here */
            cursor: pointer;
            white-space: nowrap;
            /* Prevent buttons from wrapping text */
        }

        .interval-buttons button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        #filter-btn,
        #reset-zoom-btn {
            /* Style for both action buttons */
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            padding: 10px 18px;
            /* More padding for a bolder button */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            height: 40px;
            /* Set tinggi yang sama dengan input/select */
            box-sizing: border-box;
        }

        #filter-btn:hover,
        #reset-zoom-btn:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        /* ---------------------------------- */
        /* Chart Container            */
        /* ---------------------------------- */
        .chart-page-container {
            background-color: var(--bg-white);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-top: 16px;
            /* Sesuaikan nilai ini sesuai kebutuhan Anda, misalnya dari 24px menjadi 16px atau kurang */
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
            /* Tinggi yang cukup untuk menampilkan grafik */
            border-radius: 10px;
            overflow: hidden;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
        }

        /* --- CSS UNTUK LOADING OVERLAY --- */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 9999;
            /* Pastikan berada di atas segalanya */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            border: 8px solid #f3f3f3;
            /* Abu-abu terang */
            border-top: 8px solid var(--primary-color);
            /* Biru */
            border-radius: 50%;
            width: 60px;
            height: 60px;
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

        /* ---------------------------------- */
        /* Chart Filters            */
        /* ---------------------------------- */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            /* Increased gap for better separation */
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
            /* margin-bottom: 4px; */
            /* Sedikit jarak antara label dan input */
            /* white-space: nowrap; */
            /* Pastikan label tidak putus baris */
        }

        .filter-group input,
        .filter-group select,
        .interval-buttons button {
            padding: 10px 14px;
            /* Slightly more padding for better touch targets */
            border: 1px solid var(--border-color);
            border-radius: 8px;
            /* Slightly more rounded corners */
            background-color: var(--bg-white);
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            /* box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03); */
            /* Subtle inner shadow for depth */
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            height: 40px;
            /* Nilai ini mungkin perlu disesuaikan */
            box-sizing: border-box;
            /* Pastikan padding dan border termasuk dalam tinggi */
        }

        .filter-group input:focus,
        .filter-group select:focus,
        .interval-buttons button:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            /* Focus ring */
        }

        .filter-group input[type="date"],
        .filter-group input[type="time"] {
            /* Sesuaikan lebar input tanggal/waktu jika diperlukan */
            min-width: 140px;
            /* Contoh: beri lebar minimum agar tidak terlalu sempit */
        }

        .filter-group select {
            /* Sesuaikan lebar select jika diperlukan */
            min-width: 150px;
            /* Contoh: beri lebar minimum */
        }

        /* Styling untuk metric selector */
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

        /* Styling untuk overlay */
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

        /* Styling untuk checkbox container */
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

        .interval-buttons {
            display: flex;
            gap: 4px;
        }

        .interval-buttons button {
            /* Pastikan tombol interval juga memiliki tinggi yang sama */
            height: 40px;
            flex-grow: 1;
            /* Biarkan tombol membesar jika ada ruang, atau set width spesifik */
            cursor: pointer;
            white-space: nowrap;
            /* Prevent buttons from wrapping text */
        }

        .interval-buttons button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .filter-group button {
            /* Style for action buttons */
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            padding: 10px 18px;
            /* More padding for a bolder button */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            height: 40px;
            /* Set tinggi yang sama dengan input/select */
            box-sizing: border-box;
        }

        .filter-group button:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        /* ---------------------------------- */
        /* Chart Container            */
        /* ---------------------------------- */
        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
            /* Tinggi yang cukup untuk menampilkan grafik */
            border-radius: 10px;
            overflow: hidden;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
        }
    </style>
    @livewireStyles
</head>

<body>
    <div class="dashboard-container">
        {{-- Anda bisa menambahkan kembali header dan tabs di sini jika perlu --}}
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
                {{-- Tombol log bisa ditambahkan di sini jika perlu --}}
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom/dist/chartjs-plugin-zoom.min.js"></script>

    {{-- Livewire Scripts --}}
    @livewireScripts

    <script>
        // Initialize metric selector when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            initializeMetricSelector();
        });

        document.addEventListener('livewire:navigated', () => {
            // Initialize metric selector
            initializeMetricSelector();

            const ctx = document.getElementById('historicalChart')?.getContext('2d');
            if (!ctx) return;

            let historicalChart;

            // Listener untuk event BERAT: Mengganti seluruh data grafik
            document.addEventListener('chart-data-updated', event => {
                const chartData = event.detail.chartData;
                if (!historicalChart) {
                    historicalChart = new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: {
                            /* ... Opsi Chart.js Anda ... */
                        }
                    });
                } else {
                    historicalChart.data = chartData;
                    historicalChart.update('none');
                }
            });

            // Listener untuk event RINGAN: Menambahkan satu titik data baru
            document.addEventListener('new-data-point', event => {
                if (!historicalChart || !historicalChart.data.labels || historicalChart.data.labels
                    .length === 0) return;

                const newData = event.detail.data;
                const lastLabel = historicalChart.data.labels[historicalChart.data.labels.length - 1];
                if (lastLabel && new Date(newData.timestamp) <= new Date(lastLabel)) {
                    return;
                }

                historicalChart.data.labels.push(newData.timestamp);
                historicalChart.data.datasets.forEach(dataset => {
                    dataset.data.push(newData.metrics[dataset.label] ?? null);
                });

                const maxDataPoints = 120;
                if (historicalChart.data.labels.length > maxDataPoints) {
                    historicalChart.data.labels.shift();
                    historicalChart.data.datasets.forEach(dataset => dataset.data.shift());
                }

                historicalChart.update('none');
            });
        });

        // Global function to update selected metrics display
        function updateSelectedMetricsDisplay() {
            const selectedMetricsDisplay = document.getElementById('selected-metrics-display-livewire');
            if (!selectedMetricsDisplay) return;

            const selectedTags = Array.from(document.querySelectorAll(
                '#tag-checkboxes-livewire input[type="checkbox"]:checked'
            )).map(cb => cb.value);

            if (selectedTags.length === 0) {
                selectedMetricsDisplay.innerHTML = '<span class="placeholder">No metrics selected</span>';
            } else {
                const metricTags = selectedTags.map(tag =>
                    `<span class="metric-tag">${tag}<span class="remove-tag" data-tag="${tag}">&times;</span></span>`
                ).join('');
                selectedMetricsDisplay.innerHTML = metricTags;
            }
        }

        // Metric Selector JavaScript
        function initializeMetricSelector() {
            const selectedMetricsDisplay = document.getElementById('selected-metrics-display-livewire');
            const metricOverlay = document.getElementById('metric-overlay-livewire');
            const closeOverlayBtn = document.getElementById('close-overlay-livewire');

            if (!selectedMetricsDisplay || !metricOverlay) return;

            // Toggle overlay
            selectedMetricsDisplay.addEventListener('click', () => {
                metricOverlay.classList.toggle('show');
                selectedMetricsDisplay.classList.toggle('open');
            });

            // Close overlay
            closeOverlayBtn?.addEventListener('click', () => {
                metricOverlay.classList.remove('show');
                selectedMetricsDisplay.classList.remove('open');
            });

            // Close overlay when clicking outside
            document.addEventListener('click', (e) => {
                if (!selectedMetricsDisplay.contains(e.target) && !metricOverlay.contains(e.target)) {
                    metricOverlay.classList.remove('show');
                    selectedMetricsDisplay.classList.remove('open');
                }
            });

            // Listen for checkbox changes
            document.querySelectorAll('#tag-checkboxes-livewire input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedMetricsDisplay);
            });

            // Handle remove tag clicks
            selectedMetricsDisplay.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-tag')) {
                    const tagToRemove = e.target.dataset.tag;
                    const checkbox = document.querySelector(
                        `#tag-checkboxes-livewire input[value="${tagToRemove}"]`
                    );
                    if (checkbox) {
                        checkbox.checked = false;
                        checkbox.dispatchEvent(new Event('change'));
                    }
                }
            });

            // Initial update
            updateSelectedMetricsDisplay();
        }

        // Update metric selector when Livewire updates
        document.addEventListener('livewire:updated', () => {
            setTimeout(() => {
                updateSelectedMetricsDisplay();
            }, 100);
        });
    </script>
</body>

</html>
