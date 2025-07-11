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
        Schema::create('recording_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type', 50)->index(); // 'save', 'validate', 'calculate', etc.
            $table->uuid('livestock_id')->index(); // UUID format to match livestocks table
            $table->decimal('execution_time', 8, 4); // Execution time in seconds
            $table->boolean('success')->default(true)->index();
            $table->string('service_version', 20)->nullable(); // 'legacy', 'modular_v1.0', etc.
            $table->json('metadata')->nullable(); // Additional performance data
            $table->text('error_message')->nullable(); // Error details if failed
            $table->uuid('user_id')->nullable()->index(); // UUID format to match users table
            $table->uuid('company_id')->nullable()->index(); // UUID format to match companies table
            $table->timestamps();

            // Indexes for performance
            $table->index(['created_at', 'operation_type']);
            $table->index(['livestock_id', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index(['service_version', 'created_at']);

            // Foreign key constraints
            $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recording_performance_logs');
    }
};
