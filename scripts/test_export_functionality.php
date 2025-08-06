<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ExportService;
use App\Services\ScadaDataService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Export Functionality\n";
echo "===========================\n\n";

$exportService = app(ExportService::class);
$scadaDataService = app(ScadaDataService::class);

// Test 1: Basic CSV Export
echo "1. Testing basic CSV export...\n";
$filters = [
    'startDate' => '2025-01-01',
    'endDate' => '2025-12-31',
    'search' => '',
    'sortField' => 'id',
    'sortDirection' => 'desc'
];

try {
    $filename = $exportService->exportToCsv($filters);
    $stats = $exportService->getExportStats($filters);

    echo "   ✅ CSV export successful!\n";
    echo "   - Filename: {$filename}\n";
    echo "   - Total records: {$stats['total_records']}\n";
    echo "   - Date range: {$stats['date_range']['start']} to {$stats['date_range']['end']}\n";
    echo "   - Groups: {$stats['groups']}\n";
    echo "   - Export time: {$stats['export_time']}\n\n";

    // Check if file exists
    $filepath = __DIR__ . '/../storage/app/public/exports/' . $filename;
    if (file_exists($filepath)) {
        $filesize = filesize($filepath);
        echo "   - File size: " . number_format($filesize) . " bytes\n";
        echo "   - File path: {$filepath}\n\n";
    } else {
        echo "   ❌ File not found at: {$filepath}\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Export failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Export with search filter
echo "2. Testing export with search filter...\n";
$filtersWithSearch = [
    'startDate' => '',
    'endDate' => '',
    'search' => 'temperature',
    'sortField' => 'timestamp_device',
    'sortDirection' => 'desc'
];

try {
    $filename = $exportService->exportToCsv($filtersWithSearch);
    $stats = $exportService->getExportStats($filtersWithSearch);

    echo "   ✅ Search export successful!\n";
    echo "   - Filename: {$filename}\n";
    echo "   - Records with 'temperature': {$stats['total_records']}\n\n";
} catch (Exception $e) {
    echo "   ❌ Search export failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Export with date range
echo "3. Testing export with date range...\n";
$filtersWithDate = [
    'startDate' => '2025-07-01',
    'endDate' => '2025-07-31',
    'search' => '',
    'sortField' => 'temperature',
    'sortDirection' => 'asc'
];

try {
    $filename = $exportService->exportToCsv($filtersWithDate);
    $stats = $exportService->getExportStats($filtersWithDate);

    echo "   ✅ Date range export successful!\n";
    echo "   - Filename: {$filename}\n";
    echo "   - Records in July 2025: {$stats['total_records']}\n\n";
} catch (Exception $e) {
    echo "   ❌ Date range export failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Export all data
echo "4. Testing export all data...\n";
$filtersAll = [
    'startDate' => '',
    'endDate' => '',
    'search' => '',
    'sortField' => 'id',
    'sortDirection' => 'desc'
];

try {
    $filename = $exportService->exportToCsv($filtersAll);
    $stats = $exportService->getExportStats($filtersAll);

    echo "   ✅ All data export successful!\n";
    echo "   - Filename: {$filename}\n";
    echo "   - Total records: {$stats['total_records']}\n\n";
} catch (Exception $e) {
    echo "   ❌ All data export failed: " . $e->getMessage() . "\n\n";
}

echo "Export functionality test completed!\n";
echo "Check the storage/app/public/exports/ directory for generated files.\n";
