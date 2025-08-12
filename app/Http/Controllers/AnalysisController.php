<?php

namespace App\Http\Controllers;

use App\Services\ScadaDataService;
// Removed ScadaDataTall - using only ScadaDataWide for efficiency
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

    /**
     * Endpoint API super ringan untuk pembaruan real-time.
     */
    public function getLatestDataApi(Request $request)
    {
        $startTime = microtime(true);

        try {
            // Validasi input
            $validated = $request->validate([
                'tags' => 'required|array',
                'tags.*' => 'string',
                'interval' => 'required|string|in:second,minute,hour,day',
            ]);

            // Panggil service untuk mendapatkan data terbaru
            $latestData = $this->scadaDataService->getLatestAggregatedDataPoint(
                $validated['tags'],
                $validated['interval']
            );

            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000, 2);

            // Log performance metrics
            Log::info('Latest data API called', [
                'tags' => $validated['tags'],
                'interval' => $validated['interval'],
                'processing_time_ms' => $processingTime,
                'has_data' => !is_null($latestData),
                'user_agent' => $request->header('User-Agent')
            ]);

            if (!$latestData) {
                return response()->json(null, 204)
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }

            return response()->json($latestData)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000, 2);

            Log::error('Error fetching latest data: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
                'processing_time_ms' => $processingTime
            ]);
            return response()->json(['error' => 'Failed to fetch latest data.'], 500);
        }
    }
}
