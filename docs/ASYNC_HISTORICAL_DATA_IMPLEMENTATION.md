# Implementasi Data Historis Asinkron dengan Antrian (Queues)

## Ringkasan Masalah

**Masalah Utama**: Timeout Nginx saat memuat data historis dalam jumlah besar menggunakan pendekatan sinkron Livewire.

**Penyebab**:

-   Satu permintaan HTTP panjang yang memproses semua data historis
-   Batas waktu server (Nginx + PHP-FPM) yang terlampaui
-   Pengalaman pengguna yang buruk dengan loading yang tidak pasti

## Solusi yang Direkomendasikan

**Pendekatan Asinkron dengan Antrian (Queues)** - Solusi paling robust dan skalabel untuk proses yang berjalan lama.

## Arsitektur Solusi

### 1. Flow Proses Asinkron

```
User Request → Livewire → Dispatch Job → Queue → Worker Process → Store Result → Notify Frontend
     ↓              ↓           ↓         ↓         ↓              ↓            ↓
   "Load Data"   Controller   Job       Queue    Background    Cache/DB    Frontend Update
                  Response    Created    System   Processing    Storage     (Polling/SSE)
```

### 2. Komponen Sistem

-   **Job Dispatcher**: Mengirim tugas ke antrian
-   **Queue System**: Menyimpan dan mengelola tugas
-   **Worker Process**: Memproses tugas di latar belakang
-   **Result Storage**: Menyimpan hasil pemrosesan
-   **Frontend Polling**: Memeriksa status dan memuat hasil

## Implementasi Detail

### Phase 1: Konfigurasi Queue System

#### 1.1 Konfigurasi Driver Antrian

**File**: `config/queue.php`

```php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
    ],

    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

#### 1.2 Migration untuk Tabel Jobs

```bash
php artisan queue:table
php artisan migrate
```

### Phase 2: Implementasi Job Class

#### 2.1 Update ProcessLargeScadaDatasetJob

**File**: `app/Jobs/ProcessLargeScadaDatasetJob.php`

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ScadaDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessLargeScadaDatasetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 menit timeout
    public $tries = 3;     // Retry 3 kali jika gagal

    protected $userId;
    protected $startDate;
    protected $endDate;
    protected $jobId;

    public function __construct($userId, $startDate, $endDate)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->jobId = uniqid('hist_', true);
    }

    public function handle(ScadaDataService $scadaService)
    {
        try {
            // Update status job
            $this->updateJobStatus('processing');

            // Proses data historis
            $historicalData = $scadaService->getHistoricalData(
                $this->startDate,
                $this->endDate,
                $this->userId
            );

            // Simpan hasil ke cache dengan TTL 1 jam
            $cacheKey = "historical_data_{$this->jobId}";
            Cache::put($cacheKey, $historicalData, 3600);

            // Update status job selesai
            $this->updateJobStatus('completed', $cacheKey);

            Log::info("Historical data job completed", [
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'data_points' => count($historicalData)
            ]);

        } catch (\Exception $e) {
            $this->updateJobStatus('failed', null, $e->getMessage());
            Log::error("Historical data job failed", [
                'job_id' => $this->jobId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function updateJobStatus($status, $resultKey = null, $error = null)
    {
        $jobInfo = [
            'status' => $status,
            'updated_at' => now()->toISOString(),
            'result_cache_key' => $resultKey,
            'error' => $error
        ];

        Cache::put("job_status_{$this->jobId}", $jobInfo, 3600);
    }

    public function getJobId()
    {
        return $this->jobId;
    }
}
```

### Phase 3: Update Livewire Component

#### 3.1 Modifikasi AnalysisChart Component

**File**: `app/Livewire/AnalysisChart.php`

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Jobs\ProcessLargeScadaDatasetJob;
use Illuminate\Support\Facades\Cache;

class AnalysisChart extends Component
{
    public $historicalData = [];
    public $isLoadingHistorical = false;
    public $currentJobId = null;
    public $jobStatus = null;
    public $loadingProgress = 0;

