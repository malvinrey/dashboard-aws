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
     */
    /**
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
            return ['labels' => [], 'datasets' => []];
        }

        $appTimezone = config('app.timezone');
        $start = null;
        $end = null;

        // 1. Tentukan rentang waktu. Gunakan 24 jam terakhir jika tidak ditentukan.
        if (!$startDateTime && !$endDateTime) {
            // KASUS 1: Tidak ada filter tanggal, cari rentang data keseluruhan.
            $minTimestamp = ScadaDataTall::whereIn('nama_tag', $tags)->min('timestamp_device');
            $maxTimestamp = ScadaDataTall::whereIn('nama_tag', $tags)->max('timestamp_device');

            // Jika tidak ada data sama sekali untuk tag ini, kembalikan array kosong.
            if (!$minTimestamp || !$maxTimestamp) {
                return ['labels' => [], 'datasets' => []];
            }

            $start = Carbon::parse($minTimestamp, $appTimezone)->startOfDay();
            $end = Carbon::parse($maxTimestamp, $appTimezone)->endOfDay();
        } else {
            // KASUS 2: Filter tanggal diterapkan, gunakan logika yang ada.
            // Memberikan fallback default jika hanya salah satu tanggal yang diisi.
            $end = $endDateTime ? Carbon::parse($endDateTime, $appTimezone)->endOfDay() : Carbon::now($appTimezone);
            $start = $startDateTime ? Carbon::parse($startDateTime, $appTimezone)->startOfDay() : $end->copy()->subDay();
        }

        // 2. Tentukan format untuk SQL dan interval untuk CarbonPeriod
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
                break;
            case 'hour':
            default:
                $sqlFormat = '%Y-%m-%d %H:00:00';
                $phpDateFormat = 'Y-m-d H:00:00';
                $carbonIntervalSpec = '1 hour';
                break;
        }

        // 3. Buat rangkaian waktu yang LENGKAP dari awal hingga akhir
        $period = CarbonPeriod::create($start, $carbonIntervalSpec, $end);

        $labels = [];
        foreach ($period as $date) {
            // Format tanggal agar cocok dengan kunci dari $existingData
            $formattedDate = $date->format($phpDateFormat);
            $labels[] = $formattedDate;
        }

        // Siapkan array untuk menampung semua dataset
        $datasets = [];
        // Definisikan palet warna untuk setiap garis grafik
        $colorPalette = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6f42c1', '#fd7e14'];

        // 4. Loop untuk setiap tag yang dipilih
        foreach ($tags as $key => $tag) {
            $existingData = ScadaDataTall::select(
                DB::raw("DATE_FORMAT(timestamp_device, '{$sqlFormat}') as time_group"),
                DB::raw('AVG(CAST(nilai_tag AS DECIMAL(10,2))) as avg_value')
            )->where('nama_tag', $tag)
                ->where('nilai_tag', 'REGEXP', '^[0-9.-]+$')
                ->whereBetween('timestamp_device', [$start, $end]) // Filter berdasarkan rentang waktu yang sudah ditentukan
                ->groupBy('time_group')
                ->orderBy('time_group', 'asc')
                ->get()
                ->keyBy('time_group'); // Kunci array dengan time_group agar mudah dicari

            $values = [];
            foreach ($labels as $label) {
                if (isset($existingData[$label])) {
                    $values[] = (float) $existingData[$label]->avg_value;
                } else {
                    $values[] = null;
                }
            }

            // Tentukan warna untuk dataset ini, berputar jika tag lebih banyak dari warna
            $borderColor = $colorPalette[$key % count($colorPalette)];

            // Buat struktur dataset yang dimengerti Chart.js
            $datasets[] = [
                'label' => $tag,
                'data' => $values,
                'borderColor' => $borderColor,
                'backgroundColor' => $borderColor . '1A', // Tambahkan transparansi untuk area fill
                'fill' => true,
                'tension' => 0.1,
            ];
        }
        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
    public function getLatestDataForTags(array $tags): ?array
    {
        $latestRecord = ScadaDataTall::orderBy('timestamp_device', 'desc')->first();
        if (!$latestRecord) return null;

        $latestBatchData = ScadaDataTall::where('batch_id', $latestRecord->batch_id)
            ->whereIn('nama_tag', $tags)
            ->get();

        if ($latestBatchData->isEmpty()) return null;

        $dataToDispatch = [
            'timestamp' => $latestRecord->timestamp_device,
            'metrics' => $latestBatchData->pluck('nilai_tag', 'nama_tag')->toArray(),
        ];

        return $dataToDispatch;
    }
}
