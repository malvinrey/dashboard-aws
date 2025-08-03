<div>
    {{-- Indikator loading real-time yang tidak mengganggu dari wire:poll --}}
    <div class="realtime-status" wire:loading.class="loading" wire:target="getLatestDataPoint"
        title="Fetching latest data...">
        <div class="status-dot-green"></div>
    </div>

    {{-- Overlay loading ini HANYA akan aktif untuk aksi berat seperti loadChartData --}}
    <div class="loading-overlay" wire:loading.flex wire:target="loadChartData, loadMoreSeconds">
        <div class="spinner"></div>
    </div>

    {{-- Bagian Filter --}}
    <div class="filters">
        <div class="filter-group" wire:ignore> {{-- ✅ Bungkus dengan wire:ignore --}}
            <label for="metrics-select2">Select Metrics:</label>
            <select class="js-example-basic-multiple" {{-- Gunakan kelas dari contoh Select2 --}} id="metrics-select2" {{-- Beri ID unik --}}
                name="tags[]" {{-- Atribut name standar --}} multiple="multiple">
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
            {{-- KUNCI PERBAIKAN 3: Arahkan wire:click ke metode baru --}}
            <button wire:click="setHistoricalModeAndLoad" class="btn-primary">Load Historical Data</button>
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
                    // Hanya berjalan saat tab kembali terlihat
                    if (document.visibilityState === 'visible') {
                        const plotlyChart = document.getElementById('plotlyChart');
                        const realtimeToggle = document.getElementById('realtime-toggle');

                        // Pastikan semua kondisi terpenuhi untuk melanjutkan
                        if (!plotlyChart || !plotlyChart.data || !realtimeToggle || !realtimeToggle.checked || !
                            globalState.lastKnownTimestamp) {
                            return;
                        }

                        const now = new Date();
                        const lastKnown = new Date(globalState.lastKnownTimestamp);
                        const gapInSeconds = (now - lastKnown) / 1000;

                        // Jika jeda waktu lebih dari 15 detik, buat jeda visual di grafik
                        if (gapInSeconds > 15) {
                            console.log(
                                `Gap of ${gapInSeconds.toFixed(1)}s detected. Inserting break in chart.`);

                            // Buat timestamp untuk jeda, sedikit setelah titik terakhir yang diketahui
                            const breakTimestamp = new Date(lastKnown.getTime() + 1000);

                            // Siapkan update untuk SEMUA trace yang ada di grafik
                            const traceIndices = plotlyChart.data.map((_, i) => i);
                            const updateX = traceIndices.map(() => [breakTimestamp]);
                            const updateY = traceIndices.map(() => [null]); // Inilah kuncinya

                            // Masukkan titik 'null' ke semua trace untuk menciptakan jeda
                            Plotly.extendTraces('plotlyChart', {
                                x: updateX,
                                y: updateY
                            }, traceIndices);

                            console.log('Break inserted in chart for all traces');
                        }
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

                // Fungsi untuk handle chart update secara langsung (tanpa event bus)
                function handleChartUpdate(newData) {
                    if (!newData || !newData.metrics || !newData.timestamp) {
                        console.log('Invalid data format received by handleChartUpdate:', newData);
                        return;
                    }

                    const plotlyChart = document.getElementById('plotlyChart');
                    if (!plotlyChart || !plotlyChart.data || plotlyChart.data.length === 0) {
                        console.log('No chart data available in handleChartUpdate');
                        return;
                    }

                    console.log('handleChartUpdate processing data:', newData);
                    const newPointTimestamp = new Date(newData.timestamp);
                    let needsRedraw = false;

                    Object.entries(newData.metrics).forEach(([metricName, newValue]) => {
                        const traceIndex = plotlyChart.data.findIndex(trace => trace.name === metricName);
                        if (traceIndex !== -1) {
                            const currentTrace = plotlyChart.data[traceIndex];
                            if (!currentTrace.x || currentTrace.x.length === 0) {
                                console.log(`Trace ${metricName} is empty, skipping`);
                                return; // Lewati jika trace kosong
                            }

                            const lastIndex = currentTrace.x.length - 1;
                            const lastValue = currentTrace.y[lastIndex];

                            // KUNCI PERBAIKAN: Logika baru untuk menangani jeda
                            if (lastValue === null) {
                                // Jika titik terakhir adalah 'null', ganti dengan data baru untuk menyambung garis
                                currentTrace.x[lastIndex] = newPointTimestamp;
                                currentTrace.y[lastIndex] = newValue;
                                needsRedraw = true; // Tandai untuk menggambar ulang seluruh grafik
                                console.log(`Resumed line for ${metricName} after gap.`);
                            } else {
                                // Jika tidak ada jeda, gunakan logika lama yang sudah berjalan baik
                                const lastChartTimestamp = new Date(currentTrace.x[lastIndex]);

                                console.log(
                                    `Metric: ${metricName}, New: ${newValue}, Last: ${currentTrace.y[lastIndex]}, Time: ${newPointTimestamp}`
                                );

                                if (newPointTimestamp.getTime() === lastChartTimestamp.getTime()) {
                                    // Update existing point
                                    currentTrace.y[lastIndex] = newValue;
                                    needsRedraw = true;
                                    console.log(`Updated existing point for ${metricName}`);
                                } else if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
                                    // Add new point
                                    Plotly.extendTraces('plotlyChart', {
                                        x: [
                                            [newPointTimestamp]
                                        ],
                                        y: [
                                            [newValue]
                                        ]
                                    }, [traceIndex]);
                                    console.log(`Added new point for ${metricName}`);
                                }
                            }
                        } else {
                            console.log(`Trace not found for metric: ${metricName}`);
                        }
                    });

                    if (needsRedraw) {
                        Plotly.redraw('plotlyChart');
                        console.log('Chart redrawn');
                    }
                    // Panggil updateLastKnownTimestamp setelah grafik diubah
                    updateLastKnownTimestamp();
                }

                // Event listener untuk update chart dari API polling (simplified)
                document.addEventListener('update-last-point', event => {
                    console.log('Update last point event received:', event.detail);
                    handleChartUpdate(event.detail.data);
                });

                // HAPUS listener 'append-new-points' yang lebih berat

                // HAPUS listener 'append-missed-points' - SUDAH TIDAK DIPERLUKAN
                // Gap sekarang dibuat langsung di visibilitychange listener

                // Polling API untuk data real-time
                let realtimePollingInterval = null;

                function startRealtimePolling() {
                    console.log('Starting realtime polling...');

                    // Hentikan polling lama jika ada
                    if (realtimePollingInterval) {
                        clearInterval(realtimePollingInterval);
                        console.log('Cleared previous polling interval');
                    }

                    realtimePollingInterval = setInterval(async () => {
                        const plotlyChart = document.getElementById('plotlyChart');
                        const selectedTags = @this.get('selectedTags');
                        const interval = @this.get('interval');
                        const realtimeToggle = document.getElementById('realtime-toggle');

                        console.log('Polling check:', {
                            hasChart: !!plotlyChart,
                            selectedTags: selectedTags,
                            interval: interval,
                            toggleChecked: realtimeToggle?.checked
                        });

                        if (!plotlyChart || !selectedTags || selectedTags.length === 0 || !realtimeToggle ||
                            !realtimeToggle.checked) {
                            console.log('Polling skipped - conditions not met');
                            return;
                        }

                        try {
                            const params = new URLSearchParams({
                                interval: interval
                            });
                            selectedTags.forEach(tag => params.append('tags[]', tag));

                            // Tambahkan parameter unik (_=timestamp) untuk mencegah caching
                            const cacheBuster = `&_=${new Date().getTime()}`;
                            const url = `/api/latest-data?${params.toString()}${cacheBuster}`;
                            console.log('Fetching from:', url);

                            const response = await fetch(url, {
                                cache: 'no-store', // Tambahkan opsi ini untuk lebih eksplisit
                                headers: {
                                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                                    'Pragma': 'no-cache',
                                    'Expires': '0'
                                }
                            });

                            console.log('API Response status:', response.status);

                            if (response.status === 204) { // No new data
                                console.log('No new data available');
                                return;
                            }
                            if (!response.ok) {
                                throw new Error(`Network response was not ok: ${response.status}`);
                            }

                            const data = await response.json();
                            console.log('API Response data:', data);

                            // PANGGIL FUNGSI LANGSUNG - TIDAK LAGI MENGGUNAKAN EVENT BUS
                            handleChartUpdate(data);

                        } catch (error) {
                            console.error("Realtime poll failed:", error);
                        }
                    }, 5000); // Poll setiap 5 detik

                    console.log('Realtime polling started successfully');
                }

                // Panggil fungsi ini saat halaman dimuat
                console.log('Initializing realtime polling...');
                startRealtimePolling();

                // Pastikan polling dimulai/dihentikan saat toggle diubah
                document.getElementById('realtime-toggle').addEventListener('change', (event) => {
                    console.log('Realtime toggle changed:', event.target.checked);
                    if (event.target.checked) {
                        startRealtimePolling();
                    } else {
                        if (realtimePollingInterval) {
                            clearInterval(realtimePollingInterval);
                            realtimePollingInterval = null;
                            console.log('Realtime polling stopped');
                        }
                    }
                });

                // Dan panggil juga saat filter berubah, karena tag dan interval bisa berubah
                document.addEventListener('chart-data-updated', () => {
                    console.log('Chart data updated, restarting polling...');
                    startRealtimePolling();
                });



                window.Livewire.dispatch('loadChartData');
            });
        </script>
    @endscript
</div>
