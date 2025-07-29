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
        $this->loadChartData();
    }

    public function loadChartData()
    {
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

    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
