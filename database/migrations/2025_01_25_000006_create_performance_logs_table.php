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
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('service_name')->index();
            $table->string('method_name')->index();
            $table->uuid('livestock_id')->nullable()->index();
            $table->date('tanggal')->index();
            $table->decimal('execution_time_ms', 10, 2);
            $table->decimal('memory_usage_mb', 10, 2);
            $table->json('data_sources')->nullable();
            $table->string('status')->index(); // success, error
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');

            // Indexes for performance queries
            $table->index(['service_name', 'created_at']);
            $table->index(['service_name', 'status']);
            $table->index(['execution_time_ms']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_logs');
    }
};
