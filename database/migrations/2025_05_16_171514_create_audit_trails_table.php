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
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 50)->index();
            $table->string('model_type')->index();
            $table->string('model_name');
            $table->string('model_id')->nullable()->index();
            $table->json('model_ids')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable();
            $table->json('related_records')->nullable();
            $table->json('additional_info')->nullable();
            $table->json('user_info');
            $table->timestamp('timestamp');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Add index for common queries
            $table->index(['model_type', 'action']);
            $table->index(['timestamp', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
