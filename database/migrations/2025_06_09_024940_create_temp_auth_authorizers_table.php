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
        Schema::create('temp_auth_authorizers', function (Blueprint $table) {
            $table->id();

            // User yang diberikan hak autorisasi
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // User yang memberikan hak autorisasi
            $table->foreignId('authorized_by')->constrained('users')->onDelete('cascade');

            // Status aktif/non-aktif
            $table->boolean('is_active')->default(true);

            // Apakah user bisa mengautorisasi dirinya sendiri
            $table->boolean('can_authorize_self')->default(false);

            // Maksimal durasi autorisasi yang bisa diberikan (dalam menit)
            $table->integer('max_authorization_duration')->nullable();

            // Komponen/halaman yang bisa diautorisasi (JSON array)
            $table->json('allowed_components')->nullable();

            // Catatan dari pemberi autorisasi
            $table->text('notes')->nullable();

            // Kapan hak autorisasi diberikan
            $table->timestamp('authorized_at')->useCurrent();

            // Kapan hak autorisasi berakhir (null = permanent)
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes untuk performa
            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_auth_authorizers');
    }
};
