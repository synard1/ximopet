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
        Schema::create('expedition_tariffs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('expedition_id');

            // Simplified tariff structure - just for reference, not calculation
            $table->string('zone_name'); // e.g., 'Jakarta', 'Bandung', 'Jawa Barat'
            $table->text('zone_description')->nullable(); // Description of coverage area
            $table->decimal('estimated_rate_per_kg', 10, 2)->nullable(); // Just for estimation/reference
            $table->text('notes')->nullable(); // Any additional notes

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('expedition_id')->references('id')->on('expeditions')->onDelete('cascade');
            $table->index(['company_id', 'expedition_id']);
            $table->index(['zone_name', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedition_tariffs');
    }
};
