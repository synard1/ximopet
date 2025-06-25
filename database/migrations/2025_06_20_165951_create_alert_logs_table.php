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
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 100)->index(); // Alert type (feed_stats_discrepancy, feed_usage_created, etc.)
            $table->enum('level', ['info', 'warning', 'error', 'critical'])->default('info')->index();
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable(); // Alert-specific data
            $table->json('metadata')->nullable(); // Additional metadata (channels, recipients, etc.)
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['type', 'level']);
            $table->index(['created_at', 'level']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
