<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class AnalysisChart extends Component
{
    public array $allTags = [];
    public array $selectedTags = [];
    public string $interval = 'hour';
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount()
    {
        // Mengambil semua tag yang tersedia dari service
        $this->allTags = app(ScadaDataService::class)->getUniqueTags()->toArray();

        // Menetapkan tag pertama sebagai pilihan default untuk grafik tunggal
        if (!empty($this->allTags)) {
            $this->selectedTags = [$this->allTags[0]];
        }

        // Menginisialisasi tanggal default jika kosong
        if (!$this->startDate) {
            $this->startDate = now()->subDay()->toDateString();
        }
        if (!$this->endDate) {
            $this->endDate = now()->toDateString();
        }

        // Load chart data saat mount
        $this->loadChartData();
    }

    public function loadChartData()
    {
        // Memastikan ada tag yang dipilih sebelum memuat data
        if (empty($this->selectedTags)) return;

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $this->startDate,
            $this->endDate
        );
        $this->dispatch('chart-data-updated', chartData: $chartData);
    }

    public function getLatestDataPoint()
    {
        if (empty($this->selectedTags)) return;

        $latestData = app(ScadaDataService::class)->getLatestDataForTags($this->selectedTags);

        if ($latestData) {
            $this->dispatch('new-data-point', data: $latestData);
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

        // Mengirim event yang berbeda untuk data lazy-loading
        $this->dispatch('historical-data-prepended', data: $chartData);
    }

    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
