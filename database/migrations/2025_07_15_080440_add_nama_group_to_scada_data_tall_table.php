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
            // Tambahkan kolom batch_id dan nama_group setelah kolom 'id'
            // Kita buat nullable() agar data lama tidak error
            $table->uuid('batch_id')->after('id')->nullable();
            $table->string('nama_group')->after('batch_id')->nullable();

            // Tambahkan index untuk mempercepat query
            $table->index('batch_id');
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
            $table->dropIndex(['batch_id']);
            $table->dropColumn(['nama_group', 'batch_id']);
        });
    }
};
