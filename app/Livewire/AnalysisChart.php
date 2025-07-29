<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
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
        $this->allTags = app(ScadaDataService::class)->getUniqueTags()->toArray();

        if (!empty($this->allTags)) {
            $this->selectedTags = [$this->allTags[0]];
        }

        if (!$this->startDate) {
            $this->startDate = now()->subDay()->toDateString();
        }
        if (!$this->endDate) {
            $this->endDate = now()->toDateString();
        }
    }

    // Metode updatedInterval() dan updatedSelectedTags() telah dihapus
    // untuk mencegah render ulang otomatis yang tidak diinginkan.
    // Pengguna sekarang memiliki kendali penuh melalui tombol "Load".

    public function loadChartData()
    {
        if (empty($this->selectedTags)) return;

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $this->startDate,
            $this->endDate
        );
        $this->dispatch('chart-data-updated', chartData: $chartData);
    }

    /**
     * KUNCI PERBAIKAN: Metode ini sekarang lebih cerdas.
     * Ia mengambil nilai agregat terbaru yang sesuai dengan interval yang dipilih.
     */
    public function getLatestDataPoint()
    {
        if (empty($this->selectedTags)) return;

        // Memanggil metode service baru yang dirancang untuk mengambil
        // titik data agregat terbaru berdasarkan interval.
        $latestAggregatedData = app(ScadaDataService::class)->getLatestAggregatedDataPoint(
            $this->selectedTags,
            $this->interval
        );

        if ($latestAggregatedData) {
            // Mengirim event baru yang lebih spesifik untuk pembaruan cerdas di frontend.
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
