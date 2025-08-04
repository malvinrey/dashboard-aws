<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scada_data_wides', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->string('nama_group');
            $table->timestamp('timestamp_device');

            // Definisikan setiap sensor sebagai kolomnya sendiri
            // Gunakan nullable() jika ada kemungkinan sensor tidak mengirim data
            $table->decimal('par_sensor', 10, 2)->nullable();
            $table->decimal('solar_radiation', 10, 2)->nullable();
            $table->decimal('wind_speed', 10, 2)->nullable();
            $table->decimal('wind_direction', 10, 2)->nullable();
            $table->decimal('temperature', 10, 2)->nullable();
            $table->decimal('humidity', 10, 2)->nullable();
            $table->decimal('pressure', 10, 2)->nullable();
            $table->decimal('rainfall', 10, 2)->nullable();

            $table->timestamps();

            // Tambahkan index untuk performa query
            $table->index('timestamp_device');
            $table->index('batch_id');
        });

        // Opsional: Migrasikan data lama ke tabel baru
        // Query ini mungkin memakan waktu jika data Anda sangat besar
        DB::statement("
            INSERT INTO scada_data_wides (batch_id, nama_group, timestamp_device, par_sensor, solar_radiation, wind_speed, wind_direction, temperature, humidity, pressure, rainfall, created_at, updated_at)
            SELECT
                t.batch_id,
                t.nama_group,
                t.timestamp_device,
                MAX(CASE WHEN t.nama_tag = 'par_sensor' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS par_sensor,
                MAX(CASE WHEN t.nama_tag = 'solar_radiation' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS solar_radiation,
                MAX(CASE WHEN t.nama_tag = 'wind_speed' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS wind_speed,
                MAX(CASE WHEN t.nama_tag = 'wind_direction' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS wind_direction,
                MAX(CASE WHEN t.nama_tag = 'temperature' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS temperature,
                MAX(CASE WHEN t.nama_tag = 'humidity' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS humidity,
                MAX(CASE WHEN t.nama_tag = 'pressure' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS pressure,
                MAX(CASE WHEN t.nama_tag = 'rainfall' THEN CAST(t.nilai_tag AS DECIMAL(10,2)) END) AS rainfall,
                MAX(t.created_at) as created_at,
                MAX(t.updated_at) as updated_at
            FROM
                scada_data_tall t
            GROUP BY
                t.batch_id, t.nama_group, t.timestamp_device
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scada_data_wides');
    }
};
