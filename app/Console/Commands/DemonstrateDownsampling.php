<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DemonstrateDownsampling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scada:demo-downsampling {--points=5000 : Number of test points to generate} {--threshold=1000 : Downsampling threshold}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate the LTTB downsampling algorithm with test data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 SCADA Data Downsampling Demonstration');
        $this->newLine();

        $numPoints = (int) $this->option('points');
        $threshold = (int) $this->option('threshold');

        $this->info("Generating {$numPoints} test data points...");

        // Generate test data with realistic patterns
        $testData = $this->generateTestData($numPoints);

        $this->info("✅ Generated {$testData->count()} data points");
        $this->newLine();

        // Demonstrate downsampling
        $scadaService = new ScadaDataService();

        $this->info("Applying LTTB downsampling (threshold: {$threshold})...");
        $startTime = microtime(true);

        $downsampledData = $scadaService->downsampleData($testData, $threshold);

        $endTime = microtime(true);
        $processingTime = round(($endTime - $startTime) * 1000, 2);

        $this->info("✅ Downsampling completed in {$processingTime}ms");
        $this->newLine();

        // Display results
        $this->displayResults($testData, $downsampledData, $processingTime);

        // Show data characteristics
        $this->displayDataCharacteristics($testData, $downsampledData);
    }

    /**
     * Generate realistic test data
     */
    private function generateTestData(int $numPoints): Collection
    {
        $data = collect();
        $baseTime = Carbon::now()->subHours(24);

        for ($i = 0; $i < $numPoints; $i++) {
            $timestamp = $baseTime->copy()->addSeconds($i);

            // Generate realistic sensor data with trends and noise
            $trend = sin($i * 0.01) * 10; // Long-term trend
            $seasonal = sin($i * 0.1) * 5; // Seasonal variation
            $noise = (rand(-100, 100) / 100) * 2; // Random noise
            $spike = rand(1, 100) === 1 ? rand(-20, 20) : 0; // Occasional spikes

            $value = 25 + $trend + $seasonal + $noise + $spike; // Base temperature around 25°C

            $data->push([
                'timestamp' => $timestamp,
                'value' => round($value, 2)
            ]);
        }

        return $data;
    }

    /**
     * Display downsampling results
     */
    private function displayResults(Collection $original, array $downsampled, float $processingTime): void
    {
        $originalCount = $original->count();
        $downsampledCount = count($downsampled);
        $reduction = round((($originalCount - $downsampledCount) / $originalCount) * 100, 2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Original Data Points', number_format($originalCount)],
                ['Downsampled Points', number_format($downsampledCount)],
                ['Data Reduction', "{$reduction}%"],
                ['Processing Time', "{$processingTime}ms"],
                ['Points per Second', number_format($originalCount / ($processingTime / 1000), 0)],
            ]
        );
    }

    /**
     * Display data characteristics
     */
    private function displayDataCharacteristics(Collection $original, array $downsampled): void
    {
        $this->newLine();
        $this->info('📊 Data Characteristics');
        $this->newLine();

        // Original data stats
        $originalValues = $original->pluck('value');
        $originalMin = $originalValues->min();
        $originalMax = $originalValues->max();
        $originalAvg = round($originalValues->avg(), 2);

        // Downsampled data stats
        $downsampledValues = collect($downsampled)->pluck(1);
        $downsampledMin = $downsampledValues->min();
        $downsampledMax = $downsampledValues->max();
        $downsampledAvg = round($downsampledValues->avg(), 2);

        $this->table(
            ['Statistic', 'Original Data', 'Downsampled Data', 'Difference'],
            [
                ['Minimum', $originalMin, $downsampledMin, round($downsampledMin - $originalMin, 2)],
                ['Maximum', $originalMax, $downsampledMax, round($downsampledMax - $originalMax, 2)],
                ['Average', $originalAvg, $downsampledAvg, round($downsampledAvg - $originalAvg, 2)],
            ]
        );

        // Show first and last points preservation
        $this->newLine();
        $this->info('🔍 First and Last Points Preservation');
        $this->newLine();

        $firstOriginal = $original->first();
        $lastOriginal = $original->last();
        $firstDownsampled = $downsampled[0];
        $lastDownsampled = $downsampled[count($downsampled) - 1];

        $this->table(
            ['Point', 'Original Timestamp', 'Original Value', 'Downsampled Timestamp', 'Downsampled Value'],
            [
                [
                    'First',
                    $firstOriginal['timestamp']->format('H:i:s'),
                    $firstOriginal['value'],
                    Carbon::createFromTimestampMs($firstDownsampled[0])->format('H:i:s'),
                    $firstDownsampled[1]
                ],
                [
                    'Last',
                    $lastOriginal['timestamp']->format('H:i:s'),
                    $lastOriginal['value'],
                    Carbon::createFromTimestampMs($lastDownsampled[0])->format('H:i:s'),
                    $lastDownsampled[1]
                ],
            ]
        );
    }
}