    // ... existing properties ...

    public function setHistoricalModeAndLoad($startDate, $endDate)
    {
        try {
            $this->isLoadingHistorical = true;
            $this->loadingProgress = 10;

            // Dispatch job asinkron
            $job = new ProcessLargeScadaDatasetJob(
                auth()->id(),
                $startDate,
                $endDate
            );

            dispatch($job);

            $this->currentJobId = $job->getJobId();
            $this->jobStatus = 'queued';

            // Mulai polling untuk status job
            $this->startJobPolling();

            $this->loadingProgress = 20;

        } catch (\Exception $e) {
            $this->isLoadingHistorical = false;
            $this->addError('historical_loading', 'Gagal memulai proses data historis: ' . $e->getMessage());
        }
    }

    public function startJobPolling()
    {
        // Polling setiap 2 detik untuk status job
        $this->dispatch('start-job-polling', [
            'jobId' => $this->currentJobId,
            'interval' => 2000
        ]);
    }

    public function checkJobStatus($jobId)
    {
        $jobInfo = Cache::get("job_status_{$jobId}");

        if (!$jobInfo) {
            return;
        }

        $this->jobStatus = $jobInfo['status'];

        switch ($this->jobStatus) {
            case 'processing':
                $this->loadingProgress = 50;
                break;

            case 'completed':
                $this->loadingProgress = 100;
                $this->loadHistoricalDataFromCache($jobInfo['result_cache_key']);
                $this->isLoadingHistorical = false;
                $this->currentJobId = null;
                $this->dispatch('stop-job-polling');
                break;

            case 'failed':
                $this->isLoadingHistorical = false;
                $this->addError('historical_loading', 'Gagal memproses data historis: ' . ($jobInfo['error'] ?? 'Unknown error'));
                $this->dispatch('stop-job-polling');
                break;
        }
    }

    private function loadHistoricalDataFromCache($cacheKey)
    {
        $data = Cache::get($cacheKey);
        if ($data) {
            $this->historicalData = $data;
            $this->updateChartWithHistoricalData();
        }
    }

    private function updateChartWithHistoricalData()
    {
        // Update chart dengan data historis
        $this->dispatch('update-chart-with-historical', [
            'data' => $this->historicalData
        ]);
    }

    // ... existing methods ...
}
```

### Phase 4: Frontend Implementation

#### 4.1 Update Blade Template

**File**: `resources/views/livewire/graph-analysis.blade.php`

```php
<div>
    <!-- Historical Data Loading Section -->
    @if($isLoadingHistorical)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Memuat Data Historis
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    Proses sedang berjalan di latar belakang...
                </p>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                         style="width: {{ $loadingProgress }}%"></div>
                </div>

                <p class="text-xs text-gray-500">
                    Status: {{ ucfirst($jobStatus ?? 'Initializing') }}
                </p>

                <!-- Cancel Button -->
                <button wire:click="cancelHistoricalLoading"
                        class="mt-4 px-4 py-2 text-sm text-red-600 border border-red-600 rounded hover:bg-red-50">
                    Batalkan
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Existing chart content -->
    <div id="chart-container" class="w-full h-96">
        <!-- Chart will be rendered here -->
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    let pollingInterval = null;

    // Start job polling
    Livewire.on('start-job-polling', (data) => {
        const { jobId, interval } = data;

        pollingInterval = setInterval(() => {
            @this.checkJobStatus(jobId);
        }, interval);
    });

    // Stop job polling
    Livewire.on('stop-job-polling', () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    });

    // Update chart with historical data
    Livewire.on('update-chart-with-historical', (data) => {
        // Update chart dengan data historis
        if (window.chartInstance && data.data) {
            updateChartWithHistoricalData(data.data);
        }
    });
});

