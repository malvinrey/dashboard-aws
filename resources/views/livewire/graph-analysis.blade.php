<div>
    {{-- CSS untuk layout grid grafik --}}
    <style>
        .charts-grid {
            display: grid;
            /* Membuat kolom yang fleksibel, minimal 400px */
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .chart-card {
            background-color: var(--bg-white, #ffffff);
            padding: 16px;
            border-radius: var(--radius-lg, 0.5rem);
            border: 1px solid var(--border-color, #e5e7eb);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
        }

        .chart-card-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: var(--text-primary, #111827);
        }

        .chart-container-wrapper {
            position: relative;
            height: 250px;
            /* Tinggi yang ideal untuk preview */
            flex-grow: 1;
        }
    </style>

    {{-- Elemen ini akan terus melakukan polling untuk data terbaru --}}
    <div wire:poll.5s="getLatestDataPoint">
        {{-- Indikator loading saat filter diterapkan --}}
        <div class="loading-overlay" wire:loading wire:target="loadChartData">
            <div class="spinner"></div>
        </div>

        {{-- Filter yang disederhanakan, tanpa pemilih metrik --}}
        <div class="filters">
            <div class="filter-group">
                <label>Select Interval:</label>
                <div class="interval-buttons">
                    <button wire:click="$set('interval', 'hour')"
                        class="{{ $interval === 'hour' ? 'active' : '' }}">Hour</button>
                    <button wire:click="$set('interval', 'day')"
                        class="{{ $interval === 'day' ? 'active' : '' }}">Day</button>
                    <button wire:click="$set('interval', 'minute')"
                        class="{{ $interval === 'minute' ? 'active' : '' }}">Minute</button>
                    <button wire:click="$set('interval', 'second')"
                        class="{{ $interval === 'second' ? 'active' : '' }}">Second</button>
                </div>
            </div>
            <div class="filter-group">
                <label for="start-date-livewire">Start Date:</label>
                <input type="date" id="start-date-livewire" wire:model="startDate">
            </div>
            <div class="filter-group">
                <label for="end-date-livewire">End Date:</label>
                <input type="date" id="end-date-livewire" wire:model="endDate">
            </div>
            <div class="filter-group">
                <button wire:click="loadChartData" class="btn-primary">Apply Filter</button>
            </div>
        </div>

        {{-- Grid untuk menampung semua grafik. `wire:ignore` penting agar Chart.js tidak terganggu oleh Livewire --}}
        <div class="charts-grid" wire:ignore>
            {{-- Loop ini hanya untuk membuat placeholder canvas. Chart akan di-render oleh JavaScript. --}}
            @foreach ($allTags as $metric)
                <div class="chart-card">
                    <h3 class="chart-card-title">{{ $metric }}</h3>
                    <div class="chart-container-wrapper">
                        {{-- ID canvas dibuat unik berdasarkan nama metrik --}}
                        <canvas id="chart-{{ \Illuminate\Support\Str::slug($metric) }}"></canvas>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Skrip khusus untuk merender banyak grafik --}}
    <script>
        // Pastikan Chart.js tersedia
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
        } else {
            console.log('Chart.js is loaded successfully');
        }

        // Objek untuk menyimpan semua instance Chart.js
        window.chartInstances = {};

        // Fungsi untuk membuat atau memperbarui satu grafik
        window.createOrUpdateChart = function(ctx, chartData, metricName) {
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'minute',
                            tooltipFormat: 'PPpp'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            };

            if (window.chartInstances[metricName]) {
                window.chartInstances[metricName].data = chartData;
                window.chartInstances[metricName].update('none');
            } else {
                window.chartInstances[metricName] = new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: chartOptions
                });
            }
        };

        // Listener untuk event chart-data-updated
        document.addEventListener('chart-data-updated', function(event) {
            console.log('chart-data-updated event received');
            const chartData = event.detail.chartData;
            console.log('Chart data received:', chartData);

            if (chartData && chartData.datasets && chartData.datasets.length > 0) {
                chartData.datasets.forEach(function(dataset) {
                    const metricName = dataset.label;
                    const slug = metricName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g,
                        '');
                    const canvasElement = document.getElementById('chart-' + slug);
                    const ctx = canvasElement ? canvasElement.getContext('2d') : null;

                    console.log('Looking for canvas: chart-' + slug, canvasElement);

                    if (ctx) {
                        const singleMetricData = {
                            labels: chartData.labels,
                            datasets: [dataset]
                        };
                        window.createOrUpdateChart(ctx, singleMetricData, metricName);
                    }
                });
            }
        });

        // Listener untuk event new-data-point
        document.addEventListener('new-data-point', function(event) {
            const newData = event.detail.data;
            if (!newData || !newData.metrics) return;

            for (const metricName in newData.metrics) {
                const chart = window.chartInstances[metricName];
                if (!chart) continue;

                const value = newData.metrics[metricName];
                const timestamp = newData.timestamp;

                chart.data.labels.push(timestamp);
                chart.data.datasets.forEach(function(dataset) {
                    dataset.data.push(value);
                });

                const maxDataPoints = 120;
                if (chart.data.labels.length > maxDataPoints) {
                    chart.data.labels.shift();
                    chart.data.datasets.forEach(function(dataset) {
                        dataset.data.shift();
                    });
                }

                chart.update('none');
            }
        });
    </script>
</div>
