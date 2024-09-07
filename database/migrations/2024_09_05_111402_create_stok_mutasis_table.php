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
        Schema::create('stok_mutasis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksidet_id')->nullable();
            $table->string('jenis')->nullable();
            $table->string('jenis_barang')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->uuid('rekanan_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('kandang_id')->nullable();
            $table->uuid('item_id')->nullable();
            $table->string('item_nama')->nullable();
            $table->string('harga')->nullable();
            $table->string('qty')->nullable();
            $table->string('terpakai')->nullable();
            $table->string('sisa')->nullable();
            $table->string('satuan_besar')->nullable();
            $table->string('satuan_kecil')->nullable();
            $table->string('konversi')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('user_id');


            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('item_id')->references('id')->on('master_stoks');
            $table->foreign('rekanan_id')->references('id')->on('master_rekanan');
            $table->foreign('transaksidet_id')->references('id')->on('transaksi_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_mutasis');
    }
};
