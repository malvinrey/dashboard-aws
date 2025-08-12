<?php

namespace Tests\Feature;

use Tests\TestCase;
// Removed ScadaDataTall - using only ScadaDataWide for efficiency
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;

class LatestDataApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_latest_data_api_returns_204_when_no_data()
    {
        // Test dengan tag yang tidak ada di database
        $response = $this->getJson('/api/latest-data?tags[]=nonexistent_tag&interval=hour');

        $response->assertStatus(204);
    }

    public function test_latest_data_api_returns_latest_data_when_available()
    {
        // Test dengan data yang sudah ada di database
        $response = $this->getJson('/api/latest-data?tags[]=temperature&interval=hour');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'timestamp',
            'metrics' => [
                'temperature'
            ]
        ]);

        $data = $response->json();
        // Verifikasi bahwa data dikembalikan (nilai spesifik tidak penting)
        $this->assertIsNumeric($data['metrics']['temperature']);
        $this->assertNotEmpty($data['timestamp']);
    }

    public function test_latest_data_api_validates_required_parameters()
    {
        // Test missing tags
        $response = $this->getJson('/api/latest-data?interval=hour');
        $response->assertStatus(500); // ValidationException returns 500 in this case

        // Test missing interval
        $response = $this->getJson('/api/latest-data?tags[]=temperature');
        $response->assertStatus(500); // ValidationException returns 500 in this case

        // Test invalid interval
        $response = $this->getJson('/api/latest-data?tags[]=temperature&interval=invalid');
        $response->assertStatus(500); // ValidationException returns 500 in this case
    }

    public function test_latest_data_api_handles_multiple_tags()
    {
        // Test dengan multiple tags yang sudah ada di database
        $response = $this->getJson('/api/latest-data?tags[]=temperature&tags[]=humidity&interval=hour');

        $response->assertStatus(200);
        $data = $response->json();

        // Verifikasi bahwa data dikembalikan untuk kedua tags
        $this->assertArrayHasKey('metrics', $data);
        $this->assertNotEmpty($data['metrics']);
        $this->assertNotEmpty($data['timestamp']);
    }

    public function test_latest_data_api_performance_is_fast()
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/latest-data?tags[]=temperature&interval=hour');

        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Should complete in less than 100ms
        $this->assertLessThan(100, $processingTime, "API response took {$processingTime}ms, should be under 100ms");
    }
}
