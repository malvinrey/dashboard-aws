<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use Livewire\Component;

class ScadaLogTable extends Component
{
    public $amount = 50;
    public $totalRecords = 0;

    public function mount()
    {
        $scadaDataService = app(ScadaDataService::class);
        $this->totalRecords = $scadaDataService->getTotalRecords();
    }

    public function loadMore()
    {
        $this->amount += 50;
    }

    public function render()
    {
        $scadaDataService = app(ScadaDataService::class);
        $logs = $scadaDataService->getLogData($this->amount);

        return view('livewire.log-data', [
            'logs' => $logs,
            'amount' => $this->amount,
            'totalRecords' => $this->totalRecords
        ]);
    }
}
