<?php

namespace App\Services;

use App\Models\ScadaDataWide; // Ganti model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Carbon\CarbonPeriod;

class ScadaDataService
{
    /**
     * Memproses payload dan menyimpannya ke tabel 'scada_data_wides'.
     * Logika ini menjadi jauh lebih sederhana.
     */
    public function processScadaPayload(array $payload): void
    {
        try {
            DB::transaction(function () use ($payload) {
                collect($payload['DataArray'])->each(function ($dataGroup) {
                    // Siapkan data untuk satu baris
                    $dataToInsert = [
                        'batch_id'         => Str::uuid(),
                        'nama_group'       => $dataGroup['_groupTag'],
                        'timestamp_device' => $dataGroup['_terminalTime'],
                    ];

                    // Map setiap sensor ke kolomnya
                    foreach ($dataGroup as $key => $value) {
                        if (!str_starts_with($key, '_')) {
                            $dataToInsert[$key] = is_numeric($value) ? (float) $value : null;
                        }
                    }

                    // Simpan satu baris ke database
                    ScadaDataWide::create($dataToInsert);
                });
            });
        } catch (\Exception $e) {
            Log::error('SCADA Payload Processing Error (Wide Format): ' . $e->getMessage(), ['payload' => $payload]);
            throw $e;
        }
    }

    /**
     * Mengambil data yang sudah diproses untuk ditampilkan di dashboard.
     * Logika ini menjadi jauh lebih sederhana dengan format wide.
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

        // 2. Ambil dua record terakhir untuk perbandingan
        $latestRecords = ScadaDataWide::orderBy('timestamp_device', 'desc')->limit(2)->get();

        if ($latestRecords->isEmpty()) {
            return ['metrics' => [], 'lastPayloadInfo' => null];
        }

        $latestData = $latestRecords->first();
        $previousData = $latestRecords->count() > 1 ? $latestRecords->get(1) : null;

        // 3. Proses data untuk membuat struktur metrik yang lengkap
        $metrics = [];
        $sensorColumns = ['par_sensor', 'solar_radiation', 'wind_speed', 'wind_direction', 'temperature', 'humidity', 'pressure', 'rainfall'];

        foreach ($sensorColumns as $sensor) {
            $currentValue = $latestData->$sensor;

            if (is_null($currentValue)) {
                continue; // Lewati sensor jika nilainya null
            }

            $previousValue = $previousData ? $previousData->$sensor : null;

            $change = null;
            if (!is_null($previousValue) && $previousValue != 0) {
                $change = (($currentValue - $previousValue) / abs($previousValue)) * 100;
            }

            $metrics[$sensor] = [
                'value' => $currentValue,
                'unit' => $units[$sensor] ?? '-',
                'timestamp' => $latestData->timestamp_device,
                'change' => is_numeric($change) ? round($change, 1) : null,
                'change_class' => is_null($change) ? '' : ($change >= 0 ? 'positive' : 'negative'),
            ];
        }

        return [
            'metrics' => $metrics,
            'lastPayloadInfo' => $latestData
        ];
    }

    /**
     * Mengambil data historis untuk grafik dari tabel 'scada_data_wides'.
     * Query menjadi sangat sederhana dan cepat dengan agregasi berdasarkan interval.
     */
    public function getHistoricalChartData(array $tags, string $interval, ?string $startDateTime = null, ?string $endDateTime = null): array
    {
        if (empty($tags)) {
            return ['data' => [], 'layout' => []];
        }

        $start = Carbon::parse($startDateTime);
        $end = Carbon::parse($endDateTime);

        $query = ScadaDataWide::whereBetween('timestamp_device', [$start, $end]);

        // KUNCI PERBAIKAN: Lakukan agregasi berdasarkan interval
        if ($interval !== 'second') {
            // Tentukan format grouping berdasarkan interval
            $timeGroupFormat = match ($interval) {
                'minute' => '%Y-%m-%d %H:%i:00',
                'hour'   => '%Y-%m-%d %H:00:00',
                'day'    => '%Y-%m-%d 00:00:00',
                default  => '%Y-%m-%d %H:00:00', // Default ke jam
            };

            // Buat query dengan agregasi
            $queryResult = $query
                ->selectRaw("DATE_FORMAT(timestamp_device, '$timeGroupFormat') as timestamp_group")
                ->addSelect(DB::raw("AVG(temperature) as temperature"))
                ->addSelect(DB::raw("AVG(humidity) as humidity"))
                ->addSelect(DB::raw("AVG(pressure) as pressure"))
                ->addSelect(DB::raw("AVG(rainfall) as rainfall"))
                ->addSelect(DB::raw("AVG(wind_speed) as wind_speed"))
                ->addSelect(DB::raw("AVG(wind_direction) as wind_direction"))
                ->addSelect(DB::raw("AVG(par_sensor) as par_sensor"))
                ->addSelect(DB::raw("AVG(solar_radiation) as solar_radiation"))
                ->groupBy('timestamp_group')
                ->orderBy('timestamp_group', 'asc')
                ->get();
        } else {
            // Untuk interval 'second', ambil data mentah
            $columnsToSelect = array_merge(['timestamp_device'], $tags);
            $queryResult = $query
                ->select($columnsToSelect)
                ->orderBy('timestamp_device', 'asc')
                ->get();
        }

        if ($queryResult->isEmpty()) {
            return ['data' => [], 'layout' => []];
        }

        $plotlyTraces = [];
        $colorPalette = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6f42c1', '#fd7e14'];

        foreach ($tags as $key => $tag) {
            $labels = $queryResult->pluck($interval === 'second' ? 'timestamp_device' : 'timestamp_group')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d H:i:s');
            })->toArray();

