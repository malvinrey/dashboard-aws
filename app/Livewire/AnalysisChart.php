<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use App\Events\ScadaDataReceived;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
class AnalysisChart extends Component
{
    // Properties for all available metrics
    public array $allTags = [];

    // Properties bound to the filter inputs in the view
    public array $selectedTags = [];
    public string $interval = 'hour';
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Properties for historical data loading
    public array $historicalData = [];
    public bool $isLoading = false;

    // Property to manage state for the "Load More" feature (lazy loading)
    public ?string $earliestLoadedDate = null;

    // KUNCI PERBAIKAN: Tambahkan properti ini untuk mengontrol polling
    public bool $realtimeEnabled = true;

    // WebSocket Integration Properties (Updated for Reverb)
    public string $websocketStatus = 'disconnected';
    public ?string $lastWebSocketUpdate = null;
    public array $websocketData = [];
    public array $realtimeMetrics = [];

    // WebSocket Event Listeners (Updated for Reverb)
    protected $listeners = [
        'echo:scada-channel,scada.data.received' => 'handleWebSocketData',
        'echo:scada-realtime,scada.data.received' => 'handleRealtimeData',
        'echo:scada-analysis,scada.data.updated' => 'handleAnalysisData',
        'websocket-status-updated' => 'updateWebSocketStatus',
        'reverb-connected' => 'handleReverbConnected',
        'reverb-disconnected' => 'handleReverbDisconnected'
    ];

    /**
     * Runs once, when the component is first mounted.
     * Sets default values for filters.
     */
    public function mount()
    {
        $this->allTags = app(ScadaDataService::class)->getUniqueTags()->toArray();

        // Set default metrics to show on first load
        $defaultMetrics = ['temperature', 'humidity', 'pressure'];
        $this->selectedTags = array_intersect($defaultMetrics, $this->allTags);

        // Set default date range to the last 24 hours
        $this->startDate = now()->subDay()->toDateString();
        $this->endDate = now()->toDateString();

        // Initialize realtime metrics
        $this->initializeRealtimeMetrics();
    }

    /**
     * Initialize realtime metrics structure
     */
    private function initializeRealtimeMetrics()
    {
        foreach ($this->selectedTags as $tag) {
            $this->realtimeMetrics[$tag] = [
                'current' => 0,
                'min' => null,
                'max' => null,
                'avg' => 0,
                'count' => 0,
                'last_update' => null,
                'trend' => 'stable' // rising, falling, stable
            ];
        }
    }

    /**
     * Dijalankan saat toggle real-time diubah oleh pengguna.
     */
    public function updatedRealtimeEnabled()
    {
        if ($this->realtimeEnabled) {
            // Jika pengguna MENGAKTIFKAN toggle, reset tampilan ke "live".
            Log::info('Real-time updates re-enabled by user action.');

            // KUNCI PERBAIKAN: Selalu gunakan format tanggal yang kompatibel dengan input HTML
            $this->startDate = now()->subDay()->toDateString();
            $this->endDate = now()->toDateString();

            // Muat ulang chart dengan data live.
            $this->loadChartData();
        } else {
            // Jika pengguna MENONAKTIFKAN, cukup log aksi tersebut.
            // Tidak perlu memuat ulang data apa pun.
            Log::info('Real-time updates disabled by user action.');
        }
    }

    // KUNCI PERBAIKAN 1: Buat metode baru yang dipanggil oleh tombol.
    /**
     * Aksi ini secara eksplisit mengalihkan ke mode historis dan memuat data.
     */
    public function setHistoricalModeAndLoad()
    {
        // Langkah 1: Nonaktifkan mode real-time. Ini adalah niat pengguna.
        $this->realtimeEnabled = false;

        // Langkah 2: Panggil metode pemuat data.
        $this->loadChartData();
    }

    // KUNCI PERBAIKAN 2: Jadikan loadChartData sebagai pemuat murni.
    /**
     * Metode ini SEKARANG HANYA bertugas memuat data berdasarkan
     * properti yang ada, tanpa mengubah state 'realtimeEnabled'.
     */
    public function loadChartData()
    {
        Log::info('Executing loadChartData', [
            'selectedTags' => $this->selectedTags,
            'interval' => $this->interval,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'isRealtime' => $this->realtimeEnabled
        ]);

        if (empty($this->selectedTags)) {
            $this->dispatch('chart-data-updated', chartData: ['data' => [], 'layout' => []]);
            return;
        }

        $startCarbon = Carbon::parse($this->startDate);
        $endCarbon = Carbon::parse($this->endDate);

        $start = $startCarbon->startOfDay()->toDateTimeString();
        $end = $endCarbon->endOfDay()->toDateTimeString();

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $start,
            $end
        );

