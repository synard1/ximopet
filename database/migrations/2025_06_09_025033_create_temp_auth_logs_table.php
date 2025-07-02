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
        Schema::create('temp_auth_logs', function (Blueprint $table) {
            $table->id();

            // User yang mendapat autorisasi
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

            // User yang memberikan autorisasi (nullable untuk password-based auth)
            $table->foreignUuid('authorizer_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Action yang dilakukan: 'granted', 'revoked', 'expired'
            $table->enum('action', ['granted', 'revoked', 'expired'])->default('granted');

            // Komponen/halaman yang diautorisasi
            $table->string('component')->nullable();

            // Alasan autorisasi
            $table->text('reason')->nullable();

            // Durasi autorisasi dalam menit
            $table->integer('duration_minutes')->nullable();

            // Method autorisasi: 'password', 'user', 'role', 'permission'
            $table->enum('auth_method', ['password', 'user', 'role', 'permission'])->default('password');

            // IP address dan user agent untuk tracking
            $table->string('ip_address', 45)->nullable(); // IPv6 support
            $table->text('user_agent')->nullable();

            // Timestamps untuk lifecycle
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('auto_expired_at')->nullable();

            // Metadata tambahan (JSON)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes untuk performa dan reporting
            $table->index(['user_id', 'action']);
            $table->index(['action', 'created_at']);
            $table->index(['component', 'action']);
            $table->index(['auth_method', 'action']);
            $table->index(['granted_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_auth_logs');
    }
};
