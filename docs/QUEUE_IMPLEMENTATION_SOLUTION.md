# Queue Implementation Solution untuk SCADA Data Processing

## Overview

Dokumen ini menjelaskan implementasi solusi **Background Queue** untuk mengatasi masalah **504 Gateway Timeout** yang terjadi pada endpoint `/api/aws/receiver`. Solusi ini memindahkan proses berat dari API request ke background processing, sehingga API dapat merespons dengan cepat tanpa timeout.

## Masalah yang Dipecahkan

### Sebelumnya: 504 Gateway Timeout

-   **Penyebab**: Proses bulk insert data SCADA memakan waktu > 5 menit
-   **Gejala**: Nginx mengembalikan error 504 setelah menunggu PHP
-   **Dampak**: API tidak responsif, pengguna tidak mendapat konfirmasi

### Sekarang: Background Queue Processing

-   **Solusi**: Data diterima, divalidasi, lalu dikirim ke queue
-   **Hasil**: API merespons dalam < 100ms dengan status 202 Accepted
-   **Keuntungan**: Tidak ada timeout, processing berjalan di background

## Arsitektur Solusi

### 1. Job Classes

```
app/Jobs/
├── ProcessScadaDataJob.php          # Job untuk dataset normal (< 5000 records)
└── ProcessLargeScadaDatasetJob.php  # Job untuk dataset besar (≥ 5000 records)
```

### 2. Queue Configuration

-   **Driver**: Database (tabel `jobs` dan `failed_jobs`)
-   **Queues**:
    -   `scada-processing` - untuk dataset normal
    -   `scada-large-datasets` - untuk dataset besar dengan chunking

### 3. Flow Processing

```
API Request → Validation → Job Dispatch → Queue → Background Processing → Database
     ↓              ↓           ↓         ↓           ↓              ↓
   < 100ms      < 50ms      < 10ms    Instant    Async (1-30 min)   Stored
```

## Implementasi Detail

### ReceiverController (Refactored)

```php
// Sebelumnya: Processing langsung di API
$this->scadaDataService->processScadaPayload($request->all());

// Sekarang: Dispatch ke queue
if ($dataCount >= self::LARGE_DATASET_THRESHOLD) {
    $job = new ProcessLargeScadaDatasetJob($request->all(), 1000);
} else {
    $job = new ProcessScadaDataJob($request->all());
}
dispatch($job);
```

### ProcessScadaDataJob

-   **Timeout**: 10 menit
-   **Retries**: 3 kali
-   **Queue**: `scada-processing`
-   **Processing**: Menggunakan `ScadaDataService::processScadaPayload()`

### ProcessLargeScadaDatasetJob

-   **Timeout**: 30 menit
-   **Retries**: 2 kali
-   **Queue**: `scada-large-datasets`
-   **Processing**: Menggunakan `ScadaDataService::processLargeDataset()` dengan chunking

## Scripts Management

### 1. Start Single Queue Worker

```powershell
# scripts/start-queue-worker.ps1
php artisan queue:work --queue=scada-processing,scada-large-datasets --tries=3 --timeout=1800 --verbose
```

### 2. Start Multiple Queue Workers

```powershell
# scripts/start-multiple-queue-workers.ps1
# Menjalankan 3 workers untuk load balancing
```

### 3. Monitor Queue Status

```powershell
# scripts/monitor-queue-status.ps1
# Real-time monitoring queue, jobs, dan system resources
```

### 4. Test Implementation

```bash
# scripts/test_queue_implementation.php
# Test dengan dataset kecil dan besar
```

## Cara Penggunaan

### 1. Setup Database Queue Tables

```bash
# Pastikan tabel jobs dan failed_jobs sudah ada
php artisan queue:table
php artisan migrate
```

### 2. Start Queue Workers

```powershell
# Option 1: Single worker
.\scripts\start-queue-worker.ps1

# Option 2: Multiple workers
.\scripts\start-multiple-queue-workers.ps1
```

