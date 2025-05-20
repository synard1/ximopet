<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('ovk_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('usage_date');
            $table->foreignUuid('farm_id');
            $table->foreignUuid('kandang_id')->nullable();
            $table->foreignUuid('livestock_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('livestock_id')->references('id')->on('livestocks');

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('ovk_records');
    }
};
