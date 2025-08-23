<?php

/**
 * Script untuk menguji WebSocket broadcasting
 * Mengirim data test ke API dan memverifikasi bahwa data diterima
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🔧 Testing WebSocket Broadcasting\n";
echo "================================\n\n";

// Konfigurasi
$apiUrl = 'http://localhost:8000/api/receiver';
$testData = [
    'DataArray' => [
        [
            '_groupTag' => 'test_group_1',
            '_terminalTime' => now()->toISOString(),
            'temperature' => rand(20, 30),
            'humidity' => rand(40, 80),
            'pressure' => rand(1000, 1100),
            'rainfall' => rand(0, 10),
            'wind_speed' => rand(0, 20),
            'wind_direction' => rand(0, 360),
            'par_sensor' => rand(0, 1000),
            'solar_radiation' => rand(0, 800)
        ],
        [
            '_groupTag' => 'test_group_2',
            '_terminalTime' => now()->toISOString(),
            'temperature' => rand(20, 30),
            'humidity' => rand(40, 80),
            'pressure' => rand(1000, 1100),
            'rainfall' => rand(0, 10),
            'wind_speed' => rand(0, 20),
            'wind_direction' => rand(0, 360),
            'par_sensor' => rand(0, 1000),
            'solar_radiation' => rand(0, 800)
        ]
    ]
];

echo "📡 Sending test data to API...\n";
echo "API URL: {$apiUrl}\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

try {
    // Kirim data ke API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ cURL Error: {$error}\n";
        exit(1);
    }

    echo "📊 API Response:\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "Response: {$response}\n\n";

    if ($httpCode === 202) {
        echo "✅ Data berhasil dikirim dan di-queue!\n";

        // Parse response
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo "Status: {$responseData['status']}\n";
            echo "Message: {$responseData['message']}\n";
            echo "Data Count: {$responseData['data_count']}\n";
            echo "Queue: {$responseData['queue']}\n";
            echo "Response Time: {$responseData['response_time_ms']}ms\n";
            echo "Estimated Processing: {$responseData['estimated_processing_time']}\n";
        }

        echo "\n🔍 Verifikasi Broadcasting:\n";
        echo "1. Buka file test-websocket-fix.html di browser\n";
        echo "2. Pastikan status WebSocket menunjukkan 'Connected'\n";
        echo "3. Data seharusnya diterima dalam beberapa detik\n";
        echo "4. Periksa console browser untuk log detail\n";
    } else {
        echo "❌ API Error: HTTP {$httpCode}\n";
        echo "Response: {$response}\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n📝 Tips Troubleshooting:\n";
echo "1. Pastikan Laravel server berjalan di port 8000\n";
echo "2. Pastikan Reverb server berjalan di port 8080\n";
echo "3. Pastikan Redis server berjalan\n";
echo "4. Periksa log Laravel di storage/logs/laravel.log\n";
echo "5. Periksa log Reverb server\n";
echo "6. Pastikan queue worker berjalan\n";

echo "\n🚀 Untuk menjalankan queue worker:\n";
echo "php artisan queue:work --queue=scada-processing,scada-large-datasets\n";

echo "\n🔧 Untuk memulai semua service:\n";
echo "start-all-services-fixed.bat\n";
