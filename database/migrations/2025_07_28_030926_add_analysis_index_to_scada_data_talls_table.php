<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scada_data_tall', function (Blueprint $table) {
            // Indeks ini mempercepat pencarian berdasarkan tag dan rentang waktu
            $table->index(['nama_tag', 'timestamp_device']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scada_data_tall', function (Blueprint $table) {
            $table->dropIndex(['nama_tag', 'timestamp_device']);
        });
    }
};
