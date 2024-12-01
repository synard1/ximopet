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
            $table->unsignedBigInteger('created_by');
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
        Schema::dropIfExists('master_farms');
    }
};
