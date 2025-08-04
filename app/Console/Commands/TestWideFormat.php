<?php

namespace App\Console\Commands;

use App\Models\ScadaDataTall;
use App\Models\ScadaDataWide;
use App\Services\ScadaDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TestWideFormat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:wide-format {--performance : Run performance tests} {--data : Show data comparison}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and validate wide format migration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Testing Wide Format Migration ===');
        $this->newLine();

        // Basic data comparison
        if ($this->option('data') || !$this->option('performance')) {
            $this->testDataComparison();
        }

        // Performance tests
        if ($this->option('performance')) {
            $this->testPerformance();
        }

        $this->info('=== Test Complete ===');
    }

    private function testDataComparison()
    {
        $this->info('1. Data Comparison:');

        $tallCount = ScadaDataTall::count();
        $wideCount = ScadaDataWide::count();
        $compressionRatio = round($tallCount / $wideCount, 2);

        $this->line("   - ScadaDataTall: {$tallCount} records");
        $this->line("   - ScadaDataWide: {$wideCount} records");
        $this->line("   - Compression ratio: {$compressionRatio}x");
        $this->newLine();

        // Sample data structure
        $this->info('2. Sample Data Structure:');
        $sampleWide = ScadaDataWide::first();

        if ($sampleWide) {
            $this->line("   - Sample record ID: {$sampleWide->id}");
            $this->line("   - Timestamp: {$sampleWide->timestamp_device}");

            $sensors = ['par_sensor', 'solar_radiation', 'wind_speed', 'wind_direction', 'temperature', 'humidity', 'pressure', 'rainfall'];
            $availableSensors = [];

            foreach ($sensors as $sensor) {
                if (!is_null($sampleWide->$sensor)) {
                    $availableSensors[] = $sensor;
                }
            }

            $this->line("   - Available sensors: " . implode(', ', $availableSensors));
            $this->line("   - Sample values:");

            foreach ($availableSensors as $sensor) {
                $this->line("     * {$sensor}: {$sampleWide->$sensor}");
            }
        } else {
            $this->error("   - No data found in wide table");
        }
        $this->newLine();

        // Service tests
        $this->info('3. Service Tests:');
        $service = new ScadaDataService();

        // Test getDashboardMetrics
        $metrics = $service->getDashboardMetrics();
        $this->line("   - getDashboardMetrics(): Found " . count($metrics['metrics']) . " metrics");
        foreach ($metrics['metrics'] as $tag => $metric) {
            $this->line("     * {$tag}: {$metric['value']} {$metric['unit']}");
        }
        $this->newLine();

        // Test getUniqueTags
        $tags = $service->getUniqueTags();
        $this->line("   - getUniqueTags(): " . implode(', ', $tags->toArray()));
        $this->newLine();

        // Test getLatestDataPoint
        $latestData = $service->getLatestDataPoint(['temperature', 'humidity']);
        if ($latestData) {
            $this->line("   - getLatestDataPoint(): {$latestData['timestamp']}");
            foreach ($latestData['metrics'] as $tag => $value) {
                $this->line("     * {$tag}: {$value}");
            }
        } else {
            $this->error("   - No latest data found");
        }
        $this->newLine();
    }

    private function testPerformance()
    {
        $this->info('4. Performance Tests:');

        $service = new ScadaDataService();
        $startDate = now()->subDay()->toDateTimeString();
        $endDate = now()->toDateTimeString();

        // Test historical chart data performance
        $this->line("   - Testing getHistoricalChartData()...");
        $startTime = microtime(true);

        $chartData = $service->getHistoricalChartData(['temperature', 'humidity'], 'hour', $startDate, $endDate);

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        $this->line("     * Execution time: {$executionTime}ms");
        $this->line("     * Chart data traces: " . count($chartData['data']));

        foreach ($chartData['data'] as $trace) {
            $this->line("     * Trace '{$trace['name']}': " . count($trace['x']) . " data points");
        }
        $this->newLine();

        // Test dashboard metrics performance
        $this->line("   - Testing getDashboardMetrics()...");
        $startTime = microtime(true);

        $metrics = $service->getDashboardMetrics();

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        $this->line("     * Execution time: {$executionTime}ms");
        $this->line("     * Metrics found: " . count($metrics['metrics']));
        $this->newLine();

        // Test latest data point performance
        $this->line("   - Testing getLatestDataPoint()...");
        $startTime = microtime(true);

        $latestData = $service->getLatestDataPoint(['temperature', 'humidity']);

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        $this->line("     * Execution time: {$executionTime}ms");
        if ($latestData) {
            $this->line("     * Data retrieved successfully");
        } else {
            $this->error("     * No data found");
        }
        $this->newLine();

        // Database query performance comparison
        $this->info('5. Database Query Performance:');

        // Test wide format query
        $startTime = microtime(true);
        $wideQuery = ScadaDataWide::whereBetween('timestamp_device', [$startDate, $endDate])
            ->whereNotNull('temperature')
            ->count();
        $endTime = microtime(true);
        $wideTime = round(($endTime - $startTime) * 1000, 2);

        // Test tall format query (for comparison)
        $startTime = microtime(true);
        $tallQuery = ScadaDataTall::where('nama_tag', 'temperature')
            ->whereBetween('timestamp_device', [$startDate, $endDate])
            ->count();
        $endTime = microtime(true);
        $tallTime = round(($endTime - $startTime) * 1000, 2);

        $this->line("   - Wide format query: {$wideTime}ms ({$wideQuery} records)");
        $this->line("   - Tall format query: {$tallTime}ms ({$tallQuery} records)");

        if ($tallTime > 0) {
            $improvement = round((($tallTime - $wideTime) / $tallTime) * 100, 1);
            $this->line("   - Performance improvement: {$improvement}%");
        }
        $this->newLine();
    }
}
