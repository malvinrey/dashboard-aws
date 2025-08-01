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

    /**
     * Mengambil semua data mentah (bukan agregasi) untuk tag tertentu
     * sejak timestamp terakhir yang diketahui.
     * Metode ini digunakan untuk "catch-up" data yang terlewat.
     */
    public function getRecentDataSince(array $tags, string $lastKnownTimestamp): array
    {
        if (empty($tags)) return [];

        $data = ScadaDataTall::select('timestamp_device', 'nama_tag', 'nilai_tag')
            ->whereIn('nama_tag', $tags)
            ->where('timestamp_device', '>', $lastKnownTimestamp)
            ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
            ->orderBy('timestamp_device', 'asc')
            ->get();

        // Mengelompokkan hasil berdasarkan nama_tag
        return $data->groupBy('nama_tag')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'timestamp' => $item->timestamp_device->format('Y-m-d H:i:s'),
                    'value' => (float) $item->nilai_tag,
                ];
            });
        })->toArray();
    }

    public function getHistoricalChartData(array $tags, string $interval, ?string $startDateTime = null, ?string $endDateTime = null): array
    {
        if (empty($tags)) {
            Log::warning('No tags provided for historical chart data');
            // KEMBALIKAN STRUKTUR PLOTLY
            return ['data' => [], 'layout' => []];
        }

        // ===================================================================
        // KUNCI PERBAIKAN: Logika penentuan rentang waktu yang lebih cerdas
        // ===================================================================
        $start = null;
        $end = null;

        if ($startDateTime && $endDateTime) {
            // Jika intervalnya besar (jam/hari), bulatkan ke rentang hari penuh.
            if (in_array($interval, ['hour', 'day'])) {
                $start = Carbon::parse($startDateTime)->startOfDay();
                $end = Carbon::parse($endDateTime)->endOfDay();
            } elseif ($interval === 'minute') {
                // Untuk interval menit, gunakan rentang hari penuh dari tanggal yang diberikan
                $start = Carbon::parse($startDateTime)->startOfDay();
                $end = Carbon::parse($endDateTime)->endOfDay();
            } else {
                // Hanya untuk interval second, gunakan waktu presisi yang diberikan.
                $start = Carbon::parse($startDateTime);
                $end = Carbon::parse($endDateTime);
            }
        } else {
            // Logika fallback jika tidak ada tanggal yang disediakan
            $minTimestamp = ScadaDataTall::whereIn('nama_tag', $tags)->min('timestamp_device');
            $maxTimestamp = ScadaDataTall::whereIn('nama_tag', $tags)->max('timestamp_device');

            if (!$minTimestamp || !$maxTimestamp) {
                return ['data' => [], 'layout' => []];
            }
            $start = Carbon::parse($minTimestamp)->startOfDay();
            $end = Carbon::parse($maxTimestamp)->endOfDay();
        }

        // Pastikan start < end untuk menghindari range negatif
        if ($start->gte($end)) {
            Log::warning('Invalid time range detected in service, swapping start and end', [
                'original_start' => $start->toDateTimeString(),
                'original_end' => $end->toDateTimeString()
            ]);
            // Swap start dan end jika start >= end
            $temp = $start;
            $start = $end;
            $end = $temp;
        }

        // Debug log untuk time range calculation
        Log::info('Smart time range calculation', [
            'interval' => $interval,
            'input_startDateTime' => $startDateTime,
            'input_endDateTime' => $endDateTime,
            'calculated_start' => $start->toDateTimeString(),
            'calculated_end' => $end->toDateTimeString(),
            'time_range_seconds' => $end->diffInSeconds($start),
            'is_precise_range' => in_array($interval, ['minute', 'second'])
        ]);

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
                $sqlFormat = '';
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
            if ($interval === 'second') {
                // ===================================================================
                // KODE BARU YANG LEBIH CERDAS UNTUK INTERVAL DETIK
                // ===================================================================

                // 1. Ambil HANYA data mentah yang benar-benar ada di database
                // Optimasi: Gunakan chunking untuk dataset yang sangat besar
                $maxBatchSize = config('scada.performance.max_batch_size', 1000);
                $rawData = collect();

                ScadaDataTall::select('timestamp_device', 'nilai_tag')
                    ->where('nama_tag', $tag)
                    ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
                    ->whereBetween('timestamp_device', [$start, $end])
                    ->orderBy('timestamp_device', 'asc')
                    ->chunk($maxBatchSize, function ($chunk) use (&$rawData) {
                        $rawData = $rawData->merge($chunk);
                    });

                if ($rawData->isEmpty()) {
                    continue; // Lanjut ke tag berikutnya jika tidak ada data sama sekali
                }

                // --- PERUBAHAN DI SINI ---
                // Ubah data mentah ke format yang lebih sederhana untuk downsampling
                $processedData = $rawData->map(function ($item) {
                    return ['timestamp' => $item->timestamp_device, 'value' => (float)$item->nilai_tag];
                });

                // Terapkan downsampling jika data terlalu banyak
                $originalCount = $processedData->count();
                $maxPoints = config('scada.downsampling.max_points_per_series', 1000);
                $minThreshold = config('scada.downsampling.min_points_threshold', 1000);
                $downsamplingEnabled = config('scada.downsampling.enabled', true);

                if ($downsamplingEnabled && $originalCount > $minThreshold) {
                    $downsampledPoints = $this->downsampleData($processedData, $maxPoints);
                    $downsampledCount = count($downsampledPoints);

                    // Log downsampling performance
                    if (config('scada.processing.enable_logging', true)) {
                        Log::info('Data downsampling applied', [
                            'tag' => $tag,
                            'original_points' => $originalCount,
                            'downsampled_points' => $downsampledCount,
                            'reduction_percentage' => round((($originalCount - $downsampledCount) / $originalCount) * 100, 2)
                        ]);
                    }
                } else {
                    // Jika downsampling tidak diperlukan, gunakan data asli
                    $downsampledPoints = $processedData->map(fn($item) => [
                        $item['timestamp']->getTimestamp() * 1000,
                        $item['value']
                    ])->all();
                    $downsampledCount = $originalCount;
                }

                // Konversi kembali ke format label & value yang dibutuhkan Plotly
                $labels = [];
                $values = [];
                foreach ($downsampledPoints as $point) {
                    // Konversi UNIX timestamp (ms) kembali ke format tanggal ISO untuk Plotly
                    $labels[] = Carbon::createFromTimestampMs($point[0])->format('Y-m-d H:i:s');
                    $values[] = $point[1];
                }
                // --- AKHIR PERUBAHAN ---

            } else {
                // ===================================================================
                // KODE BARU UNTUK MENYISIPKAN NULL
                // ===================================================================

                // 1. Ambil data yang ada dari database, kelompokkan berdasarkan waktu
                $aggregatedData = ScadaDataTall::select(
                    DB::raw("DATE_FORMAT(timestamp_device, '{$sqlFormat}') as time_group"),
                    DB::raw('AVG(CAST(nilai_tag AS DECIMAL(10,2))) as avg_value')
                )->where('nama_tag', $tag)
                    ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
                    ->whereBetween('timestamp_device', [$start, $end])
                    ->groupBy('time_group')
                    ->orderBy('time_group', 'asc')
                    ->get()
                    ->keyBy('time_group'); // Gunakan keyBy untuk pencarian cepat

                // 2. Buat rentang waktu lengkap dari awal hingga akhir
                // PERBAIKAN: Untuk interval day, gunakan pendekatan yang berbeda
                if ($interval === 'day') {
                    // Untuk day interval, buat period berdasarkan hari saja
                    $periodStart = $start->copy()->startOfDay();
                    $periodEnd = $end->copy()->startOfDay();
                    $period = CarbonPeriod::create($periodStart, '1 day', $periodEnd);
                } else {
                    // Untuk interval lain, gunakan CarbonPeriod seperti biasa
                    $period = CarbonPeriod::create($start, $carbonIntervalSpec, $end);
                }

                // Inisialisasi array kosong untuk hasil akhir
                $labels = [];
                $values = [];

                // 3. Loop melalui setiap "slot" waktu, isi dengan data atau null
                foreach ($period as $date) {
                    $formattedDate = $date->format($phpDateFormat);
                    $labels[] = $formattedDate;
                    // Jika data untuk waktu ini ada, gunakan nilainya. Jika tidak, masukkan null.
                    $values[] = $aggregatedData->get($formattedDate)?->avg_value ?? null;
                }
            }

            if (empty($labels)) {
                continue; // Skip jika tidak ada data untuk tag ini
            }

            $borderColor = $colorPalette[$key % count($colorPalette)];

            // Tambahkan 'connectgaps: false' untuk memastikan grafik terputus
            $plotlyTraces[] = [
                'x' => $labels,
                'y' => $values,
                'type' => 'scatter',
                'mode' => 'lines+markers',
                'name' => $tag,
                'line' => ['color' => $borderColor, 'width' => 2],
                'marker' => [
                    'color' => $borderColor,
                    'size' => 6, // Atur ukuran titik menjadi 6 piksel
                    'symbol' => 'circle'
                ],
                'connectgaps' => false,
                'hovertemplate' => '<b>%{x}</b><br>Value: %{y}<extra></extra>'
            ];
        }

        // 5. Buat object layout untuk styling grafik
        $layout = [
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
            'sample_trace' => $plotlyTraces[0] ?? null,
            'is_second_interval' => $interval === 'second'
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

        $aggregatedData = [];
        $latestTimeGroup = null;

        foreach ($tags as $tag) {
            if ($interval === 'second') {
                // Untuk interval second, ambil data terbaru tanpa grouping
                $latestData = ScadaDataTall::select(
                    'timestamp_device',
                    'nilai_tag'
                )
                    ->where('nama_tag', $tag)
                    ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
                    ->orderBy('timestamp_device', 'desc')
                    ->first();

                if ($latestData) {
                    $aggregatedData[$tag] = (float) $latestData->nilai_tag;
                    $latestTimeGroup = $latestData->timestamp_device->format('Y-m-d H:i:s');
                }
            } else {
                // Untuk interval lain, gunakan grouping seperti biasa
                $sqlFormat = match ($interval) {
                    'minute' => '%Y-%m-%d %H:%i:00',
                    'day' => '%Y-%m-%d',
                    default => '%Y-%m-%d %H:00:00',
                };

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
        }

        if (empty($aggregatedData) || !$latestTimeGroup) return null;

        // KUNCI PERUBAHAN: Kirim 'time_group' sebagai timestamp.
        // Ini memastikan frontend tahu ember waktu mana yang sedang diupdate.
        Log::info('Latest aggregated data point', [
            'time_group' => $latestTimeGroup,
            'metrics' => $aggregatedData,
            'interval' => $interval
        ]);

        return [
            'timestamp' => $latestTimeGroup,
            'metrics' => $aggregatedData,
        ];
    }

    /**
     * Menyederhanakan kumpulan data besar menggunakan algoritma LTTB.
     *
     * @param Collection $data Koleksi data dengan objek ['timestamp' => Carbon, 'value' => float]
     * @param int $threshold Jumlah titik data yang diinginkan
     * @return array Data yang telah disederhanakan
     */
    public function downsampleData(Collection $data, int $threshold): array
    {
        if ($data->count() <= $threshold) {
            return $data->map(fn($item) => [
                $item['timestamp']->getTimestamp() * 1000, // LTTB butuh UNIX timestamp (ms)
                $item['value']
            ])->all();
        }

        // Ubah data ke format yang dibutuhkan oleh LTTB: [[timestamp, value], ...]
        $formattedData = $data->map(fn($item) => [
            $item['timestamp']->getTimestamp() * 1000,
            $item['value']
        ])->all();

        // Lakukan downsampling menggunakan algoritma LTTB
        return $this->lttbDownsample($formattedData, $threshold);
    }

    /**
     * Implementasi algoritma Largest-Triangle-Three-Buckets (LTTB)
     *
     * @param array $data Array of [timestamp, value] pairs
     * @param int $threshold Target number of points
     * @return array Downsampled data
     */
    private function lttbDownsample(array $data, int $threshold): array
    {
        $dataLength = count($data);

        if ($threshold >= $dataLength || $threshold <= 2) {
            return $data;
        }

        $sampled = [];
        $sampled[0] = $data[0]; // Always include the first point

        $bucketSize = ($dataLength - 2) / ($threshold - 2);
        $a = 0; // Index of the first point in the current bucket

        for ($i = 0; $i < $threshold - 2; $i++) {
            $bucketStart = floor(($i + 1) * $bucketSize) + 1;
            $bucketEnd = floor(($i + 2) * $bucketSize) + 1;

            if ($bucketEnd >= $dataLength) {
                $bucketEnd = $dataLength - 1;
            }

            // Find the point with the largest triangle area
            $maxArea = -1;
            $maxAreaIndex = $bucketStart;

            for ($j = $bucketStart; $j < $bucketEnd; $j++) {
                $area = $this->triangleArea($data[$a], $data[$j], $data[$bucketEnd]);
                if ($area > $maxArea) {
                    $maxArea = $area;
                    $maxAreaIndex = $j;
                }
            }

            $sampled[] = $data[$maxAreaIndex];
            $a = $maxAreaIndex;
        }

        $sampled[] = $data[$dataLength - 1]; // Always include the last point

        return $sampled;
    }

    /**
     * Calculate the area of a triangle formed by three points
     *
     * @param array $a First point [timestamp, value]
     * @param array $b Second point [timestamp, value]
     * @param array $c Third point [timestamp, value]
     * @return float Triangle area
     */
    public function triangleArea(array $a, array $b, array $c): float
    {
        return abs(($b[0] - $a[0]) * ($c[1] - $a[1]) - ($c[0] - $a[0]) * ($b[1] - $a[1])) / 2;
    }

    /**
     * Estimate the number of data points for a given time range and tag
     *
     * @param string $tag The tag name
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @return int Estimated number of data points
     */
    public function estimateDataPoints(string $tag, Carbon $start, Carbon $end): int
    {
        return ScadaDataTall::where('nama_tag', $tag)
            ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
            ->whereBetween('timestamp_device', [$start, $end])
            ->count();
    }

    /**
     * Get performance statistics for data processing
     *
     * @param array $tags Array of tag names
     * @param string $interval Time interval
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @return array Performance statistics
     */
    public function getPerformanceStats(array $tags, string $interval, Carbon $start, Carbon $end): array
    {
        $stats = [];

        foreach ($tags as $tag) {
            $totalPoints = $this->estimateDataPoints($tag, $start, $end);
            $maxPoints = config('scada.downsampling.max_points_per_series', 1000);
            $downsamplingEnabled = config('scada.downsampling.enabled', true);

            $stats[$tag] = [
                'total_points' => $totalPoints,
                'will_downsample' => $downsamplingEnabled && $totalPoints > $maxPoints,
                'estimated_reduction' => $downsamplingEnabled && $totalPoints > $maxPoints
                    ? round((($totalPoints - $maxPoints) / $totalPoints) * 100, 2)
                    : 0,
                'final_points' => $downsamplingEnabled && $totalPoints > $maxPoints ? $maxPoints : $totalPoints
            ];
        }

        return $stats;
    }
}
