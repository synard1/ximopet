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

        // Drop the incorrect foreign key constraint that references 'expeditions' table
        Schema::table('feed_purchases', function (Blueprint $table) {
            $table->dropForeign(['expedition_id']);
        });

        // Add the correct foreign key constraint that references 'partners' table
        Schema::table('feed_purchases', function (Blueprint $table) {
            $table->foreign('expedition_id')->references('id')->on('partners')->onDelete('set null');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop the correct foreign key constraint
        Schema::table('feed_purchases', function (Blueprint $table) {
            $table->dropForeign(['expedition_id']);
        });

        // Restore the original incorrect foreign key constraint
        Schema::table('feed_purchases', function (Blueprint $table) {
            $table->foreign('expedition_id')->references('id')->on('expeditions')->onDelete('set null');
        });

        Schema::enableForeignKeyConstraints();
    }
};
