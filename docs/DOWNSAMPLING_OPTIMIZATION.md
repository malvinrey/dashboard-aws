# Data Downsampling Optimization

## Overview

This document describes the implementation of data downsampling optimization in the SCADA dashboard to improve performance when dealing with large datasets.

## Problem

When users select long time ranges with second intervals, the `ScadaDataService` was sending all raw data points to the frontend. With tens of thousands of points, this caused:

-   Slow data transfer
-   Browser rendering difficulties with Plotly charts
-   High memory usage
-   Poor user experience

## Solution

Implemented the **Largest-Triangle-Three-Buckets (LTTB)** algorithm for intelligent data downsampling that preserves the visual shape of the chart while dramatically reducing the number of data points.

## Implementation Details

### 1. LTTB Algorithm

The LTTB algorithm intelligently selects the most important data points by:

-   Dividing data into buckets
-   Calculating triangle areas between points
-   Selecting points that create the largest triangles (most significant changes)
-   Preserving the first and last points of each series

### 2. Configuration

Created `config/scada.php` with configurable settings:

```php
'downsampling' => [
    'max_points_per_series' => env('SCADA_MAX_POINTS_PER_SERIES', 1000),
    'enabled' => env('SCADA_DOWNSAMPLING_ENABLED', true),
    'min_points_threshold' => env('SCADA_MIN_POINTS_THRESHOLD', 1000),
],
```

### 3. Performance Optimizations

-   **Chunked Database Queries**: Large datasets are processed in chunks to prevent memory issues
-   **Configurable Thresholds**: Downsampling only applies when data exceeds minimum threshold
-   **Performance Logging**: Detailed logs track downsampling effectiveness
-   **Database Indexes**: Existing indexes on `nama_tag` and `timestamp_device` optimize queries

### 4. Key Methods

#### `downsampleData(Collection $data, int $threshold)`

-   Main downsampling method
-   Converts data to LTTB format
-   Applies downsampling algorithm
-   Returns optimized data points

#### `lttbDownsample(array $data, int $threshold)`

-   Core LTTB algorithm implementation
-   Divides data into buckets
-   Selects optimal points based on triangle areas

#### `getPerformanceStats(array $tags, string $interval, Carbon $start, Carbon $end)`

-   Estimates data points before processing
-   Provides performance statistics
-   Helps with monitoring and optimization

## Usage

### Environment Variables

Add these to your `.env` file:

```env
# Downsampling Configuration
SCADA_MAX_POINTS_PER_SERIES=1000
SCADA_DOWNSAMPLING_ENABLED=true
SCADA_MIN_POINTS_THRESHOLD=1000

# Performance Settings
SCADA_MAX_BATCH_SIZE=1000
SCADA_ENABLE_LOGGING=true
```

### Automatic Application

Downsampling is automatically applied in `getHistoricalChartData()` when:

-   Interval is 'second'
-   Data points exceed the minimum threshold
-   Downsampling is enabled in configuration

### Manual Usage

```php
$scadaService = new ScadaDataService();

// Get performance statistics
$stats = $scadaService->getPerformanceStats(
    ['temperature', 'humidity'],
    'second',
    $startTime,
    $endTime
);

// Manual downsampling
$downsampledData = $scadaService->downsampleData($rawData, 1000);
```

## Performance Benefits

### Before Optimization

-   **10,000 data points** → **10,000 points sent to frontend**
-   Transfer time: ~2-5 seconds
-   Browser rendering: Slow, potential freezing
-   Memory usage: High

### After Optimization

-   **10,000 data points** → **1,000 points sent to frontend**
-   Transfer time: ~0.2-0.5 seconds
-   Browser rendering: Fast, smooth
-   Memory usage: Reduced by ~90%
-   Data reduction: ~90% with visual fidelity preserved

## Testing

Run the unit tests to verify downsampling functionality:

```bash
php artisan test tests/Unit/ScadaDataServiceTest.php
```

Tests cover:

-   Data point reduction
-   First/last point preservation
-   Threshold handling
-   Triangle area calculations

## Monitoring

### Logs

Downsampling performance is logged when enabled:

```
[INFO] Data downsampling applied {
    "tag": "temperature",
    "original_points": 15000,
    "downsampled_points": 1000,
    "reduction_percentage": 93.33
}
```

### Performance Statistics

Use `getPerformanceStats()` to monitor:

-   Total data points per tag
-   Whether downsampling will be applied
-   Estimated reduction percentage
-   Final point count

## Future Enhancements

1. **Adaptive Thresholds**: Dynamic threshold adjustment based on data characteristics
2. **Multiple Algorithms**: Support for different downsampling algorithms
3. **Real-time Optimization**: Apply downsampling to real-time data streams
4. **User Preferences**: Allow users to adjust downsampling settings
5. **Caching**: Cache downsampled results for repeated queries

## Troubleshooting

### Common Issues

1. **Downsampling not applied**

    - Check `SCADA_DOWNSAMPLING_ENABLED` setting
    - Verify data points exceed minimum threshold
    - Ensure interval is 'second'

2. **Performance still slow**

    - Increase `SCADA_MAX_POINTS_PER_SERIES`
    - Check database indexes
    - Monitor query execution time

3. **Data loss concerns**
    - LTTB preserves visual shape, not exact values
    - Original data remains in database
    - Adjust threshold for higher fidelity

### Debug Mode

Enable detailed logging:

```env
SCADA_ENABLE_LOGGING=true
LOG_LEVEL=debug
```

This will provide detailed information about:

-   Query execution times
-   Data processing steps
-   Downsampling decisions
-   Performance metrics
