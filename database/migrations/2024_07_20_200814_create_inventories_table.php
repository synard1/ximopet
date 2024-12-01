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

        Schema::create('item_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');
            $table->string('kode', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('satuan_besar');
            $table->string('satuan_kecil');
            $table->decimal('konversi', 10, 2);
            $table->decimal('minimum_stock', 15, 2)->default(0);
            $table->decimal('maximum_stock', 15, 2)->default(0);
            $table->decimal('reorder_point', 15, 2)->default(0);
            $table->string('status');
            $table->boolean('is_feed')->default(false); // To identify feed items
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('item_categories');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('silos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('farm_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('capacity', 15, 2); // Maximum capacity in kg/tons
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('farm_id')->nullable();
            $table->uuid('kandang_id')->nullable();
            $table->uuid('silo_id')->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // warehouse, farm, kandang, silo etc
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('silo_id')->references('id')->on('silos');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('item_location_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('location_id');
            $table->uuid('farm_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('location_id')->references('id')->on('inventory_locations');
            $table->foreign('farm_id')->references('id')->on('master_farms');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('current_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('location_id');
            $table->dateTime('expiry_date')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('reserved_quantity', 15, 2)->default(0);
            $table->decimal('available_quantity', 15, 2)->default(0);
            $table->decimal('hpp', 15, 2)->default(0);
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('location_id')->references('id')->on('inventory_locations');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('silo_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('silo_id');
            $table->uuid('item_id');
            $table->string('movement_type'); // fill, consume, transfer
            $table->decimal('quantity', 15, 2);
            $table->decimal('remaining_quantity', 15, 2);
            $table->string('batch_number')->nullable();
            $table->dateTime('movement_date');
            $table->text('notes')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('silo_id')->references('id')->on('silos');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaksi_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('item_id');
            $table->uuid('source_location_id')->nullable();
            $table->uuid('destination_location_id')->nullable();
            $table->uuid('source_silo_id')->nullable();
            $table->uuid('destination_silo_id')->nullable();
            $table->string('movement_type'); // purchase, transfer, adjustment, consumption, silo_fill, silo_consume
            $table->dateTime('tanggal');
            $table->string('batch_number')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->string('satuan');
            $table->decimal('hpp', 15, 2);
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaksi_id')->references('id')->on('transaksi_beli');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('source_location_id')->references('id')->on('inventory_locations');
            $table->foreign('destination_location_id')->references('id')->on('inventory_locations');
            $table->foreign('source_silo_id')->references('id')->on('silos');
            $table->foreign('destination_silo_id')->references('id')->on('silos');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('stock_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_id');
            $table->uuid('item_id');
            $table->uuid('location_id');
            $table->uuid('transaksi_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('jenis')->nullable(); // Pembelian Penjualan Mutasi
            $table->string('batch_number')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('reserved_quantity', 15, 2)->default(0);
            $table->decimal('available_quantity', 15, 2)->default(0);
            $table->decimal('hpp', 15, 2);
            $table->string('status');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stock_id')->references('id')->on('current_stocks');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('location_id')->references('id')->on('inventory_locations');
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
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('items');
        Schema::dropIfExists('silos');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('item_location_mappings');
        Schema::dropIfExists('current_stocks');
        Schema::dropIfExists('silo_movements');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_histories');
    }
};
