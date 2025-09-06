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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chat_session_id');
            $table->uuid('user_id');
            $table->enum('message_type', ['user', 'assistant', 'system']);
            $table->longText('content');
            $table->json('metadata')->nullable(); // Store additional data like tokens, processing time, etc.
            $table->decimal('processing_time', 8, 3)->nullable(); // in seconds
            $table->integer('token_count')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['chat_session_id', 'created_at']);
            $table->index(['user_id', 'message_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
