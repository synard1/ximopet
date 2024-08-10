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
        Schema::create('master_farms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 64)->unique();
            $table->string('nama');
            $table->text('alamat');
            $table->string('telp', 50)->nullable();
            $table->string('pic', 64)->nullable();
            $table->string('telp_pic', 50)->nullable();
            $table->string('jumlah')->nullable();
            $table->string('kapasitas')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_farms');
    }
};