function updateChartWithHistoricalData(historicalData) {
    // Implementasi update chart sesuai dengan library yang digunakan
    // Contoh untuk Chart.js:
    if (window.chartInstance) {
        // Update dataset chart
        window.chartInstance.data.datasets[1].data = historicalData;
        window.chartInstance.update();
    }
}
</script>
@endpush
```

### Phase 5: Queue Worker Management

#### 5.1 Script Start Queue Worker

**File**: `scripts/start-queue-worker.ps1`

```powershell
# Start Queue Worker
Write-Host "Starting Laravel Queue Worker..." -ForegroundColor Green

# Navigate to project directory
Set-Location "D:\dashboard-aws"

# Start queue worker with specific configuration
php artisan queue:work --queue=default --timeout=300 --tries=3 --max-jobs=100 --max-time=3600

Write-Host "Queue Worker started successfully!" -ForegroundColor Green
```

#### 5.2 Script Monitor Queue Status

**File**: `scripts/monitor-queue-status.ps1`

```powershell
# Monitor Queue Status
Write-Host "Monitoring Laravel Queue Status..." -ForegroundColor Yellow

# Navigate to project directory
Set-Location "D:\dashboard-aws"

# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Check queue size
php artisan queue:size
```

### Phase 6: Testing & Validation

#### 6.1 Test Script untuk Queue System

**File**: `scripts/test_queue_implementation.php`

```php
<?php
require_once 'vendor/autoload.php';

use App\Jobs\ProcessLargeScadaDatasetJob;
use Illuminate\Support\Facades\Cache;

// Test job dispatch
echo "Testing Job Dispatch...\n";

$job = new ProcessLargeScadaDatasetJob(
    1, // user_id
    '2025-01-01',
    '2025-01-31'
);

dispatch($job);

echo "Job dispatched with ID: " . $job->getJobId() . "\n";

// Test job status checking
$jobId = $job->getJobId();
echo "Checking job status for ID: $jobId\n";

$maxAttempts = 30; // 30 attempts with 2 second intervals = 1 minute
$attempt = 0;

while ($attempt < $maxAttempts) {
    $jobInfo = Cache::get("job_status_{$jobId}");

    if ($jobInfo) {
        echo "Job Status: " . $jobInfo['status'] . "\n";

        if ($jobInfo['status'] === 'completed') {
            echo "Job completed successfully!\n";
            break;
        } elseif ($jobInfo['status'] === 'failed') {
            echo "Job failed: " . ($jobInfo['error'] ?? 'Unknown error') . "\n";
            break;
        }
    } else {
        echo "Job status not found yet...\n";
    }

    $attempt++;
    sleep(2);
}

if ($attempt >= $maxAttempts) {
    echo "Timeout waiting for job completion\n";
}
```

## Keuntungan Implementasi

### 1. **Tidak Ada Timeout**

-   Proses berjalan di latar belakang tanpa batas waktu HTTP
-   Worker process terpisah dari request-response cycle

### 2. **UX yang Lebih Baik**

-   Feedback real-time tentang status proses
-   Progress bar yang informatif
-   Kemampuan untuk membatalkan proses

### 3. **Skalabilitas**

-   Multiple worker processes
-   Queue management yang robust
-   Retry mechanism untuk handling error

### 4. **Monitoring & Debugging**

-   Job status tracking
-   Error logging yang detail
-   Queue monitoring tools

## Monitoring & Maintenance

### 1. **Queue Health Monitoring**

```bash
# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### 2. **Worker Process Management**

```bash
# Start multiple workers
php artisan queue:work --queue=default --timeout=300 --tries=3

# Restart workers after code changes
php artisan queue:restart
```

### 3. **Cache Management**

```bash
# Clear cache if needed
php artisan cache:clear

# Check cache status
php artisan cache:table
```

## Troubleshooting

### 1. **Job Tidak Berjalan**

-   Pastikan queue worker berjalan
-   Check database connection
-   Verify queue configuration

### 2. **Job Gagal Berulang**

-   Check error logs
-   Verify data format
-   Check memory limits

### 3. **Performance Issues**

