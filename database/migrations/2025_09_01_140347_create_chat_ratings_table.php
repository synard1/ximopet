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
        Schema::create('chat_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chat_message_id')->index();
            $table->uuid('user_id')->index();
            $table->uuid('company_id')->nullable()->index();
            $table->tinyInteger('rating')->comment('Rating from 1-5');
            $table->text('feedback')->nullable()->comment('Optional user feedback');
            $table->json('metadata')->nullable()->comment('Additional metadata for analysis');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('chat_message_id')->references('id')->on('chat_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_ratings');
    }
};
