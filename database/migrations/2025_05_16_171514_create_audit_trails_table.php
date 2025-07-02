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
            $table->uuid('user_id')->index();
            $table->string('action')->index();
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
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->string('table_name')->index();

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
