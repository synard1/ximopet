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
        Schema::create('mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // feed / supply / livestock
            $table->uuid('from_livestock_id')->nullable(); // Bisa null jika antar farm (khusus supply)
            $table->uuid('to_livestock_id')->nullable();   // Bisa null jika antar farm (khusus supply)

            // Untuk Supply: gunakan farm_id dan pen_id bisa kosong
            $table->uuid('from_farm_id')->nullable();
            $table->uuid('from_kandang_id')->nullable();
            $table->uuid('to_farm_id')->nullable();
            $table->uuid('to_kandang_id')->nullable();

            $table->date('date');
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->enum('mutation_scope', ['internal', 'interfarm', 'rollback'])->default('internal');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutations');
    }
};
