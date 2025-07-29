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
            {{-- Filter Controls --}}
            <div class="filters">
                @if ($allTags->isNotEmpty())
                    <div class="filter-group">
                        <label>Select Metrics:</label>
                        <div class="metric-selector">
                            <div class="selected-metrics-display" id="selected-metrics-display">
                                <span class="placeholder">No metrics selected</span>
                            </div>
                            <div class="metric-overlay" id="metric-overlay">
                                <div class="overlay-header">
                                    <span>Select Metrics</span>
                                    <button class="close-overlay" id="close-overlay">&times;</button>
                                </div>
                                <div id="tag-checkboxes" class="tag-checkboxes">
                                    @forelse ($allTags as $tag)
                                        <div class="checkbox-item">
                                            <input type="checkbox" id="tag-{{ $loop->index }}" name="tags"
                                                value="{{ $tag }}">
                                            <label for="tag-{{ $loop->index }}">{{ $tag }}</label>
                                        </div>
                                    @empty
                                        <p>No metrics available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Select Interval:</label>
                        <div class="interval-buttons">
                            <button data-interval="hour" class="active">Hour</button>
                            <button data-interval="day">Day</button>
                            <button data-interval="minute">Minute</button>
                            <button data-interval="second">Second</button>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label for="start-date">Start Date:</label>
                        <input type="date" id="start-date">
                    </div>
                    <div class="filter-group">
                        <label for="start-time">Start Time:</label>
                        <input type="time" id="start-time">
                    </div>
                    <div class="filter-group">
                        <label for="end-date">End Date:</label>
                        <input type="date" id="end-date">
                    </div>
                    <div class="filter-group">
                        <label for="end-time">End Time:</label>
                        <input type="time" id="end-time">
                    </div>
                    <div class="filter-group">
                        <button id="filter-btn">Apply Filter</button>
                    </div>
                    <div class="filter-group">
                        <button id="reset-zoom-btn">Reset Zoom</button>
                    </div>
                @else
                    <p>No data available to analyze.</p>
                @endif
                <livewire:analysis-chart />
            </div>
            {{-- Chart Canvas --}}
            <div class="chart-container">
                <canvas id="historicalChart"></canvas>
                <div style="margin-top: 10px; font-size: 12px; color: #6c757d; text-align: center;">
                    <strong>Zoom & Pan Controls:</strong><br>
                    • Mouse wheel: Zoom in/out | • Ctrl + Drag: Pan | • Drag to select area for zoom | • Touch: Pinch to
                    zoom, drag to pan
                </div>
            </div>
        </div>
    </div>

    {{-- Memuat library dengan urutan yang benar --}}
    {{-- 1. Hammer.js harus dimuat terlebih dahulu --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>

    {{-- 2. Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- 3. Date adapter untuk Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>

    {{-- 4. Zoom plugin --}}
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom/dist/chartjs-plugin-zoom.min.js"></script>

    <script>
        // Pastikan plugin zoom tersedia secara global
        window.addEventListener('load', () => {
            if (typeof Chart !== 'undefined' && typeof window.zoomPlugin !== 'undefined') {
                Chart.register(window.zoomPlugin);
            }
        });
        @livewireScripts
        document.addEventListener('DOMContentLoaded', () => {
            // Inisialisasi plugin zoom
            const zoomPlugin = window.zoomPlugin;

            const intervalButtons = document.querySelectorAll('.interval-buttons button');
            const startDateInput = document.getElementById('start-date');
            const startTimeInput = document.getElementById('start-time');
            const endDateInput = document.getElementById('end-date');
            const endTimeInput = document.getElementById('end-time');
            const filterBtn = document.getElementById('filter-btn');
            const resetZoomBtn = document.getElementById('reset-zoom-btn');
            const selectedMetricsDisplay = document.getElementById('selected-metrics-display');
            const metricOverlay = document.getElementById('metric-overlay');
            const closeOverlayBtn = document.getElementById('close-overlay');
            const ctx = document.getElementById('historicalChart').getContext('2d');
            let historicalChart;

            // Fungsi untuk memperbarui tampilan selected metrics
            function updateSelectedMetricsDisplay() {
                const selectedTags = Array.from(document.querySelectorAll(
                        '#tag-checkboxes input[name="tags"]:checked'))
                    .map(cb => cb.value);

                const displayElement = selectedMetricsDisplay;

                if (selectedTags.length === 0) {
                    displayElement.innerHTML = '<span class="placeholder">No metrics selected</span>';
                } else {
                    const metricTags = selectedTags.map(tag =>
                        `<span class="metric-tag">${tag}<span class="remove-tag" data-tag="${tag}">&times;</span></span>`
                    ).join('');
                    displayElement.innerHTML = metricTags;
                }
            }

            // Fungsi untuk toggle overlay
            function toggleOverlay() {
                metricOverlay.classList.toggle('show');
            }

            // Fungsi untuk menutup overlay
            function closeOverlay() {
                metricOverlay.classList.remove('show');
            }

            // Fungsi untuk mengambil data dan memperbarui grafik
            async function updateChart() {
                // --- UBAH CARA MENGAMBIL TAG ---
                const selectedTags = Array.from(document.querySelectorAll(
                        '#tag-checkboxes input[name="tags"]:checked'))
                    .map(cb => cb.value);

                const selectedInterval = document.querySelector('.interval-buttons button.active').dataset
                    .interval;
                const startDate = startDateInput.value;
                const startTime = startTimeInput.value;
                const endDate = endDateInput.value;
                const endTime = endTimeInput.value;

                // Update tampilan selected metrics
                updateSelectedMetricsDisplay();

                if (selectedTags.length === 0) {
                    // Jika tidak ada yang dipilih, kosongkan grafik atau tampilkan pesan
                    if (historicalChart) {
                        historicalChart.data.labels = [];
                        historicalChart.data.datasets = [];
                        historicalChart.update();
                    }
                    return;
                }

                // --- UBAH CARA MEMBANGUN URL ---
                const params = new URLSearchParams({
                    interval: selectedInterval,
                });
                selectedTags.forEach(tag => params.append('tag[]', tag)); // Gunakan 'tag[]'
                if (startDate) params.append('start_date', startDate);
                if (endDate) params.append('end_date', endDate);
                if (startTime) params.append('start_time', startTime);
                if (endTime) params.append('end_time', endTime);

                // Membuat base URL secara dinamis dari lokasi halaman saat ini
                const baseUrl = `${window.location.protocol}//${window.location.host}`;
                const url = `${baseUrl}/api/analysis-data?${params.toString()}`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const chartData = await response
                        .json(); // Data sekarang berisi { labels: [], datasets: [] }

                    if (!historicalChart) {
                        historicalChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: chartData.labels,
                                datasets: chartData.datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                scales: {
                                    x: {
                                        type: 'time',
                                        time: {
                                            tooltipFormat: 'yyyy-MM-dd HH:mm:ss',
                                            displayFormats: {
                                                hour: 'MMM d, HH:mm',
                                                day: 'MMM d',
                                                minute: 'HH:mm',
                                                second: 'HH:mm:ss'
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: 'Timestamp'
                                        },
                                        grid: {
                                            display: true
                                        }
                                    },
                                    y: {
                                        beginAtZero: false,
                                        title: {
                                            display: true,
                                            text: 'Value'
                                        },
                                        grid: {
                                            display: true
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    },
                                    tooltip: {
                                        enabled: true,
                                        mode: 'index',
                                        intersect: false
                                    },
                                    zoom: {
                                        pan: {
                                            enabled: true,
                                            mode: 'x',
                                            modifierKey: 'ctrl',
                                            threshold: 10
                                        },
                                        zoom: {
                                            wheel: {
                                                enabled: true,
                                                speed: 0.1
                                            },
                                            pinch: {
                                                enabled: true
                                            },
                                            mode: 'x',
                                            drag: {
                                                enabled: true,
                                                backgroundColor: 'rgba(225,225,225,0.3)',
                                                borderColor: 'rgba(225,225,225)',
                                                borderWidth: 1,
                                                threshold: 10
                                            },
                                            mode: 'x'
                                        }
                                    }
                                }
                            }
                        });
                    } else { // Jika sudah ada, update datanya
                        historicalChart.data.labels = chartData.labels;
                        historicalChart.data.datasets = chartData.datasets; // <-- GANTI SELURUH DATASET
                        historicalChart.update('none');
                    }
                } catch (error) {
                    console.error("Failed to fetch chart data:", error);
                }
            }

            // Atur event listener
            filterBtn?.addEventListener('click', updateChart);

            // Event listener untuk selected metrics display (toggle overlay)
            selectedMetricsDisplay?.addEventListener('click', toggleOverlay);

            // Event listener untuk close overlay button
            closeOverlayBtn?.addEventListener('click', closeOverlay);

            // Event listener untuk checkbox tags
            document.querySelectorAll('#tag-checkboxes input[name="tags"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateChart);
            });

            // Event listener untuk remove tag buttons (delegated)
            selectedMetricsDisplay?.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-tag')) {
                    const tagToRemove = e.target.dataset.tag;
                    const checkbox = document.querySelector(
                        `#tag-checkboxes input[value="${tagToRemove}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                        updateChart();
                    }
                }
            });

            // Close overlay when clicking outside
            document.addEventListener('click', (e) => {
                if (!selectedMetricsDisplay?.contains(e.target) && !metricOverlay?.contains(e.target)) {
                    closeOverlay();
                }
            });

            intervalButtons.forEach(button => {
                button.addEventListener('click', () => {
                    intervalButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    updateChart(); // Update chart ketika interval berubah
                });
            });

            // Atur event listener untuk tombol Reset Zoom
            resetZoomBtn?.addEventListener('click', () => {
                if (historicalChart && historicalChart.resetZoom) {
                    historicalChart.resetZoom();
                }
            });

            // Inisialisasi tampilan selected metrics
            updateSelectedMetricsDisplay();

            // Muat data awal saat halaman dibuka (hanya jika ada checkbox yang terpilih)
            const hasCheckedTags = document.querySelectorAll('#tag-checkboxes input[name="tags"]:checked').length >
                0;
            if (hasCheckedTags) {
                updateChart();
            }

            // === Listener untuk event BERAT: Mengganti seluruh data grafik ===
            // Dipicu oleh loadChartData()
            document.addEventListener('chart-data-updated', event => {
                const chartData = event.detail.chartData;
                if (!historicalChart) {
                    // Buat chart jika belum ada
                    historicalChart = new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            scales: {
                                x: {
                                    type: 'time',
                                    time: {
                                        tooltipFormat: 'yyyy-MM-dd HH:mm:ss',
                                        displayFormats: {
                                            hour: 'MMM d, HH:mm',
                                            day: 'MMM d',
                                            minute: 'HH:mm',
                                            second: 'HH:mm:ss'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Timestamp'
                                    },
                                    grid: {
                                        display: true
                                    }
                                },
                                y: {
                                    beginAtZero: false,
                                    title: {
                                        display: true,
                                        text: 'Value'
                                    },
                                    grid: {
                                        display: true
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    enabled: true,
                                    mode: 'index',
                                    intersect: false
                                },
                                zoom: {
                                    pan: {
                                        enabled: true,
                                        mode: 'x',
                                        modifierKey: 'ctrl',
                                        threshold: 10
                                    },
                                    zoom: {
                                        wheel: {
                                            enabled: true,
                                            speed: 0.1
                                        },
                                        pinch: {
                                            enabled: true
                                        },
                                        mode: 'x',
                                        drag: {
                                            enabled: true,
                                            backgroundColor: 'rgba(225,225,225,0.3)',
                                            borderColor: 'rgba(225,225,225)',
                                            borderWidth: 1,
                                            threshold: 10
                                        },
                                        mode: 'x'
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // Ganti seluruh data
                    historicalChart.data.labels = chartData.labels;
                    historicalChart.data.datasets = chartData.datasets;
                    historicalChart.update('none');
                }
            });

            // === Listener untuk event RINGAN: Menambahkan satu titik data baru ===
            // Dipicu oleh getLatestDataPoint()
            document.addEventListener('new-data-point', event => {

                if (!historicalChart || historicalChart.data.labels.length === 0) return;

                const newData = event.detail.data;

                // Cek duplikasi
                const lastLabel = historicalChart.data.labels[historicalChart.data.labels.length - 1];
                if (lastLabel && new Date(newData.timestamp) <= new Date(lastLabel)) {
                    return;
                }

                // TAMBAHKAN data baru ke ujung grafik
                historicalChart.data.labels.push(newData.timestamp);
                historicalChart.data.datasets.forEach(dataset => {
                    dataset.data.push(newData.metrics[dataset.label] ?? null);
                });

                // HAPUS data tertua dari awal grafik
                historicalChart.data.labels.shift();
                historicalChart.data.datasets.forEach(dataset => dataset.data.shift());

                historicalChart.update('none');
            });
        });
    </script>
</body>

</html>
