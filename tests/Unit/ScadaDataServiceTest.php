<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ScadaDataService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScadaDataServiceTest extends TestCase
{
    private ScadaDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScadaDataService();
    }

    public function test_downsampling_reduces_data_points_correctly()
    {
        // Create test data with 2000 points
        $testData = collect();
        for ($i = 0; $i < 2000; $i++) {
            $testData->push([
                'timestamp' => Carbon::now()->addSeconds($i),
                'value' => sin($i * 0.1) + rand(-10, 10) / 100 // Sine wave with noise
            ]);
        }

        // Test downsampling to 1000 points
        $downsampled = $this->service->downsampleData($testData, 1000);

        // Verify the result
        $this->assertCount(1000, $downsampled);
        $this->assertLessThan(2000, count($downsampled));

        // Verify data structure
        foreach ($downsampled as $point) {
            $this->assertIsArray($point);
            $this->assertCount(2, $point);
            $this->assertIsNumeric($point[0]); // timestamp
            $this->assertIsNumeric($point[1]); // value
        }
    }

    public function test_downsampling_preserves_first_and_last_points()
    {
        // Create test data
        $testData = collect();
        for ($i = 0; $i < 100; $i++) {
            $testData->push([
                'timestamp' => Carbon::now()->addSeconds($i),
                'value' => $i
            ]);
        }

        // Test downsampling to 10 points
        $downsampled = $this->service->downsampleData($testData, 10);

        // Verify first and last points are preserved
        $this->assertEquals($testData->first()['timestamp']->getTimestamp() * 1000, $downsampled[0][0]);
        $this->assertEquals($testData->first()['value'], $downsampled[0][1]);

        $this->assertEquals($testData->last()['timestamp']->getTimestamp() * 1000, $downsampled[count($downsampled) - 1][0]);
        $this->assertEquals($testData->last()['value'], $downsampled[count($downsampled) - 1][1]);
    }

    public function test_downsampling_returns_original_data_when_threshold_not_exceeded()
    {
        // Create test data with only 50 points
        $testData = collect();
        for ($i = 0; $i < 50; $i++) {
            $testData->push([
                'timestamp' => Carbon::now()->addSeconds($i),
                'value' => $i
            ]);
        }

        // Test downsampling to 100 points (threshold not exceeded)
        $downsampled = $this->service->downsampleData($testData, 100);

        // Should return original data
        $this->assertCount(50, $downsampled);
    }

    public function test_lttb_algorithm_calculates_triangle_area_correctly()
    {
        // Test triangle area calculation
        $a = [0, 0];   // Point at origin
        $b = [3, 0];   // Point at (3,0)
        $c = [0, 4];   // Point at (0,4)

        // This should form a right triangle with area = (3 * 4) / 2 = 6
        $area = $this->service->triangleArea($a, $b, $c);

        $this->assertEquals(6.0, $area);
    }
}
