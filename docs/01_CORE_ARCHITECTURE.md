# 01. Core Architecture - SCADA Dashboard

## 🏗️ Arsitektur Sistem

### Overview

SCADA Dashboard adalah sistem monitoring real-time untuk data weather station yang dibangun dengan Laravel 10, Livewire 3, dan MySQL. Sistem ini dirancang untuk handle data SCADA dalam volume besar dengan performa optimal.

### Komponen Utama

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   SCADA Source │    │   Laravel App  │    │   Frontend     │
│   (Weather     │───▶│                 │───▶│   (Livewire)   │
│   Station)     │    │  - API         │    │                 │
└─────────────────┘    │  - Jobs        │    └─────────────────┘
                       │  - Services    │
                       └─────────────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │   Database      │
                       │   (MySQL)       │
                       └─────────────────┘
```

### 1. Data Flow

#### 1.1 Data Ingestion

-   **Endpoint**: `POST /api/aws/receiver`
-   **Controller**: `ReceiverController`
-   **Validation**: Strict data validation dengan range limits
-   **Job Dispatch**: Otomatis dispatch ke queue berdasarkan ukuran dataset

#### 1.2 Data Processing

-   **Small Dataset** (< 5000 records): `ProcessScadaDataJob`
-   **Large Dataset** (≥ 5000 records): `ProcessLargeScadaDatasetJob`
-   **Queue System**: Redis-based dengan multiple queues

#### 1.3 Data Storage

-   **Model**: `ScadaDataWide` (wide format)
-   **Table**: `scada_data_wides`
-   **Optimization**: Bulk insert, proper indexing

### 2. Database Schema

#### 2.1 ScadaDataWide Table

```sql
CREATE TABLE scada_data_wides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(255),
    nama_group VARCHAR(255),
    timestamp_device DATETIME,

    -- Sensor Data (Wide Format)
    temperature DECIMAL(8,2),
    humidity DECIMAL(8,2),
    pressure DECIMAL(8,2),
    rainfall DECIMAL(8,2),
    wind_speed DECIMAL(8,2),
    wind_direction DECIMAL(8,2),
    par_sensor DECIMAL(8,2),
    solar_radiation DECIMAL(8,2),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_timestamp (timestamp_device),
    INDEX idx_group (nama_group),
    INDEX idx_batch (batch_id)
);
```

#### 2.2 Keuntungan Wide Format

-   **Query Performance**: Single table query untuk semua metrics
-   **Storage Efficiency**: Tidak ada data duplication
-   **Maintenance**: Lebih mudah untuk backup dan maintenance
-   **Scalability**: Mendukung penambahan sensor baru

### 3. Service Layer

#### 3.1 ScadaDataService

```php
class ScadaDataService
{
    // Data Processing
    public function processScadaPayload(array $payload): void
    public function processLargeDataset(array $payload, int $chunkSize): void

    // Data Retrieval
    public function getDashboardMetrics(): array
    public function getHistoricalChartData(array $tags, string $interval, ?string $startDate, ?string $endDate): array
    public function getLatestAggregatedDataPoint(array $tags, string $interval): ?array

    // Database Health
    public function getDatabaseHealth(): array
    public function getUniqueTags(): Collection
}
```

#### 3.2 ScadaBroadcastingService

```php
class ScadaBroadcastingService
{
    // Broadcasting Methods
    public function broadcastData($data, $channel = 'scada-data'): bool
    public function broadcastBatchData($dataArray, $channel = 'scada-batch'): bool
    public function broadcastAggregatedData($data, $channel = 'scada-aggregated', $throttleMs = 100): bool
}
```

### 4. Queue System

#### 4.1 Queue Configuration

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### 4.2 Queue Types

-   **`scada-processing`**: Dataset normal (< 5000 records)
-   **`scada-large-datasets`**: Dataset besar (≥ 5000 records)

#### 4.3 Job Processing

-   **Retry Logic**: 3x untuk job normal, 2x untuk dataset besar
-   **Timeout**: 10 menit untuk job normal, 30 menit untuk dataset besar
-   **Chunking**: Dataset besar diproses dalam chunks 1000 records

### 5. API Endpoints

#### 5.1 Data Ingestion

```http
POST /api/aws/receiver
Content-Type: application/json

{
    "DataArray": [
        {
            "_groupTag": "weather_station_1",
            "_terminalTime": "2024-01-15 10:30:00",
            "temperature": 25.5,
            "humidity": 65.2,
            "pressure": 1013.25,
            "rainfall": 0.0,
            "wind_speed": 12.3,
            "wind_direction": 180
        }
    ]
}
```

#### 5.2 Data Retrieval

```http
GET /api/analysis-data?tag[]=temperature&tag[]=humidity&interval=hour&start_date=2024-01-15&end_date=2024-01-16
```

#### 5.3 Real-time Updates

```http
GET /api/latest-data?tags[]=temperature&tags[]=humidity&interval=minute
```

### 6. Performance Features

#### 6.1 Data Aggregation

-   **Automatic**: Berdasarkan interval yang dipilih
-   **Database Level**: Menggunakan SQL aggregation
-   **Configurable**: Threshold dan method bisa diatur
-   **Efficient**: 90% data reduction untuk dataset besar

#### 6.2 Caching Strategy

-   **Redis Cache**: Untuk performance metrics
-   **Query Optimization**: Proper indexing dan query structure
-   **Lazy Loading**: Data dimuat sesuai kebutuhan

### 7. Security & Validation

#### 7.1 Input Validation

-   **Data Range**: Temperature (-50°C to 100°C), Humidity (0-100%), Pressure (800-1200 hPa)
-   **Payload Size**: Maksimal 10,000 records per request
-   **Required Fields**: Group tag dan timestamp wajib ada

#### 7.2 Error Handling

-   **Comprehensive Logging**: Semua error dan warning di-log
-   **Graceful Degradation**: Sistem tetap berjalan meski ada error
-   **User Feedback**: Error message yang informatif

### 8. Monitoring & Health Checks

#### 8.1 Performance Metrics

-   **Processing Time**: Response time untuk setiap job
-   **Database Health**: Connection status, table size, insertion rate
-   **Queue Status**: Job count, failure rate, processing rate

#### 8.2 Health Dashboard

-   **Real-time Monitoring**: Live metrics display
-   **Alert System**: Warning untuk kondisi abnormal
-   **Optimization Tips**: Rekomendasi berdasarkan metrics

---

**Status**: ✅ **IMPLEMENTED** - Semua fitur core sudah berfungsi dengan baik
**Last Updated**: January 2025
**Version**: 1.0.0
