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

        Schema::create('transaksi_jual', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id');
            $table->string('tipe_transaksi');
            $table->string('faktur')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->uuid('transaksi_beli_id')->nullable();
            $table->uuid('kelompok_ternak_id')->nullable();
            $table->uuid('ternak_jual_id')->nullable();
            $table->integer('jumlah')->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaksi_beli_id')->references('id')->on('transaksi_beli');
            $table->foreign('kelompok_ternak_id')->references('id')->on('kelompok_ternak');
            $table->foreign('ternak_jual_id')->references('id')->on('ternak_jual');
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
        Schema::dropIfExists('transaksi_juals');
    }
};
