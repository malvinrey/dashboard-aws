# Perbaikan Agregasi Real-time Update

## Ringkasan Masalah

Sebelumnya, logika pembaruan grafik "jebol" karena sistem secara keliru menambahkan (append) titik data real-time (per detik) ke grafik yang seharusnya menampilkan data agregat (per jam, hari, atau menit).

### **Penyebab Utama:**

1. **Backend**: API mengirim data yang salah untuk interval agregat
2. **Frontend**: Logika update terlalu sederhana dan tidak memperhatikan interval

## Analisis Masalah

### **1. Backend: API Mengirim Data yang Salah**

**Logika LAMA yang Salah:**

```php
// Di ScadaDataService.php - getLatestAggregatedDataPoint
public function getLatestAggregatedDataPoint(array $tags, string $interval): ?array
{
    // INI SALAH: Kode ini hanya mengambil baris terbaru,
    // bukan data agregat untuk interval waktu saat ini
    $latestData = ScadaDataWide::select($columnsToSelect)
        ->orderBy('timestamp_device', 'desc')
        ->first();

    return [
        'timestamp' => $latestData->timestamp_device->format('Y-m-d H:i:s'),
        'metrics' => $metrics,
    ];
}
```

**Akibatnya:**

-   Saat interval grafik adalah 'Hour', API tetap mengirimkan data per detik (misal: 12:25:05)
-   Bukan data rata-rata untuk jam 12:00 (12:00:00)
-   Frontend menerima timestamp yang tidak cocok dengan grafik agregat

### **2. Frontend: Logika Update Terlalu Sederhana**

**Logika yang Bermasalah:**

```javascript
// Di graph-analysis.blade.php - handleChartUpdate
if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
    // Tambahkan titik baru
    Plotly.extendTraces('plotlyChart', {...});
}
```

**Akibatnya:**

-   Grafik 'Hour' punya titik 12:00:00
-   API mengirim 12:25:05 (data mentah)
-   JavaScript membandingkan 12:00:00 vs 12:25:05
-   Selalu menambahkan titik baru, merusak tampilan agregat

## Solusi yang Diterapkan

### **Langkah 1: Perbaiki Logika Agregasi di Backend**

**Logika BARU yang Benar:**

```php
/**
 * Mengambil titik data agregat terbaru berdasarkan interval yang dipilih.
 * Metode ini dirancang untuk pembaruan real-time yang cerdas.
 */
public function getLatestAggregatedDataPoint(array $tags, string $interval): ?array
{
    if (empty($tags)) return null;

    // 1. Dapatkan timestamp data paling akhir di database
    $latestTimestampStr = ScadaDataWide::max('timestamp_device');
    if (!$latestTimestampStr) return null;

    $latestTimestamp = Carbon::parse($latestTimestampStr);

    // 2. Jika intervalnya 'second', kembalikan data mentah terbaru
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

    // 3. Untuk interval lain (minute, hour, day), hitung agregat
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

    // 4. Bangun query agregasi untuk wadah waktu tersebut
    $selects = [];
    foreach ($tags as $tag) {
        $selects[] = DB::raw("AVG(`$tag`) as `$tag`");
    }

    $aggregatedData = ScadaDataWide::select($selects)
        ->whereBetween('timestamp_device', [$bucketStart, $bucketEnd])
        ->first();

    if (!$aggregatedData) return null;

    $metrics = [];
    foreach ($tags as $tag) {
        $metrics[$tag] = (float) $aggregatedData->$tag;
    }

    // Timestamp yang dikembalikan adalah timestamp awal dari wadah waktu
    return [
        'timestamp' => $bucketStart->format('Y-m-d H:i:s'),
        'metrics' => $metrics,
    ];
}
```

### **Langkah 2: Frontend Otomatis Berfungsi Benar**

Dengan perbaikan di backend, logika frontend sekarang berfungsi dengan benar:

**Sebelumnya:**

-   Grafik 'Hour' punya titik 12:00:00
-   API mengirim 12:25:05 (data mentah)
-   JavaScript menambahkan titik baru ❌

**Sesudah Perbaikan:**

-   Grafik 'Hour' punya titik 12:00:00
-   API mengirim 12:00:00 (data agregat)
-   JavaScript mengupdate titik yang sudah ada ✅

## Hasil Test

### **1. Timestamp Format Verification:**

```
✅ Timestamp format correct for minute: 2025-08-03 12:29:00
✅ Timestamp format correct for hour: 2025-08-03 12:00:00
✅ Timestamp format correct for day: 2025-08-03 00:00:00
```

