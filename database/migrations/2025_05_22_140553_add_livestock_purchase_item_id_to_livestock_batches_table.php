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
        Schema::table('livestock_batches', function (Blueprint $table) {
            $table->uuid('livestock_purchase_item_id')->nullable()->after('harga');
            $table->foreign('livestock_purchase_item_id')->references('id')->on('livestock_purchase_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('livestock_batches', function (Blueprint $table) {
            $table->dropForeign(['livestock_purchase_item_id']);
            $table->dropColumn('livestock_purchase_item_id');
        });
    }
};
