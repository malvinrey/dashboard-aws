<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ScadaDataService;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Log Data Filtering Functionality\n";
echo "========================================\n\n";

$scadaDataService = app(ScadaDataService::class);

// Test 1: Basic filtering
echo "1. Testing basic date filtering...\n";
$startDate = '2025-01-01';
$endDate = '2025-12-31';
$search = '';
$sortField = 'id';
$sortDirection = 'desc';

$filteredData = $scadaDataService->getLogDataWithFilters(10, $startDate, $endDate, $search, $sortField, $sortDirection);
$totalRecords = $scadaDataService->getTotalRecordsWithFilters($startDate, $endDate, $search);

echo "   - Date range: {$startDate} to {$endDate}\n";
echo "   - Records found: " . count($filteredData) . "\n";
echo "   - Total records with filters: {$totalRecords}\n\n";

// Test 2: Search filtering
echo "2. Testing search filtering...\n";
$search = '5'; // Search for value 5
$filteredData = $scadaDataService->getLogDataWithFilters(10, '', '', $search, $sortField, $sortDirection);
$totalRecords = $scadaDataService->getTotalRecordsWithFilters('', '', $search);

echo "   - Search term: '{$search}'\n";
echo "   - Records found: " . count($filteredData) . "\n";
echo "   - Total records with search: {$totalRecords}\n\n";

// Test 3: Sorting
echo "3. Testing column sorting...\n";
$sortField = 'temperature';
$sortDirection = 'asc';
$filteredData = $scadaDataService->getLogDataWithFilters(5, '', '', '', $sortField, $sortDirection);

echo "   - Sort by: {$sortField} ({$sortDirection})\n";
echo "   - First 5 records:\n";
foreach ($filteredData as $index => $record) {
    echo "     " . ($index + 1) . ". ID: {$record->id}, Temperature: {$record->temperature}\n";
}
echo "\n";

// Test 4: Combined filters
echo "4. Testing combined filters...\n";
$startDate = '2025-01-01';
$endDate = '2025-12-31';
$search = 'temperature';
$sortField = 'timestamp_device';
$sortDirection = 'desc';

$filteredData = $scadaDataService->getLogDataWithFilters(5, $startDate, $endDate, $search, $sortField, $sortDirection);
$totalRecords = $scadaDataService->getTotalRecordsWithFilters($startDate, $endDate, $search);

echo "   - Combined filters applied\n";
echo "   - Records found: " . count($filteredData) . "\n";
echo "   - Total records with all filters: {$totalRecords}\n\n";

echo "Test completed successfully!\n";
