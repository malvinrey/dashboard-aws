# Perbaikan Agregasi Berdasarkan Interval

## Ringkasan Masalah

Sebelumnya, aplikasi tidak melakukan agregasi data berdasarkan interval waktu yang dipilih (Hour, Day, Minute). Ia selalu mengambil semua data mentah (yang tercatat per detik) dalam rentang waktu yang diberikan, sehingga grafiknya selalu tampak seperti dalam format "Second".

## Analisis Masalah

### 1. **Tidak Ada Agregasi di Service Layer**

-   Metode `getHistoricalChartData` menerima parameter `$interval`, tetapi tidak pernah menggunakannya
-   Query hanya mengambil semua baris data mentah antara `startDate` dan `endDate`
-   Tidak ada GROUP BY atau agregasi berdasarkan interval

### 2. **Rentang Tanggal Tidak Disesuaikan Otomatis**

-   Saat interval diubah, hanya properti `$interval` yang berubah
-   Properti `$startDate` dan `$endDate` tidak ikut berubah
-   Pengalaman pengguna kurang intuitif

## Solusi yang Diterapkan

### Langkah 1: Implementasi Agregasi di ScadaDataService.php

**Sebelum (Tidak Ada Agregasi):**

```php
public function getHistoricalChartData(array $tags, string $interval, ?string $startDateTime = null, ?string $endDateTime = null): array
{
    // Query sederhana tanpa agregasi
    $queryResult = ScadaDataWide::select($columnsToSelect)
        ->whereBetween('timestamp_device', [$start, $end])
        ->orderBy('timestamp_device', 'asc')
        ->get();
}
```

**Sesudah (Dengan Agregasi):**

```php
public function getHistoricalChartData(array $tags, string $interval, ?string $startDateTime = null, ?string $endDateTime = null): array
{
    $query = ScadaDataWide::whereBetween('timestamp_device', [$start, $end]);

    // KUNCI PERBAIKAN: Lakukan agregasi berdasarkan interval
    if ($interval !== 'second') {
        // Tentukan format grouping berdasarkan interval
        $timeGroupFormat = match ($interval) {
            'minute' => '%Y-%m-%d %H:%i:00',
            'hour'   => '%Y-%m-%d %H:00:00',
            'day'    => '%Y-%m-%d 00:00:00',
            default  => '%Y-%m-%d %H:00:00',
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
}
```

### Langkah 2: Auto-Adjustment Rentang Tanggal

**Tambahan di AnalysisChart.php:**

```php
/**
 * Livewire lifecycle hook.
 * Dijalankan SETIAP KALI properti $interval diubah.
 */
public function updatedInterval(string $value): void
{
    // Atur rentang tanggal default yang sesuai dengan interval baru
    switch ($value) {
        case 'hour':
            $this->startDate = now()->subDay()->toDateString();
            $this->endDate = now()->toDateString();
            break;
        case 'day':
            $this->startDate = now()->subDays(30)->toDateString();
            $this->endDate = now()->toDateString();
            break;
        case 'minute':
            $this->startDate = now()->subHour()->toDateString();
            $this->endDate = now()->toDateString();
            break;
        case 'second':
            $this->startDate = now()->subMinutes(30)->toDateTimeString();
            $this->endDate = now()->toDateTimeString();
            break;
    }

    // Nonaktifkan real-time karena kita memuat data historis berdasarkan interval baru
    $this->realtimeEnabled = false;

    // Panggil metode untuk memuat ulang data grafik dengan pengaturan baru
    $this->loadChartData();
}
```

## Hasil Test

### **Data Availability:**

```
- Total records in database: 127
- First record: 2025-08-03 11:53:14
- Last record: 2025-08-03 12:09:36
- Date range: 2025-08-02 11:53:14 to 2025-08-03 12:09:36
```

### **Interval Performance:**

#### **1. Second Interval (Raw Data):**

```
- Execution time: 20.76ms
- Chart traces: 2
- Trace 'Temperature': 127 data points
- Trace 'Humidity': 127 data points
```

#### **2. Minute Interval (Aggregated):**

