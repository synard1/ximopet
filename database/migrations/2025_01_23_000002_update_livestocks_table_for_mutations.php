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
        Schema::table('livestocks', function (Blueprint $table) {
            // Check if the old column exists and rename/add new columns
            if (Schema::hasColumn('livestocks', 'quantity_mutated')) {
                // Rename existing column to outgoing mutations
                $table->renameColumn('quantity_mutated', 'quantity_mutated_out');
            } else {
                // Add outgoing mutations column if it doesn't exist
                $table->integer('quantity_mutated_out')->default(0)->after('quantity_sales');
            }

            // Add incoming mutations column
            if (!Schema::hasColumn('livestocks', 'quantity_mutated_in')) {
                $table->integer('quantity_mutated_in')->default(0)->after('quantity_mutated_out');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('livestocks', function (Blueprint $table) {
            // Check if the new columns exist before dropping
            if (Schema::hasColumn('livestocks', 'quantity_mutated_in')) {
                $table->dropColumn('quantity_mutated_in');
            }

            if (Schema::hasColumn('livestocks', 'quantity_mutated_out')) {
                // Rename back to original column name
                $table->renameColumn('quantity_mutated_out', 'quantity_mutated');
            }
        });
    }
};
