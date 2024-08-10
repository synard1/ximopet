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
        Schema::create('master_stoks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 64)->unique();
            $table->string('nama');
            $table->string('jumlah')->default(0);
            $table->string('kapasitas')->default(0);
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
        Schema::dropIfExists('master_stoks');
    }
};
