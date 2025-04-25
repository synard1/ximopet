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

        Schema::create('transaksi_jual_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_jual_id')->nullable();
            $table->uuid('rekanan_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('kandang_id')->nullable();
            $table->decimal('harga_beli', 15, 2)->nullable();
            $table->decimal('harga_jual', 15, 2)->nullable();
            $table->decimal('qty', 15, 2)->nullable();
            $table->decimal('berat', 15, 2)->nullable();
            $table->integer('umur')->nullable();
            $table->text('notes')->nullable();
            $table->json('payload')->nullable(); // JSON/array type column to save data
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaksi_jual_id')->references('id')->on('transaksi_jual');
            $table->foreign('rekanan_id')->references('id')->on('partners');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
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
        Schema::dropIfExists('transaksi_jual_details');
    }
};
