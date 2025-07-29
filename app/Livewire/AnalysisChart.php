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
        // Set semua tags sebagai selected karena sekarang menampilkan semua grafik
        $this->selectedTags = $this->allTags;
        $this->loadChartData();
    }

    public function loadChartData()
    {
        // Jika tidak ada selected tags, gunakan semua tags
        $tagsToLoad = empty($this->selectedTags) ? $this->allTags : $this->selectedTags;

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $tagsToLoad,
            $this->interval,
            $this->startDate,
            $this->endDate
        );
        $this->dispatch('chart-data-updated', chartData: $chartData);
    }

    public function getLatestDataPoint()
    {
        // Jika tidak ada selected tags, gunakan semua tags
        $tagsToCheck = empty($this->selectedTags) ? $this->allTags : $this->selectedTags;

        if (empty($tagsToCheck)) return;

        $latestData = app(ScadaDataService::class)->getLatestDataForTags($tagsToCheck);

        if ($latestData) {
            $this->dispatch('new-data-point', data: $latestData);
        }
    }

    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
