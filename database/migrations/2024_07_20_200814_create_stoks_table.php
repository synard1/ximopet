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
        Schema::disableForeignKeyConstraints();

        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis');
            $table->string('kode', 64)->unique();
            $table->string('name');
            $table->string('satuan_besar');
            $table->string('satuan_kecil');
            $table->string('konversi');
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('transaksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('faktur')->nullable();
            $table->string('jenis')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->uuid('rekanan_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('kandang_id')->nullable();
            $table->decimal('total_qty', 15, 2)->nullable();
            // $table->decimal('total_berat', 15, 2)->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->decimal('terpakai', 15, 2)->nullable();
            $table->decimal('sisa', 15, 2)->nullable();
            $table->uuid('kelompok_ternak_id')->nullable(); 
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('rekanan_id')->references('id')->on('master_rekanan');
            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('kelompok_ternak_id')->references('id')->on('kelompok_ternak');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('transaksi_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('jenis')->nullable();
            $table->string('jenis_barang')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->uuid('item_id')->nullable();
            $table->string('item_name')->nullable();
            $table->decimal('qty', 15, 2)->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->decimal('terpakai', 15, 2)->nullable();
            $table->decimal('sisa', 15, 2)->nullable();
            $table->string('satuan_besar')->nullable();
            $table->string('satuan_kecil')->nullable();
            $table->string('konversi')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('transaksi_id')->references('id')->on('transaksis');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('stok_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
            // $table->uuid('transaksi_detail_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('kandang_id')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->string('jenis')->nullable();
            $table->uuid('item_id')->nullable();
            $table->string('qty'); //dalam satuan terkecil
            $table->string('stok_awal');
            $table->string('stok_akhir');
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // $table->foreign('transaksi_detail_id')->references('id')->on('transaksi_details');
            $table->foreign('transaksi_id')->references('id')->on('transaksis');
            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
        Schema::dropIfExists('stok_histories');
        Schema::dropIfExists('transaksi_details');
        Schema::dropIfExists('transaksis');
        Schema::dropIfExists('master_kandangs');
        Schema::dropIfExists('master_farms');
        Schema::dropIfExists('master_rekanan');
    }
};
