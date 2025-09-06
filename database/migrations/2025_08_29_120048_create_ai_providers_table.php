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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['openwebui', 'ollama']);
            $table->string('base_url');
            $table->string('api_key')->nullable();
            $table->string('default_model')->nullable();
            $table->json('available_models')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('company_id')->nullable();
            $table->json('configuration')->nullable(); // Additional provider-specific config
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
