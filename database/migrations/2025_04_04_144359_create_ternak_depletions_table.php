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

        Schema::create('ternak_depletions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ternak_id');
            $table->date('tanggal_deplesi');
            $table->integer('jumlah_deplesi');
            $table->string('jenis_deplesi');
            $table->string('alasan_deplesi')->nullable();
            $table->text('keterangan')->nullable();
            $table->json('data')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('ternak_id')->references('id')->on('ternaks');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ternak_depletions');
    }
};