### **2. Performance Test:**

```
- second: 0.75ms average per call
- minute: 0.89ms average per call
- hour: 1.3ms average per call
- day: 1.4ms average per call
```

### **3. Data Consistency Test:**

```
✅ temperature values consistent: 54.943598
✅ humidity values consistent: 55.089121
```

### **4. Real-time Update Compatibility:**

```
✅ Timestamps match - Real-time update will work correctly!
```

## Format Agregasi yang Benar

### **Time Bucket Mapping:**

| Interval | Bucket Start      | Bucket End      | Example               |
| -------- | ----------------- | --------------- | --------------------- |
| `second` | Raw timestamp     | Raw timestamp   | `2025-08-03 12:29:49` |
| `minute` | `startOfMinute()` | `endOfMinute()` | `2025-08-03 12:29:00` |
| `hour`   | `startOfHour()`   | `endOfHour()`   | `2025-08-03 12:00:00` |
| `day`    | `startOfDay()`    | `endOfDay()`    | `2025-08-03 00:00:00` |

### **SQL Query Examples:**

#### **Minute Interval:**

```sql
SELECT
    AVG(temperature) as temperature,
    AVG(humidity) as humidity
FROM scada_data_wides
WHERE timestamp_device BETWEEN '2025-08-03 12:29:00' AND '2025-08-03 12:29:59'
```

#### **Hour Interval:**

```sql
SELECT
    AVG(temperature) as temperature,
    AVG(humidity) as humidity
FROM scada_data_wides
WHERE timestamp_device BETWEEN '2025-08-03 12:00:00' AND '2025-08-03 12:59:59'
```

#### **Day Interval:**

```sql
SELECT
    AVG(temperature) as temperature,
    AVG(humidity) as humidity
FROM scada_data_wides
WHERE timestamp_device BETWEEN '2025-08-03 00:00:00' AND '2025-08-03 23:59:59'
```

## Keuntungan Perbaikan

### **1. Real-time Updates yang Benar**

-   ✅ Data agregat ditampilkan dengan benar
-   ✅ Tidak ada lagi penambahan titik yang salah
-   ✅ Update pada titik yang sudah ada, bukan append

### **2. Konsistensi Data**

-   ✅ Backend dan frontend menggunakan format yang sama
-   ✅ Timestamp bucket konsisten
-   ✅ Agregasi yang akurat

### **3. Performance**

-   ✅ Query agregasi tetap cepat (< 2ms)
-   ✅ Tidak ada overhead tambahan
-   ✅ Real-time updates smooth

### **4. User Experience**

-   ✅ Grafik agregat tetap rapi
-   ✅ Real-time updates tidak merusak tampilan
-   ✅ Data yang akurat dan konsisten

## Implementasi Detail

### **File yang Diubah:**

-   `app/Services/ScadaDataService.php`

### **Method yang Diperbaiki:**

-   `getLatestAggregatedDataPoint(array $tags, string $interval)`

### **Perubahan Kunci:**

1. **Time Bucket Calculation**: Menggunakan `startOfMinute()`, `startOfHour()`, `startOfDay()`
2. **Aggregation Query**: Menggunakan `AVG()` untuk menghitung rata-rata
3. **Consistent Timestamps**: Mengembalikan timestamp bucket start
4. **Interval-specific Logic**: Logika berbeda untuk setiap interval

## Testing

### **Script Test:**

-   `scripts/test_aggregated_realtime.php`

### **Test Coverage:**

1. **Timestamp Format**: Memastikan format timestamp sesuai interval
2. **Data Consistency**: Verifikasi data konsisten antar panggilan
3. **Performance**: Test kecepatan agregasi
4. **Real-time Compatibility**: Verifikasi kompatibilitas dengan frontend

### **Expected Results:**

-   ✅ Semua timestamp format correct
-   ✅ Data values consistent
-   ✅ Performance < 2ms per call
-   ✅ Real-time updates work correctly

## Kesimpulan

Perbaikan ini menyelesaikan masalah fundamental dalam real-time updates untuk data agregat:

1. **✅ Backend Agregasi**: API sekarang mengirim data agregat yang benar
2. **✅ Frontend Compatibility**: Logika update otomatis berfungsi
3. **✅ Performance**: Tidak ada degradasi performa
4. **✅ Data Accuracy**: Agregasi yang akurat dan konsisten

Sekarang aplikasi SCADA dapat menampilkan data real-time agregat dengan benar, memberikan pengalaman pengguna yang smooth dan akurat untuk semua interval waktu.
