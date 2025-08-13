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
            $table->string('type')->index(); // feed / supply / livestock
            $table->uuid('from_livestock_id')->nullable()->index(); // Bisa null jika antar farm (khusus supply)
            $table->uuid('to_livestock_id')->nullable()->index();   // Bisa null jika antar farm (khusus supply)

            // Untuk Supply: gunakan farm_id dan pen_id bisa kosong
            $table->uuid('from_farm_id')->nullable()->index();
            $table->uuid('from_coop_id')->nullable();
            $table->uuid('to_farm_id')->nullable()->index();
            $table->uuid('to_coop_id')->nullable();

            $table->date('date')->index();
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->enum('mutation_scope', ['internal', 'interfarm', 'rollback'])->default('internal');
            $table->uuid('created_by')->index();
            $table->uuid('updated_by')->nullable()->index();

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
