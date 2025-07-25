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
        // Add numbering columns to livestock_purchases table
        Schema::table('livestock_purchases', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('id');
            $table->string('number_full', 50)->nullable()->after('number');

            // Add index for better performance on numbering queries
            $table->index(['number', 'created_at']);
        });

        // Add numbering columns to livestocks table
        Schema::table('livestocks', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('id');
            $table->string('number_full', 50)->nullable()->after('number');

            // Add index for better performance on numbering queries
            $table->index(['number', 'created_at']);
        });

        // Add numbering columns to livestock_batches table
        Schema::table('livestock_batches', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('id');
            $table->string('number_full', 50)->nullable()->after('number');

            // Add index for better performance on numbering queries
            $table->index(['number', 'created_at']);
        });

        // Add numbering columns to livestock_mutations table
        Schema::table('livestock_mutations', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('id');
            $table->string('number_full', 50)->nullable()->after('number');

            // Add index for better performance on numbering queries
            $table->index(['number', 'created_at']);
        });

        // Add numbering columns to livestock_sales table
        Schema::table('livestock_sales', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('id');
            $table->string('number_full', 50)->nullable()->after('number');

            // Add index for better performance on numbering queries
            $table->index(['number', 'created_at']);
        });

        // Add numbering columns to livestock_depletions table
        Schema::table('livestock_depletions', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('id');
            $table->string('number_full', 50)->nullable()->after('number');

            // Add index for better performance on numbering queries
            $table->index(['number', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove numbering columns from livestock_depletions table
        Schema::table('livestock_depletions', function (Blueprint $table) {
            $table->dropIndex(['number', 'created_at']);
            $table->dropColumn(['number', 'number_full']);
        });

        // Remove numbering columns from livestock_sales table
        Schema::table('livestock_sales', function (Blueprint $table) {
            $table->dropIndex(['number', 'created_at']);
            $table->dropColumn(['number', 'number_full']);
        });

        // Remove numbering columns from livestock_mutations table
        Schema::table('livestock_mutations', function (Blueprint $table) {
            $table->dropIndex(['number', 'created_at']);
            $table->dropColumn(['number', 'number_full']);
        });

        // Remove numbering columns from livestock_batches table
        Schema::table('livestock_batches', function (Blueprint $table) {
            $table->dropIndex(['number', 'created_at']);
            $table->dropColumn(['number', 'number_full']);
        });

        // Remove numbering columns from livestocks table
        Schema::table('livestocks', function (Blueprint $table) {
            $table->dropIndex(['number', 'created_at']);
            $table->dropColumn(['number', 'number_full']);
        });

        // Remove numbering columns from livestock_purchases table
        Schema::table('livestock_purchases', function (Blueprint $table) {
            $table->dropIndex(['number', 'created_at']);
            $table->dropColumn(['number', 'number_full']);
        });
    }
};
