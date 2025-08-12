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
        // Drop the scada_data_tall table as we only need scada_data_wides
        Schema::dropIfExists('scada_data_tall');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the scada_data_tall table if needed
        Schema::create('scada_data_tall', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp');
            $table->string('tag_name');
            $table->decimal('value', 10, 4);
            $table->string('quality');
            $table->timestamps();

            $table->index(['timestamp', 'tag_name']);
        });
    }
};
