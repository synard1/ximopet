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

        Schema::create('transaksi_beli_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('ekspedisi_id')->nullable();
            $table->string('no_sj')->nullable();
            $table->string('jenis')->nullable();
            $table->string('jenis_barang')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->uuid('item_id')->nullable();
            $table->string('item_name')->nullable();
            $table->decimal('qty', 15, 2)->nullable();
            $table->decimal('berat', 15, 2)->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->decimal('terpakai', 15, 2)->nullable();
            $table->decimal('sisa', 15, 2)->nullable();
            $table->decimal('tarif_ekspedisi', 15, 2)->nullable();
            $table->string('satuan_besar')->nullable();
            $table->string('satuan_kecil')->nullable();
            $table->string('konversi')->nullable();
            $table->json('payload')->nullable(); // JSON/array type column to save data
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('ekspedisi_id')->references('id')->on('partners');
            $table->foreign('transaksi_id')->references('id')->on('transaksi_beli');
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
        Schema::dropIfExists('transaksi_beli_details');
    }
};
