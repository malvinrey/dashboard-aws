@php
    // Logika untuk menentukan tanggal default
    $endDate = now()->toDateTimeString();
    $startDate = now()->subDay()->toDateTimeString();
@endphp

<div data-selected-tags="{{ json_encode($selectedTags) }}" data-interval="{{ $interval }}"
    data-realtime-enabled="{{ $realtimeEnabled ? 'true' : 'false' }}" {{-- Alpine.js component untuk mengelola state dan logic --}} {{-- SOLUSI DEFINITIF: Menggunakan Web Worker untuk koneksi SSE yang stabil --}}
    {{-- Koneksi SSE akan tetap aktif bahkan saat tab tidak terlihat --}} x-data="{
        // State variables untuk Web Worker
        sseWorker: null, // Instance Web Worker untuk SSE
        sseConnectionStatus: 'disconnected', // Status koneksi SSE
        lastSuccessfulPollTimestamp: Date.now(), // Timestamp polling terakhir
        connectionCheckInterval: null, // Interval untuk cek koneksi
        lastKnownTimestamp: null, // Timestamp data terakhir

        // Initialize component
        initComponent() {
            console.log('Initializing Alpine component...');

            // Tambahkan class loading ke body untuk mencegah scroll
            document.body.classList.add('loading');

            // Setup event listeners
            this.setupEventListeners();

            // Mulai proses loading komponen secara berurutan
            this.loadComponentsSequentially();
        },

        // Load komponen secara berurutan dengan delay untuk memastikan semua siap
        async loadComponentsSequentially() {
            try {
                console.log('🔄 Starting sequential component loading...');

                // Aktifkan fallback untuk memastikan overlay tidak stuck
                this.ensureOverlayHidden();

                // Step 1: Setup SSE connection
                await this.delay(500);
                this.startSseConnection();
                console.log('✅ SSE connection setup completed');

                // Step 2: Start connection checker
                await this.delay(300);
                this.startConnectionChecker();
                console.log('✅ Connection checker started');

                // Step 3: Wait for Select2 to be ready
                await this.waitForSelect2();
                console.log('✅ Select2 initialization completed');

                // Step 4: Wait for interval buttons to be ready
                await this.waitForIntervalButtons();
                console.log('✅ Interval buttons setup completed');

                // Step 5: Dispatch loadChartData event
                await this.delay(200);
                window.Livewire.dispatch('loadChartData');
                console.log('✅ Chart data loading initiated');

                // Step 6: Final delay untuk memastikan semua komponen benar-benar siap
                await this.delay(500);

                // Hide loading overlay
                this.hideMainLoadingOverlay();
                console.log('🎉 All components loaded successfully!');

            } catch (error) {
                console.error('❌ Error during component loading:', error);
                // Tetap hide overlay meskipun ada error
                this.hideMainLoadingOverlay();
            }
        },

        // Utility function untuk delay
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        // Wait for Select2 to be ready
        waitForSelect2() {
            return new Promise((resolve) => {
                const checkSelect2 = () => {
                    if (typeof $ !== 'undefined' && $('#metrics-select2').length > 0) {
                        resolve();
                    } else {
                        setTimeout(checkSelect2, 100);
                    }
                };
                checkSelect2();
            });
        },

        // Wait for interval buttons to be ready
        waitForIntervalButtons() {
            return new Promise((resolve) => {
                const checkButtons = () => {
                    const buttons = document.getElementById('interval-buttons');
                    if (buttons && buttons.querySelectorAll('button').length > 0) {
                        resolve();
                    } else {
                        setTimeout(checkButtons, 100);
                    }
                };
                checkButtons();
            });
        },

        // Hide main loading overlay
        hideMainLoadingOverlay() {
            const overlay = document.getElementById('main-loading-overlay');
            if (overlay) {
                overlay.classList.add('hidden');
                // Remove loading class from body
                document.body.classList.remove('loading');

                // Remove overlay completely after animation
                setTimeout(() => {
                    if (overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                }, 300);
            }
        },

        // Fallback untuk memastikan overlay loading selalu berfungsi
        ensureOverlayHidden() {
            // Force hide overlay setelah 10 detik sebagai fallback
            setTimeout(() => {
                if (document.getElementById('main-loading-overlay')) {
                    console.log('⚠️ Force hiding overlay after timeout');
                    this.hideMainLoadingOverlay();
                }
            }, 10000);
        },

        // Setup all event listeners
        setupEventListeners() {
            // Chart data updated event
            document.addEventListener('chart-data-updated', (event) => {
                console.log('chart-data-updated event received:', event.detail);
                this.handleChartDataUpdated(event.detail);
            });

            // Historical data prepended event
            document.addEventListener('historical-data-prepended-second', (event) => {
                this.handleHistoricalDataPrepended(event.detail);
            });

            // Show warning event
            document.addEventListener('show-warning', (event) => {
                this.showWarning(event.detail.message);
            });

            // Update last point event
            document.addEventListener('update-last-point', (event) => {
                console.log('Update last point event received:', event.detail);
                this.handleChartUpdate(event.detail.data);
            });

            // Realtime toggle change
            const realtimeToggle = document.getElementById('realtime-toggle');
            if (realtimeToggle) {
                realtimeToggle.addEventListener('change', (event) => {
                    console.log('Realtime toggle changed:', event.target.checked);
                    if (event.target.checked) {
                        this.lastSuccessfulPollTimestamp = Date.now();
                        this.startSseConnection();
                    } else {
                        if (this.sseWorker) {
                            this.sseWorker.terminate();
                            this.sseWorker = null;
                            console.log('SSE Worker stopped');
                        }
                    }
                });
            }

            // Chart data updated - restart SSE
            document.addEventListener('chart-data-updated', () => {
                console.log('Chart data updated, restarting SSE...');
                this.startSseConnection();
            });
        },

        // Handle chart data updated
        handleChartDataUpdated(chartData) {
            const warningBox = document.getElementById('chart-warning');
            if (warningBox) warningBox.style.display = 'none';

            if (chartData && chartData.data && chartData.data.length > 0) {
                console.log('Rendering chart with data:', chartData);
                const plotlyData = chartData.data.map(trace => ({
                    ...trace,
                    x: trace.x.map(dateStr => new Date(dateStr)),
                }));
                this.renderChart(plotlyData, chartData.layout);
                this.updateLastKnownTimestamp();
                console.log('Chart rendered successfully');
            } else {
                console.log('No chart data available, rendering empty chart');
                this.renderChart([], {
                    title: 'No data to display. Please check filters.'
                });
                this.updateLastKnownTimestamp();
            }
        },

        // Handle historical data prepended
        handleHistoricalDataPrepended(chartData) {
            const plotlyChart = document.getElementById('plotlyChart');
            if (chartData && chartData.data && chartData.data.length > 0 && plotlyChart && plotlyChart.data) {
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
        },

        // Show warning
        showWarning(message) {
            const warningBox = document.getElementById('chart-warning');
            const warningMessage = document.getElementById('warning-message');
            if (warningBox && warningMessage) {
                warningMessage.textContent = message;
                warningBox.style.display = 'block';
            }
        },

        // Render chart using Plotly
        renderChart(plotlyData, layout) {
            console.log('renderChart called with:', { plotlyData, layout });
            const chartContainer = document.getElementById('plotlyChart');
            if (!chartContainer) {
                console.error('Chart container not found!');
                return;
            }
            console.log('Chart container found, rendering with Plotly...');
            Plotly.react('plotlyChart', plotlyData, layout, {
                responsive: true,
                displaylogo: false
            });
            console.log('Plotly.react completed');
        },

        // Update last known timestamp
        updateLastKnownTimestamp() {
            const plotlyChart = document.getElementById('plotlyChart');
            if (plotlyChart && plotlyChart.data && plotlyChart.data.length > 0) {
                let maxTimestamp = 0;
                plotlyChart.data.forEach(trace => {
                    if (trace.x && trace.x.length > 0) {
                        const lastTimestampInTrace = new Date(trace.x[trace.x.length - 1]).getTime();
                        if (lastTimestampInTrace > maxTimestamp) {
                            maxTimestamp = lastTimestampInTrace;
                        }
                    }
                });
                if (maxTimestamp > 0) {
                    const lastDate = new Date(maxTimestamp);
                    this.lastKnownTimestamp = lastDate.toISOString().slice(0, 19).replace('T', ' ');
                } else {
                    this.lastKnownTimestamp = null;
                }
            } else {
                this.lastKnownTimestamp = null;
            }
        },

        // Start SSE connection using Web Worker
        startSseConnection() {
            console.log('Starting SSE connection using Web Worker...');

            // Hentikan worker lama jika ada untuk mencegah duplikasi
            if (this.sseWorker) {
                this.sseWorker.terminate();
                this.sseWorker = null;
            }

            const container = document.querySelector('[data-selected-tags]');
            const selectedTags = container ? JSON.parse(container.dataset.selectedTags || '[]') : [];
            const interval = container ? container.dataset.interval || 'hour' : 'hour';
            const realtimeToggle = document.getElementById('realtime-toggle');

            if (!selectedTags || selectedTags.length === 0 || !realtimeToggle || !realtimeToggle.checked) {
                console.log('SSE connection skipped - conditions not met');
                return;
            }

            const params = new URLSearchParams({ interval: interval });
            selectedTags.forEach(tag => params.append('tags[]', tag));
            const sseUrl = `/api/sse/stream?${params.toString()}`;

            try {
                // Buat instance worker baru dari file eksternal
                this.sseWorker = new Worker('/js/sse-worker.js');
                const statusDot = document.querySelector('.realtime-status-dot');

                // Dengarkan pesan yang dikirim KEMBALI DARI WORKER
                this.sseWorker.onmessage = (e) => {
                    console.log('SSE Worker message received:', e.data);
                    this.handleWorkerMessage(e.data, statusDot);
                };

                this.sseWorker.onerror = (error) => {
                    console.error('SSE Worker error:', error);
                    statusDot?.classList.add('stale');
                    this.sseConnectionStatus = 'error';
                };

                // Kirim URL SSE ke worker agar ia bisa memulai koneksi
                this.sseWorker.postMessage({
                    type: 'start',
                    url: sseUrl,
                    tags: selectedTags,
                    interval: interval
                });

                console.log('SSE Worker started successfully with URL:', sseUrl);

            } catch (error) {
                console.error('Failed to create SSE Worker:', error);
                const statusDot = document.querySelector('.realtime-status-dot');
                statusDot?.classList.add('stale');
                this.sseConnectionStatus = 'error';
            }
        },

        // Handle worker messages
        handleWorkerMessage(data, statusDot) {
            console.log('Processing worker message:', data.type, data);

            switch (data.type) {
                case 'status':
                    if (data.status === 'connected') {
                        this.sseConnectionStatus = 'connected';
                        statusDot?.classList.remove('stale');
                        statusDot?.classList.add('connected');
                        console.log('✅ SSE connection established via Worker');
                    } else if (data.status === 'stopped') {
                        this.sseConnectionStatus = 'stopped';
                        statusDot?.classList.remove('connected');
                        console.log('⏹️ SSE connection stopped via Worker');
                    }
                    break;

                case 'data':
                    window.lastSuccessfulPollTimestamp = Date.now();
                    console.log('📊 Real-time data received via Worker:', data.data);
                    this.handleChartUpdate(data.data);
                    break;

                case 'connected':
                    console.log('🔗 SSE connected event via Worker:', data.data);
                    break;

                case 'heartbeat':
                    console.log('💓 SSE heartbeat received via Worker');
                    window.lastSuccessfulPollTimestamp = Date.now();
                    break;

                case 'error':
                    console.error('❌ SSE error via Worker:', data.error);
                    statusDot?.classList.add('stale');
                    this.sseConnectionStatus = 'error';
                    break;

                default:
                    console.log('⚠️ Unknown worker message type:', data.type);
                    break;
            }
        },

        // Handle chart updates
        handleChartUpdate(newData) {
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
                const formattedMetricName = metricName.charAt(0).toUpperCase() + metricName.slice(1).replace(/_/g, ' ');
                const traceIndex = plotlyChart.data.findIndex(trace => trace.name === formattedMetricName);

                if (traceIndex !== -1) {
                    const currentTrace = plotlyChart.data[traceIndex];
                    if (!currentTrace.x || currentTrace.x.length === 0) {
                        console.log(`Trace ${formattedMetricName} is empty, skipping`);
                        return;
                    }

                    const lastIndex = currentTrace.x.length - 1;
                    const lastValue = currentTrace.y[lastIndex];

                    if (lastValue === null) {
                        currentTrace.x[lastIndex] = newPointTimestamp;
                        currentTrace.y[lastIndex] = newValue;
                        needsRedraw = true;
                        console.log(`Resumed line for ${formattedMetricName} after gap.`);
                    } else {
                        const lastChartTimestamp = new Date(currentTrace.x[lastIndex]);

                        if (newPointTimestamp.getTime() === lastChartTimestamp.getTime()) {
                            currentTrace.y[lastIndex] = newValue;
                            needsRedraw = true;
                            console.log(`Updated existing point for ${formattedMetricName}`);
                        } else if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
                            Plotly.extendTraces('plotlyChart', {
                                x: [
                                    [newPointTimestamp]
                                ],
                                y: [
                                    [newValue]
                                ]
                            }, [traceIndex], 60); // MAX_POINTS_PER_TRACE = 60
                            console.log(`Added new point for ${formattedMetricName}`);
                        }
                    }
                } else {
                    console.log(`Trace not found for metric: ${formattedMetricName}`);
                }
            });

            if (needsRedraw) {
                Plotly.redraw('plotlyChart');
                console.log('Chart redrawn');
            }
            this.updateLastKnownTimestamp();
        },

        // Start connection checker
        startConnectionChecker() {
            if (this.connectionCheckInterval) {
                clearInterval(this.connectionCheckInterval);
            }

            this.connectionCheckInterval = setInterval(() => {
                const realtimeToggle = document.getElementById('realtime-toggle');
                const statusDot = document.querySelector('.realtime-status-dot');

                if (!realtimeToggle || !realtimeToggle.checked || !statusDot) {
                    statusDot?.classList.remove('stale');
                    return;
                }

                const secondsSinceLastPoll = (Date.now() - this.lastSuccessfulPollTimestamp) / 1000;
                const STALE_THRESHOLD_SECONDS = 15;

                if (secondsSinceLastPoll > STALE_THRESHOLD_SECONDS) {
                    statusDot.classList.add('stale');
                } else {
                    statusDot.classList.remove('stale');
                }
            }, 2000);
        }
    }" {{-- Initialize component when Alpine is ready --}} x-init="initComponent()">
    {{-- Indikator loading real-time yang tidak mengganggu dari wire:poll --}}
    <div title="{{ $realtimeEnabled ? 'Real-time updates active' : 'Real-time updates disabled' }}"
        class="realtime-status-dot {{ $realtimeEnabled ? 'connected' : 'disconnected' }}">
    </div>

    {{-- Overlay loading utama yang menutupi seluruh halaman hingga semua komponen terload --}}
    <div id="main-loading-overlay" class="main-loading-overlay">
        <div class="loading-content">
            <div class="spinner-large"></div>
            <p class="loading-text">Loading Graph Analysis...</p>
            <p class="loading-subtext">Please wait while all components are being initialized</p>
        </div>
    </div>

    {{-- Overlay loading untuk aksi berat seperti loadChartData --}}
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
                @this.call('setHistoricalModeAndLoad').then((result) => {
                    console.log('setHistoricalModeAndLoad completed:', result);
                    // Reset tombol setelah selesai
                    loadButton.textContent = originalText;
                    loadButton.disabled = false;
                    loadButton.style.opacity = '1';

                    // Tambahkan log untuk debugging
                    console.log('Historical data load completed successfully');

                }).catch((error) => {
                    console.error('setHistoricalModeAndLoad failed:', error);
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

    <style>
        /* Overlay loading utama yang menutupi seluruh halaman */
        .main-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s ease-out;
        }

        .main-loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loading-content {
            text-align: center;
            padding: 2rem;
            border-radius: 12px;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .spinner-large {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .loading-text {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 0.5rem 0;
        }

        .loading-subtext {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Pastikan overlay loading tidak bisa di-scroll */
        body.loading {
            overflow: hidden;
            position: fixed;
            width: 100%;
        }

        /* Responsive design untuk overlay loading */
        @media (max-width: 768px) {
            .loading-content {
                padding: 1.5rem;
                margin: 1rem;
            }

            .spinner-large {
                width: 50px;
                height: 50px;
            }

            .loading-text {
                font-size: 1.1rem;
            }

            .loading-subtext {
                font-size: 0.8rem;
            }
        }

        /* Pastikan overlay loading selalu di atas semua elemen */
        .main-loading-overlay {
            z-index: 99999 !important;
        }

        /* Animasi fade in untuk overlay */
        .main-loading-overlay {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>

    @script
        <script>
            // TAMBAHKAN DUA BARIS INI - BUAT GLOBAL
            window.lastSuccessfulPollTimestamp = Date.now();
            window.connectionCheckInterval = null;

            document.addEventListener('livewire:navigated', () => {
                console.log('🚀 Livewire navigated event triggered');

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
                console.log('✅ Select2 initialized successfully');

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

                // HAPUS SEMUA KODE visibilitychange yang bermasalah
                // Web Worker akan menangani koneksi SSE secara independen
                // tanpa terpengaruh oleh perubahan visibilitas tab

                // Event listener untuk memastikan state interval tetap konsisten setelah Livewire update
                // (hanya diperlukan jika ada update dari server)
                document.addEventListener('livewire:update', () => {
                    console.log('🔄 Livewire update event received');
                    isProcessingInterval = false;
                });

                // Event listener untuk memastikan overlay loading berfungsi dengan baik
                document.addEventListener('livewire:load', () => {
                    console.log('📊 Livewire load event received');
                });

                // Event listener untuk memastikan semua komponen siap
                document.addEventListener('DOMContentLoaded', () => {
                    console.log('🌐 DOM content loaded');
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
            });
        </script>
    @endscript
</div>
