<?php

namespace App\Livewire;

use App\Services\ScadaDataService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

// Tidak ada atribut #[Layout] karena view ini akan dipanggil dari file Blade utama
class RealtimeChart extends Component
{
    public array $allTags = [];

    /**
     * Metode ini berjalan saat komponen pertama kali dimuat.
     * Tugasnya adalah mengambil semua nama metrik (tag) yang tersedia.
     */
    public function mount()
    {
        try {
            $this->allTags = app(ScadaDataService::class)->getUniqueTags()->toArray();
            Log::info('RealtimeChart: Loaded tags', ['tags' => $this->allTags]);
        } catch (\Exception $e) {
            Log::error('RealtimeChart: Error loading tags', ['error' => $e->getMessage()]);
            $this->allTags = [];
        }
    }

    /**
     * Metode ini dipanggil oleh wire:poll setiap 5 detik.
     * Tugasnya adalah mengambil titik data mentah terbaru untuk semua tag
     * dan mengirimkannya ke frontend.
     */
    public function getLatestDataPoint()
    {
        if (empty($this->allTags)) {
            Log::warning('RealtimeChart: No tags available for data fetching');
            return;
        }

        try {
            $latestData = app(ScadaDataService::class)->getLatestDataForTags($this->allTags);

            if ($latestData) {
                Log::info('RealtimeChart: Sending new data point', [
                    'timestamp' => $latestData['timestamp'],
                    'metrics_count' => count($latestData['metrics'])
                ]);

                // Mengirim event 'new-streaming-point' yang akan ditangkap oleh JavaScript
                // untuk memperbarui grafik.
                $this->dispatch('new-streaming-point', data: $latestData);
            } else {
                Log::warning('RealtimeChart: No latest data available');
            }
        } catch (\Exception $e) {
            Log::error('RealtimeChart: Error fetching latest data', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Merender file view yang terkait dengan komponen ini.
     */
    public function render()
    {
        return view('livewire.realtime-chart');
    }
}
