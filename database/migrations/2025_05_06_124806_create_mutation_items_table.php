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
        Schema::create('mutation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mutation_id');
            $table->string('item_type'); // feed / supply / vitamin / medicine
            $table->uuid('item_id'); // id dari item terkait (feed_id / item_id)
            $table->uuid('stock_id'); // ID dari stok yang digunakan
            $table->integer('quantity');
            $table->json('unit_metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->foreign('mutation_id')->references('id')->on('mutations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutation_items');
    }
};
