<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount()
    {
        // Component initialization
    }

    public function updateData()
    {
        // Method ini akan dipanggil setiap 5 detik oleh wire:poll
        // Data akan otomatis di-refresh
    }

    public function render()
    {
        $scadaDataService = app(ScadaDataService::class);
        $dashboardData = $scadaDataService->getDashboardMetrics();

        return view('livewire.dashboard', $dashboardData);
    }
}
