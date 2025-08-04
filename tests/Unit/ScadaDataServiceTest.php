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

    public function test_get_unique_tags_returns_correct_tags()
    {
        $tags = $this->service->getUniqueTags();

        $this->assertInstanceOf(Collection::class, $tags);
        $this->assertCount(8, $tags);

        $expectedTags = [
            'par_sensor',
            'solar_radiation',
            'wind_speed',
            'wind_direction',
            'temperature',
            'humidity',
            'pressure',
            'rainfall'
        ];

        foreach ($expectedTags as $tag) {
            $this->assertContains($tag, $tags);
        }
    }

    public function test_get_total_records_returns_integer()
    {
        $totalRecords = $this->service->getTotalRecords();

        $this->assertIsInt($totalRecords);
        $this->assertGreaterThanOrEqual(0, $totalRecords);
    }

    public function test_get_log_data_returns_collection()
    {
        $logData = $this->service->getLogData(10);

        $this->assertInstanceOf(Collection::class, $logData);
        $this->assertLessThanOrEqual(10, $logData->count());
    }

    public function test_get_dashboard_metrics_returns_array()
    {
        $metrics = $this->service->getDashboardMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('metrics', $metrics);
        $this->assertArrayHasKey('lastPayloadInfo', $metrics);

        // Check if metrics is an array
        $this->assertIsArray($metrics['metrics']);
    }
}
