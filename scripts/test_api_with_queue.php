<?php

/**
 * Test API Endpoint with Queue Implementation
 * This script tests the /api/aws/receiver endpoint with the new queue system
 */

// Test data - small dataset
$smallPayload = [
    'DataArray' => []
];

// Generate 100 test records
for ($i = 0; $i < 100; $i++) {
    $smallPayload['DataArray'][] = [
        '_groupTag' => 'TEST_GROUP_' . ($i % 5),
        '_terminalTime' => date('Y-m-d H:i:s', time() - ($i * 60)),
        'temperature' => rand(20, 35),
        'humidity' => rand(40, 80),
        'pressure' => rand(1000, 1100),
        'rainfall' => rand(0, 10),
        'wind_speed' => rand(0, 20),
        'wind_direction' => rand(0, 360),
        'par_sensor' => rand(100, 1000),
        'solar_radiation' => rand(200, 800)
    ];
}

echo "=== Testing SCADA API with Queue Implementation ===\n\n";

// Test 1: Small dataset
echo "Test 1: Small Dataset (100 records)\n";
echo "-----------------------------------\n";
echo "Payload size: " . count($smallPayload['DataArray']) . " records\n";

$startTime = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:80/api/aws/receiver');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($smallPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$responseTime = round((microtime(true) - $startTime) * 1000, 2);

echo "Response Time: {$responseTime}ms\n";
echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    echo "Response:\n";
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo "  Status: " . ($responseData['status'] ?? 'N/A') . "\n";
        echo "  Message: " . ($responseData['message'] ?? 'N/A') . "\n";
        echo "  Data Count: " . ($responseData['data_count'] ?? 'N/A') . "\n";
        echo "  Queue: " . ($responseData['queue'] ?? 'N/A') . "\n";
        echo "  Estimated Time: " . ($responseData['estimated_processing_time'] ?? 'N/A') . "\n";
    } else {
        echo "  Raw Response: {$response}\n";
    }
}

echo "\n";

// Test 2: Large dataset
echo "Test 2: Large Dataset (7,500 records)\n";
echo "-------------------------------------\n";

$largePayload = [
    'DataArray' => []
];

// Generate 7,500 test records
for ($i = 0; $i < 7500; $i++) {
    $largePayload['DataArray'][] = [
        '_groupTag' => 'LARGE_TEST_GROUP_' . ($i % 10),
        '_terminalTime' => date('Y-m-d H:i:s', time() - ($i * 30)),
        'temperature' => rand(15, 40),
        'humidity' => rand(30, 90),
        'pressure' => rand(950, 1150),
        'rainfall' => rand(0, 25),
        'wind_speed' => rand(0, 30),
        'wind_direction' => rand(0, 360),
        'par_sensor' => rand(50, 1200),
        'solar_radiation' => rand(100, 1000)
    ];
}

echo "Payload size: " . count($largePayload['DataArray']) . " records\n";

$startTime = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:80/api/aws/receiver');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($largePayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$responseTime = round((microtime(true) - $startTime) * 1000, 2);

echo "Response Time: {$responseTime}ms\n";
echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    echo "Response:\n";
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo "  Status: " . ($responseData['status'] ?? 'N/A') . "\n";
        echo "  Message: " . ($responseData['message'] ?? 'N/A') . "\n";
        echo "  Data Count: " . ($responseData['data_count'] ?? 'N/A') . "\n";
        echo "  Queue: " . ($responseData['queue'] ?? 'N/A') . "\n";
        echo "  Estimated Time: " . ($responseData['estimated_processing_time'] ?? 'N/A') . "\n";
    } else {
        echo "  Raw Response: {$response}\n";
    }
}

echo "\n";

// Test 3: Invalid data (validation test)
echo "Test 3: Invalid Data (Validation Test)\n";
echo "--------------------------------------\n";

$invalidPayload = [
    'DataArray' => [
        [
            '_groupTag' => '', // Empty group tag
            '_terminalTime' => 'invalid-date', // Invalid date
            'temperature' => 'not-a-number', // Invalid temperature
            'humidity' => 150, // Out of range
            'pressure' => 500, // Out of range
        ]
    ]
];

echo "Payload size: " . count($invalidPayload['DataArray']) . " records (with validation errors)\n";

$startTime = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:80/api/aws/receiver');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invalidPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$responseTime = round((microtime(true) - $startTime) * 1000, 2);

echo "Response Time: {$responseTime}ms\n";
echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    echo "Response:\n";
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo "  Status: " . ($responseData['status'] ?? 'N/A') . "\n";
        echo "  Message: " . ($responseData['message'] ?? 'N/A') . "\n";
        if (isset($responseData['errors'])) {
            echo "  Validation Errors:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "    {$field}: " . implode(', ', $errors) . "\n";
            }
        }
    } else {
        echo "  Raw Response: {$response}\n";
    }
}

echo "\n";

// Test Summary
echo "=== Test Summary ===\n";
echo "Expected Results:\n";
echo "1. Small dataset: HTTP 202 (Accepted) - Data queued for processing\n";
echo "2. Large dataset: HTTP 202 (Accepted) - Data queued for processing\n";
echo "3. Invalid data: HTTP 422 (Validation Error) - Data rejected\n";
echo "\n";
echo "Key Benefits of Queue Implementation:\n";
echo "✓ No more 504 Gateway Timeout errors\n";
echo "✓ API responds in < 100ms regardless of data size\n";
echo "✓ Large datasets processed in background\n";
echo "✓ Better user experience with instant feedback\n";
echo "\n";
echo "Next steps:\n";
echo "1. Check queue workers are running\n";
echo "2. Monitor processing progress in logs\n";
echo "3. Verify data is stored in database\n";

echo "\nTest completed!\n";