```
- Execution time: 5.89ms
- Chart traces: 2
- Trace 'Temperature': 17 data points
- Trace 'Humidity': 17 data points
- Sample timestamps: 2025-08-03 11:53:00, 2025-08-03 11:54:00, 2025-08-03 11:55:00
```

#### **3. Hour Interval (Aggregated):**

```
- Execution time: 2.69ms
- Chart traces: 2
- Trace 'Temperature': 2 data points
- Trace 'Humidity': 2 data points
- Sample timestamps: 2025-08-03 11:00:00, 2025-08-03 12:00:00
```

#### **4. Day Interval (Aggregated):**

```
- Execution time: 3.85ms
- Chart traces: 2
- Trace 'Temperature': 1 data points
- Trace 'Humidity': 1 data points
- Sample timestamps: 2025-08-03 00:00:00
```

### **Performance Ranking:**

```
1. hour: 1.99ms, 4 points, 2.01 points/ms
2. day: 2.44ms, 2 points, 0.82 points/ms
3. minute: 5.89ms, 34 points, 5.77 points/ms
4. second: 20.76ms, 254 points, 12.23 points/ms
```

## Keuntungan Perbaikan

### **1. Performance**

-   **Query lebih efisien**: Agregasi di database lebih cepat daripada di aplikasi
-   **Data reduction**: Dari 127 data points menjadi 17 (minute), 2 (hour), 1 (day)
-   **Memory usage berkurang**: Lebih sedikit data yang perlu diproses

### **2. User Experience**

-   **Auto-adjustment**: Rentang tanggal menyesuaikan otomatis dengan interval
-   **Intuitive interaction**: Perubahan interval langsung terlihat efeknya
-   **Better visualization**: Grafik lebih mudah dibaca dengan data yang sesuai

### **3. Data Accuracy**

-   **Proper aggregation**: Menggunakan AVG untuk menghitung nilai rata-rata
-   **Time grouping**: Data dikelompokkan dengan benar berdasarkan interval
-   **Consistent formatting**: Timestamp diformat dengan konsisten

## Format Agregasi

### **SQL Query Examples:**

#### **Minute Interval:**

```sql
SELECT
    DATE_FORMAT(timestamp_device, '%Y-%m-%d %H:%i:00') as timestamp_group,
    AVG(temperature) as temperature,
    AVG(humidity) as humidity
FROM scada_data_wides
WHERE timestamp_device BETWEEN '2025-08-02 11:53:14' AND '2025-08-03 12:09:36'
GROUP BY timestamp_group
ORDER BY timestamp_group ASC
```

#### **Hour Interval:**

```sql
SELECT
    DATE_FORMAT(timestamp_device, '%Y-%m-%d %H:00:00') as timestamp_group,
    AVG(temperature) as temperature,
    AVG(humidity) as humidity
FROM scada_data_wides
WHERE timestamp_device BETWEEN '2025-08-02 11:53:14' AND '2025-08-03 12:09:36'
GROUP BY timestamp_group
ORDER BY timestamp_group ASC
```

#### **Day Interval:**

```sql
SELECT
    DATE_FORMAT(timestamp_device, '%Y-%m-%d 00:00:00') as timestamp_group,
    AVG(temperature) as temperature,
    AVG(humidity) as humidity
FROM scada_data_wides
WHERE timestamp_device BETWEEN '2025-08-02 11:53:14' AND '2025-08-03 12:09:36'
GROUP BY timestamp_group
ORDER BY timestamp_group ASC
```

## Kesimpulan

Perbaikan ini memberikan:

1. **✅ Agregasi yang Benar**: Data dikelompokkan dan diagregasi sesuai interval
2. **✅ Performance yang Lebih Baik**: Query lebih efisien dengan data reduction
3. **✅ UX yang Lebih Baik**: Auto-adjustment rentang tanggal
4. **✅ Visualisasi yang Akurat**: Grafik menampilkan data yang sesuai dengan interval

Sekarang aplikasi dapat menampilkan data historis dengan benar berdasarkan interval yang dipilih, memberikan pengalaman pengguna yang jauh lebih baik dan performa yang optimal.
