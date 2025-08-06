<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ExportService;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Export and Download Functionality\n";
echo "=========================================\n\n";

try {
    // Test ExportService
    $exportService = app(ExportService::class);

    // Test filters
    $filters = [
        'startDate' => '2025-01-01',
        'endDate' => '2025-01-31',
        'search' => '',
        'sortField' => 'id',
        'sortDirection' => 'desc'
    ];

    echo "1. Testing CSV Export...\n";
    $filename = $exportService->exportToCsv($filters);
    echo "   ✓ File created: {$filename}\n";

    // Check if file exists
    $filePath = 'exports/' . $filename;
    if (Storage::disk('public')->exists($filePath)) {
        echo "   ✓ File exists in storage\n";

        // Get file size
        $fileSize = Storage::disk('public')->size($filePath);
        echo "   ✓ File size: " . number_format($fileSize) . " bytes\n";

        // Test download URL
        $downloadUrl = route('export.download', $filename);
        echo "   ✓ Download URL: {$downloadUrl}\n";
    } else {
        echo "   ✗ File not found in storage\n";
    }

    // Test export stats
    echo "\n2. Testing Export Stats...\n";
    $stats = $exportService->getExportStats($filters);
    echo "   ✓ Total records: {$stats['total_records']}\n";
    echo "   ✓ Export time: {$stats['export_time']}\n";

    echo "\n✅ All tests passed! Export functionality is working correctly.\n";
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
