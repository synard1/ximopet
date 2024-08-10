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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis');
            $table->string('faktur', 64)->unique();
            $table->dateTime('tanggal');
            $table->uuid('rekanan_id');
            $table->string('rekanan_nama');
            $table->string('harga');
            $table->string('jumlah');
            $table->string('sub_total');
            $table->json('payload');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('rekanan_id')->references('id')->on('master_rekanan');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
