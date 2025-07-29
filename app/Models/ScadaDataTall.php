<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaDataTall extends Model
{
    use HasFactory;

    // Beritahu model untuk menggunakan tabel ini
    protected $table = 'scada_data_tall';

    // Izinkan kolom-kolom ini untuk diisi secara massal
    protected $fillable = [
        'timestamp_device',
        'batch_id',
        'nama_group',
        'nama_tag',
        'nilai_tag',
    ];
    // App\Models\ScadaDataTall.php
protected $casts = [
    'nilai_tag' => 'float', // or 'integer' depending on your data
    'timestamp_device' => 'datetime',
];
}
