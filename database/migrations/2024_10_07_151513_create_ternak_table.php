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

        Schema::create('kelompok_ternak', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
            $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('cascade');
            $table->string('name'); //name for batch code / periode
            $table->string('breed'); //jenis
            $table->date('start_date'); //tanggal mulai
            $table->date('estimated_end_date'); //tanggal selesai
            $table->integer('initial_quantity'); //jumlah awal
            $table->integer('current_quantity'); //jumlah saat ini
            $table->integer('death_quantity'); //jumlah mati
            $table->integer('slaughter_quantity'); //jumlah yang dipotong
            $table->integer('sold_quantity'); //jumlah yang terjual
            $table->integer('remaining_quantity'); //jumlah yang tersisa
            $table->string('status'); //status
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('histori_ternak', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->foreign('kelompok_ternak_id')->references('id')->on('kelompok_ternak');
            $table->integer('quantity');
            $table->decimal('total_berat', 10, 2);
            $table->enum('status', ['hidup', 'mati', 'terjual', 'dibunuh', 'dipotong', 'sakit', 'abnormal']);
            $table->dateTime('tanggal');
            $table->string('keterangan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('konsumsi_pakan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->foreign('kelompok_ternak_id')->references('id')->on('kelompok_ternak');
            $table->uuid('item_id')->nullable();    
            $table->foreign('item_id')->references('id')->on('items');
            $table->decimal('quantity', 10, 2);
            $table->decimal('harga', 10, 2);
            $table->dateTime('tanggal');
            $table->string('keterangan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('kematian_ternak', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->foreign('kelompok_ternak_id')->references('id')->on('kelompok_ternak');
            $table->dateTime('tanggal');
            $table->integer('quantity');
            $table->string('sebab');
            $table->decimal('total_berat', 10, 2);
            $table->string('keterangan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('penjualan_ternak', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelompok_ternak_id');
            $table->foreign('kelompok_ternak_id')->references('id')->on('kelompok_ternak');
            $table->dateTime('tanggal');
            $table->integer('quantity');
            $table->decimal('harga', 10, 2); //harga satuan
            $table->decimal('total_berat', 10, 2);
            $table->decimal('harga_jual', 10, 2); //harga jual
            $table->decimal('total_harga', 10, 2);
            $table->uuid('pembeli_id');
            $table->foreign('pembeli_id')->references('id')->on('master_rekanan');
            $table->string('keterangan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('kelompok_ternak');
    }
};
