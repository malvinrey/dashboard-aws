# Implementasi Data Historis dengan Agregasi Dinamis

## Overview

Sistem ini mengimplementasikan metode `getAggregatedHistoricalData` yang menjadi "otak" dari query database yang dinamis untuk data historis SCADA. Metode ini secara otomatis menentukan level agregasi yang optimal berdasarkan rentang waktu yang diminta.

## Fitur Utama

### 1. Agregasi Dinamis Otomatis

-   **Second**: Data mentah tanpa agregasi (untuk rentang waktu pendek)
-   **Minute**: Agregasi per menit (untuk rentang > 6 jam)
-   **Hour**: Agregasi per jam (untuk rentang > 7 hari)
-   **Day**: Agregasi per hari (untuk rentang > 30 hari)

### 2. Optimasi Performa

-   Query database yang efisien dengan agregasi di level database
-   Pembatasan data mentah (1000 records max untuk level 'second')
-   Penggunaan indeks yang optimal

### 3. Fleksibilitas Data

-   Mendukung semua sensor yang tersedia
-   Menyediakan nilai rata-rata, maksimum, dan minimum
-   Format output yang konsisten

## Implementasi Backend

### ScadaDataService.php

```php
public function getAggregatedHistoricalData(
    array $tags,
    string $startDate,
    string $endDate,
    string $aggregationLevel = 'second'
)
```

**Parameter:**

-   `$tags`: Array tag sensor yang diminta
-   `$startDate`: Tanggal mulai (format: 'Y-m-d H:i:s')
-   `$endDate`: Tanggal akhir (format: 'Y-m-d H:i:s')
-   `$aggregationLevel`: Level agregasi (default: 'second')

**Return:**

-   Collection data yang sudah diagregasi sesuai level

### Logic Agregasi

```php
$timeFormat = match ($aggregationLevel) {
    'minute' => '%Y-%m-%d %H:%i:00',
    'hour'   => '%Y-%m-%d %H:00:00',
    'day'    => '%Y-%m-%d 00:00:00',
    default  => '%Y-%m-%d %H:%i:%s', // 'second'
};
```

## Implementasi Frontend

### AnalysisChart.php (Livewire Component)

Komponen ini mengatur logika pemuatan data secara asinkron dan menentukan level agregasi secara otomatis.

#### Method `loadHistoricalData`

```php
public function loadHistoricalData($startDate, $endDate)
{
    // Logika penentuan agregasi dinamis
    $durationInSeconds = Carbon::parse($endDate)->diffInSeconds(Carbon::parse($startDate));

    $aggregationLevel = 'second';
    if ($durationInSeconds > 3600 * 6) { // > 6 jam
        $aggregationLevel = 'minute';
    }
    if ($durationInSeconds > 86400 * 7) { // > 7 hari
        $aggregationLevel = 'hour';
    }
    if ($durationInSeconds > 86400 * 30) { // > 30 hari
        $aggregationLevel = 'day';
    }

    // Panggil service
    $this->historicalData = $this->scadaDataService->getAggregatedHistoricalData(
        $this->selectedTags,
        $startDate,
        $endDate,
        $aggregationLevel
    );
}
```

#### Properties yang Ditambahkan

```php
public array $historicalData = [];
public bool $isLoading = false;
```

#### Events yang Dikirim

-   `historicalDataLoaded`: Data historis berhasil dimuat
-   `historicalDataError`: Error saat memuat data

## Konfigurasi Nginx

### Timeout Settings

```nginx
location ~ \.php$ {
    fastcgi_pass   127.0.0.1:9000;
    fastcgi_index  index.php;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include        "D:/dashboard-aws/nginx/fastcgi_params";

    # Timeout settings untuk query database yang lama
    fastcgi_read_timeout 300s;
    fastcgi_connect_timeout 60s;
}
```

**Penjelasan:**

-   `fastcgi_read_timeout 300s`: Memberikan waktu 5 menit untuk query database
-   `fastcgi_connect_timeout 60s`: Timeout untuk koneksi ke PHP-FPM

## Penggunaan

### 1. Dari Livewire Component

```php
// Otomatis dipanggil dengan wire:init
$this->loadHistoricalData('2025-08-01 00:00:00', '2025-08-02 00:00:00');
```

### 2. Dari Controller

```php
$scadaService = new ScadaDataService();
$data = $scadaService->getAggregatedHistoricalData(
    ['temperature', 'humidity'],
    '2025-08-01 00:00:00',
    '2025-08-02 00:00:00',
    'hour'
);
```

### 3. Dari Blade Template

```blade
<div wire:init="loadHistoricalData('{{ $startDate }}', '{{ $endDate }}')">
    <!-- Chart akan dimuat otomatis -->
</div>
```

## Testing

### Test Scripts

File `scripts/test_aggregated_historical_data.php` tersedia untuk menguji:

1. **Performance Testing**: Membandingkan waktu eksekusi antar level agregasi
2. **Data Validation**: Memastikan struktur data yang dikembalikan benar
3. **Error Handling**: Menguji penanganan error

File `scripts/test_aggregated_values.php` tersedia untuk menguji:

1. **Data Quality**: Memverifikasi nilai agregasi yang dihasilkan
2. **Performance Analysis**: Analisis efisiensi setiap level agregasi
3. **Data Structure**: Memastikan format output yang konsisten

### Menjalankan Test

```bash
cd scripts
php test_aggregated_historical_data.php
php test_aggregated_values.php
```

### Hasil Test Terbaru

Berdasarkan test dengan dataset 7321 records (2025-08-08 hingga 2025-08-12):

| Level      | Records | Time (ms) | Efficiency (records/ms) |
| ---------- | ------- | --------- | ----------------------- |
| **second** | 1000    | 15.4      | 64.93                   |
| **minute** | 1440    | 19.15     | 75.19                   |
| **hour**   | 24      | 9.62      | 2.49                    |
| **day**    | 1       | 7.78      | 0.13                    |

**Catatan**: Level 'second' dibatasi maksimal 1000 records untuk performa optimal.

## Keuntungan Implementasi

### 1. Performa

-   Query database yang dioptimasi
-   Agregasi di level database (bukan di PHP)
-   Pembatasan data mentah untuk level 'second'

### 2. User Experience

-   Loading time yang lebih cepat
-   Responsivitas yang lebih baik
-   Error handling yang informatif

### 3. Maintainability

-   Kode yang terstruktur dan mudah dipahami
-   Separation of concerns yang jelas
-   Dokumentasi yang lengkap

## Troubleshooting

### Common Issues

1. **Timeout Error**

    - Pastikan nginx timeout settings sudah benar
    - Cek performa database

2. **Memory Issues**

    - Level 'second' dibatasi 1000 records
    - Gunakan level agregasi yang sesuai

3. **Data Tidak Muncul**
    - Cek log Laravel untuk error
    - Validasi parameter tanggal

### Debug Mode

```php
// Tambahkan logging untuk debug
Log::info('Historical data query', [
    'tags' => $tags,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'aggregationLevel' => $aggregationLevel
]);
```

## Roadmap

### Future Enhancements

1. **Caching Layer**

    - Redis cache untuk hasil agregasi
    - Cache invalidation strategy

2. **Advanced Aggregation**

    - Custom aggregation functions
    - Statistical functions (median, percentile)

3. **Real-time Updates**
    - WebSocket integration
    - Live chart updates

## Kesimpulan

Implementasi ini memberikan solusi yang robust dan performant untuk menangani data historis SCADA dengan berbagai level agregasi. Sistem secara otomatis menyesuaikan level agregasi berdasarkan rentang waktu, memastikan performa optimal dan user experience yang baik.
