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

        Schema::create('transaksi_harians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->uuid('rekanan_id')->nullable()->comment('ID rekanan/supplier');
            $table->uuid('kelompok_ternak_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('coop_id')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('rekanan_id')->references('id')->on('partners');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('transaksi_harian_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id');
            $table->uuid('parent_id');
            $table->string('type'); //['feed', 'medication', 'vitamin', 'sale', 'death', 'culling']
            $table->uuid('item_id');
            $table->decimal('quantity', 8, 2)->nullable(); // For sales, deaths, and culling
            $table->decimal('total_berat', 8, 2)->nullable(); // For sales
            $table->decimal('harga', 8, 2)->nullable(); // For sales
            $table->text('notes')->nullable(); // Additional notes for any type of transaction
            $table->json('payload')->nullable(); // JSON/array type column to save data
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id')->references('id')->on('items'); // Assuming a generic items table
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('transaksi_id')->references('id')->on('transaksi_harians');
            $table->foreign('parent_id')->references('id')->on('transaksi_beli_details');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('transaksi_harian_details');
        Schema::dropIfExists('transaksi_harians');
        Schema::enableForeignKeyConstraints();
    }
};
