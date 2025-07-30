<div>
    {{-- KUNCI PERBAIKAN: Indikator loading real-time yang tidak mengganggu --}}
    <div class="realtime-status" wire:loading.class="loading" wire:target="getLatestDataPoint"
        title="Fetching latest data...">
        <div class="status-dot-green"></div>
    </div>

    <div wire:poll.5s="getLatestDataPoint">
        {{-- Overlay loading ini HANYA akan aktif untuk aksi berat seperti loadChartData --}}
        <div class="loading-overlay" wire:loading.flex wire:target="loadChartData, loadMoreHistoricalData">
            <div class="spinner"></div>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label for="metric-selector">Select Metric:</label>
                <select id="metric-selector" class="metric-dropdown" wire:model.defer="selectedTags.0">
                    @foreach ($allTags as $tag)
                        <option value="{{ $tag }}">{{ ucfirst($tag) }}</option>
                    @endforeach
                </select>
            </div>
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
                <input type="date" id="start-date-livewire" wire:model.defer="startDate">
            </div>
            <div class="filter-group">
                <label for="end-date-livewire">End Date:</label>
                <input type="date" id="end-date-livewire" wire:model.defer="endDate">
            </div>
            {{-- Input waktu kondisional telah dihapus --}}
            <div class="filter-group">
                <button wire:click="loadChartData" class="btn-primary">Load Historical Data</button>
            </div>
        </div>

        <div id="chart-warning"
            style="display: none; padding: 12px; margin-bottom: 16px; border-radius: 0.375rem; background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
            <strong id="warning-message"></strong>
        </div>

        <div class="single-chart-container" wire:ignore>
            <div id="plotlyChart" style="width: 100%; height: 100%;"></div>
            <div
                style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 8px; border-radius: 4px; font-size: 12px; pointer-events: none;">
                <div>Scroll: Zoom | Drag: Pan</div>
            </div>
        </div>
    </div>

    @script
        <script>
            document.addEventListener('livewire:navigated', () => {
                let plotlyChart = null;
                const chartContainer = document.getElementById('plotlyChart');
                const warningBox = document.getElementById('chart-warning');
                const warningMessage = document.getElementById('warning-message');

                if (!chartContainer || !warningBox) return;

                const createOrUpdateChart = (plotlyData, layout) => {
                    if (plotlyChart) {
                        Plotly.purge('plotlyChart');
                    }

                    if (!plotlyData || plotlyData.length === 0) {
                        console.log('No data to display');
                        return;
                    }

                    // Konfigurasi layout default jika tidak ada
                    const defaultLayout = {
                        title: 'Historical Data Analysis',
                        xaxis: {
                            title: 'Timestamp',
                            type: 'date',
                            rangeslider: {
                                visible: false
                            }
                        },
                        yaxis: {
                            title: 'Value'
                        },
                        margin: {
                            l: 50,
                            r: 20,
                            b: 40,
                            t: 40
                        },
                        paper_bgcolor: '#ffffff',
                        plot_bgcolor: '#ffffff',
                        hovermode: 'x unified',
                        showlegend: true,
                        legend: {
                            x: 0,
                            y: 1
                        }
                    };

                    const finalLayout = {
                        ...defaultLayout,
                        ...layout
                    };

                    Plotly.newPlot('plotlyChart', plotlyData, finalLayout, {
                        responsive: true,
                        displayModeBar: true,
                        modeBarButtonsToRemove: ['pan2d', 'lasso2d', 'select2d'],
                        displaylogo: false
                    });

                    plotlyChart = document.getElementById('plotlyChart');
                };

                document.addEventListener('show-warning', event => {
                    warningMessage.textContent = event.detail.message;
                    warningBox.style.display = 'block';
                });

                document.addEventListener('chart-data-updated', event => {
                    warningBox.style.display = 'none';
                    const chartData = event.detail.chartData;

                    if (chartData && chartData.data && chartData.data.length > 0) {
                        console.log('Plotly data received:', chartData);

                        // Konversi data untuk Plotly.js - menggunakan timestamp asli dari database
                        const plotlyData = chartData.data.map(trace => ({
                            ...trace,
                            x: trace.x.map(dateStr => new Date(dateStr)),
                            y: trace.y.map(val => val === null ? null : parseFloat(val)),
                            type: 'scatter',
                            mode: 'lines+markers',
                            line: {
                                width: 2
                            },
                            marker: {
                                size: 4
                            }
                        }));

                        createOrUpdateChart(plotlyData, chartData.layout);
                    } else {
                        console.log('No chart data or data empty');
                    }
                });

                document.addEventListener('historical-data-prepended', event => {
                    if (plotlyChart && event.detail.data && event.detail.data.data.length > 0) {
                        const newData = event.detail.data.data[0];
                        const newX = newData.x.map(dateStr => new Date(dateStr));
                        const newY = newData.y.map(val => val === null ? null : parseFloat(val));

                        Plotly.extendTraces('plotlyChart', {
                            x: [newX],
                            y: [newY]
                        }, [0]);
                    }
                });

                document.addEventListener('update-last-point', event => {
                    const newData = event.detail.data;
                    if (!plotlyChart || !newData || !newData.metrics || !newData.timestamp) return;

                    const currentMetric = newData.metrics;
                    const newPointValue = Object.values(currentMetric)[0];
                    if (typeof newPointValue === 'undefined') return;

                    // Parse timestamp sebagai string lokal tanpa konversi timezone
                    const newPointTimestamp = new Date(newData.timestamp);

                    console.log('Real-time update:', {
                        originalTimestamp: newData.timestamp,
                        parsedTimestamp: newPointTimestamp,
                        value: newPointValue
                    });

                    // Update titik terakhir atau tambah titik baru
                    Plotly.extendTraces('plotlyChart', {
                        x: [
                            [newPointTimestamp]
                        ],
                        y: [
                            [newPointValue]
                        ]
                    }, [0]);
                });

                // Memuat data awal
                window.Livewire.dispatch('loadChartData');
            });
        </script>
    @endscript
</div>
