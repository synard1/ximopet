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
            // Add weight column only if it doesn't exist
            if (!Schema::hasColumn('livestock_batches', 'weight')) {
                $table->decimal('weight', 10, 2)->nullable()->after('quantity_available')->comment('Calculated weight based on historical recording data');
            }

            // Add weight calculation metadata only if they don't exist
            if (!Schema::hasColumn('livestock_batches', 'weight_calculated_at')) {
                $table->timestamp('weight_calculated_at')->nullable()->after('weight')->comment('When the weight was last calculated');
            }

            if (!Schema::hasColumn('livestock_batches', 'weight_calculation_method')) {
                $table->string('weight_calculation_method')->nullable()->after('weight_calculated_at')->comment('Method used for weight calculation');
            }

            if (!Schema::hasColumn('livestock_batches', 'weight_calculation_metadata')) {
                $table->json('weight_calculation_metadata')->nullable()->after('weight_calculation_method')->comment('Metadata about weight calculation process');
            }

            // Add indexes for performance (only if they don't exist)
            if (!Schema::hasIndex('livestock_batches', 'idx_livestock_batches_weight_calculated_at')) {
                $table->index(['weight_calculated_at'], 'idx_livestock_batches_weight_calculated_at');
            }

            if (!Schema::hasIndex('livestock_batches', 'idx_livestock_batches_weight_calculation_method')) {
                $table->index(['weight_calculation_method'], 'idx_livestock_batches_weight_calculation_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('livestock_batches', function (Blueprint $table) {
            // Drop indexes if they exist
            if (Schema::hasIndex('livestock_batches', 'idx_livestock_batches_weight_calculated_at')) {
                $table->dropIndex('idx_livestock_batches_weight_calculated_at');
            }

            if (Schema::hasIndex('livestock_batches', 'idx_livestock_batches_weight_calculation_method')) {
                $table->dropIndex('idx_livestock_batches_weight_calculation_method');
            }

            // Drop columns if they exist
            $columnsToDrop = [];

            if (Schema::hasColumn('livestock_batches', 'weight')) {
                $columnsToDrop[] = 'weight';
            }

            if (Schema::hasColumn('livestock_batches', 'weight_calculated_at')) {
                $columnsToDrop[] = 'weight_calculated_at';
            }

            if (Schema::hasColumn('livestock_batches', 'weight_calculation_method')) {
                $columnsToDrop[] = 'weight_calculation_method';
            }

            if (Schema::hasColumn('livestock_batches', 'weight_calculation_metadata')) {
                $columnsToDrop[] = 'weight_calculation_metadata';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
