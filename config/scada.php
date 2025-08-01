<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SCADA Data Processing Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for SCADA data processing,
    | including downsampling thresholds and performance settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Data Downsampling Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how data is downsampled for chart visualization
    | to improve performance when dealing with large datasets.
    |
    */
    'downsampling' => [
        // Maximum number of points to send to frontend for second intervals
        'max_points_per_series' => env('SCADA_MAX_POINTS_PER_SERIES', 1000),

        // Enable/disable downsampling
        'enabled' => env('SCADA_DOWNSAMPLING_ENABLED', true),

        // Minimum number of points before downsampling is applied
        'min_points_threshold' => env('SCADA_MIN_POINTS_THRESHOLD', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Processing Settings
    |--------------------------------------------------------------------------
    |
    | Settings for data processing and validation.
    |
    */
    'processing' => [
        // Gap threshold in seconds for inserting null values in charts
        'gap_threshold_seconds' => env('SCADA_GAP_THRESHOLD_SECONDS', 30),

        // Enable logging of data processing operations
        'enable_logging' => env('SCADA_ENABLE_LOGGING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize performance for large datasets.
    |
    */
    'performance' => [
        // Maximum number of records to process in a single batch
        'max_batch_size' => env('SCADA_MAX_BATCH_SIZE', 1000),

        // Enable query optimization hints
        'enable_query_optimization' => env('SCADA_ENABLE_QUERY_OPTIMIZATION', true),
    ],
];
