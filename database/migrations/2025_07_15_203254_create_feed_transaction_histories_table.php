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
        Schema::create('feed_transaction_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('coop_id')->nullable();
            $table->uuid('livestock_id')->nullable();
            $table->uuid('supply_id')->nullable();
            $table->uuid('feed_id')->nullable();
            $table->uuid('unit_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expired_date')->nullable();
            $table->dateTime('date')->index(); // transaction date not created date
            $table->string('transaction_type');
            $table->string('transaction_number')->nullable(); // number_full
            $table->decimal('quantity_in', 12, 2)->default(0);
            $table->decimal('quantity_out', 12, 2)->default(0);
            $table->decimal('initial_stock', 12, 2)->default(0);
            $table->decimal('quantity_available', 12, 2)->default(0);
            $table->decimal('price', 14, 4)->nullable();
            $table->text('description')->nullable();
            $table->json('data')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('livestock_id')->references('id')->on('livestocks');
            $table->foreign('supply_id')->references('id')->on('supplies');
            $table->foreign('feed_id')->references('id')->on('feeds');
            $table->foreign('unit_id')->references('id')->on('units');
            $table->foreign('supplier_id')->references('id')->on('partners');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_transaction_histories');
    }
};
