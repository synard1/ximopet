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
        Schema::create('database_performance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('operation_type'); // create, update, delete, bulk_create, bulk_update, bulk_delete, transaction
            $table->string('model_class');
            $table->uuid('model_id')->nullable();
            $table->string('table_name');
            $table->integer('records_count')->default(1);
            $table->decimal('execution_time_ms', 10, 3); // Execution time in milliseconds
            $table->decimal('memory_usage_mb', 8, 2); // Memory usage in MB
            $table->integer('query_count')->default(0);
            $table->integer('slow_query_count')->default(0);
            $table->string('status'); // success, error, slow
            $table->text('error_message')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->string('request_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['model_class', 'operation_type']);
            $table->index(['status', 'created_at']);
            $table->index(['execution_time_ms']);
            $table->index(['user_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['created_at']);
            $table->index(['table_name', 'created_at']);

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_performance_logs');
    }
};
