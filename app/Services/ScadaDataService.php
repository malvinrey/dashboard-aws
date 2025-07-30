<?php

namespace App\Services;

use App\Models\ScadaDataTall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Carbon\CarbonPeriod;

class ScadaDataService
{
    /**
     * Memproses payload yang masuk dari SCADA dan menyimpannya ke database.
     * (Logika dari ReceiverController dipindahkan ke sini)
     */
    public function processScadaPayload(array $payload): void
    {
        try {
            DB::transaction(function () use ($payload) {
                $dataToInsert = [];
                $now = Carbon::now();
                $batchId = Str::uuid();

                collect($payload['DataArray'])->each(function ($dataGroup) use (&$dataToInsert, $batchId, $now) {
                    $namaGroup = $dataGroup['_groupTag'];
                    $timestamp = $dataGroup['_terminalTime'];

                    collect($dataGroup)->reject(function ($value, $key) {
                        return str_starts_with($key, '_');
                    })->each(function ($nilaiTag, $namaTag) use (&$dataToInsert, $batchId, $namaGroup, $timestamp, $now) {
                        $dataToInsert[] = [
                            'batch_id'         => $batchId,
                            'nama_group'       => $namaGroup,
                            'timestamp_device' => $timestamp,
                            'nama_tag'         => $namaTag,
                            'nilai_tag'        => $nilaiTag,
                            'created_at'       => $now,
                            'updated_at'       => $now,
                        ];
                    });
                });

                if (!empty($dataToInsert)) {
                    ScadaDataTall::insert($dataToInsert);
                }
            });
        } catch (\Exception $e) {
            Log::error('SCADA Payload Processing Error: ' . $e->getMessage(), ['payload' => $payload]);
            // Melempar kembali exception agar controller bisa menangani respons HTTP
            throw $e;
        }
    }

    /**
     * Mengambil data yang sudah diproses untuk ditampilkan di dashboard.
     * (Logika dari DashboardController dipindahkan ke sini)
     */
    public function getDashboardMetrics(): array
    {
        // 1. Tentukan satuan untuk setiap tag
        $units = [
            'temperature' => '°C',
            'humidity' => '%',
            'pressure' => 'hPa',
            'rainfall' => 'mm',
            'wind_speed' => 'm/s',
            'wind_direction' => '°',
            'par_sensor' => 'μmol/m²/s',
            'solar_radiation' => 'W/m²',
        ];

        // 2. Ambil dua batch ID terakhir untuk perbandingan
        $latestBatchIds = ScadaDataTall::distinct('batch_id')->orderBy('id', 'desc')->limit(2)->pluck('batch_id');

        if ($latestBatchIds->isEmpty()) {
            return ['metrics' => [], 'lastPayloadInfo' => null];
        }

        // 3. Ambil data dari batch terbaru
        $latestData = ScadaDataTall::where('batch_id', $latestBatchIds->first())
            ->get()
            ->keyBy('nama_tag');

        // 4. Ambil data dari batch sebelumnya (jika ada)
        $previousData = collect();
        if ($latestBatchIds->count() > 1) {
            $previousData = ScadaDataTall::where('batch_id', $latestBatchIds->get(1))
                ->get()
                ->keyBy('nama_tag');
        }

        // 5. Proses data untuk membuat struktur metrik yang lengkap
        $metrics = [];
        foreach ($latestData as $tag => $data) {
            // Pastikan nilai adalah numerik untuk kalkulasi
            if (!is_numeric($data->nilai_tag)) {
                continue; // Lewati tag jika nilainya bukan angka
            }

            $currentValue = (float) $data->nilai_tag;
            $previousValue = isset($previousData[$tag]) && is_numeric($previousData[$tag]->nilai_tag) ? (float) $previousData[$tag]->nilai_tag : null;

            $change = null;
            if (!is_null($previousValue) && $previousValue != 0) {
                $change = (($currentValue - $previousValue) / abs($previousValue)) * 100;
            }

            $metrics[$tag] = [
                'value' => $data->nilai_tag,
                'unit' => $units[$tag] ?? '-', // Ambil satuan dari map, atau default ke '-'
                'timestamp' => $data->timestamp_device,
                'change' => is_numeric($change) ? round($change, 1) : null,
                'change_class' => is_null($change) ? '' : ($change >= 0 ? 'positive' : 'negative'),
            ];
        }

        $lastPayloadInfo = $latestData->first();

        return [
            'metrics' => $metrics,
            'lastPayloadInfo' => $lastPayloadInfo
        ];
    }
    /**
     * FUNGSI BARU: Mengambil data historis yang sudah diagregasi untuk grafik.
     * (Logika dari HistoricalChart dipindahkan ke sini)
     * PERUBAHAN: Method baru untuk mengambil semua nama_tag yang unik.
     * Logika ini dipindahkan dari komponen Livewire ke sini.
     */
    public function getUniqueTags(): Collection
    {
        return ScadaDataTall::select('nama_tag')->distinct()->pluck('nama_tag');
    }

