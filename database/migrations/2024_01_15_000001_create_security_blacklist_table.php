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
        Schema::create('security_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index(); // Support IPv6
            $table->string('reason')->default('security_violation');
            $table->integer('violation_count')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Unique constraint on IP address
            $table->unique('ip_address');

            // Index for cleanup queries
            $table->index(['expires_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_blacklist');
    }
};