            $values = $queryResult->pluck($tag)->toArray();

            $plotlyTraces[] = [
                'x' => $labels,
                'y' => $values,
                'type' => 'scatter',
                'mode' => 'lines+markers',
                'name' => ucfirst(str_replace('_', ' ', $tag)),
                'line' => ['color' => $colorPalette[$key % count($colorPalette)], 'width' => 2],
                'marker' => ['size' => 4],
                'connectgaps' => false,
            ];
        }

        $layout = [
            'title' => 'Historical Sensor Data',
            'xaxis' => ['title' => 'Timestamp'],
            'yaxis' => ['title' => 'Value'],
            'margin' => ['l' => 50, 'r' => 50, 'b' => 50, 't' => 50],
            'legend' => ['orientation' => 'h', 'y' => 1.1],
            'template' => 'plotly_dark',
        ];

        return ['data' => $plotlyTraces, 'layout' => $layout];
    }

    /**
     * Mengambil data terbaru untuk polling.
     */
    public function getLatestDataPoint(array $tags): ?array
    {
        if (empty($tags)) return null;

        $columnsToSelect = array_merge(['timestamp_device'], $tags);

        $latestData = ScadaDataWide::select($columnsToSelect)
            ->orderBy('timestamp_device', 'desc')
            ->first();

        if (!$latestData) return null;

        $metrics = [];
        foreach ($tags as $tag) {
            $metrics[$tag] = $latestData->$tag;
        }

        return [
            'timestamp' => $latestData->timestamp_device->format('Y-m-d H:i:s'),
            'metrics' => $metrics,
        ];
    }

    /**
     * Mengambil data log untuk ditampilkan di halaman log-data
     * Menggunakan format wide untuk efisiensi dan dapat menampilkan lebih banyak data
     */
    public function getLogData(int $limit = 50)
    {
        return ScadaDataWide::orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mengambil total jumlah records untuk pagination
     * Menghitung total records wide yang sebenarnya
     */
    public function getTotalRecords(): int
    {
        return ScadaDataWide::count();
    }

    /**
     * Mengambil semua data mentah (bukan agregasi) untuk tag tertentu
     * sejak timestamp terakhir yang diketahui.
     * Metode ini digunakan untuk "catch-up" data yang terlewat.
     */
    public function getRecentDataSince(array $tags, string $lastKnownTimestamp): array
    {
        if (empty($tags)) return [];

        $columnsToSelect = array_merge(['timestamp_device'], $tags);

        $data = ScadaDataWide::select($columnsToSelect)
            ->where('timestamp_device', '>', $lastKnownTimestamp)
            ->orderBy('timestamp_device', 'asc')
            ->get();

        // Mengelompokkan hasil berdasarkan tag
        $result = [];
        foreach ($tags as $tag) {
            $result[$tag] = $data->map(function ($item) use ($tag) {
                return [
                    'timestamp' => $item->timestamp_device->format('Y-m-d H:i:s'),
                    'value' => $item->$tag,
                ];
            })->filter(function ($item) {
                return !is_null($item['value']);
            })->values()->toArray();
        }

        return $result;
    }

    /**
     * Mengambil data terbaru untuk tag tertentu
     */
    public function getLatestDataForTags(array $tags): ?array
    {
        try {
            $columnsToSelect = array_merge(['timestamp_device'], $tags);

            $latestData = ScadaDataWide::select($columnsToSelect)
                ->orderBy('timestamp_device', 'desc')
                ->first();

            if (!$latestData) {
                Log::warning('No data found in ScadaDataWide table');
                return null;
            }

            $metrics = [];
            foreach ($tags as $tag) {
                $metrics[$tag] = $latestData->$tag;
            }

            $dataToDispatch = [
                'timestamp' => $latestData->timestamp_device,
                'metrics' => $metrics,
            ];

            Log::info('Latest data fetched successfully', [
                'timestamp' => $latestData->timestamp_device,
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
     */
    public function getLatestAggregatedDataPoint(array $tags, string $interval): ?array
    {
        if (empty($tags)) return null;

        // 1. Dapatkan timestamp data paling akhir di database untuk tahu "sekarang" itu kapan.
        $latestTimestampStr = ScadaDataWide::max('timestamp_device');
        if (!$latestTimestampStr) return null; // Tidak ada data sama sekali

        $latestTimestamp = Carbon::parse($latestTimestampStr);

        // 2. Jika intervalnya 'second', cukup kembalikan data mentah terbaru (logika lama).
        if ($interval === 'second') {
            $columnsToSelect = array_merge(['timestamp_device'], $tags);
            $latestData = ScadaDataWide::select($columnsToSelect)
                ->where('timestamp_device', $latestTimestamp)
                ->first();

            if (!$latestData) return null;

            $metrics = [];
            foreach ($tags as $tag) {
                $metrics[$tag] = $latestData->$tag;
            }
            return [
                'timestamp' => $latestData->timestamp_device->format('Y-m-d H:i:s'),
                'metrics' => $metrics,
            ];
        }

        // 3. Untuk interval lain (minute, hour, day), hitung agregat.
        // Tentukan awal dan akhir dari "wadah waktu" (time bucket) saat ini.
        $bucketStart = match ($interval) {
            'minute' => $latestTimestamp->copy()->startOfMinute(),
            'hour'   => $latestTimestamp->copy()->startOfHour(),
            'day'    => $latestTimestamp->copy()->startOfDay(),
        };
        $bucketEnd = match ($interval) {
            'minute' => $bucketStart->copy()->endOfMinute(),
            'hour'   => $bucketStart->copy()->endOfHour(),
            'day'    => $bucketStart->copy()->endOfDay(),
        };

        // 4. Bangun query agregasi untuk wadah waktu tersebut.
        $selects = [];
        foreach ($tags as $tag) {
            // Hitung rata-rata nilai untuk setiap tag.
            $selects[] = DB::raw("AVG(`$tag`) as `$tag`");
        }

        $aggregatedData = ScadaDataWide::select($selects)
            ->whereBetween('timestamp_device', [$bucketStart, $bucketEnd])
            ->first(); // Kita hanya ingin satu baris hasil agregasi

        if (!$aggregatedData) return null;

        $metrics = [];
        foreach ($tags as $tag) {
            $metrics[$tag] = (float) $aggregatedData->$tag;
        }

        // Timestamp yang dikembalikan adalah timestamp awal dari wadah waktu,
        // agar cocok dengan titik data di grafik.
        return [
            'timestamp' => $bucketStart->format('Y-m-d H:i:s'),
            'metrics' => $metrics,
        ];
    }

    /**
     * Mendapatkan daftar tag yang tersedia (statis karena skema wide)
     */
    public function getUniqueTags(): Collection
    {
        return collect([
            'par_sensor',
            'solar_radiation',
            'wind_speed',
            'wind_direction',
            'temperature',
            'humidity',
            'pressure',
            'rainfall'
        ]);
    }

    /**
     * Estimate the number of data points for a given time range
     */
    public function estimateDataPoints(string $tag, Carbon $start, Carbon $end): int
    {
        return ScadaDataWide::whereBetween('timestamp_device', [$start, $end])
            ->whereNotNull($tag)
            ->count();
    }

    /**
     * Get performance statistics for data processing
     */
    public function getPerformanceStats(array $tags, string $interval, Carbon $start, Carbon $end): array
    {
        $stats = [];

        foreach ($tags as $tag) {
            $totalPoints = $this->estimateDataPoints($tag, $start, $end);

            $stats[$tag] = [
                'total_points' => $totalPoints,
                'will_downsample' => false, // Tidak perlu downsampling dengan format wide
                'estimated_reduction' => 0,
                'final_points' => $totalPoints
            ];
        }

        return $stats;
    }
}
