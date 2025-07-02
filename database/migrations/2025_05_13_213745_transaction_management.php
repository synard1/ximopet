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
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number');
            $table->date('transaction_date');
            $table->uuid('livestock_id');
            $table->uuid('customer_id');
            $table->uuid('expedition_id')->nullable();       // ekspedisi pengiriman
            $table->uuid('livestock_sale_id')->nullable();               // referensi ke LivestockSale

            $table->decimal('weight', 15, 2);
            $table->decimal('quantity', 15, 2);
            $table->decimal('price', 15, 2);
            $table->decimal('expedition_fee', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2);
            $table->json('payload')->nullable();

            $table->text('notes')->nullable();
            $table->string('status')->nullable()->index(); // e.g. 'draft', 'confirmed', 'paid'

            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('approved_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('partners');
            $table->foreign('expedition_id')->references('id')->on('expeditions');
            $table->foreign('livestock_id')->references('id')->on('livestocks');
            $table->foreign('livestock_sale_id')->references('id')->on('livestock_sales');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['livestock_id']);
            $table->index(['customer_id']);
            $table->index(['expedition_id']);
            $table->index(['transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
