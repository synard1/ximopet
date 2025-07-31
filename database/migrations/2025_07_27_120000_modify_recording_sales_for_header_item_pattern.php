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
        // Add columns to recording_sales to support header-item pattern
        Schema::table('recording_sales', function (Blueprint $table) {
            // Add total columns for header aggregation
            $table->integer('total_quantity')->default(0)->after('quantity');
            $table->decimal('total_weight', 15, 2)->default(0)->after('weight');
            $table->decimal('total_amount', 15, 2)->default(0)->after('price');

            // Add is_header flag to distinguish header records from legacy single records
            $table->boolean('is_header')->default(false)->after('livestock_batch_id');

            // Add batch_count for easy tracking
            $table->integer('batch_count')->default(1)->after('is_header');

            // Indexes for new columns
            $table->index('is_header', 'idx_rs_is_header');
            $table->index(['is_header', 'livestock_id'], 'idx_rs_header_livestock');
        });

        // Create recording_sale_items table if not exists
        if (!Schema::hasTable('recording_sale_items')) {
            Schema::create('recording_sale_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('recording_sale_id'); // Points to recording_sales (header)
                $table->uuid('livestock_batch_id');
                $table->integer('quantity');
                $table->decimal('weight', 15, 2);
                $table->decimal('price_per_unit', 15, 2);
                $table->decimal('amount', 15, 2);
                $table->json('metadata')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('recording_sale_id', 'idx_rsi_recording_sale');
                $table->index('livestock_batch_id', 'idx_rsi_batch');
                $table->index(['recording_sale_id', 'livestock_batch_id'], 'idx_rsi_sale_batch');

                // Foreign Keys
                $table->foreign('recording_sale_id', 'fk_rsi_recording_sale')
                    ->references('id')->on('recording_sales')->onDelete('cascade');
                $table->foreign('livestock_batch_id', 'fk_rsi_batch')
                    ->references('id')->on('livestock_batches')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop recording_sale_items table
        Schema::dropIfExists('recording_sale_items');

        // Remove added columns from recording_sales
        Schema::table('recording_sales', function (Blueprint $table) {
            $table->dropIndex('idx_rs_is_header');
            $table->dropIndex('idx_rs_header_livestock');

            $table->dropColumn([
                'total_quantity',
                'total_weight',
                'total_amount',
                'is_header',
                'batch_count'
            ]);
        });
    }
};
