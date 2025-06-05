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

        // Supply
        Schema::create('feeds', function (Blueprint $table) {
            $table->uuid('id')->primary();
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

        // Tabel feed_purchase_batches (1 nota bisa beberapa jenis pakan)
        Schema::create('feed_purchase_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number');
            $table->string('do_number')->nullable(); // delivery order number / surat jalan
            $table->foreignUuid('supplier_id')->constrained('partners')->onDelete('cascade');
            $table->foreignUuid('expedition_id')->nullable()->constrained('expeditions')->onDelete('set null');
            $table->date('date');
            $table->decimal('expedition_fee', 12, 2)->default(0); // tarif ekspedisi
            $table->json('payload')->nullable(); // simpan kebutuhan data mendatang

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Tabel feed_purchases (detail per jenis pakan)
        Schema::create('feed_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('feed_purchase_batch_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('feed_id')->constrained('feeds')->onDelete('cascade');
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

        // Tabel feed_stocks
        Schema::create('feed_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('feed_id')->constrained('feeds')->onDelete('cascade');
            $table->foreignUuid('feed_purchase_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date');
            $table->string('source_type');
            $table->uuid('source_id'); // purchase_id / mutation_id
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

        // Tabel feed_usages 
        Schema::create('feed_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('recording_id')->constrained()->onDelete('cascade');
            $table->date('usage_date');
            $table->decimal('total_quantity', 10, 2); // jumlah awal
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Tabel feed_usage_details 
        Schema::create('feed_usage_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('feed_usage_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('feed_stock_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('feed_id')->constrained('feeds')->onDelete('cascade');
            $table->decimal('quantity_taken', 10, 2); // jumlah awal
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Tabel feed_mutations
        Schema::create('feed_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->foreignUuid('from_livestock_id')->constrained('livestocks')->onDelete('cascade');
            $table->foreignUuid('to_livestock_id')->constrained('livestocks')->onDelete('cascade');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Tabel feed_mutation_livestocks
        Schema::create('feed_mutation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('feed_mutation_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('feed_stock_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('feed_id')->constrained('feeds')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Tabel feed_rollbacks (info rollback secara umum)
        Schema::create('feed_rollbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('performed_by')->constrained('users'); // siapa yang rollback
            $table->string('rollback_type'); // mutation / usage
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Tabel feed_rollback_items (detail data yang di-rollback)
        Schema::create('feed_rollback_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('feed_rollback_id')->constrained()->onDelete('cascade');
            $table->uuid('target_id'); // ID yang di-rollback (mutation_id, stock_id, dll)
            $table->string('target_type'); // mutation / stock
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Log rollback
        Schema::create('feed_rollback_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('feed_rollback_id')->constrained()->onDelete('cascade');
            $table->json('before');
            $table->json('after')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('current_feeds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id')->nullable();
            $table->uuid('farm_id')->constrained()->onDelete('cascade');
            $table->uuid('coop_id')->nullable();
            $table->uuid('feed_id');
            $table->uuid('unit_id');
            $table->decimal('quantity', 12, 2);
            $table->string('status')->default('active')->index();

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
        Schema::dropIfExists('feeds');
        Schema::dropIfExists('feed_rollback_logs');
        Schema::dropIfExists('feed_rollback_items');
        Schema::dropIfExists('feed_rollbacks');
        Schema::dropIfExists('feed_mutation_items');
        Schema::dropIfExists('feed_mutations');
        Schema::dropIfExists('feed_stocks');
        Schema::dropIfExists('feed_purchases');
        Schema::dropIfExists('feed_purchase_batches');
        Schema::dropIfExists('current_feeds');
    }
};
