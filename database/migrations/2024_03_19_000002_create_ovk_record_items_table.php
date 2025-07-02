<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('ovk_record_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ovk_record_id')->constrained('ovk_records')->cascadeOnDelete();
            $table->foreignUuid('supply_id');
            $table->decimal('quantity', 10, 2);
            $table->foreignUuid('unit_id');
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('supply_id')->references('id')->on('supplies');
            $table->foreign('unit_id')->references('id')->on('units');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('ovk_record_items');
    }
};
