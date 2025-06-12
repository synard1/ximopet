<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Livestock (master ternak)
        Schema::create('livestocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('farm_id');
            $table->uuid('coop_id');
            $table->string('name'); //name for batch code / periode
            $table->dateTime('start_date'); //tanggal mulai
            $table->dateTime('end_date')->nullable();
            $table->integer('initial_quantity'); //jumlah awal
            $table->integer('quantity_depletion')->nullable();
            $table->integer('quantity_sales')->nullable();
            $table->integer('quantity_mutated')->nullable();
            $table->decimal('initial_weight', 10, 2)->default(0); //berat beli rata - rata
            $table->decimal('price', 10, 2);        // Harga per unit saat beli
            $table->json('data')->nullable();
            $table->string('status'); //status
            $table->text('notes')->nullable(); //keterangan
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->index(['id', 'start_date']);
        });

        Schema::create('livestock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id');
            $table->uuid('livestock_purchase_item_id')->nullable();
            $table->string('source_type');
            $table->uuid('source_id');
            $table->uuid('farm_id');
            $table->uuid('coop_id');
            $table->uuid('livestock_strain_id')->nullable();
            $table->uuid('livestock_strain_standard_id')->nullable();
            $table->string('name'); // Batch name/code
            $table->string('livestock_strain_name'); // Chicken strain type
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->integer('initial_quantity');
            $table->integer('quantity_depletion')->default(0);
            $table->integer('quantity_sales')->default(0);
            $table->integer('quantity_mutated')->default(0);
            $table->decimal('initial_weight', 10, 2);
            $table->decimal('weight', 10, 2);
            $table->string('weight_type');
            $table->decimal('weight_per_unit', 10, 2);
            $table->decimal('weight_total', 10, 2);
            $table->json('data')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Livestock Purchase
        Schema::create('livestock_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->string('invoice_number');
            $table->foreignUuid('supplier_id')->constrained('partners')->onDelete('cascade');
            $table->foreignUuid('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignUuid('coop_id')->constrained('coops')->onDelete('cascade');
            $table->foreignUuid('expedition_id')->nullable()->constrained('partners')->default(null);
            $table->decimal('expedition_fee', 15, 2)->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('livestock_purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->foreignUuid('livestock_purchase_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('livestock_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('livestock_strain_id')->constrained('livestock_strains');
            $table->foreignUuid('livestock_strain_standard_id')->nullable()->constrained('livestock_strain_standards');

            // Quantity information
            $table->integer('quantity');

            // Price information
            $table->decimal('price_value', 12, 2); // Input price value
            $table->string('price_type')->default('per_unit'); // per_unit or total
            $table->decimal('price_per_unit', 12, 2); // Price per unit (calculated)
            $table->decimal('price_total', 12, 2); // Total price (calculated)
            $table->decimal('tax_amount', 12, 2)->nullable(); // Tax amount if any
            $table->decimal('tax_percentage', 5, 2)->nullable(); // Tax percentage

            // Weight information
            $table->decimal('weight_value', 10, 2); // Input weight value
            $table->string('weight_type')->default('per_unit'); // per_unit or total
            $table->decimal('weight_per_unit', 10, 2); // Weight per unit (calculated)
            $table->decimal('weight_total', 10, 2); // Total weight (calculated)

            // Additional information
            $table->text('notes')->nullable(); // Catatan tambahan jika ada
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->index(['livestock_id']);
        });

        // Livestock Mutation
        Schema::create('livestock_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->foreignUuid('from_livestock_id')->constrained('livestocks')->onDelete('cascade');
            $table->foreignUuid('to_livestock_id')->constrained('livestocks')->onDelete('cascade');
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('livestock_mutation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_mutation_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('keterangan')->nullable();
            $table->json('payload')->nullable(); // JSON/array type column to save data
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Livestock Sales
        Schema::create('livestock_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->string('customer_name')->nullable();
            $table->foreignUuid('customer_id')->nullable()->constrained('partners')->onDelete('cascade');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('livestock_sales_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_sales_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->integer('quantity');
            $table->decimal('berat_total', 10, 2)->nullable();
            $table->decimal('harga_satuan', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Daily Recording
        Schema::create('recordings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->integer('age');
            $table->integer('stock_awal');
            $table->integer('stock_akhir');
            $table->integer('total_deplesi')->nullable();
            $table->integer('total_penjualan')->nullable();
            $table->decimal('berat_semalam', 15, 2)->nullable();
            $table->decimal('berat_hari_ini', 15, 2)->nullable();
            $table->decimal('kenaikan_berat', 15, 2)->nullable();
            $table->string('pakan_jenis')->nullable();
            $table->string('pakan_harian')->nullable();
            $table->string('pakan_total')->nullable();
            $table->json('payload')->nullable(); // JSON/array type column to save data

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Deplesi (mati, afkir)
        Schema::create('livestock_depletions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('recording_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->string('jenis'); // Mati / Afkir
            $table->integer('jumlah');
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Biaya Harian
        Schema::create('livestock_costs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->foreignUuid('recording_id')->constrained()->onDelete('cascade');
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('cost_per_ayam', 10, 2)->default(0);
            $table->json('cost_breakdown')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Ternak aktif saat ini
        Schema::create('current_livestocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->uuid('farm_id');
            $table->uuid('coop_id');
            $table->integer('quantity');          // Jumlah saat ini
            $table->decimal('weight_total', 10, 2); // Estimasi berat total
            $table->decimal('weight_avg', 10, 2);   // Rata-rata berat per ekor
            $table->json('data')->nullable();
            $table->string('status');              // active, sold, dead, culled

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
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
        Schema::dropIfExists('current_livestocks');
        Schema::dropIfExists('livestock_costs');
        Schema::dropIfExists('livestock_depletions');
        Schema::dropIfExists('recordings');
        Schema::dropIfExists('livestock_sales_items');
        Schema::dropIfExists('livestock_sales');
        Schema::dropIfExists('livestock_mutation_items');
        Schema::dropIfExists('livestock_mutations');
        Schema::dropIfExists('livestock_purchase_items');
        Schema::dropIfExists('livestock_purchases');
        Schema::dropIfExists('livestocks');
        Schema::dropIfExists('livestock_batches');
    }
};
