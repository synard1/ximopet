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
        // Fix livestock_strains table constraints
        Schema::table('livestock_strains', function (Blueprint $table) {
            // Drop existing foreign key constraints if they exist
            try {
                $table->dropForeign(['company_id']);
            } catch (\Exception $e) {
                // Constraint might not exist
            }

            try {
                $table->dropForeign(['created_by']);
            } catch (\Exception $e) {
                // Constraint might not exist
            }

            try {
                $table->dropForeign(['updated_by']);
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        });

        // Recreate constraints properly
        Schema::table('livestock_strains', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix supply_mutations table constraints
        Schema::table('supply_mutations', function (Blueprint $table) {
            try {
                $table->dropForeign(['company_id']);
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        });

        Schema::table('supply_mutations', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Fix supply_mutation_items table constraints
        Schema::table('supply_mutation_items', function (Blueprint $table) {
            try {
                $table->dropForeign(['company_id']);
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        });

        Schema::table('supply_mutation_items', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the fixed constraints
        Schema::table('livestock_strains', function (Blueprint $table) {
            try {
                $table->dropForeign(['company_id']);
                $table->dropForeign(['created_by']);
                $table->dropForeign(['updated_by']);
            } catch (\Exception $e) {
                // Constraints might not exist
            }
        });

        Schema::table('supply_mutations', function (Blueprint $table) {
            try {
                $table->dropForeign(['company_id']);
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        });

        Schema::table('supply_mutation_items', function (Blueprint $table) {
            try {
                $table->dropForeign(['company_id']);
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        });
    }
};
