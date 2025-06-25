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
        Schema::table('livestock_batches', function (Blueprint $table) {
            // Add price-related columns
            $table->decimal('price_per_unit', 12, 2)->default(0)->after('weight_total');
            $table->decimal('price_total', 12, 2)->default(0)->after('price_per_unit');
            $table->decimal('price_value', 12, 2)->nullable()->after('price_total');
            $table->string('price_type')->default('per_unit')->after('price_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('livestock_batches', function (Blueprint $table) {
            $table->dropColumn(['price_per_unit', 'price_total', 'price_value', 'price_type']);
        });
    }
}; 