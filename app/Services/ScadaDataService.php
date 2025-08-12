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
                $insertData = [];
                $now = now(); // Gunakan waktu yang sama untuk semua record dalam satu batch

                foreach ($payload['DataArray'] as $dataGroup) {
                    // Siapkan data untuk satu baris
                    $dataToInsert = [
                        'batch_id'         => Str::uuid(),
                        'nama_group'       => $dataGroup['_groupTag'],
                        'timestamp_device' => $dataGroup['_terminalTime'],
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];

                    // Map setiap sensor ke kolomnya
                    foreach ($dataGroup as $key => $value) {
                        if (!str_starts_with($key, '_')) {
                            $dataToInsert[$key] = is_numeric($value) ? (float) $value : null;
                        }
                    }

                    $insertData[] = $dataToInsert;
                }

                // Lakukan satu kali query untuk semua data (BULK INSERT)
                if (!empty($insertData)) {
                    DB::table('scada_data_wides')->insert($insertData);
                }
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
        try {
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

            // 2. Optimasi query: Ambil hanya kolom yang diperlukan
            $latestRecords = ScadaDataWide::select([
                'id',
                'timestamp_device',
                'nama_group',
                'par_sensor',
                'solar_radiation',
                'wind_speed',
                'wind_direction',
                'temperature',
                'humidity',
                'pressure',
                'rainfall'
            ])
                ->orderBy('timestamp_device', 'desc')
                ->limit(2)
                ->get();

            if ($latestRecords->isEmpty()) {
                return [
                    'metrics' => [],
                    'lastPayloadInfo' => null,
                    'error' => 'No data available'
                ];
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

            // 4. Tambahkan informasi performa dengan struktur yang konsisten
            $lastPayloadInfo = [
                'id' => $latestData->id,
                'timestamp' => $latestData->timestamp_device,
                'group' => $latestData->nama_group,
                'data_age_seconds' => now()->diffInSeconds($latestData->timestamp_device),
                'is_recent' => now()->diffInMinutes($latestData->timestamp_device) <= 5,
                'batch_id' => $latestData->batch_id ?? 'N/A'
            ];

            return [
                'metrics' => $metrics,
                'lastPayloadInfo' => $lastPayloadInfo
            ];
        } catch (\Exception $e) {
            Log::error('Error in getDashboardMetrics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'metrics' => [],
                'lastPayloadInfo' => null,
                'error' => 'Failed to load dashboard data: ' . $e->getMessage()
            ];
        }
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

        try {
            $start = Carbon::parse($startDateTime);
            $end = Carbon::parse($endDateTime);

            // Validasi range waktu (maksimal 30 hari untuk performa)
            if ($start->diffInDays($end) > 30) {
                $end = $start->copy()->addDays(30);
                Log::warning('Date range too large, limiting to 30 days', [
                    'original_start' => $startDateTime,
                    'original_end' => $endDateTime,
                    'adjusted_end' => $end->format('Y-m-d H:i:s')
                ]);
            }

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

                // Buat query dengan agregasi - PERBAIKAN: Gunakan select() dan addSelect() yang benar
                $query = $query->selectRaw("DATE_FORMAT(timestamp_device, '$timeGroupFormat') as timestamp_group");

                // Hanya select kolom yang diminta dengan addSelect
                foreach ($tags as $tag) {
                    if (in_array($tag, ['temperature', 'humidity', 'pressure', 'rainfall', 'wind_speed', 'wind_direction', 'par_sensor', 'solar_radiation'])) {
                        $query->addSelect(DB::raw("AVG($tag) as $tag"));
                    }
                }

                $queryResult = $query
                    ->groupBy('timestamp_group')
                    ->orderBy('timestamp_group', 'asc')
                    ->get();
            } else {
                // Untuk interval 'second', ambil data mentah dengan optimasi
                $columnsToSelect = array_merge(['timestamp_device'], array_intersect($tags, [
                    'temperature',
                    'humidity',
                    'pressure',
                    'rainfall',
                    'wind_speed',
                    'wind_direction',
                    'par_sensor',
                    'solar_radiation'
                ]));

                $queryResult = $query
                    ->select($columnsToSelect)
                    ->orderBy('timestamp_device', 'asc')
                    ->limit(1000) // Batasi data mentah untuk performa
                    ->get();
            }

            if ($queryResult->isEmpty()) {
                return ['data' => [], 'layout' => []];
            }

            $plotlyTraces = [];
            $colorPalette = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6f42c1', '#fd7e14'];

            foreach ($tags as $key => $tag) {
                // Validasi tag sebelum diproses
                if (!in_array($tag, ['temperature', 'humidity', 'pressure', 'rainfall', 'wind_speed', 'wind_direction', 'par_sensor', 'solar_radiation'])) {
                    continue;
                }

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
        } catch (\Exception $e) {
            Log::error('Error fetching historical chart data', [
                'error' => $e->getMessage(),
                'tags' => $tags,
                'interval' => $interval,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime
            ]);

            return ['data' => [], 'layout' => [], 'error' => 'Failed to fetch chart data'];
        }
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
    public function getLogData(int $limit = 50, int $page = 1)
    {
        return ScadaDataWide::orderBy('id', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Mengambil data log dengan filter tanggal, pencarian, dan pengurutan
     * Menggunakan pagination untuk performa yang lebih baik
     */
    public function getLogDataWithFilters(int $limit = 50, string $startDate = '', string $endDate = '', string $search = '', string $sortField = 'id', string $sortDirection = 'desc', int $page = 1)
    {
        $query = ScadaDataWide::query();

        // Apply date filters
        if (!empty($startDate)) {
            $query->whereDate('timestamp_device', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->whereDate('timestamp_device', '<=', $endDate);
        }

        // Apply search filter - Optimized with proper indexing consideration
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('nama_group', 'LIKE', "%{$search}%")
                    ->orWhere('par_sensor', 'LIKE', "%{$search}%")
                    ->orWhere('solar_radiation', 'LIKE', "%{$search}%")
                    ->orWhere('wind_speed', 'LIKE', "%{$search}%")
                    ->orWhere('wind_direction', 'LIKE', "%{$search}%")
                    ->orWhere('temperature', 'LIKE', "%{$search}%")
                    ->orWhere('humidity', 'LIKE', "%{$search}%")
                    ->orWhere('pressure', 'LIKE', "%{$search}%")
                    ->orWhere('rainfall', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting with validation
        $allowedSortFields = ['id', 'timestamp_device', 'nama_group', 'temperature', 'humidity', 'pressure', 'rainfall'];
        $allowedSortDirections = ['asc', 'desc'];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }
        if (!in_array($sortDirection, $allowedSortDirections)) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($limit, ['*'], 'page', $page);
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
     * Mengambil total jumlah records dengan filter untuk pagination
     */
    public function getTotalRecordsWithFilters(string $startDate = '', string $endDate = '', string $search = ''): int
    {
        $query = ScadaDataWide::query();

        // Apply date filters
        if (!empty($startDate)) {
            $query->whereDate('timestamp_device', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->whereDate('timestamp_device', '<=', $endDate);
        }

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('nama_group', 'LIKE', "%{$search}%")
                    ->orWhere('par_sensor', 'LIKE', "%{$search}%")
                    ->orWhere('solar_radiation', 'LIKE', "%{$search}%")
                    ->orWhere('wind_speed', 'LIKE', "%{$search}%")
                    ->orWhere('wind_direction', 'LIKE', "%{$search}%")
                    ->orWhere('temperature', 'LIKE', "%{$search}%")
                    ->orWhere('humidity', 'LIKE', "%{$search}%")
                    ->orWhere('pressure', 'LIKE', "%{$search}%")
                    ->orWhere('rainfall', 'LIKE', "%{$search}%");
            });
        }

        return $query->count();
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

    /**
     * Get database performance metrics and health status
     */
    public function getDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);

            // Check table size
            $tableSize = DB::select("
                SELECT
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB'
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name = 'scada_data_wides'
            ");

            // Check record count
            $totalRecords = ScadaDataWide::count();

            // Check latest data age
            $latestRecord = ScadaDataWide::select('timestamp_device')->orderBy('timestamp_device', 'desc')->first();
            $dataAge = $latestRecord ? now()->diffInMinutes($latestRecord->timestamp_device) : null;

            // Check data insertion rate (last hour)
            $lastHourCount = ScadaDataWide::where('created_at', '>=', now()->subHour())->count();

            $endTime = microtime(true);
            $queryTime = round(($endTime - $startTime) * 1000, 2);

            return [
                'status' => 'healthy',
                'table_size_mb' => $tableSize[0]->Size_MB ?? 0,
                'total_records' => $totalRecords,
                'latest_data_age_minutes' => $dataAge,
                'insertion_rate_last_hour' => $lastHourCount,
                'health_check_time_ms' => $queryTime,
                'last_check' => now()->format('Y-m-d H:i:s'),
                'recommendations' => $this->getHealthRecommendations($totalRecords, $dataAge, $lastHourCount)
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->format('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Get health recommendations based on metrics
     */
    private function getHealthRecommendations(int $totalRecords, ?int $dataAge, int $lastHourCount): array
    {
        $recommendations = [];

        if ($totalRecords > 1000000) {
            $recommendations[] = 'Consider implementing data archiving for records older than 1 year';
        }

        if ($dataAge > 60) {
            $recommendations[] = 'Data is more than 1 hour old. Check data source connectivity';
        }

        if ($lastHourCount > 10000) {
            $recommendations[] = 'High insertion rate detected. Monitor database performance';
        }

        if ($lastHourCount === 0) {
            $recommendations[] = 'No new data in the last hour. Investigate data pipeline';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'All systems operating normally';
        }

        return $recommendations;
    }

    /**
     * Optimize database queries with chunking for large datasets
     */
    public function processLargeDataset(array $payload, int $chunkSize = 1000): void
    {
        try {
            $dataArray = $payload['DataArray'];
            $totalChunks = ceil(count($dataArray) / $chunkSize);

            Log::info('Processing large dataset with chunking', [
                'total_records' => count($dataArray),
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks
            ]);

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunk = array_slice($dataArray, $i * $chunkSize, $chunkSize);

                DB::transaction(function () use ($chunk) {
                    $insertData = [];
                    $now = now();

                    foreach ($chunk as $dataGroup) {
                        $dataToInsert = [
                            'batch_id'         => Str::uuid(),
                            'nama_group'       => $dataGroup['_groupTag'],
                            'timestamp_device' => $dataGroup['_terminalTime'],
                            'created_at'       => $now,
                            'updated_at'       => $now,
                        ];

                        foreach ($dataGroup as $key => $value) {
                            if (!str_starts_with($key, '_')) {
                                $dataToInsert[$key] = is_numeric($value) ? (float) $value : null;
                            }
                        }

                        $insertData[] = $dataToInsert;
                    }

                    if (!empty($insertData)) {
                        DB::table('scada_data_wides')->insert($insertData);
                    }
                });

                Log::info('Chunk processed successfully', [
                    'chunk_number' => $i + 1,
                    'chunk_size' => count($chunk),
                    'progress' => round((($i + 1) / $totalChunks) * 100, 2) . '%'
                ]);
            }

            Log::info('Large dataset processing completed successfully', [
                'total_records' => count($dataArray),
                'total_chunks' => $totalChunks
            ]);
        } catch (\Exception $e) {
            Log::error('Large dataset processing failed', [
                'error' => $e->getMessage(),
                'payload_size' => count($payload['DataArray'] ?? [])
            ]);
            throw $e;
        }
    }
}
