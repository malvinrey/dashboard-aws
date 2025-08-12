<?php

namespace App\Http\Controllers;

use App\Services\ScadaDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends Controller
{
    protected ScadaDataService $scadaDataService;

    public function __construct(ScadaDataService $scadaDataService)
    {
        $this->scadaDataService = $scadaDataService;
    }

    /**
     * SSE endpoint untuk streaming data real-time SCADA
     */
    public function stream(Request $request): StreamedResponse
    {
        // Validasi input
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string',
            'interval' => 'required|string|in:second,minute,hour,day',
        ]);

        $tags = $validated['tags'];
        $interval = $validated['interval'];

        // Set headers untuk SSE
        $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ];

        return new StreamedResponse(function () use ($tags, $interval) {
            // Set unlimited time limit untuk koneksi persisten
            set_time_limit(0);

            // Disable output buffering
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Kirim initial connection message
            $this->sendSseMessage('connected', [
                'message' => 'SSE connection established',
                'tags' => $tags,
                'interval' => $interval,
                'timestamp' => now()->toISOString()
            ]);

            Log::info('SSE connection established', [
                'tags' => $tags,
                'interval' => $interval,
                'client_ip' => request()->ip()
            ]);

            $lastDataHash = null;
            $heartbeatCounter = 0;

            while (true) {
                try {
                    // Cek apakah koneksi masih aktif
                    if (connection_aborted()) {
                        Log::info('SSE connection aborted by client');
                        break;
                    }

                    // Ambil data terbaru
                    $latestData = $this->scadaDataService->getLatestAggregatedDataPoint(
                        $tags,
                        $interval
                    );

                    if ($latestData) {
                        // Buat hash dari data untuk deteksi perubahan
                        $currentDataHash = md5(serialize($latestData));

                        // Kirim data hanya jika ada perubahan
                        if ($currentDataHash !== $lastDataHash) {
                            $this->sendSseMessage('data', $latestData);
                            $lastDataHash = $currentDataHash;

                            Log::debug('SSE data sent', [
                                'timestamp' => $latestData['timestamp'] ?? 'unknown',
                                'metrics_count' => count($latestData['metrics'] ?? [])
                            ]);
                        }
                    }

                    // Kirim heartbeat setiap 30 detik untuk menjaga koneksi
                    $heartbeatCounter++;
                    if ($heartbeatCounter >= 6) { // 6 * 5 detik = 30 detik
                        $this->sendSseMessage('heartbeat', [
                            'timestamp' => now()->toISOString(),
                            'message' => 'Connection alive'
                        ]);
                        $heartbeatCounter = 0;
                    }

                    // Flush output buffer
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();

                    // Tunggu 5 detik sebelum check berikutnya
                    sleep(5);
                } catch (\Exception $e) {
                    Log::error('SSE stream error: ' . $e->getMessage(), [
                        'tags' => $tags,
                        'interval' => $interval
                    ]);

                    // Kirim error message ke client
                    $this->sendSseMessage('error', [
                        'message' => 'Data stream error occurred',
                        'timestamp' => now()->toISOString()
                    ]);

                    // Tunggu sebentar sebelum retry
                    sleep(10);
                }
            }

            Log::info('SSE connection closed', [
                'tags' => $tags,
                'interval' => $interval
            ]);
        }, 200, $headers);
    }

    /**
     * Kirim pesan SSE dengan format yang benar
     */
    private function sendSseMessage(string $event, array $data): void
    {
        $jsonData = json_encode($data);

        echo "event: {$event}\n";
        echo "data: {$jsonData}\n\n";
    }

    /**
     * Endpoint untuk test koneksi SSE
     */
    public function test(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'SSE endpoint available',
            'endpoint' => '/api/sse/stream',
            'supported_events' => ['connected', 'data', 'heartbeat', 'error']
        ]);
    }
}
