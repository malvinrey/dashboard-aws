<div>
    <div wire:poll.5s="getLatestDataPoint">
        {{-- Overlay loading untuk data historis --}}
        <div class="loading-overlay" wire:loading wire:target="loadChartData">
            <div class="spinner"></div>
        </div>

        {{-- Icon loading kecil untuk data real-time --}}
        <div class="realtime-loading-indicator" wire:loading wire:target="getLatestDataPoint"
            title="Updating real-time data...">
            <div class="realtime-spinner"></div>
        </div>

        {{-- Status indicator untuk real-time data --}}
        <div class="realtime-status" wire:loading.remove wire:target="getLatestDataPoint"
            title="Real-time data connected">
            <div class="status-dot-green"></div>
        </div>

        {{-- Filter Controls --}}
        <div class="filters">
            <div class="filter-group">
                <label for="metric-selector">Select Metric:</label>
                {{-- Dropdown ini dikelola oleh JS, tapi nilainya dikirim ke Livewire --}}
                <div wire:ignore>
                    <select id="metric-selector" class="metric-dropdown">
                        @foreach ($allTags as $tag)
                            <option value="{{ $tag }}" @if (in_array($tag, $selectedTags)) selected @endif>
                                {{ ucfirst($tag) }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
            <div class="filter-group">
                <button wire:click="loadChartData" class="btn-primary">Load Historical Data</button>
            </div>
        </div>

        {{-- Chart Container --}}
        <div class="single-chart-container" wire:ignore>
            <canvas id="singleChart"></canvas>
            <div
                style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 8px; border-radius: 4px; font-size: 12px; pointer-events: none;">
                <div>Scroll: Zoom | Drag: Pan</div>
            </div>
        </div>
    </div>

    @script
        <script>
            document.addEventListener('livewire:navigated', () => {
                let singleChartInstance = null;
                let isLoadingMore = false;
                const ctx = document.getElementById('singleChart')?.getContext('2d');
                if (!ctx) return;

                // KUNCI: Fungsi untuk lazy loading data historis
                const handleLazyLoad = (chart) => {
                    if (isLoadingMore || !chart.data.datasets[0].data.length) return;

                    const currentMin = chart.scales.x.min;
                    const oldestDataPoint = chart.data.datasets[0].data[0].x;

                    if (currentMin < oldestDataPoint) {
                        isLoadingMore = true;
                        const newEndDate = new Date(oldestDataPoint);
                        const newStartDate = new Date(newEndDate.getTime() - (24 * 60 * 60 * 1000));

                        window.Livewire.dispatch('loadMoreHistoricalData', {
                            startDate: newStartDate.toISOString().split('T')[0],
                            endDate: newEndDate.toISOString().split('T')[0]
                        });
                    }
                };

                const createOrUpdateChart = (initialData, metricName) => {
                    if (singleChartInstance) singleChartInstance.destroy();
                    isLoadingMore = false;

                    singleChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            datasets: [{
                                label: metricName,
                                data: initialData,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.1,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            parsing: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                },
                                zoom: {
                                    pan: {
                                        enabled: true,
                                        mode: 'x',
                                        onPanComplete: ({
                                            chart
                                        }) => handleLazyLoad(chart)
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
                            },
                            scales: {
                                x: {
                                    type: 'time',
                                    time: {
                                        unit: 'minute',
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
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    });
                };

                // Listener untuk data historis awal
                document.addEventListener('chart-data-updated', event => {
                    const chartData = event.detail.chartData;
                    if (chartData && chartData.datasets.length > 0) {
                        const dataset = chartData.datasets[0];
                        const formattedData = chartData.labels.map((label, index) => ({
                            x: new Date(label).getTime(),
                            y: dataset.data[index]
                        }));
                        createOrUpdateChart(formattedData, dataset.label);
                    }
                });

                // Listener untuk data historis yang di-lazy load
                document.addEventListener('historical-data-prepended', event => {
                    if (singleChartInstance && event.detail.data.labels.length > 0) {
                        const newPoints = event.detail.data.labels.map((label, index) => ({
                            x: new Date(label).getTime(),
                            y: event.detail.data.datasets[0].data[index]
                        }));
                        singleChartInstance.data.datasets[0].data.unshift(...newPoints);
                        singleChartInstance.update('none');
                    }
                    isLoadingMore = false;
                });

                // Listener untuk data real-time baru
                document.addEventListener('new-data-point', event => {
                    const newData = event.detail.data;
                    if (!singleChartInstance || !newData || !newData.metrics) return;

                    const currentMetric = singleChartInstance.data.datasets[0].label;
                    const newValue = newData.metrics[currentMetric];

                    if (typeof newValue !== 'undefined') {
                        singleChartInstance.data.datasets[0].data.push({
                            x: new Date(newData.timestamp).getTime(),
                            y: newValue
                        });

                        const lastDataTime = singleChartInstance.data.datasets[0].data[singleChartInstance.data
                            .datasets[0].data.length - 2]?.x;
                        if (lastDataTime && singleChartInstance.scales.x.max >= lastDataTime) {
                            singleChartInstance.update('quiet');
                        } else {
                            singleChartInstance.update('none');
                        }
                    }
                });

                // Setup listener untuk dropdown
                const metricSelector = document.getElementById('metric-selector');
                metricSelector.addEventListener('change', function() {
                    // Saat dropdown berubah, kirim event ke Livewire dan muat ulang data
                    @this.set('selectedTags', [this.value]);
                    @this.call('loadChartData');
                });

                // Memuat data awal saat halaman pertama kali dibuka
                window.Livewire.dispatch('loadChartData');
            });
        </script>
    @endscript
</div>
