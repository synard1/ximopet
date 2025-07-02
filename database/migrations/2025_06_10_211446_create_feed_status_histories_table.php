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
        Schema::create('feed_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic relationship columns
            $table->string('feedable_type'); // Model class name (FeedPurchaseBatch, FeedPurchase, etc.)
            $table->uuid('feedable_id');     // Model instance ID
            $table->index(['feedable_type', 'feedable_id'], 'feed_status_histories_feedable_index');

            // Additional model information
            $table->string('model_name')->nullable(); // Simple model name for quick reference

            // Status transition
            $table->string('status_from')->nullable();
            $table->string('status_to');

            // Additional information
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data like IP, user agent, etc.

            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for better query performance
            $table->index('status_from');
            $table->index('status_to');
            $table->index('created_by');
            $table->index('created_at');
            $table->index(['status_from', 'status_to'], 'feed_status_histories_transition_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_status_histories');
    }
};
