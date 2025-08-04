<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaDataWide extends Model
{
    use HasFactory;

    // Beritahu model untuk menggunakan tabel ini
    protected $table = 'scada_data_wides';

    // Izinkan kolom-kolom ini untuk diisi secara massal
    protected $fillable = [
        'batch_id',
        'nama_group',
        'timestamp_device',
        'par_sensor',
        'solar_radiation',
        'wind_speed',
        'wind_direction',
        'temperature',
        'humidity',
        'pressure',
        'rainfall',
    ];

    protected $casts = [
        'timestamp_device' => 'datetime',
        'par_sensor' => 'float',
        'solar_radiation' => 'float',
        'wind_speed' => 'float',
        'wind_direction' => 'float',
        'temperature' => 'float',
        'humidity' => 'float',
        'pressure' => 'float',
        'rainfall' => 'float',
    ];
}
