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

        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->nullable();
            $table->string('code', 64);
            $table->string('symbol');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->uuid('created_by')->index();
            $table->uuid('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->nullable();
            $table->foreignUuid('item_id'); // Barang atau Item yang terkait
            $table->foreignUuid('unit_id'); // Satuan utama
            $table->foreignUuid('conversion_unit_id'); // Satuan konversi
            $table->decimal('conversion_value', 20, 2)->default(1); // Nilai konversi, misal: 1000 (1 KG = 1000 GR)
            $table->boolean('default_purchase')->default(false); // Default saat beli
            $table->boolean('default_mutation')->default(false); // Default saat mutasi
            $table->boolean('default_sale')->nullable(); // Default saat jual
            $table->boolean('smallest')->default(false); // Ini satuan terkecil
            $table->string('status')->default('active')->index();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

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
        Schema::dropIfExists('units');
        Schema::dropIfExists('unit_conversions');
    }
};
