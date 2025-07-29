<?php

namespace App\Http\Controllers;

use App\Services\ScadaDataService;
use App\Models\ScadaDataTall;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    protected ScadaDataService $scadaDataService;

    public function __construct(ScadaDataService $scadaDataService)
    {
        $this->scadaDataService = $scadaDataService;
    }

    /**
     * Menampilkan halaman utama analisa menggunakan Livewire component.
     */
    public function index()
    {
        return app(\App\Livewire\AnalysisChart::class);
    }

    /**
     * Endpoint API yang akan dipanggil oleh JavaScript untuk mendapatkan data grafik.
     */
    public function getAnalysisData(Request $request)
    {
        try {
            $tags = $request->input('tag', []); // Get the array of tags
            $interval = $request->input('interval', 'hour'); // Default to 'hour'
            $startDateTime = $request->input('start_date');
            $endDateTime = $request->input('end_date');

            $chartData = $this->scadaDataService->getHistoricalChartData(
                $tags,
                $interval,
                $startDateTime,
                $endDateTime
            );

            return response()->json($chartData);
        } catch (\Exception $e) {
            Log::error('Error fetching analysis data: ' . $e->getMessage(), ['request' => $request->all(), 'exception' => $e]);
            return response()->json(['error' => 'Failed to fetch analysis data.'], 500);
        }
    }
}
