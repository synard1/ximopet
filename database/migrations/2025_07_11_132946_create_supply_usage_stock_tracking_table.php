<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supply_usage_stock_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(Str::uuid());
            $table->uuid('supply_usage_id');
            $table->uuid('supply_stock_id');
            $table->decimal('quantity_processed', 15, 2)->default(0);
            $table->timestamp('last_processed_at')->nullable();
            $table->uuid('last_processed_by')->nullable();
            $table->string('status', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['supply_usage_id', 'supply_stock_id'], 'idx_usage_stock_tracking');
            $table->index('supply_usage_id');
            $table->index('supply_stock_id');
            $table->index('status');

            // Foreign keys
            $table->foreign('supply_usage_id')->references('id')->on('supply_usages')->onDelete('cascade');
            $table->foreign('supply_stock_id')->references('id')->on('supply_stocks')->onDelete('cascade');
            $table->foreign('last_processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_usage_stock_tracking');
    }
};