    /**
     * Mengambil data log untuk ditampilkan di halaman log-data
     */
    public function getLogData(int $limit = 50)
    {
        return ScadaDataTall::orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mengambil total jumlah records untuk pagination
     */
    public function getTotalRecords(): int
    {
        return ScadaDataTall::count();
    }

    public function getHistoricalChartData(array $tags, string $interval, ?string $startDateTime = null, ?string $endDateTime = null): array
    {
        if (empty($tags)) {
            Log::warning('No tags provided for historical chart data');
            // KEMBALIKAN STRUKTUR PLOTLY
            return ['data' => [], 'layout' => []];
        }

        // Menggunakan timestamp device persis seperti di database
        $start = null;
        $end = null;

        // 1. Logika penentuan rentang waktu (start & end) menggunakan timestamp device
        if (!$startDateTime && !$endDateTime) {
            $minTimestamp = ScadaDataTall::whereIn('nama_tag', $tags)->min('timestamp_device');
            $maxTimestamp = ScadaDataTall::whereIn('nama_tag', $tags)->max('timestamp_device');

            if (!$minTimestamp || !$maxTimestamp) {
                return ['data' => [], 'layout' => []];
            }
            $start = Carbon::parse($minTimestamp)->startOfDay();
            $end = Carbon::parse($maxTimestamp)->endOfDay();
        } else {
            if ($interval === 'second') {
                $end = $endDateTime ? Carbon::parse($endDateTime) : Carbon::now();
                $start = $startDateTime ? Carbon::parse($startDateTime) : $end->copy()->subHour();
            } else {
                $end = $endDateTime ? Carbon::parse($endDateTime)->endOfDay() : Carbon::now();
                $start = $startDateTime ? Carbon::parse($startDateTime)->startOfDay() : $end->copy()->subDay();
            }
        }

        // 2. Logika format SQL dan interval CarbonPeriod tetap sama.
        $sqlFormat = '';
        $carbonIntervalSpec = '';
        $phpDateFormat = '';
        switch ($interval) {
            case 'minute':
                $sqlFormat = '%Y-%m-%d %H:%i:00';
                $phpDateFormat = 'Y-m-d H:i:00';
                $carbonIntervalSpec = '1 minute';
                break;
            case 'day':
                $sqlFormat = '%Y-%m-%d';
                $phpDateFormat = 'Y-m-d';
                $carbonIntervalSpec = '1 day';
                break;
            case 'second':
                $sqlFormat = '%Y-%m-%d %H:%i:%s';
                $phpDateFormat = 'Y-m-d H:i:s';
                $carbonIntervalSpec = '1 second';
                if ($end->diffInSeconds($start) > 300) {
                    $start = $end->copy()->subSeconds(300);
                }
                break;
            case 'hour':
            default:
                $sqlFormat = '%Y-%m-%d %H:00:00';
                $phpDateFormat = 'Y-m-d H:00:00';
                $carbonIntervalSpec = '1 hour';
                break;
        }

        // PERUBAHAN UTAMA DIMULAI DI SINI
        $plotlyTraces = [];
        $colorPalette = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6f42c1', '#fd7e14'];

        // 4. Loop untuk setiap tag untuk membuat "trace" Plotly
        foreach ($tags as $key => $tag) {
            $existingData = ScadaDataTall::select(
                DB::raw("DATE_FORMAT(timestamp_device, '{$sqlFormat}') as time_group"),
                DB::raw('AVG(CAST(nilai_tag AS DECIMAL(10,2))) as avg_value')
            )->where('nama_tag', $tag)
                ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
                ->whereBetween('timestamp_device', [$start, $end])
                ->groupBy('time_group')
                ->orderBy('time_group', 'asc')
                ->get();

            // Hanya gunakan data yang benar-benar ada
            $labels = $existingData->pluck('time_group')->toArray();
            $values = $existingData->pluck('avg_value')->map(fn($val) => (float) $val)->toArray();

            if (empty($labels)) {
                continue; // Skip jika tidak ada data untuk tag ini
            }

            $borderColor = $colorPalette[$key % count($colorPalette)];

            // Buat struktur "trace" yang dimengerti Plotly.js
            $plotlyTraces[] = [
                'x' => $labels,
                'y' => $values,
                'type' => 'scatter',
                'mode' => 'lines+markers',
                'name' => $tag, // Nama metrik
                'line' => ['color' => $borderColor, 'width' => 2],
                'marker' => ['size' => 4],
                'hovertemplate' => '<b>%{x}</b><br>Value: %{y}<extra></extra>' // Custom tooltip
            ];
        }

        // 5. Buat object layout untuk styling grafik
        $layout = [
            'title' => 'Historical Data Analysis',
            'xaxis' => ['title' => 'Timestamp'],
            'yaxis' => ['title' => 'Value'],
            'margin' => ['l' => 50, 'r' => 20, 'b' => 40, 't' => 40],
            'paper_bgcolor' => '#ffffff',
            'plot_bgcolor' => '#ffffff',
            'hovermode' => 'x unified'
        ];

        // Log untuk debugging
        Log::info('Historical chart data generated', [
            'tags' => $tags,
            'interval' => $interval,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'traces_count' => count($plotlyTraces),
            'sample_trace' => $plotlyTraces[0] ?? null
        ]);

        return [
            'data' => $plotlyTraces,
            'layout' => $layout,
        ];
    }

    public function getLatestDataForTags(array $tags): ?array
    {
        try {
            // Get the latest batch ID first
            $latestRecord = ScadaDataTall::orderBy('timestamp_device', 'desc')->first();
            if (!$latestRecord) {
                Log::warning('No data found in ScadaDataTall table');
                return null;
            }

            // Get all data from the latest batch for the requested tags
            $latestBatchData = ScadaDataTall::where('batch_id', $latestRecord->batch_id)
                ->whereIn('nama_tag', $tags)
                ->get();

            if ($latestBatchData->isEmpty()) {
                Log::warning('No data found for requested tags in latest batch', ['tags' => $tags, 'batch_id' => $latestRecord->batch_id]);
                return null;
            }

            // Convert to array format expected by frontend
            $dataToDispatch = [
                'timestamp' => $latestRecord->timestamp_device,
                'metrics' => $latestBatchData->pluck('nilai_tag', 'nama_tag')->toArray(),
            ];

            Log::info('Latest data fetched successfully', [
                'batch_id' => $latestRecord->batch_id,
                'timestamp' => $latestRecord->timestamp_device,
                'metrics_count' => count($dataToDispatch['metrics']),
                'available_metrics' => array_keys($dataToDispatch['metrics'])
            ]);

            return $dataToDispatch;
        } catch (\Exception $e) {
            Log::error('Error fetching latest data for tags', [
                'error' => $e->getMessage(),
                'tags' => $tags
            ]);
            return null;
        }
    }

    /**
     * Mengambil titik data agregat terbaru berdasarkan interval yang dipilih.
     * Metode ini dirancang untuk pembaruan real-time yang cerdas.
     * PERUBAHAN: Mengembalikan 'time_group' sebagai timestamp utama.
     */
    public function getLatestAggregatedDataPoint(array $tags, string $interval): ?array
    {
        if (empty($tags)) return null;

        // Tentukan format SQL berdasarkan interval (logika ini tetap sama)
        $sqlFormat = match ($interval) {
            'minute' => '%Y-%m-%d %H:%i:00',
            'day' => '%Y-%m-%d',
            'second' => '%Y-%m-%d %H:%i:%s',
            default => '%Y-%m-%d %H:00:00',
        };

        $aggregatedData = [];
        $latestTimeGroup = null;

        foreach ($tags as $tag) {
            $latestAggregated = ScadaDataTall::select(
                DB::raw("DATE_FORMAT(timestamp_device, '{$sqlFormat}') as time_group"),
                DB::raw('AVG(CAST(nilai_tag AS DECIMAL(10,2))) as avg_value')
            )
                ->where('nama_tag', $tag)
                ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
                ->groupBy('time_group')
                ->orderBy('time_group', 'desc')
                ->first();

            if ($latestAggregated) {
                $aggregatedData[$tag] = (float) $latestAggregated->avg_value;
                $latestTimeGroup = $latestAggregated->time_group;
            }
        }

        if (empty($aggregatedData) || !$latestTimeGroup) return null;

        // KUNCI PERUBAHAN: Kirim 'time_group' sebagai timestamp.
        // Ini memastikan frontend tahu ember waktu mana yang sedang diupdate.
        Log::info('Latest aggregated data point', [
            'time_group' => $latestTimeGroup,
            'metrics' => $aggregatedData
        ]);

        return [
            'timestamp' => $latestTimeGroup,
            'metrics' => $aggregatedData,
        ];
    }
}
