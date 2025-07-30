<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Illuminate\Support\Carbon;

#[Layout('components.layouts.app')]
class AnalysisChart extends Component
{
    public array $allTags = [];
    public array $selectedTags = [];
    public string $interval = 'hour';
    public ?string $startDate = null;
    public ?string $endDate = null;
    // Properti startTime dan endTime telah dihapus untuk menyederhanakan UI

    public function mount()
    {
        $this->allTags = app(ScadaDataService::class)->getUniqueTags()->toArray();

        if (!empty($this->allTags)) {
            $this->selectedTags = [$this->allTags[0]];
        }

        $this->startDate = now()->subDay()->toDateString();
        $this->endDate = now()->toDateString();
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

        // KUNCI PERBAIKAN: Validasi rentang waktu sebelum menjalankan query
        if ($this->interval === 'second') {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            // Jika rentang lebih dari 1 jam (3600 detik), kirim peringatan dan hentikan.
            // Anda bisa menyesuaikan batas ini sesuai kebutuhan.
            if ($end->diffInSeconds($start) > 3600) {
                $this->dispatch('show-warning', message: 'Rentang waktu terlalu besar untuk interval per detik. Harap pilih rentang di bawah 1 jam.');
                return; // Hentikan eksekusi
            }
        }

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $this->startDate,
            $this->endDate
        );

        Log::info('Chart data loaded', [
            'data_count' => count($chartData['data'] ?? []),
            'layout' => $chartData['layout'] ?? null
        ]);

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

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $startDate,
            $endDate
        );

        $this->dispatch('historical-data-prepended', data: $chartData);
    }

    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
