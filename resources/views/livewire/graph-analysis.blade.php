<div>
    {{-- Indikator loading real-time yang tidak mengganggu dari wire:poll --}}
    <div class="realtime-status" wire:loading.class="loading" wire:target="getLatestDataPoint"
        title="Fetching latest data...">
        <div class="status-dot-green"></div>
    </div>

    {{-- Polling untuk data real-time, berjalan di latar belakang --}}
    <div @if ($realtimeEnabled) wire:poll.5s="getLatestDataPoint" @endif>
        {{-- Overlay loading ini HANYA akan aktif untuk aksi berat seperti loadChartData --}}
        <div class="loading-overlay" wire:loading.flex wire:target="loadChartData, loadMoreSeconds">
            <div class="spinner"></div>
        </div>

        {{-- Bagian Filter --}}
        <div class="filters">
            <div class="filter-group" wire:ignore> {{-- ✅ Bungkus dengan wire:ignore --}}
                <label for="metrics-select2">Select Metrics:</label>
                <select class="js-example-basic-multiple" {{-- Gunakan kelas dari contoh Select2 --}} id="metrics-select2"
                    {{-- Beri ID unik --}} name="tags[]" {{-- Atribut name standar --}} multiple="multiple">
                    {{-- <option value="" disabled selected>Pilih metrics...</option> --}}
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
            <div class="filter-group">
                <button wire:click="loadChartData" class="btn-primary">Load Historical Data</button>
            </div>

            {{-- KUNCI PERBAIKAN: Tambahkan blok toggle switch di sini --}}
            <div class="filter-group">
                <label for="realtime-toggle">Real-time Updates</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="realtime-toggle" wire:model.live="realtimeEnabled">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        {{-- Kontainer untuk pesan peringatan --}}
        <div id="chart-warning"
            style="display: none; padding: 12px; margin-bottom: 16px; border-radius: 0.375rem; background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
            <strong id="warning-message"></strong>
        </div>

        {{-- Kontainer untuk Grafik Plotly --}}
        <div class="single-chart-container" wire:ignore>
            <div id="plotlyChart" style="width: 100%; height: 100%;"></div>
            {{-- Tombol "Load More" yang hanya muncul untuk interval 'second' --}}
            @if ($interval === 'second')
                <div id="load-more-container">
                    <button wire:click="loadMoreSeconds" wire:loading.attr="disabled" class="btn-secondary">
                        <div wire:loading wire:target="loadMoreSeconds" class="spinner"
                            style="width: 16px; height: 16px; border-width: 2px; margin-right: 8px;"></div>
                        <span wire:loading.remove wire:target="loadMoreSeconds">Load 30 Minutes Earlier</span>
                        <span wire:loading wire:target="loadMoreSeconds">Loading...</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @script
        <script>
            document.addEventListener('livewire:navigated', () => {

                // Inisialisasi Select2 pada elemen dengan ID #metrics-select2
                $('#metrics-select2').select2({
                    // Ukuran static untuk kontainer select
                    width: '300px',
                    maximumSelectionLength: 5,
                    dropdownAutoWidth: false,
                    dropdownParent: $('body')
                });

                // Pasang event listener 'change' dari Select2
                $('#metrics-select2').on('change', function(e) {
                    // Ambil semua nilai yang dipilih
                    var selectedValues = $(this).val();

                    // Kirim data ke properti 'selectedTags' di Livewire
                    @this.set('selectedTags', selectedValues);
                });

                const chartContainer = document.getElementById('plotlyChart');
                const warningBox = document.getElementById('chart-warning');
                const warningMessage = document.getElementById('warning-message');

                if (!chartContainer || !warningBox) return;

                let globalState = {
                    lastKnownTimestamp: null,
                    isCatchingUp: false,
                };

                const updateLastKnownTimestamp = () => {
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (plotlyChart && plotlyChart.data && plotlyChart.data.length > 0) {
                        let maxTimestamp = 0;
                        plotlyChart.data.forEach(trace => {
                            if (trace.x && trace.x.length > 0) {
                                const lastTimestampInTrace = new Date(trace.x[trace.x.length - 1])
                                    .getTime();
                                if (lastTimestampInTrace > maxTimestamp) {
                                    maxTimestamp = lastTimestampInTrace;
                                }
                            }
                        });
                        if (maxTimestamp > 0) {
                            const lastDate = new Date(maxTimestamp);
                            globalState.lastKnownTimestamp = lastDate.toISOString().slice(0, 19).replace('T', ' ');
                        } else {
                            globalState.lastKnownTimestamp = null;
                        }
                    } else {
                        globalState.lastKnownTimestamp = null;
                    }
                };

                document.addEventListener('visibilitychange', () => {
                    const realtimeToggle = document.getElementById('realtime-toggle');
                    if (document.visibilityState === 'visible' && globalState.lastKnownTimestamp &&
                        realtimeToggle && realtimeToggle.checked) {
                        globalState.isCatchingUp = true;
                        window.Livewire.dispatch('catchUpMissedData', {
                            lastKnownTimestamp: globalState.lastKnownTimestamp
                        });
                    }
                });

                const renderChart = (plotlyData, layout) => {
                    Plotly.react('plotlyChart', plotlyData, layout, {
                        responsive: true,
                        displaylogo: false
                    });
                };

                document.addEventListener('chart-data-updated', event => {
                    warningBox.style.display = 'none';
                    const chartData = event.detail.chartData;
                    if (chartData && chartData.data && chartData.data.length > 0) {
                        const plotlyData = chartData.data.map(trace => ({
                            ...trace,
                            x: trace.x.map(dateStr => new Date(dateStr)),
                        }));
                        renderChart(plotlyData, chartData.layout);
                        updateLastKnownTimestamp();
                    } else {
                        renderChart([], {
                            title: 'No data to display. Please check filters.'
                        });
                        globalState.lastKnownTimestamp = null;
                    }
                });

                document.addEventListener('historical-data-prepended-second', event => {
                    const chartData = event.detail.data;
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (chartData && chartData.data && chartData.data.length > 0 && plotlyChart && plotlyChart
                        .data) {
                        chartData.data.forEach((newTrace, traceIndex) => {
                            if (traceIndex < plotlyChart.data.length) {
                                const newDates = newTrace.x.map(dateStr => new Date(dateStr));
                                if (newDates.length > 0) {
                                    Plotly.prependTraces('plotlyChart', {
                                        x: [newDates],
                                        y: [newTrace.y]
                                    }, [traceIndex]);
                                }
                            }
                        });
                    }
                });

                document.addEventListener('show-warning', event => {
                    warningMessage.textContent = event.detail.message;
                    warningBox.style.display = 'block';
                });

                // KUNCI PERBAIKAN 2: Kembalikan listener 'update-last-point' yang lebih sederhana
                document.addEventListener('update-last-point', event => {
                    if (globalState.isCatchingUp) {
                        return; // Hentikan jika sedang catch-up
                    }
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (!plotlyChart || !plotlyChart.data || plotlyChart.data.length === 0) return;
                    const newData = event.detail.data;
                    if (!newData || !newData.metrics || !newData.timestamp) return;

                    const newPointTimestamp = new Date(newData.timestamp);
                    let needsRedraw = false;

                    Object.entries(newData.metrics).forEach(([metricName, newValue]) => {
                        const traceIndex = plotlyChart.data.findIndex(trace => trace.name ===
                            metricName);
                        if (traceIndex !== -1) {
                            const currentTrace = plotlyChart.data[traceIndex];
                            const lastIndex = currentTrace.x.length - 1;
                            const lastChartTimestamp = new Date(currentTrace.x[lastIndex]);

                            if (newPointTimestamp.getTime() === lastChartTimestamp.getTime()) {
                                currentTrace.y[lastIndex] = newValue;
                                needsRedraw = true;
                            } else if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
                                Plotly.extendTraces('plotlyChart', {
                                    x: [
                                        [newPointTimestamp]
                                    ],
                                    y: [
                                        [newValue]
                                    ]
                                }, [traceIndex]);
                            }
                        }
                    });

                    if (needsRedraw) {
                        Plotly.redraw('plotlyChart');
                    }
                    updateLastKnownTimestamp();
                });

                // HAPUS listener 'append-new-points' yang lebih berat

                document.addEventListener('append-missed-points', event => {
                    const missedData = event.detail.data;
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (!plotlyChart || !plotlyChart.data || !missedData) {
                        globalState.isCatchingUp = false;
                        return;
                    }
                    plotlyChart.data.forEach((trace, traceIndex) => {
                        const metricName = trace.name;
                        if (missedData[metricName] && missedData[metricName].length > 0) {
                            const pointsToAdd = missedData[metricName];
                            const newX = pointsToAdd.map(p => new Date(p.timestamp));
                            const newY = pointsToAdd.map(p => p.value);
                            const breakDate = new Date(newX[0].getTime() - 1000);
                            const finalX = [breakDate, ...newX];
                            const finalY = [null, ...newY];
                            Plotly.extendTraces('plotlyChart', {
                                x: [finalX],
                                y: [finalY]
                            }, [traceIndex]);
                        }
                    });
                    updateLastKnownTimestamp();
                    globalState.isCatchingUp = false;
                });

                window.Livewire.dispatch('loadChartData');
            });
        </script>
    @endscript
</div>
