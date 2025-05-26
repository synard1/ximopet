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
            $table->uuid('livestock_strain_id')->nullable(); // Add this!
            $table->uuid('livestock_strain_standard_id')->nullable();
            $table->uuid('farm_id');
            $table->uuid('kandang_id');
            $table->string('name'); //name for batch code / periode
            $table->string('breed'); //jenis
            $table->dateTime('start_date'); //tanggal mulai
            $table->dateTime('end_date')->nullable();
            $table->integer('populasi_awal'); //jumlah awal
            $table->integer('quantity_depletion')->nullable();
            $table->integer('quantity_sales')->nullable();
            $table->integer('quantity_mutated')->nullable();
            $table->decimal('berat_awal', 10, 2)->default(0); //berat beli rata - rata
            $table->decimal('harga', 10, 2);        // Harga per unit saat beli
            $table->string('pic')->nullable();
            $table->json('data')->nullable();
            $table->string('status'); //status
            $table->text('keterangan')->nullable(); //keterangan
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('kandang_id')->references('id')->on('master_kandangs');
            $table->foreign('livestock_strain_id')->references('id')->on('livestock_strains'); // Add this!
            $table->foreign('livestock_strain_standard_id')->references('id')->on('livestock_strain_standards'); // Add this!


            $table->index(['id', 'start_date']);
        });

        // Livestock Purchase
        Schema::create('livestock_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->string('invoice_number');
            $table->foreignUuid('vendor_id')->constrained('partners')->onDelete('cascade');
            $table->foreignUuid('expedition_id')->nullable()->constrained('partners');
            $table->decimal('expedition_fee', 15, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('livestock_purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_purchase_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->integer('jumlah');
            $table->decimal('harga_per_ekor', 12, 2);
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
            $table->integer('jumlah');
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
            $table->uuid('kandang_id');
            $table->integer('quantity');          // Jumlah saat ini
            $table->decimal('berat_total', 10, 2); // Estimasi berat total
            $table->decimal('avg_berat', 10, 2);   // Rata-rata berat per ekor
            $table->integer('age');
            $table->json('metadata')->nullable();
            $table->string('status');              // active, sold, dead, culled

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
    }
};
