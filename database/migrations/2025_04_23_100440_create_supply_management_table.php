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
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
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
            $table->json('data')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedBigInteger('number')->nullable()->index();
            $table->string('number_full', 50)->nullable()->index();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('supply_purchase_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number');
            $table->string('do_number')->nullable(); // delivery order number / surat jalan
            $table->foreignUuid('farm_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('supplier_id')->constrained('partners')->onDelete('cascade');
            $table->foreignUuid('expedition_id')->nullable()->constrained('expeditions')->onDelete('set null');
            $table->dateTime('date');
            $table->decimal('expedition_fee', 12, 2)->default(0); // tarif ekspedisi
            $table->json('data')->nullable(); // simpan kebutuhan data mendatang
            $table->text('notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('number')->nullable()->index();
            $table->string('number_full', 50)->nullable()->index();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
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
            $table->unsignedBigInteger('number')->nullable()->index();
            $table->string('number_full', 50)->nullable()->index();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('farm_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('coop_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('supply_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('supply_purchase_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('source_type');
            $table->uuid('source_id');
            $table->decimal('quantity_in', 12, 2)->default(0);
            $table->decimal('quantity_used', 12, 2)->default(0);
            $table->decimal('quantity_mutated', 12, 2)->default(0);
            $table->decimal('quantity_reserved', 12, 2)->default(0); // stok yang sedang di-hold/reserved
            $table->decimal('quantity_available', 12, 2)->default(0); // stok akhir siap pakai
            $table->json('metadata')->nullable(); // breakdown transaksi, histori, dsb
            $table->unsignedBigInteger('number')->nullable()->index();
            $table->string('number_full', 50)->nullable()->index();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('farm_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('coop_id')->nullable()->constrained()->onDelete('cascade');
            $table->dateTime('usage_date');
            $table->foreignUuid('livestock_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->unsignedBigInteger('number')->nullable()->index();
            $table->string('number_full', 50)->nullable()->index();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_usage_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supply_usage_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('supply_stock_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('supply_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_taken', 12, 2);
            $table->foreignUuid('unit_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('converted_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->decimal('converted_quantity', 12, 2);
            $table->decimal('price_per_unit', 12, 2)->nullable();
            $table->decimal('price_per_converted_unit', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });


        Schema::create('supply_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('mutation_id')->constrained('mutations')->onDelete('cascade');
            $table->foreignUuid('from_farm_id')->constrained('farms')->onDelete('cascade')->onUpdate('restrict');
            $table->foreignUuid('to_farm_id')->constrained('farms')->onDelete('cascade')->onUpdate('restrict');
            $table->foreignUuid('from_coop_id')->nullable()->constrained('coops')->onDelete('cascade')->onUpdate('restrict');
            $table->foreignUuid('to_coop_id')->nullable()->constrained('coops')->onDelete('cascade')->onUpdate('restrict');
            $table->foreignUuid('from_livestock_id')->nullable()->constrained('livestocks')->onDelete('cascade')->onUpdate('restrict');
            $table->foreignUuid('to_livestock_id')->nullable()->constrained('livestocks')->onDelete('cascade')->onUpdate('restrict');
            $table->dateTime('date');
            $table->string('status')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('verified')->default(false);
            $table->dateTime('verified_at')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->json('data')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('number')->nullable()->index();
            $table->string('number_full', 50)->nullable()->index();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('rejected_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('verified_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });


        Schema::create('supply_mutation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('supply_mutation_id')->constrained('supply_mutations')->onDelete('cascade');
            $table->foreignUuid('supply_stock_id')->constrained('supply_stocks')->onDelete('cascade');
            $table->foreignUuid('supply_id')->constrained('supplies')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->decimal('converted_quantity', 12, 2);
            $table->foreignUuid('unit_id')->constrained('units')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignUuid('converted_unit_id')->constrained('units')->restrictOnDelete()->restrictOnUpdate();
            $table->dateTime('expiry_date')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('current_supplies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id')->nullable();
            $table->uuid('farm_id')->constrained()->onDelete('cascade');
            $table->uuid('coop_id')->nullable();
            $table->uuid('item_id');
            $table->uuid('unit_id');
            $table->string('type');
            $table->decimal('quantity', 12, 2);
            $table->string('status')->default('active')->index();

            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
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
