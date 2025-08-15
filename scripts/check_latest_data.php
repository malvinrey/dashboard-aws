<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ScadaDataWide;

echo "=== Latest SCADA Data Check ===\n\n";

try {
    $latest = ScadaDataWide::latest('timestamp_device')->first();

    if ($latest) {
        echo "Latest Record ID: " . $latest->id . "\n";
        echo "Timestamp: " . $latest->timestamp_device . "\n";
        echo "Batch ID: " . $latest->batch_id . "\n";
        echo "Group: " . $latest->nama_group . "\n\n";

        echo "Sensor Values:\n";
        echo "- Temperature: " . ($latest->temperature ?? 'NULL') . " °C\n";
        echo "- Humidity: " . ($latest->humidity ?? 'NULL') . " %\n";
        echo "- Pressure: " . ($latest->pressure ?? 'NULL') . " hPa\n";
        echo "- Wind Speed: " . ($latest->wind_speed ?? 'NULL') . " m/s\n";
        echo "- Wind Direction: " . ($latest->wind_direction ?? 'NULL') . " °\n";
        echo "- Rainfall: " . ($latest->rainfall ?? 'NULL') . " mm\n";
        echo "- Solar Radiation: " . ($latest->solar_radiation ?? 'NULL') . " W/m²\n";
        echo "- PAR Sensor: " . ($latest->par_sensor ?? 'NULL') . " μmol/m²/s\n";

        echo "\n=== Data Analysis ===\n";
        $nonNullSensors = [];
        foreach (['temperature', 'humidity', 'pressure', 'wind_speed', 'wind_direction', 'rainfall', 'solar_radiation', 'par_sensor'] as $sensor) {
            if (!is_null($latest->$sensor)) {
                $nonNullSensors[] = $sensor;
            }
        }

        echo "Sensors with data: " . count($nonNullSensors) . "\n";
        echo "Sensor list: " . implode(', ', $nonNullSensors) . "\n";
    } else {
        echo "No data found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
