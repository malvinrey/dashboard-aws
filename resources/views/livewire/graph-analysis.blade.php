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
                <label>Select Metrics:</label>
                <div x-data="{ open: false }" @click.outside="open = false" class="relative filter-item-width">
                    <div>
                        <button type="button" @click="open = !open" class="select-metrics-button" id="options-menu"
                            aria-haspopup="true" :aria-expanded="open.toString()">
                            Select Metrics ({{ count($selectedTags) }} selected)
                            <svg class="dropdown-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95" class="dropdown-panel" role="menu"
                        aria-orientation="vertical" aria-labelledby="options-menu">
                        <div class="py-1">
                            <div class="metrics-actions px-4 py-2 flex-container gap-2">
                                <button type="button" wire:click="selectAllMetrics" @click="open = false"
                                    class="btn-secondary flex-grow">Select All</button>
                                <button type="button" wire:click="clearAllMetrics" @click="open = false"
                                    class="btn-secondary flex-grow">Clear All</button>
                            </div>
                            <div class="metrics-checkbox-container max-h-60 overflow-y-auto px-4 py-2">
                                @foreach ($allTags as $tag)
                                    <label class="metric-checkbox block py-1">
                                        <input type="checkbox" wire:model.live.debounce.500ms="selectedTags"
                                            value="{{ $tag }}" class="metric-checkbox-input mr-2">
                                        <span class="metric-checkbox-label">{{ ucfirst($tag) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
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
        </div>

        {{-- KUNCI PERUBAHAN: Tambahkan blok tombol "Load More" di sini --}}
        @if ($interval === 'second')
            <div style="text-align: center; margin-top: 16px;">
                <button wire:click="loadMoreSeconds" wire:loading.attr="disabled" class="btn-secondary">
                    <div wire:loading wire:target="loadMoreSeconds" class="spinner"
                        style="width: 16px; height: 16px; border-width: 2px; margin-right: 8px;"></div>
                    <span wire:loading.remove wire:target="loadMoreSeconds">Load 30 Minutes Earlier</span>
                    <span wire:loading wire:target="loadMoreSeconds">Loading...</span>
                </button>
            </div>
        @endif
    </div>

    <style>
        /* CSS Umum untuk Filter Group */
        .filters {
            display: flex;
            /* Membuat filter berjejer horizontal */
            flex-wrap: wrap;
            /* Memungkinkan wrap ke baris baru jika ruang tidak cukup */
            gap: 16px;
            /* Jarak antar grup filter */
            margin-bottom: 20px;
            align-items: flex-end;
            /* Menyelaraskan item di bagian bawah */
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            /* Label di atas input/button */
            gap: 8px;
            /* Jarak antara label dan input */
        }

        .filter-group label {
            font-weight: bold;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-group input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 150px;
            /* Lebar minimum untuk input tanggal */
        }

        /* --- Style untuk Select Metrics Dropdown (Baru) --- */
        .select-metrics-button {
            display: inline-flex;
            justify-content: space-between;
            /* Untuk meletakkan teks dan ikon di ujung */
            align-items: center;
            width: 100%;
            /* Agar selebar parent (filter-item-width) */
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
            color: #333;
            font-size: 1rem;
            cursor: pointer;
            min-width: 200px;
            /* Sesuaikan dengan lebar yang diinginkan */
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease-in-out;
        }

        .select-metrics-button:hover {
            background-color: #f5f5f5;
            border-color: #bbb;
        }

        .select-metrics-button:focus {
            outline: none;
            border-color: #007bff;
            /* Warna fokus */
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .dropdown-arrow {
            width: 20px;
            height: 20px;
            margin-left: 8px;
            color: #666;
        }

        .dropdown-panel {
            position: absolute;
            left: 0;
            margin-top: 8px;
            width: 100%;
            /* Selebar tombol */
            max-width: 300px;
            /* Batas lebar agar tidak terlalu besar */
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            z-index: 10;
        }

        .metrics-actions {
            display: flex;
            /* Untuk Select All / Clear All */
            gap: 8px;
            /* Jarak antar tombol */
            margin-bottom: 8px;
        }

        .metrics-actions .btn-secondary {
            flex-grow: 1;
            /* Agar tombol mengisi ruang yang tersedia */
            padding: 6px 10px;
            /* Padding sedikit lebih kecil */
            font-size: 0.85rem;
        }

        .metrics-checkbox-container {
            max-height: 200px;
            /* Tinggi maksimal agar bisa di-scroll */
            overflow-y: auto;
            border-top: 1px solid #eee;
            /* Garis pemisah dari tombol aksi */
            padding-top: 8px;
        }

        .metric-checkbox {
            display: flex;
            /* Untuk menyelaraskan checkbox dan label */
            align-items: center;
            padding: 4px 0;
            cursor: pointer;
        }

        .metric-checkbox-input {
            margin-right: 8px;
        }

        /* --- Style Umum untuk Tombol --- */
        .btn-primary {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
            border-color: #bbb;
        }

        /* --- Style untuk Interval Buttons --- */
        .interval-buttons {
            display: flex;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
            /* Untuk membuat tombol terlihat menyatu */
        }

        .interval-buttons button {
            padding: 8px 15px;
            border: none;
            background-color: #f9f9f9;
            color: #555;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .interval-buttons button:hover {
            background-color: #e9e9e9;
        }

        .interval-buttons button.active {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        .interval-buttons button:not(:last-child) {
            border-right: 1px solid #ccc;
            /* Pemisah antar tombol */
        }

        /* --- Utilitas Umum --- */
        .relative {
            position: relative;
        }

        .flex-container {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .items-center {
            align-items: center;
        }

        .flex-grow {
            flex-grow: 1;
        }

        .gap-2 {
            gap: 8px;
        }

        /* Sesuaikan sesuai kebutuhan */
        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .block {
            display: block;
        }

        .mr-2 {
            margin-right: 0.5rem;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .z-10 {
            z-index: 10;
        }

        .py-1 {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        /* Kelas untuk lebar item filter (sesuaikan jika perlu) */
        .filter-item-width {
            width: 220px;
            /* Contoh lebar yang cocok dengan input tanggal */
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

    @script
        <script>
            document.addEventListener('livewire:navigated', () => {
                const chartContainer = document.getElementById('plotlyChart');
                const warningBox = document.getElementById('chart-warning');
                const warningMessage = document.getElementById('warning-message');

                if (!chartContainer || !warningBox) return;

                // ===================================================================
                // KUNCI PERBAIKAN: Manajemen State Terpusat
                // ===================================================================
                let globalState = {
                    lastKnownTimestamp: null,
                    isLazyLoading: false,
                };

                /**
                 * Fungsi ini sekarang menjadi SATU-SATUNYA cara untuk mengupdate
                 * timestamp terakhir yang diketahui. Ini memastikan konsistensi.
                 * @returns {string|null} Timestamp UTC dalam format string.
                 */
                const updateLastKnownTimestamp = () => {
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (plotlyChart && plotlyChart.data && plotlyChart.data.length > 0) {
                        const trace = plotlyChart.data[0];
                        const lastIndex = trace.x.length - 1;
                        if (lastIndex >= 0) {
                            const lastDate = new Date(trace.x[lastIndex]);
                            // Konversi ke string UTC 'YYYY-MM-DD HH:MM:SS'
                            globalState.lastKnownTimestamp = lastDate.toISOString().slice(0, 19).replace('T', ' ');
                            return globalState.lastKnownTimestamp;
                        }
                    }
                    globalState.lastKnownTimestamp = null;
                    return null;
                };

                // Listener untuk deteksi fokus tab
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible' && globalState.lastKnownTimestamp) {
                        console.log('Tab is visible. Catching up since:', globalState.lastKnownTimestamp);
                        window.Livewire.dispatch('catchUpMissedData', {
                            lastKnownTimestamp: globalState.lastKnownTimestamp
                        });
                    }
                });

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
                        // Jika ADA data, gambar chart seperti biasa
                        const plotlyData = chartData.data.map(trace => ({
                            ...trace,
                            x: trace.x.map(dateStr => new Date(dateStr)),
                            y: trace.y,
                        }));
                        createChart(plotlyData, chartData.layout);
                        updateLastKnownTimestamp(); // Perbarui timestamp setelah chart dibuat
                    } else {
                        // KUNCI PERBAIKAN: Jika TIDAK ADA data, bersihkan chart
                        // Plotly.purge akan menghapus chart, atau Anda bisa menggantinya dengan chart kosong.
                        // Menggunakan newPlot dengan data kosong lebih aman dan memberikan pesan.
                        Plotly.newPlot('plotlyChart', [], {
                            title: 'Select metrics and load data to begin'
                        }, {
                            responsive: true,
                            displaylogo: false
                        });
                        globalState.lastKnownTimestamp = null; // Reset timestamp juga
                    }
                });

                // Listener untuk data historis (lazy loading)
                document.addEventListener('historical-data-prepended', event => {
                    // ... (logika ini bisa dibiarkan sama atau disesuaikan jika perlu)
                });

                // Listener untuk lazy loading data historis (second interval)
                document.addEventListener('historical-data-prepended-second', event => {
                    const chartData = event.detail.data;
                    const plotlyChart = document.getElementById('plotlyChart');

                    if (chartData && chartData.data && chartData.data.length > 0 && plotlyChart && plotlyChart
                        .data) {
                        console.log('Lazy loading data received:', {
                            traces: chartData.data.length,
                            sampleData: chartData.data[0] ? {
                                x: chartData.data[0].x.slice(0, 3),
                                y: chartData.data[0].y.slice(0, 3)
                            } : null
                        });

                        // Loop melalui setiap trace data baru yang diterima dari backend
                        chartData.data.forEach((newTrace, traceIndex) => {
                            // Pastikan ada trace yang sesuai di grafik yang ada
                            if (traceIndex < plotlyChart.data.length) {
                                const existingTrace = plotlyChart.data[traceIndex];

                                // Siapkan data X (waktu) dan Y (nilai) yang akan ditambahkan
                                const newDates = newTrace.x.map(dateStr => new Date(dateStr));
                                const newValues = newTrace.y;

                                if (newDates.length > 0) {
                                    console.log(
                                        `Prepending ${newDates.length} points to trace ${traceIndex}`, {
                                            newDates: newDates.slice(0, 3),
                                            existingPoints: existingTrace.x.length
                                        });

                                    // Gunakan fungsi Plotly.prependTraces dengan promise untuk memastikan operasi selesai
                                    Plotly.prependTraces('plotlyChart', {
                                        x: [newDates],
                                        y: [newValues]
                                    }, [traceIndex]).then(() => {
                                        console.log(
                                            `Successfully prepended data to trace ${traceIndex}`
                                        );

                                        // Hapus titik data lama jika totalnya melebihi batas untuk menjaga performa
                                        const maxPoints = 2000;
                                        if (plotlyChart.data[traceIndex].x.length > maxPoints) {
                                            const newX = plotlyChart.data[traceIndex].x.slice(-
                                                maxPoints);
                                            const newY = plotlyChart.data[traceIndex].y.slice(-
                                                maxPoints);

                                            Plotly.restyle('plotlyChart', {
                                                x: [newX],
                                                y: [newY]
                                            }, [traceIndex]);
                                        }
                                    }).catch(error => {
                                        console.error('Error prepending traces:', error);
                                    });
                                }
                            }
                        });
                    }

                    // Sembunyikan indikator loading setelah selesai
                    const loadingIndicator = document.getElementById('lazy-loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.style.display = 'none';
                    }
                });

                // Listener untuk menampilkan peringatan, tidak berubah
                document.addEventListener('show-warning', event => {
                    warningMessage.textContent = event.detail.message;
                    warningBox.style.display = 'block';
                });

                // ===================================================================
                // KUNCI PERBAIKAN: LOGIKA BARU UNTUK REAL-TIME UPDATE (MULTIPLE TRACES)
                // ===================================================================
                document.addEventListener('update-last-point', event => {
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (!plotlyChart || !plotlyChart.data || plotlyChart.data.length === 0) return;

                    const newData = event.detail.data;
                    if (!newData || !newData.metrics || !newData.timestamp) return;

                    const newPointTimestamp = new Date(newData.timestamp); // Timestamp grup (misal: 11:00:00)

                    // Update setiap trace yang sesuai dengan metrics yang dipilih
                    Object.entries(newData.metrics).forEach(([metricName, newValue], traceIndex) => {
                        if (traceIndex < plotlyChart.data.length) {
                            const currentTrace = plotlyChart.data[traceIndex];
                            const lastIndex = currentTrace.x.length - 1;
                            const lastChartTimestamp = new Date(currentTrace.x[lastIndex]);

                            console.log(`Smart real-time update for ${metricName}:`, {
                                newTimeGroup: newData.timestamp,
                                newPointTimestamp: newPointTimestamp,
                                lastChartTimestamp: lastChartTimestamp,
                                newValue: newValue,
                                isSameInterval: newPointTimestamp.getTime() ===
                                    lastChartTimestamp.getTime()
                            });

                            // Cek apakah timestamp baru sama dengan timestamp titik terakhir di grafik
                            if (newPointTimestamp.getTime() === lastChartTimestamp.getTime()) {
                                // --- KASUS 1: UPDATE NILAI TITIK TERAKHIR ---
                                // Interval waktu masih sama, jadi kita hanya perbarui nilai Y
                                currentTrace.y[lastIndex] = newValue;

                            } else if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
                                // --- KASUS 2: TAMBAHKAN TITIK BARU ---
                                // Interval waktu telah berganti (misal: dari jam 10 ke jam 11)
                                Plotly.extendTraces('plotlyChart', {
                                    x: [
                                        [newPointTimestamp]
                                    ],
                                    y: [
                                        [newValue]
                                    ]
                                }, [traceIndex]); // Update trace sesuai index
                            }
                        }
                    });

                    // Gambar ulang grafik dengan data yang sudah diupdate
                    Plotly.redraw('plotlyChart');

                    // Setelah diupdate, panggil fungsi terpusat kita
                    updateLastKnownTimestamp();
                });

                // ===================================================================
                // KUNCI PERBAIKAN: LISTENER BARU UNTUK DATA YANG TERLEWAT
                // ===================================================================
                document.addEventListener('append-missed-points', event => {
                    const missedData = event.detail.data;
                    const plotlyChart = document.getElementById('plotlyChart');
                    if (!plotlyChart || !plotlyChart.data || !missedData) return;

                    console.log('Appending missed points:', missedData);

                    // Loop melalui setiap trace yang ada di grafik
                    plotlyChart.data.forEach((trace, traceIndex) => {
                        const metricName = trace.name;

                        // Cek apakah ada data yang terlewat untuk trace ini
                        if (missedData[metricName] && missedData[metricName].length > 0) {
                            const pointsToAdd = missedData[metricName];

                            // Siapkan data baru
                            const newX = pointsToAdd.map(p => new Date(p.timestamp));
                            const newY = pointsToAdd.map(p => p.value);

                            // Buat satu titik "jeda" dengan nilai null.
                            // Timestamp-nya kita set 1 detik sebelum data baru pertama untuk memastikan urutan.
                            const firstNewDate = newX[0];
                            const breakDate = new Date(firstNewDate.getTime() - 1000);

                            // Gabungkan titik jeda dengan data baru
                            const finalX = [breakDate, ...newX];
                            const finalY = [null, ...newY];

                            // Gunakan extendTraces untuk menambahkan jeda dan data baru dalam satu operasi
                            Plotly.extendTraces('plotlyChart', {
                                x: [finalX],
                                y: [finalY]
                            }, [traceIndex]);
                        }
                    });

                    // Panggil fungsi terpusat setelah menambahkan data yang terlewat
                    updateLastKnownTimestamp();
                });

                // Memuat data awal saat halaman dibuka
                window.Livewire.dispatch('loadChartData');
            });
        </script>
    @endscript
</div>