-   Monitor queue size
-   Adjust worker count
-   Optimize database queries

## Evaluasi Rencana Implementasi

### ✅ **Kelebihan Utama dari Rencana**

**1. Arsitektur yang Tepat**

-   Pemetaan alur proses asinkron yang benar dari dispatching job hingga proses di latar belakang
-   Secara fundamental akan menyelesaikan masalah timeout Nginx

**2. Pemisahan Tugas (Separation of Concerns)**

-   Pemisahan yang jelas antara API (respon cepat) dan worker (pekerjaan berat)
-   Desain sistem yang kuat dan maintainable

**3. Pengalaman Pengguna (UX) yang Ditingkatkan**

-   Loading progress yang informatif
-   Status pekerjaan real-time
-   Pemberitahuan yang jelas di frontend
-   Jauh lebih baik daripada membiarkan pengguna menunggu tanpa kepastian

**4. Manajemen Status yang Baik**

-   Penggunaan cache untuk menyimpan status pekerjaan (`job_status_{jobId}`)
-   Penyimpanan hasil (`historical_data_{jobId}`)
-   Pendekatan yang efisien dan umum digunakan

**5. Kelengkapan Dokumentasi**

-   Mencakup semua aspek yang diperlukan
-   Konfigurasi backend, implementasi job, modifikasi Livewire
-   Manajemen frontend dan skrip testing

### 💡 **Saran dan Poin untuk Dipertimbangkan**

#### **1. Pembatalan Job (Job Cancellation)**

Meskipun sudah ada tombol "Batalkan" di UI, perlu implementasi logika di backend. Laravel tidak memiliki cara bawaan untuk membatalkan job yang sudah berjalan, tetapi bisa diimplementasikan dengan "cancellation flag".

**Implementasi Cancellation Flag**:

```php
// Di dalam job handle() method
public function handle(ScadaDataService $scadaService)
{
    try {
        $this->updateJobStatus('processing');

        // Check cancellation flag secara berkala
        while ($this->shouldContinueProcessing()) {
            // Process data in chunks
            $chunk = $this->processNextChunk();

            // Check cancellation setiap chunk
            if ($this->isCancelled()) {
                $this->updateJobStatus('cancelled');
                return;
            }

            // Update progress
            $this->updateProgress();
        }

        // ... rest of processing
    } catch (\Exception $e) {
        // ... error handling
    }
}

private function isCancelled()
{
    return Cache::has("cancel_job_{$this->jobId}");
}

private function shouldContinueProcessing()
{
    return !$this->isCancelled() && $this->hasMoreDataToProcess();
}
```

**Frontend Cancellation Handler**:

```php
public function cancelHistoricalLoading()
{
    if ($this->currentJobId) {
        // Set cancellation flag
        Cache::put("cancel_job_{$this->currentJobId}", true, 3600);

        // Update UI
        $this->isLoadingHistorical = false;
        $this->currentJobId = null;
        $this->dispatch('stop-job-polling');

        $this->addError('historical_loading', 'Proses data historis dibatalkan');
    }
}
```

#### **2. Keamanan dan Konteks Pengguna**

Validasi hak akses pengguna di dalam job untuk mencegah kebocoran data pada sistem multi-user.

**Enhanced Security Implementation**:

```php
class ProcessLargeScadaDatasetJob implements ShouldQueue
{
    protected $userId;
    protected $userPermissions;

    public function __construct($userId, $startDate, $endDate)
    {
        $this->userId = $userId;
        $this->userPermissions = $this->getUserPermissions($userId);
    }

    public function handle(ScadaDataService $scadaService)
    {
        // Validate user permissions before processing
        if (!$this->canAccessHistoricalData()) {
            throw new UnauthorizedException('User does not have permission to access historical data');
        }

        // Validate date range permissions
        if (!$this->canAccessDateRange($this->startDate, $this->endDate)) {
            throw new UnauthorizedException('User cannot access data for specified date range');
        }

        // ... rest of processing
    }

    private function canAccessHistoricalData()
    {
        return in_array('historical_data_access', $this->userPermissions);
    }

    private function canAccessDateRange($startDate, $endDate)
    {
        // Implement date range validation logic
        return $this->validateDateRangeAccess($startDate, $endDate);
    }
}
```

