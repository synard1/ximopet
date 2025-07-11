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

        Schema::create('livestock_strain_standards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_strain_id');
            $table->string('livestock_strain_name');
            $table->json('standar_data');
            $table->string('description')->nullable();
            $table->string('status')->index();

            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('livestock_strain_id')->references('id')->on('livestock_strains');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livestock_strain_standards');
    }
};
