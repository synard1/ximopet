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
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ternak_id');
            $table->string('jenis')->nullable();
            $table->dateTime('tanggal');
            $table->json('data')->nullable();

            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ternak_id')->references('id')->on('ternaks');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
