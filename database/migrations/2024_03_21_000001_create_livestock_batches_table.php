<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('livestock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id');
            $table->uuid('farm_id');
            $table->uuid('kandang_id');
            $table->uuid('livestock_breed_id')->nullable();
            $table->uuid('livestock_breed_standard_id')->nullable();
            $table->string('name'); // Batch name/code
            $table->string('breed'); // Chicken breed type
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->integer('populasi_awal');
            $table->integer('quantity_depletion')->default(0);
            $table->integer('quantity_sales')->default(0);
            $table->integer('quantity_mutated')->default(0);
            $table->decimal('berat_awal', 10, 2);
            $table->decimal('harga', 10, 2); // Price per unit
            $table->string('pic')->nullable();
            $table->json('data')->nullable();
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_batches');
    }
};
