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
        Schema::table('ternak_jual', function (Blueprint $table) {
            $table->uuid('transaksi_jual_id')->nullable()->after('transaksi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ternak_jual', function (Blueprint $table) {
            //
        });
    }
};