#### **3. Manajemen Antrian (Queue Priority)**

Gunakan antrian terpisah untuk data besar dan normal sesuai implementasi sebelumnya.

**Queue Priority Implementation**:

```php
// Dispatch job dengan antrian spesifik
public function setHistoricalModeAndLoad($startDate, $endDate)
{
    try {
        $this->isLoadingHistorical = true;
        $this->loadingProgress = 10;

        $job = new ProcessLargeScadaDatasetJob(
            auth()->id(),
            $startDate,
            $endDate
        );

        // Dispatch ke antrian khusus untuk data historis
        dispatch($job)->onQueue('historical-data-processing');

        // ... rest of implementation
    } catch (\Exception $e) {
        // ... error handling
    }
}
```

**Queue Configuration**:

```php
// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
    ],
],

// Multiple queue workers
'worker_queues' => [
    'default' => ['default', 'scada-processing'],
    'historical' => ['historical-data-processing'],
],
```

#### **4. Alternatif untuk Polling**

Untuk optimasi lebih lanjut di masa depan, pertimbangkan Laravel Echo dan WebSockets.

**WebSocket Implementation (Future Enhancement)**:

```php
// Broadcast job completion event
class HistoricalDataProcessed implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $userId;
    public $jobId;
    public $data;

    public function __construct($userId, $jobId, $data)
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("user.{$this->userId}");
    }
}

// Di dalam job setelah selesai
$this->updateJobStatus('completed', $cacheKey);
broadcast(new HistoricalDataProcessed($this->userId, $this->jobId, $historicalData));
```

**Frontend WebSocket Listener**:

```javascript
// Listen for real-time updates
Echo.private(`user.${userId}`).listen("HistoricalDataProcessed", (e) => {
    if (e.jobId === currentJobId) {
        updateChartWithHistoricalData(e.data);
        stopLoading();
    }
});
```

### **5. Monitoring dan Alerting**

Tambahkan sistem monitoring yang lebih advanced untuk production environment.

**Advanced Monitoring**:

```php
// Job monitoring dengan metrics
class ProcessLargeScadaDatasetJob implements ShouldQueue
{
    public function handle(ScadaDataService $scadaService)
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage();

        try {
            // ... processing

            // Log performance metrics
            $this->logPerformanceMetrics($startTime, $memoryStart);

        } catch (\Exception $e) {
            // Log failure metrics
            $this->logFailureMetrics($startTime, $memoryStart, $e);
            throw $e;
        }
    }

    private function logPerformanceMetrics($startTime, $memoryStart)
    {
        $executionTime = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage() - $memoryStart;

        Log::info('Historical data job performance', [
            'job_id' => $this->jobId,
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
            'data_points' => count($this->historicalData ?? [])
        ]);
    }
}
```

## Kesimpulan

Implementasi sistem antrian asinkron ini akan secara fundamental menyelesaikan masalah timeout Nginx dan memberikan pengalaman pengguna yang jauh lebih baik. Pendekatan ini juga membangun fondasi yang kokoh untuk fitur-fitur berat lainnya di masa depan.

**Evaluasi Keseluruhan**: Rencana ini sudah sangat matang dan siap untuk diimplementasikan. Menunjukkan pemahaman yang kuat tentang arsitektur perangkat lunak modern untuk menangani tugas-tugas berat.

**Langkah Implementasi Selanjutnya**:

1. Setup queue system dan migration
2. Implementasi job class dengan security dan cancellation
3. Update Livewire component dengan queue priority
4. Setup frontend polling (dengan opsi WebSocket future)
5. Implementasi monitoring dan alerting
6. Test dan validasi
7. Deploy dan monitoring production
