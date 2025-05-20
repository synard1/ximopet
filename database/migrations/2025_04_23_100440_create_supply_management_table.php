<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Supply Category
        Schema::create('supply_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        // Supply
        Schema::create('supplies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supply_category_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->json('payload')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('supply_purchase_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number');
            $table->string('do_number')->nullable(); // delivery order number / surat jalan
            $table->foreignUuid('supplier_id')->constrained('partners')->onDelete('cascade');
            $table->foreignUuid('expedition_id')->nullable()->constrained('expeditions')->onDelete('set null');
            $table->dateTime('date');
            $table->decimal('expedition_fee', 12, 2)->default(0); // tarif ekspedisi
            $table->json('payload')->nullable(); // simpan kebutuhan data mendatang

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('supply_purchase_batch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('supply_id')->constrained()->onDelete('cascade');
            $table->uuid('unit_id')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->uuid('converted_unit')->nullable();
            $table->decimal('converted_quantity', 12, 2);
            $table->decimal('price_per_unit', 12, 2);
            $table->decimal('price_per_converted_unit', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('kandang_id')->nullable();
            $table->uuid('supply_id');
            $table->uuid('supply_purchase_id');
            $table->date('date');
            $table->string('source_type');
            $table->uuid('source_id');
            $table->decimal('quantity_in', 12, 2)->default(0);
            $table->decimal('quantity_used', 12, 2)->default(0);
            $table->decimal('quantity_mutated', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id');
            $table->dateTime('usage_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_usage_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supply_usage_id');
            $table->uuid('supply_stock_id');
            $table->uuid('supply_id');
            $table->decimal('quantity_taken', 12, 2);
            $table->foreign('supply_usage_id')->references('id')->on('supply_usages')->cascadeOnDelete();
            $table->foreign('supply_stock_id')->references('id')->on('supply_stocks')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('supply_id')->references('id')->on('supplies')->cascadeOnDelete();
        });


        Schema::create('supply_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('from_farm_id');
            $table->uuid('to_farm_id');
            $table->dateTime('date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_mutation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supply_mutation_id');
            $table->uuid('supply_stock_id');
            $table->decimal('quantity', 12, 2);
            $table->foreign('supply_mutation_id')->references('id')->on('supply_mutations')->cascadeOnDelete();
            $table->foreign('supply_stock_id')->references('id')->on('supply_stocks')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('current_supplies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id')->nullable();
            $table->uuid('farm_id')->constrained()->onDelete('cascade');
            $table->uuid('kandang_id')->nullable();
            $table->uuid('item_id');
            $table->uuid('unit_id');
            $table->string('type');
            $table->decimal('quantity', 12, 2);
            $table->string('status')->default('active')->index();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_categories');
        Schema::dropIfExists('supplies');
        Schema::dropIfExists('supply_purchase_batches');
        Schema::dropIfExists('supply_purchases');
        Schema::dropIfExists('supply_stocks');
        Schema::dropIfExists('supply_usages');
        Schema::dropIfExists('supply_usage_details');
        Schema::dropIfExists('supply_mutations');
        Schema::dropIfExists('supply_mutation_items');
        Schema::dropIfExists('current_supplies');
    }
};
