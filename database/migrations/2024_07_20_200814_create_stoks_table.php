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
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis');
            $table->string('kode', 64)->unique();
            $table->string('nama');
            $table->string('satuan_besar');
            $table->string('satuan_kecil');
            $table->string('konversi');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stoks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis');
            $table->string('kode', 64)->unique();
            $table->string('name');
            $table->string('satuan_besar');
            $table->string('satuan_kecil');
            $table->string('konversi');
            $table->string('status');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transaksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('faktur')->nullable();
            $table->string('jenis')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->uuid('rekanan_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('kandang_id')->nullable();
            $table->string('total_qty')->nullable();
            $table->string('harga')->nullable();
            $table->string('sub_total')->nullable();
            $table->string('terpakai')->nullable();
            $table->string('sisa')->nullable();
            $table->string('periode')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('rekanan_id')->references('id')->on('master_rekanan');
            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
        });

        Schema::create('stok_mutasis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
            $table->uuid('item_id')->nullable();
            $table->string('qty'); //dalam satuan terkecil
            $table->string('stok_awal');
            $table->string('stok_akhir');
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaksi_id')->references('id')->on('transaksis');
            $table->foreign('item_id')->references('id')->on('stoks');
        });

        Schema::create('transaksi_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
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
            $table->string('sub_total')->nullable();
            $table->string('terpakai')->nullable();
            $table->string('sisa')->nullable();
            $table->string('satuan_besar')->nullable();
            $table->string('satuan_kecil')->nullable();
            $table->string('konversi')->nullable();
            $table->string('periode')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('item_id')->references('id')->on('stoks');
            $table->foreign('rekanan_id')->references('id')->on('master_rekanan');
            $table->foreign('transaksi_id')->references('id')->on('transaksis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
        Schema::dropIfExists('stoks');
        Schema::dropIfExists('stok_mutasis');
        Schema::dropIfExists('transaksi_details');
        Schema::dropIfExists('transaksis');
        Schema::dropIfExists('master_kandangs');
        Schema::dropIfExists('master_farms');
        Schema::dropIfExists('master_rekanan');
    }
};