### 3. Monitor Progress

```powershell
# Real-time monitoring
.\scripts\monitor-queue-status.ps1
```

### 4. Test Implementation

```bash
php scripts/test_queue_implementation.php
```

## Response API Baru

### Success Response (HTTP 202)

```json
{
    "status": "accepted",
    "message": "Data accepted and queued for processing.",
    "data_count": 7321,
    "queue": "scada-large-datasets",
    "response_time_ms": 45.23,
    "estimated_processing_time": "5-10 minutes",
    "note": "Data will be processed in the background. Check logs for progress updates."
}
```

### Error Response (HTTP 422/500)

```json
{
    "status": "error",
    "message": "Data validation failed",
    "errors": { ... }
}
```

## Monitoring dan Logging

### 1. Job Logging

-   **Start**: `Starting SCADA data processing job`
-   **Success**: `SCADA data processing job completed successfully`
-   **Failure**: `SCADA data processing job failed`
-   **Permanent Failure**: `SCADA data processing job permanently failed`

### 2. Queue Metrics

-   Jobs in queue
-   Failed jobs count
-   Processing time per job
-   System resources (CPU, Memory, Disk)

### 3. Performance Metrics

-   Response time: < 100ms
-   Queue dispatch time: < 10ms
-   Background processing: 1-30 menit (tergantung dataset size)

## Troubleshooting

### 1. Queue Workers Tidak Berjalan

```bash
# Check PHP processes
Get-Process -Name "php"

# Check queue status
php artisan queue:work --once --verbose
```

### 2. Jobs Stuck in Queue

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### 3. Memory Issues

```bash
# Restart workers
Stop-Job -State Running
# Start workers again
```

## Keuntungan Implementasi

### 1. Performance

-   **API Response**: < 100ms (sebelumnya: timeout)
-   **Scalability**: Dapat handle multiple requests bersamaan
-   **Reliability**: Tidak ada timeout, processing guaranteed

### 2. User Experience

-   **Instant Feedback**: User langsung mendapat konfirmasi
-   **Progress Tracking**: Bisa monitor status processing
-   **Error Handling**: Better error reporting dan retry

### 3. System Health

-   **Resource Management**: Processing tidak memblokir web server
-   **Monitoring**: Real-time visibility ke processing status
-   **Recovery**: Automatic retry dan failure handling

## Best Practices

### 1. Queue Configuration

-   Gunakan queue terpisah untuk dataset besar
-   Set timeout yang sesuai dengan processing time
-   Implement retry logic dengan exponential backoff

### 2. Monitoring

-   Monitor queue length dan processing time
-   Set up alerts untuk failed jobs
-   Track system resources selama processing

### 3. Error Handling

-   Log semua errors dengan detail
-   Implement graceful degradation
-   Provide meaningful error messages

## Testing

### 1. Unit Tests

```bash
php artisan test --filter=ProcessScadaDataJob
php artisan test --filter=ProcessLargeScadaDatasetJob
```

### 2. Integration Tests

```bash
php scripts/test_queue_implementation.php
```

### 3. Load Tests

-   Test dengan dataset 10,000+ records
-   Test multiple concurrent requests
-   Test queue worker restart scenarios

## Kesimpulan

Implementasi **Background Queue** ini menyelesaikan masalah **504 Gateway Timeout** dengan cara yang profesional dan scalable:

1. **API Response Time**: Dari timeout menjadi < 100ms
2. **Processing Capacity**: Dapat handle dataset besar tanpa blocking
3. **User Experience**: Instant feedback dan progress tracking
4. **System Reliability**: Better error handling dan recovery
5. **Scalability**: Multiple workers untuk load distribution

Solusi ini mengikuti best practices industri dan membuat aplikasi SCADA dashboard menjadi enterprise-ready dengan kemampuan processing data yang robust dan reliable.
