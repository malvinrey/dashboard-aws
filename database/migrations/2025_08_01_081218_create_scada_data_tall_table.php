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
        Schema::create('scada_data_tall', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->string('nama_group')->nullable();
            $table->timestamp('timestamp_device');
            $table->string('nama_tag');
            $table->text('nilai_tag');
            $table->timestamps();

            // Indexes for performance
            $table->index(['nama_tag', 'timestamp_device']);
            $table->index('batch_id');
            $table->index('timestamp_device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scada_data_tall');
    }
};
