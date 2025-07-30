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
                const chartContainer = document.getElementById('plotlyChart');
                const warningBox = document.getElementById('chart-warning');
                const warningMessage = document.getElementById('warning-message');

                if (!chartContainer || !warningBox) return;

                // Fungsi untuk membuat chart baru
                const createChart = (plotlyData, layout) => {
                    const finalLayout = {
                        title: 'Historical Data Analysis',
                        xaxis: {
                            title: 'Timestamp',
                            type: 'date'
                        },
                        yaxis: {
                            title: 'Value',
                            autorange: true
                        },
                        margin: {
                            l: 60,
                            r: 30,
                            b: 50,
                            t: 50
                        },
                        hovermode: 'x unified',
                        showlegend: true,
                        ...layout
                    };
                    Plotly.newPlot('plotlyChart', plotlyData, finalLayout, {
                        responsive: true,
                        displaylogo: false
                    });
                };

                // Listener untuk data historis awal
                document.addEventListener('chart-data-updated', event => {
                    warningBox.style.display = 'none';
                    const chartData = event.detail.chartData;
                    if (chartData && chartData.data && chartData.data.length > 0) {
                        // Konversi string tanggal menjadi objek Date untuk Plotly
                        const plotlyData = chartData.data.map(trace => ({
                            ...trace,
                            x: trace.x.map(dateStr => new Date(dateStr)),
                            y: trace.y,
                        }));
                        createChart(plotlyData, chartData.layout);
                    }
                });

                // Listener untuk data historis (lazy loading), tidak berubah
                document.addEventListener('historical-data-prepended', event => {
                    // ... (logika ini bisa dibiarkan sama atau disesuaikan jika perlu)
                });

                // Listener untuk menampilkan peringatan, tidak berubah
                document.addEventListener('show-warning', event => {
                    warningMessage.textContent = event.detail.message;
                    warningBox.style.display = 'block';
                });

                // ===================================================================
                // KUNCI PERBAIKAN: LOGIKA BARU UNTUK REAL-TIME UPDATE
                // ===================================================================
                document.addEventListener('update-last-point', event => {
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (!plotlyChart || !plotlyChart.data || plotlyChart.data.length === 0) return;

                    const newData = event.detail.data;
                    if (!newData || !newData.metrics || !newData.timestamp) return;

                    const newPointValue = Object.values(newData.metrics)[0];
                    const newPointTimestamp = new Date(newData.timestamp); // Timestamp grup (misal: 11:00:00)

                    // Ambil data trace yang ada di grafik
                    const currentTrace = plotlyChart.data[0];
                    const lastIndex = currentTrace.x.length - 1;
                    const lastChartTimestamp = new Date(currentTrace.x[lastIndex]);

                    console.log('Smart real-time update:', {
                        newTimeGroup: newData.timestamp,
                        newPointTimestamp: newPointTimestamp,
                        lastChartTimestamp: lastChartTimestamp,
                        newValue: newPointValue,
                        isSameInterval: newPointTimestamp.getTime() === lastChartTimestamp.getTime()
                    });

                    // Cek apakah timestamp baru sama dengan timestamp titik terakhir di grafik
                    if (newPointTimestamp.getTime() === lastChartTimestamp.getTime()) {
                        // --- KASUS 1: UPDATE NILAI TITIK TERAKHIR ---
                        // Interval waktu masih sama, jadi kita hanya perbarui nilai Y
                        currentTrace.y[lastIndex] = newPointValue;

                        // Gambar ulang grafik dengan data yang sudah diupdate
                        Plotly.redraw('plotlyChart');

                    } else if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
                        // --- KASUS 2: TAMBAHKAN TITIK BARU ---
                        // Interval waktu telah berganti (misal: dari jam 10 ke jam 11)
                        Plotly.extendTraces('plotlyChart', {
                            x: [
                                [newPointTimestamp]
                            ],
                            y: [
                                [newPointValue]
                            ]
                        }, [0]); // [0] berarti update trace pertama
                    }
                });

                // Memuat data awal saat halaman dibuka
                window.Livewire.dispatch('loadChartData');
            });
        </script>
    @endscript
</div>