        if ($this->interval === 'second' && !empty($chartData['data'])) {
            $earliestTimestamp = collect($chartData['data'])->flatMap(fn($trace) => $trace['x'])->min();
            if ($earliestTimestamp) {
                $this->earliestLoadedDate = $earliestTimestamp;
            }
        }

        $this->dispatch('historicalDataLoaded', data: $chartData);
    }

    /**
     * Fetches more historical data for the 'second' interval, going further back in time.
     * Called by the "Load More" button.
     */
    public function loadMoreSeconds()
    {
        if ($this->interval !== 'second' || empty($this->selectedTags) || !$this->earliestLoadedDate) {
            return;
        }

        // Calculate the new time window: 30 minutes before the currently oldest data point.
        $endDate = Carbon::parse($this->earliestLoadedDate);
        $startDate = $endDate->copy()->subMinutes(30);

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $startDate->toDateTimeString(),
            $endDate->toDateTimeString()
        );

        if (!empty($chartData['data'][0]['x'])) {
            // Update the state with the new earliest date for the next "Load More" click
            $this->earliestLoadedDate = $startDate->toDateTimeString();
            // Send the new data to the frontend to be prepended to the chart
            $this->dispatch('historical-data-prepended-second', data: $chartData);
        } else {
            $this->dispatch('show-warning', message: 'No more historical data available.');
        }
    }

    /**
     * Fetches the latest data point for real-time updates.
     * Called by `wire:poll` every 5 seconds.
     *
     * @deprecated Gunakan WebSocket stream untuk real-time updates yang lebih efisien
     */
    public function getLatestDataPoint()
    {
        if (empty($this->selectedTags)) return;

        $latestData = app(ScadaDataService::class)->getLatestAggregatedDataPoint(
            $this->selectedTags,
            $this->interval
        );

        if ($latestData) {
            $this->dispatch('update-last-point', data: $latestData);
        }
    }

    /**
     * Get WebSocket stream URL untuk real-time updates
     */
    public function getWebSocketStreamUrl(): string
    {
        if (empty($this->selectedTags)) {
            return '';
        }

        $params = http_build_query([
            'tags' => $this->selectedTags,
            'interval' => $this->interval
        ]);

        return "/api/websocket/stream?{$params}";
    }

    /**
     * Method ini akan dipanggil secara otomatis oleh `wire:init` di frontend.
     * Anda bisa memanggilnya lagi dari UI untuk refresh data dengan rentang waktu berbeda.
     *
     * @param string $startDate (contoh: '2025-08-11 00:00:00')
     * @param string $endDate (contoh: '2025-08-12 00:00:00')
     */
    public function loadHistoricalData($startDate, $endDate)
    {
        $this->isLoading = true;

        // --- Logika Penentuan Agregasi Dinamis ---
        try {
            $durationInSeconds = Carbon::parse($endDate)->diffInSeconds(Carbon::parse($startDate));

            $aggregationLevel = 'second';
            if ($durationInSeconds > 3600 * 6) { // > 6 jam -> agregat per menit
                $aggregationLevel = 'minute';
            }
            if ($durationInSeconds > 86400 * 7) { // > 7 hari -> agregat per jam
                $aggregationLevel = 'hour';
            }
            if ($durationInSeconds > 86400 * 30) { // > 30 hari -> agregat per hari
                $aggregationLevel = 'day';
            }

            // Panggil service untuk mengambil data yang sudah diagregasi
            $this->historicalData = app(ScadaDataService::class)->getAggregatedHistoricalData(
                $this->selectedTags,
                $startDate,
                $endDate,
                $aggregationLevel
            )->toArray();

            // Kirim event ke frontend bahwa data sudah siap, beserta datanya
            $this->dispatch('historicalDataLoaded', data: $this->historicalData);
        } catch (\Exception $e) {
            // Tangani error jika terjadi, misal kirim notifikasi error ke frontend
            $this->dispatch('historicalDataError', message: 'Failed to load historical data.');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Handle WebSocket data received from scada-data channel (Updated for Reverb)
     */
    public function handleWebSocketData($event)
    {
        try {
            $data = $event['data'] ?? [];

            if (!empty($data)) {
                $this->websocketData[] = $data;
                $this->lastWebSocketUpdate = now();

                // Update realtime metrics
                $this->updateRealtimeMetrics($data);

                // Limit data points untuk performance
                if (count($this->websocketData) > 1000) {
                    $this->websocketData = array_slice($this->websocketData, -1000);
                }

                // Dispatch event untuk frontend update
                $this->dispatch('chart-data-updated', $data);

                Log::info('WebSocket data received and processed', [
                    'data_size' => is_array($data) ? count($data) : 1,
                    'total_points' => count($this->websocketData),
                    'timestamp' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling WebSocket data', [
                'error' => $e->getMessage(),
                'event' => $event
            ]);
        }
    }

    /**
     * Handle real-time specific data from scada-realtime channel
     */
    public function handleRealtimeData($event)
    {
        // Handle real-time specific data
        $this->handleWebSocketData($event);
    }

    /**
     * Handle analysis-specific data from scada-analysis channel
     */
    public function handleAnalysisData($event)
    {
        try {
            $data = $event['data'] ?? [];

            if (!empty($data)) {
                // Update analysis-specific metrics
                $this->updateAnalysisMetrics($data);

                // Dispatch to frontend
                $this->dispatch('analysis-data-updated', $data);

                Log::info('Analysis data received via WebSocket', [
                    'data_type' => 'analysis',
                    'timestamp' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling analysis data', [
                'error' => $e->getMessage(),
                'event' => $event
            ]);
        }
    }

    /**
     * Update realtime metrics with new data
     */
    private function updateRealtimeMetrics($data)
    {
        foreach ($this->selectedTags as $tag) {
            if (isset($data[$tag]) && is_numeric($data[$tag])) {
                $value = floatval($data[$tag]);
                $current = $this->realtimeMetrics[$tag]['current'];

                // Update current value
                $this->realtimeMetrics[$tag]['current'] = $value;

                // Update min/max
                if ($this->realtimeMetrics[$tag]['min'] === null || $value < $this->realtimeMetrics[$tag]['min']) {
                    $this->realtimeMetrics[$tag]['min'] = $value;
                }
                if ($this->realtimeMetrics[$tag]['max'] === null || $value > $this->realtimeMetrics[$tag]['max']) {
                    $this->realtimeMetrics[$tag]['max'] = $value;
                }

                // Update average
                $this->realtimeMetrics[$tag]['count']++;
                $this->realtimeMetrics[$tag]['avg'] =
                    (($this->realtimeMetrics[$tag]['avg'] * ($this->realtimeMetrics[$tag]['count'] - 1)) + $value) / $this->realtimeMetrics[$tag]['count'];

                // Update trend
                if ($current !== 0) {
                    if ($value > $current) {
                        $this->realtimeMetrics[$tag]['trend'] = 'rising';
                    } elseif ($value < $current) {
                        $this->realtimeMetrics[$tag]['trend'] = 'falling';
                    } else {
                        $this->realtimeMetrics[$tag]['trend'] = 'stable';
                    }
                }

                $this->realtimeMetrics[$tag]['last_update'] = now();
            }
        }
    }

    /**
     * Update analysis-specific metrics
     */
    private function updateAnalysisMetrics($data)
    {
        // Handle analysis-specific data updates
        // This can include trend analysis, anomaly detection, etc.
        if (isset($data['anomalies'])) {
            $this->dispatch('anomalies-detected', $data['anomalies']);
        }

        if (isset($data['trends'])) {
            $this->dispatch('trends-updated', $data['trends']);
        }
    }

    /**
     * Handle Reverb connection established
     */
    public function handleReverbConnected()
    {
        $this->websocketStatus = 'connected';
        $this->dispatch('websocket-status-updated', 'connected');

        Log::info('Reverb WebSocket connection established');
    }

    /**
     * Handle Reverb connection lost
     */
    public function handleReverbDisconnected()
    {
        $this->websocketStatus = 'disconnected';
        $this->dispatch('websocket-status-updated', 'disconnected');

        Log::info('Reverb WebSocket connection lost');
    }

    /**
     * Update WebSocket connection status
     */
    public function updateWebSocketStatus($status)
    {
        $this->websocketStatus = $status;
        $this->dispatch('websocket-status-updated', $status);
    }

    /**
     * Get WebSocket performance metrics
     */
    public function getWebSocketMetrics()
    {
        return [
            'status' => $this->websocketStatus,
            'last_update' => $this->lastWebSocketUpdate,
            'data_points' => count($this->websocketData),
            'realtime_enabled' => $this->realtimeEnabled,
            'realtime_metrics' => $this->realtimeMetrics
        ];
    }

    /**
     * Get realtime metrics for display
     */
    public function getRealtimeMetrics()
    {
        return $this->realtimeMetrics;
    }

    /**
     * Renders the component's view.
     */
    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
