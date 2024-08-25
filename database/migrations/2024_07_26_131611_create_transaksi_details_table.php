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
        Schema::create('transaksi_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id');
            $table->uuid('parent_id')->nullable();
            $table->string('jenis'); // Pembelian, Pemakaian, Penjualan
            $table->string('jenis_barang'); // DOC atau lainnya
            $table->dateTime('tanggal');
            $table->uuid('rekanan_id');
            $table->uuid('farm_id');
            $table->uuid('kandang_id')->nullable();
            $table->uuid('item_id');
            $table->string('nama');
            $table->string('harga');
            $table->string('qty');
            $table->string('terpakai');
            $table->string('sisa');
            $table->string('sub_total');
            $table->string('periode')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('user_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('rekanan_id')->references('id')->on('master_rekanan');
            $table->foreign('item_id')->references('id')->on('master_stoks');
            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('transaksi_id')->references('id')->on('transaksis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_details');
    }
};