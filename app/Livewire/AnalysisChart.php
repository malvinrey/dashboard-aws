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
    public array $allTags = [];
    public array $selectedTags = [];
    public string $interval = 'hour';
    public ?string $startDate = null;
    public ?string $endDate = null;
    // Properti untuk lazy loading
    public ?string $earliestLoadedDate = null;
    public ?string $latestLoadedDate = null;
    public bool $isLoadingMore = false;
    public int $lazyLoadLimit = 100; // Jumlah data points untuk lazy loading

    public function mount()
    {
        $this->allTags = app(ScadaDataService::class)->getUniqueTags()->toArray();

        // Pilih beberapa metrics default yang umum digunakan
        $defaultMetrics = ['temperature', 'humidity', 'pressure'];
        $this->selectedTags = array_intersect($defaultMetrics, $this->allTags);

        // Jika tidak ada metrics default yang tersedia, pilih yang pertama
        if (empty($this->selectedTags) && !empty($this->allTags)) {
            $this->selectedTags = [$this->allTags[0]];
        }

        $this->startDate = now()->subDay()->toDateString();
        $this->endDate = now()->toDateString();

        // Initialize lazy loading properties
        $this->earliestLoadedDate = $this->startDate;
        $this->latestLoadedDate = $this->endDate;
    }

    public function loadChartData()
    {
        Log::info('loadChartData called', [
            'selectedTags' => $this->selectedTags,
            'interval' => $this->interval,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate
        ]);

        if (empty($this->selectedTags)) {
            Log::warning('No tags selected');
            return;
        }

        // Prepare datetime values for the service
        $startDateTime = null;
        $endDateTime = null;

        if ($this->interval === 'second') {
            // For second interval, use the full day range from the date inputs
            $startDateTime = Carbon::parse($this->startDate)->startOfDay()->toDateTimeString();
            $endDateTime = Carbon::parse($this->endDate)->endOfDay()->toDateTimeString();
        } else {
            // For other intervals, use the date inputs as is
            $startDateTime = $this->startDate;
            $endDateTime = $this->endDate;
        }

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $startDateTime,
            $endDateTime
        );

        // Update earliestLoadedDate berdasarkan data aktual yang diterima
        // Logika ini penting untuk "Load More" - hanya untuk interval second
        if ($this->interval === 'second' && !empty($chartData['data'])) {
            $earliestTimestamp = null;
            // Dapatkan timestamp paling awal dari semua trace
            foreach ($chartData['data'] as $trace) {
                if (!empty($trace['x'])) {
                    // Pastikan untuk membandingkan timestamp, bukan string
                    $traceEarliest = min(array_map('strtotime', $trace['x']));
                    if ($earliestTimestamp === null || $traceEarliest < $earliestTimestamp) {
                        $earliestTimestamp = $traceEarliest;
                    }
                }
            }

            if ($earliestTimestamp) {
                // Simpan dalam format string yang konsisten
                $this->earliestLoadedDate = Carbon::createFromTimestamp($earliestTimestamp)->toDateTimeString();
                Log::info('Updated earliestLoadedDate from actual data for second interval', [
                    'earliest_timestamp' => $this->earliestLoadedDate
                ]);
            }
        }

        $this->dispatch('chart-data-updated', chartData: $chartData);
    }

    public function getLatestDataPoint()
    {
        if (empty($this->selectedTags)) return;

        $latestAggregatedData = app(ScadaDataService::class)->getLatestAggregatedDataPoint(
            $this->selectedTags,
            $this->interval
        );

        if ($latestAggregatedData) {
            $this->dispatch('update-last-point', data: $latestAggregatedData);
        }
    }

    public function loadMoreHistoricalData($startDate, $endDate)
    {
        if (empty($this->selectedTags)) return;

        // Convert date format to datetime if needed for second interval
        $startDateTime = $startDate;
        $endDateTime = $endDate;

        if ($this->interval === 'second') {
            $startDateTime = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
            $endDateTime = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        }

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $startDateTime,
            $endDateTime
        );

        $this->dispatch('historical-data-prepended', data: $chartData);
    }

    /**
     * Mengambil data yang terlewat saat tab kembali fokus.
     * Dipanggil oleh JavaScript melalui event 'catchUpMissedData'.
     */
    #[On('catchUpMissedData')]
    public function catchUpMissedData(string $lastKnownTimestamp)
    {
        if (empty($this->selectedTags) || $this->interval !== 'second') {
            return; // Hanya berlaku untuk interval 'second'
        }

        Log::info('Catching up missed data since: ' . $lastKnownTimestamp);

        $missedData = app(ScadaDataService::class)->getRecentDataSince(
            $this->selectedTags,
            $lastKnownTimestamp
        );

        if (!empty($missedData)) {
            $this->dispatch('append-missed-points', data: $missedData);
        }
    }

    /**
     * KUNCI PERUBAHAN: Metode baru yang dipanggil oleh tombol "Load More".
     */
    public function loadMoreSeconds()
    {
        if ($this->interval !== 'second' || empty($this->selectedTags) || !$this->earliestLoadedDate) {
            return;
        }

        // Tentukan rentang waktu baru berdasarkan data paling lama yang sudah dimuat
        $endDate = Carbon::parse($this->earliestLoadedDate);
        $startDate = $endDate->copy()->subMinutes(30); // Ambil 30 menit data sebelumnya

        // Pastikan start < end untuk menghindari range negatif
        if ($startDate->gte($endDate)) {
            Log::warning('Invalid time range detected, adjusting', [
                'original_start' => $startDate->toDateTimeString(),
                'original_end' => $endDate->toDateTimeString()
            ]);
            // Jika start >= end, gunakan range 30 menit yang valid
            $endDate = Carbon::parse($this->earliestLoadedDate);
            $startDate = $endDate->copy()->subMinutes(30);
        }

        Log::info('Load More button clicked for second interval', [
            'current_earliest' => $this->earliestLoadedDate,
            'new_start' => $startDate->toDateTimeString(),
            'new_end' => $endDate->toDateTimeString(),
            'time_range_seconds' => $endDate->diffInSeconds($startDate)
        ]);

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $startDate->toDateTimeString(),
            $endDate->toDateTimeString()
        );

        if (!empty($chartData['data'])) {
            // Perbarui state tanggal paling lama yang sudah dimuat
            $this->earliestLoadedDate = $startDate->toDateTimeString();

            // Kirim data baru ke frontend untuk ditambahkan di awal grafik
            $this->dispatch('historical-data-prepended-second', data: $chartData);

            Log::info('Historical data loaded successfully for second interval', [
                'data_points' => array_sum(array_map(fn($trace) => count($trace['x']), $chartData['data'])),
                'new_earliest_date' => $this->earliestLoadedDate
            ]);
        } else {
            // Opsional: kirim notifikasi jika sudah tidak ada data lagi
            $this->dispatch('show-warning', message: 'No more historical data available.');
        }
    }

    /**
     * Auto-load data ketika selection berubah
     */
    public function updatedSelectedTags()
    {
        // Metode ini akan berjalan 500ms setelah pengguna selesai mengklik checkbox
        if (!empty($this->selectedTags)) {
            $this->loadChartData();
        } else {
            // Jika semua checkbox di-uncheck, kirim data kosong untuk membersihkan chart
            $this->dispatch('chart-data-updated', chartData: ['data' => [], 'layout' => []]);
        }
    }

    /**
     * Auto-load data ketika interval berubah.
     * Auto-adjust rentang waktu untuk interval 'second' tanpa batasan.
     */
    public function updatedInterval()
    {
        // Reset state untuk lazy loading
        $this->isLoadingMore = false;

        // Jika pengguna memilih 'second', atur rentang waktu ke 30 menit terakhir.
        if ($this->interval === 'second') {
            // Untuk input fields, gunakan format date saja
            $this->endDate = now()->toDateString();
            $this->startDate = now()->subMinutes(30)->toDateString();

            // Untuk lazy loading, gunakan format datetime
            $this->earliestLoadedDate = now()->subMinutes(30)->toDateTimeString();
            $this->latestLoadedDate = now()->toDateTimeString();
        } else {
            // Untuk interval lain, gunakan rentang harian seperti biasa
            $this->endDate = now()->toDateString();
            $this->startDate = now()->subDay()->toDateString();

            // Untuk lazy loading, gunakan format datetime
            $this->earliestLoadedDate = now()->subDay()->toDateTimeString();
            $this->latestLoadedDate = now()->toDateTimeString();
        }

        Log::info('Interval changed', [
            'new_interval' => $this->interval,
            'new_start' => $this->startDate,
            'new_end' => $this->endDate
        ]);

        if (!empty($this->selectedTags)) {
            $this->loadChartData();
        }
    }

    /**
     * Select semua metrics
     */
    public function selectAllMetrics()
    {
        $this->selectedTags = $this->allTags;
        $this->loadChartData();
    }

    /**
     * Clear semua metrics
     */
    public function clearAllMetrics()
    {
        $this->selectedTags = [];
        // Kirim event untuk membersihkan chart di frontend secara eksplisit
        $this->dispatch('chart-data-updated', chartData: ['data' => [], 'layout' => []]);
    }

    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
