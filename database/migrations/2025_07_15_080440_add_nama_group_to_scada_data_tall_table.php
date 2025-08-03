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
            // Tambahkan kolom nama_group setelah kolom 'batch_id'
            // batch_id sudah ada di migration sebelumnya
            $table->string('nama_group')->after('batch_id')->nullable();

            // Tambahkan index untuk mempercepat query
            $table->index('nama_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scada_data_tall', function (Blueprint $table) {
            // Hapus index dan kolom jika migrasi di-rollback
            $table->dropIndex(['nama_group']);
            $table->dropColumn(['nama_group']);
        });
    }
};
