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
     * Lifecycle Hook that runs when the $realtimeEnabled property is updated.
     * This method handles the logic for re-enabling real-time mode.
     */
    public function updatedRealtimeEnabled()
    {
        // This logic only runs when the toggle is turned ON.
        if ($this->realtimeEnabled) {
            Log::info('Real-time updates re-enabled by user.');

            // To avoid a jarring jump from historical data to live data,
            // we reset the view to a sensible "live" timeframe.
            if ($this->interval === 'second') {
                // For 'second' interval, jump to the last 30 minutes.
                $this->endDate = now()->toDateTimeString();
                $this->startDate = now()->subMinutes(30)->toDateTimeString();
            } else {
                // For other intervals, jump to today's data.
                $this->startDate = now()->startOfDay()->toDateString();
                $this->endDate = now()->endOfDay()->toDateString();
            }

            // After resetting the date range, reload the chart to show the new "live" view.
            $this->loadChartData();
        } else {
            Log::info('Real-time updates disabled by user.');
        }
    }

    /**
     * The MAIN method to fetch and display chart data.
     * This is called ONLY by the "Load Historical Data" button.
     */
    public function loadChartData()
    {
        Log::info('Load Historical Data button clicked', [
            'selectedTags' => $this->selectedTags,
            'interval' => $this->interval,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate
        ]);

        if (empty($this->selectedTags)) {
            // If no tags are selected, clear the chart
            $this->dispatch('chart-data-updated', chartData: ['data' => [], 'layout' => []]);
            return;
        }

        // --- TIME RANGE PREPARATION LOGIC ---
        $startCarbon = Carbon::parse($this->startDate)->startOfDay();
        $endCarbon = Carbon::parse($this->endDate)->endOfDay();

        // For intervals that aggregate by day, ensure we cover the full day.
        if (in_array($this->interval, ['hour', 'day', 'minute'])) {
            $start = $startCarbon->startOfDay()->toDateTimeString();
            $end = $endCarbon->endOfDay()->toDateTimeString();
        } else { // This is for 'second' interval
            // For 'second' interval, we must use a short time range for performance.
            // If the user's selected range is too large, we auto-adjust it.
            if ($endCarbon->diffInSeconds($startCarbon) > 3600) { // More than 1 hour
                $end = now()->toDateTimeString();
                $start = now()->subMinutes(30)->toDateTimeString();
                $this->dispatch('show-warning', message: 'Time range too large for "second" interval, adjusted to last 30 minutes.');
            } else {
                // Use the precise time if the range is short enough
                $start = $startCarbon->toDateTimeString();
                $end = $endCarbon->toDateTimeString();
            }
        }
        // --- END OF LOGIC ---

        $chartData = app(ScadaDataService::class)->getHistoricalChartData(
            $this->selectedTags,
            $this->interval,
            $start,
            $end
        );

        // Update the state for the "Load More" button based on the actual data loaded.
        if ($this->interval === 'second' && !empty($chartData['data'])) {
            // Find the earliest timestamp from all the new data traces
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

    /**
     * Fetches data that was missed while the browser tab was inactive.
     * This is triggered by a JavaScript event when the tab becomes visible again.
     */
    #[On('catchUpMissedData')]
    public function catchUpMissedData(string $lastKnownTimestamp)
    {
        if (empty($this->selectedTags) || $this->interval !== 'second') {
            return;
        }

        $missedData = app(ScadaDataService::class)->getRecentDataSince(
            $this->selectedTags,
            $lastKnownTimestamp
        );

        if (!empty($missedData)) {
            $this->dispatch('append-missed-points', data: $missedData);
        }
    }

    /**
     * Renders the component's view.
     */
    public function render()
    {
        return view('livewire.graph-analysis');
    }
}
