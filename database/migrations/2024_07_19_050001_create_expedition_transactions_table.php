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
        Schema::create('expedition_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();

            // Reference to related transaction
            $table->string('transaction_type'); // 'sales', 'purchase', 'feed_purchase', 'supply_purchase'
            $table->uuid('transaction_id'); // ID of the related transaction
            $table->string('invoice_number')->nullable(); // For easier tracking

            // Expedition details
            $table->uuid('expedition_id');

            // Shipping details
            $table->date('shipping_date');
            $table->text('destination_address')->nullable();
            $table->string('destination_zone')->nullable(); // Zone name for reporting

            // Weight (simplified)
            $table->decimal('total_weight', 10, 2)->default(0); // Actual weight shipped

            // Cost (simplified - just actual cost paid)
            $table->decimal('expedition_cost', 15, 2)->default(0); // Total expedition cost actually paid
            $table->string('currency', 3)->default('IDR');
            $table->text('notes')->nullable();

            // Optional: cost breakdown as JSON if needed
            $table->json('cost_details')->nullable(); // Optional detailed breakdown

            // Status tracking
            $table->string('status')->default('pending'); // pending, shipped, delivered, cancelled
            $table->timestamp('delivered_at')->nullable();
            $table->string('tracking_number')->nullable();

            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('expedition_id')->references('id')->on('partners');

            // Indexes for reporting and analytics
            $table->index(['company_id', 'shipping_date']);
            $table->index(['expedition_id', 'shipping_date']);
            $table->index(['transaction_type', 'transaction_id']);
            $table->index(['destination_zone', 'shipping_date']);
            $table->index(['status', 'shipping_date']);
            $table->index(['expedition_cost', 'shipping_date']); // For cost analysis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedition_transactions');
    }
};
