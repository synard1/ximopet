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

        Schema::create('transaksi_beli', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary key menggunakan UUID');
            $table->string('faktur')->nullable()->comment('Nomor faktur transaksi pembelian');
            $table->string('jenis')->nullable()->comment('Jenis transaksi pembelian doc / pakan / stok');
            $table->dateTime('tanggal')->nullable()->comment('Tanggal transaksi pembelian');
            $table->uuid('rekanan_id')->nullable()->comment('ID rekanan/supplier');
            $table->string('batch_number')->nullable();
            $table->uuid('farm_id')->nullable()->comment('ID farm tempat pembelian');
            $table->uuid('coop_id')->nullable()->comment('ID kandang terkait');
            $table->decimal('total_qty', 15, 2)->nullable()->comment('Total kuantitas pembelian');
            $table->decimal('total_berat', 15, 2)->nullable()->comment('Total berat pembelian dalam kg');
            $table->decimal('harga', 15, 2)->nullable()->comment('Harga per unit');
            $table->decimal('sub_total', 15, 2)->nullable()->comment('Total harga (qty x harga)');
            $table->decimal('terpakai', 15, 2)->nullable()->comment('Jumlah yang sudah terpakai');
            $table->decimal('sisa', 15, 2)->nullable()->comment('Sisa dari total pembelian');
            $table->uuid('ternak_id')->nullable()->comment('ID kelompok ternak terkait');
            $table->string('status')->nullable()->comment('Status transaksi');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->uuid('created_by')->nullable()->comment('ID user yang membuat');
            $table->uuid('updated_by')->nullable()->comment('ID user yang mengupdate');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('rekanan_id')->references('id')->on('partners');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('ternak_id')->references('id')->on('ternaks');
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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('transaksi_beli');

        Schema::enableForeignKeyConstraints();
    }
};
