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
        Schema::table('kelompok_ternak', function (Blueprint $table) {
            // Add new hpp column
            $table->decimal('harga_beli', 10, 2)->after('hpp')->nullable();
            $table->string('pic')->after('hpp')->nullable();
            $table->json('data')->nullable()->after('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompok_ternak', function (Blueprint $table) {
            // Reverse the changes
            $table->dropColumn('hpp');
            $table->dropColumn('pic');
        });
    }
};