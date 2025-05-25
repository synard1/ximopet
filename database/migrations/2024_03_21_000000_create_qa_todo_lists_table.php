<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_todo_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('module_name');
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->enum('environment', ['dev', 'staging', 'production']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('url')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_todo_lists');
    }
};
