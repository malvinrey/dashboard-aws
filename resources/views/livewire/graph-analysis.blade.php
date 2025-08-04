<div>
    {{-- Indikator loading real-time yang tidak mengganggu dari wire:poll --}}
    <div title="{{ $realtimeEnabled ? 'Real-time updates active' : 'Real-time updates disabled' }}"
        class="realtime-status-dot {{ $realtimeEnabled ? 'connected' : 'disconnected' }}">
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
            <div class="interval-buttons" id="interval-buttons">
                <button class="{{ $interval === 'hour' ? 'active' : '' }}" data-interval="hour">
                    Hour
                </button>
                <button class="{{ $interval === 'day' ? 'active' : '' }}" data-interval="day">
                    Day
                </button>
                <button class="{{ $interval === 'minute' ? 'active' : '' }}" data-interval="minute">
                    Minute
                </button>
                <button class="{{ $interval === 'second' ? 'active' : '' }}" data-interval="second">
                    Second
                </button>
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
            <button
                onclick="
                // 1. Ambil nilai interval yang aktif dari atribut data-interval
                const activeButton = document.querySelector('#interval-buttons button.active');
                if (!activeButton) {
                    alert('Please select an interval first');
                    return;
                }
                const selectedInterval = activeButton.dataset.interval;

                // 2. Ambil nilai metrik yang dipilih dari Select2
                const selectedTags = $('#metrics-select2').val();
                if (!selectedTags || selectedTags.length === 0) {
                    alert('Please select at least one metric');
                    return;
                }

                // 3. Validasi tanggal
                const startDate = document.getElementById('start-date-livewire').value;
                const endDate = document.getElementById('end-date-livewire').value;
                if (!startDate || !endDate) {
                    alert('Please select both start and end dates');
                    return;
                }

                // Validasi bahwa end date tidak lebih awal dari start date
                if (new Date(endDate) < new Date(startDate)) {
                    alert('End date cannot be earlier than start date');
                    return;
                }

                // 4. Set loading state pada tombol
                const loadButton = event.target;
                const originalText = loadButton.textContent;
                loadButton.textContent = 'Loading...';
                loadButton.disabled = true;
                loadButton.style.opacity = '0.7';

                // 5. Set semua properti di Livewire terlebih dahulu
                @this.set('interval', selectedInterval);
                @this.set('selectedTags', selectedTags);

                // TAMBAHKAN BARIS INI
                if (typeof window.lastSuccessfulPollTimestamp !== 'undefined') {
                    window.lastSuccessfulPollTimestamp = Date.now();
                }

                // 6. Setelah semua state di-set, panggil method utama untuk memuat chart
                @this.call('setHistoricalModeAndLoad').then(() => {
                    // Reset tombol setelah selesai
                    loadButton.textContent = originalText;
                    loadButton.disabled = false;
                    loadButton.style.opacity = '1';


                }).catch(() => {
                    // Reset tombol jika terjadi error
                    loadButton.textContent = originalText;
                    loadButton.disabled = false;
                    loadButton.style.opacity = '1';

                    // Tambahkan efek error pada tombol
                    loadButton.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        loadButton.style.animation = '';
                    }, 500);
                });
            "
                class="btn-primary">Load Historical Data</button>
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
            // TAMBAHKAN DUA BARIS INI - BUAT GLOBAL
            window.lastSuccessfulPollTimestamp = Date.now();
            window.connectionCheckInterval = null;

            document.addEventListener('livewire:navigated', () => {

                // Optimistic Update untuk Interval Buttons (tanpa komunikasi ke server)
                let isProcessingInterval = false;
                const intervalButtons = document.getElementById('interval-buttons');
                if (intervalButtons) {
                    intervalButtons.addEventListener('click', (e) => {
                        if (e.target.tagName === 'BUTTON' && e.target.dataset.interval) {
                            const clickedButton = e.target;
                            const intervalValue = clickedButton.dataset.interval;

                            // Cek apakah tombol sudah aktif atau sedang diproses
                            if (clickedButton.classList.contains('active') || isProcessingInterval) {
                                return; // Jangan lakukan apa-apa jika sudah aktif atau sedang diproses
                            }

                            isProcessingInterval = true;

                            // Optimistic update: langsung ubah tampilan (tanpa komunikasi ke server)
                            const allButtons = intervalButtons.querySelectorAll('button');
                            allButtons.forEach(btn => {
                                btn.classList.remove('active');
                            });
                            clickedButton.classList.add('active');



                            // Reset flag setelah delay untuk mencegah multiple clicks
                            setTimeout(() => {
                                isProcessingInterval = false;
                            }, 300);
                        }
                    });
                }

                // Inisialisasi Select2 pada elemen dengan ID #metrics-select2
                $('#metrics-select2').select2({
                    // Ukuran static untuk kontainer select
                    width: '300px',
                    dropdownAutoWidth: false,
                    dropdownParent: $('body')
                });

                // Pasang event listener 'change' dari Select2
                $('#metrics-select2').on('change', function(e) {
                    // Ambil semua nilai yang dipilih
                    var selectedValues = $(this).val();

                    // Baris @this.set() sudah dihapus. Biarkan kosong.
                    // Data akan dikirim ke server hanya saat tombol "Load Historical Data" ditekan

                    // Tambahkan efek visual untuk menunjukkan bahwa metrik telah dipilih
                    if (selectedValues && selectedValues.length > 0) {
                        $(this).addClass('has-selection');
                    } else {
                        $(this).removeClass('has-selection');
                    }
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
                        // KUNCI PERBAIKAN:
                        // Ubah nama metrik mentah (misal: "par_sensor") menjadi format yang ada di legenda grafik (misal: "Par sensor")
                        // Ini untuk mencocokkan logika `ucfirst(str_replace('_', ' ', $tag))` di PHP.
                        const formattedMetricName = metricName.charAt(0).toUpperCase() + metricName.slice(1)
                            .replace(/_/g, ' ');

                        // Lakukan pencarian menggunakan nama yang sudah diformat
                        const traceIndex = plotlyChart.data.findIndex(trace => trace.name ===
                            formattedMetricName);

                        if (traceIndex !== -1) {
                            const currentTrace = plotlyChart.data[traceIndex];
                            if (!currentTrace.x || currentTrace.x.length === 0) {
                                console.log(`Trace ${formattedMetricName} is empty, skipping`);
                                return; // Lewati jika trace kosong
                            }

                            const lastIndex = currentTrace.x.length - 1;
                            const lastValue = currentTrace.y[lastIndex];

                            if (lastValue === null) {
                                // Jika titik terakhir adalah 'null' (ada jeda), ganti dengan data baru
                                currentTrace.x[lastIndex] = newPointTimestamp;
                                currentTrace.y[lastIndex] = newValue;
                                needsRedraw = true;
                                console.log(`Resumed line for ${formattedMetricName} after gap.`);
                            } else {
                                const lastChartTimestamp = new Date(currentTrace.x[lastIndex]);

                                if (newPointTimestamp.getTime() === lastChartTimestamp.getTime()) {
                                    // Update titik yang sudah ada
                                    currentTrace.y[lastIndex] = newValue;
                                    needsRedraw = true;
                                    console.log(`Updated existing point for ${formattedMetricName}`);
                                } else if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
                                    // Tambahkan titik baru
                                    Plotly.extendTraces('plotlyChart', {
                                        x: [
                                            [newPointTimestamp]
                                        ],
                                        y: [
                                            [newValue]
                                        ]
                                    }, [traceIndex]);
                                    console.log(`Added new point for ${formattedMetricName}`);
                                }
                            }
                        } else {
                            // Log ini sekarang akan menggunakan nama yang sudah diformat agar lebih jelas
                            console.log(`Trace not found for metric: ${formattedMetricName}`);
                        }
                    });

                    if (needsRedraw) {
                        Plotly.redraw('plotlyChart');
                        console.log('Chart redrawn');
                    }
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

                        const statusDot = document.querySelector('.realtime-status-dot');
                        try {
                            statusDot?.classList.add(
                                'loading'); // <-- Tambahkan kelas .loading SEBELUM fetch

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
                                return; // Jangan lakukan apa-apa, biarkan finally berjalan
                            }
                            if (!response.ok) {
                                throw new Error(`Network response was not ok: ${response.status}`);
                            }

                            const data = await response.json();
                            console.log('API Response data:', data);

                            // TAMBAHKAN BARIS INI
                            window.lastSuccessfulPollTimestamp = Date.now(); // Catat waktu poll berhasil

                            // PANGGIL FUNGSI LANGSUNG - TIDAK LAGI MENGGUNAKAN EVENT BUS
                            handleChartUpdate(data);

                        } catch (error) {
                            console.error("Realtime poll failed:", error);
                        } finally {
                            statusDot?.classList.remove(
                                'loading'
                            ); // <-- Hapus kelas .loading SETELAH fetch selesai (baik sukses maupun gagal)
                        }
                    }, 5000); // Poll setiap 5 detik

                    console.log('Realtime polling started successfully');
                }

                // Tambahkan fungsi baru ini
                function startConnectionChecker() {
                    if (window.connectionCheckInterval) {
                        clearInterval(window.connectionCheckInterval);
                    }

                    // Jalankan pengecekan setiap 2 detik
                    window.connectionCheckInterval = setInterval(() => {
                        const realtimeToggle = document.getElementById('realtime-toggle');
                        const statusDot = document.querySelector('.realtime-status-dot');

                        // Hanya cek jika toggle real-time aktif
                        if (!realtimeToggle || !realtimeToggle.checked || !statusDot) {
                            statusDot?.classList.remove(
                                'stale'); // Pastikan class 'stale' bersih jika RT non-aktif
                            return;
                        }

                        const secondsSinceLastPoll = (Date.now() - window.lastSuccessfulPollTimestamp) / 1000;
                        const STALE_THRESHOLD_SECONDS = 15; // Anggap koneksi putus setelah 15 detik tanpa data

                        if (secondsSinceLastPoll > STALE_THRESHOLD_SECONDS) {
                            statusDot.classList.add('stale');
                        } else {
                            statusDot.classList.remove('stale');
                        }
                    }, 2000);
                }

                // Panggil fungsi ini saat halaman dimuat
                console.log('Initializing realtime polling...');
                startRealtimePolling();

                // Pastikan polling dimulai/dihentikan saat toggle diubah
                document.getElementById('realtime-toggle').addEventListener('change', (event) => {
                    console.log('Realtime toggle changed:', event.target.checked);
                    if (event.target.checked) {
                        window.lastSuccessfulPollTimestamp = Date.now(); // TAMBAHKAN INI
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

                // Event listener untuk perubahan status real-time
                document.addEventListener('livewire:update', () => {
                    const realtimeStatus = document.querySelector('.realtime-status');
                    const statusDot = document.querySelector('.status-dot');
                    const realtimeEnabled = @this.get('realtimeEnabled');

                    if (realtimeStatus && statusDot) {
                        // Update tooltip
                        realtimeStatus.title = realtimeEnabled ? 'Real-time updates active' :
                            'Real-time updates disabled';

                        // Update status dot class
                        statusDot.className = 'status-dot ' + (realtimeEnabled ? 'connected' : 'disconnected');

                        // Tambahkan efek visual untuk perubahan status
                        statusDot.classList.add('status-changed');
                        setTimeout(() => {
                            statusDot.classList.remove('status-changed');
                        }, 500);
                    }
                });

                // Tambahkan feedback visual untuk input tanggal
                const startDateInput = document.getElementById('start-date-livewire');
                const endDateInput = document.getElementById('end-date-livewire');

                if (startDateInput) {
                    startDateInput.addEventListener('change', function() {
                        this.style.borderColor = 'var(--primary-color)';
                    });
                }

                if (endDateInput) {
                    endDateInput.addEventListener('change', function() {
                        this.style.borderColor = 'var(--primary-color)';
                    });
                }

                // Event listener untuk memastikan state interval tetap konsisten setelah Livewire update
                // (hanya diperlukan jika ada update dari server)
                document.addEventListener('livewire:update', () => {
                    isProcessingInterval = false;
                });

                // Event listener untuk menangani error state
                document.addEventListener('livewire:error', () => {
                    isProcessingInterval = false;
                    // Tambahkan efek visual untuk error feedback
                    const intervalButtons = document.getElementById('interval-buttons');
                    if (intervalButtons) {
                        intervalButtons.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => {
                            intervalButtons.style.animation = '';
                        }, 500);
                    }
                });

                // Panggil fungsi ini di akhir blok 'livewire:navigated'
                startConnectionChecker();

                window.Livewire.dispatch('loadChartData');
            });
        </script>
    @endscript
</div>
