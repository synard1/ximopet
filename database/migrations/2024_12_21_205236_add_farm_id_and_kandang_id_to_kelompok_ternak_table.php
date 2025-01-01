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
            $table->uuid('farm_id')->nullable()->after('transaksi_id');
            $table->uuid('kandang_id')->nullable()->after('farm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompok_ternak', function (Blueprint $table) {
            //
        });
    }
};
