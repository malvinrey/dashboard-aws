<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use App\Exports\ScadaLogsExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Carbon;

class ScadaLogTable extends Component
{
    use WithPagination;

    public $amount = 50;
    public $totalRecords = 0;

    // Date filtering properties
    public $startDate = '';
    public $endDate = '';

    // Search property
    public $search = '';

    // Sorting properties
    public $sortField = 'id';
    public $sortDirection = 'desc';

    public function mount()
    {
        $scadaDataService = app(ScadaDataService::class);
        $this->totalRecords = $scadaDataService->getTotalRecords();

        // Set default date range to last 7 days
        $this->endDate = now()->format('Y-m-d');
        $this->startDate = now()->subDays(1)->format('Y-m-d');
    }

    public function loadMore()
    {
        $this->amount += 50;
    }

    public function applyFilters()
    {
        $this->amount = 50; // Reset to initial amount when filtering
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->startDate = now()->subDays(1)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->search = '';
        $this->sortField = 'id';
        $this->sortDirection = 'desc';
        $this->amount = 50;
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch()
    {
        $this->amount = 50; // Reset to initial amount when searching
        $this->resetPage();
    }

    public function exportCsv()
    {
        // Kumpulkan filter saat ini
        $filters = [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'search' => $this->search,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection
        ];

        $filename = 'scada_logs_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Buat dan langsung unduh file. Livewire akan menangani respons unduhan ini.
        return Excel::download(new ScadaLogsExport($filters), $filename);
    }

    public function render()
    {
        $scadaDataService = app(ScadaDataService::class);
        $logs = $scadaDataService->getLogDataWithFilters(
            $this->amount,
            $this->startDate,
            $this->endDate,
            $this->search,
            $this->sortField,
            $this->sortDirection
        );

        // Update total records based on current filters
        $this->totalRecords = $scadaDataService->getTotalRecordsWithFilters(
            $this->startDate,
            $this->endDate,
            $this->search
        );

        return view('livewire.log-data', [
            'logs' => $logs,
            'amount' => $this->amount,
            'totalRecords' => $this->totalRecords
        ]);
    }
}
