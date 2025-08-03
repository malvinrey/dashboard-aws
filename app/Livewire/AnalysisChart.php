<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
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

    // Property to manage state for the "Load More" feature (lazy loading)
    public ?string $earliestLoadedDate = null;

    // KUNCI PERBAIKAN: Tambahkan properti ini untuk mengontrol polling
    public bool $realtimeEnabled = true;

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

        // // If default metrics don't exist, select the first available one
        // if (empty($this->selectedTags) && !empty($this->allTags)) {
        //     $this->selectedTags = [$this->allTags[0]];
        // }

        // Set default date range to the last 24 hours
        $this->startDate = now()->subDay()->toDateString();
        $this->endDate = now()->toDateString();
    }

    /**
     * Dijalankan saat toggle real-time diubah oleh pengguna.
     */
    public function updatedRealtimeEnabled()
    {
        if ($this->realtimeEnabled) {
            // Jika pengguna MENGAKTIFKAN toggle, reset tampilan ke "live".
            Log::info('Real-time updates re-enabled by user action.');

            if ($this->interval === 'second') {
                $this->endDate = now()->toDateTimeString();
                $this->startDate = now()->subMinutes(30)->toDateTimeString();
            } else {
                $this->startDate = now()->startOfDay()->toDateString();
                $this->endDate = now()->endOfDay()->toDateString();
            }

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
        // HAPUS BARIS INI: $this->realtimeEnabled = false;

        Log::info('Executing loadChartData', [
            'selectedTags' => $this->selectedTags,
            'interval' => $this->interval,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'isRealtime' => $this->realtimeEnabled // Tambahkan log untuk status saat ini
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

        // ... (sisa logika di metode ini tidak berubah)
        if ($this->interval === 'second' && !empty($chartData['data'])) {
            $earliestTimestamp = collect($chartData['data'])->flatMap(fn($trace) => $trace['x'])->min();
            if ($earliestTimestamp) {
                $this->earliestLoadedDate = $earliestTimestamp;
            }
        }

        $this->dispatch('chart-data-updated', chartData: $chartData);
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



    // HAPUS method 'catchUpMissedData' - SUDAH TIDAK DIPERLUKAN
    // Gap sekarang dibuat langsung di frontend visibilitychange listener

    /**
     * Renders the component's view.
     */
    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
