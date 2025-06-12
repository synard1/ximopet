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
        Schema::table('temp_auth_logs', function (Blueprint $table) {
            // URL tempat autorisasi dilakukan
            $table->string('request_url', 500)->nullable()->after('component');

            // Namespace lengkap dari Livewire component
            $table->string('component_namespace', 255)->nullable()->after('request_url');

            // HTTP method (GET, POST, etc)
            $table->string('request_method', 10)->nullable()->after('component_namespace');

            // Referrer URL (dari mana user datang)
            $table->string('referrer_url', 500)->nullable()->after('request_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_auth_logs', function (Blueprint $table) {
            $table->dropColumn([
                'request_url',
                'component_namespace',
                'request_method',
                'referrer_url'
            ]);
        });
    }
};
