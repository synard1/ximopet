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
        Schema::create('recording_sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('recording_sale_id');
            $table->uuid('livestock_id');
            $table->uuid('livestock_batch_id');
            $table->integer('quantity');
            $table->decimal('weight', 15, 2);
            $table->decimal('price_per_unit', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->json('metadata')->nullable();
            $table->json('data')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('recording_sale_id')->references('id')->on('recording_sales')->onDelete('cascade');
            $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('livestock_batch_id')->references('id')->on('livestock_batches')->onDelete('cascade');

            // Indexes
            $table->index('recording_sale_id');
            $table->index('livestock_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recording_sale_items');
    }
};
