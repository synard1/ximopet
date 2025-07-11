<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Tables that need UUID field standardization
     */
    protected $tables = [
        'recordings',
        'ovk_records',
        'feed_usages',
        'supply_usages',
        'livestock_mutations',
        'livestock_mutation_items',
        'feed_mutations',
        'supply_mutations',
        'feed_purchases',
        'feed_purchase_batches',
        'livestock_purchases',
        'livestock_purchase_batches',
        'supply_purchases',
        'supply_purchase_batches',
        'sales_transactions',
        'analytics_alerts',
        'daily_analytics',
        'recording_performance_logs',
    ];

    /**
     * UUID fields to standardize
     */
    protected $uuidFields = [
        'livestock_id',
        'farm_id',
        'coop_id',
        'company_id',
        'user_id',
        'created_by',
        'updated_by',
        'verified_by',
        'source_livestock_id',
        'destination_livestock_id',
        'from_livestock_id',
        'to_livestock_id',
        'supplier_id',
        'customer_id',
        'worker_id',
        'expedition_id',
        'feed_id',
        'supply_id',
        'batch_id',
        'resolved_by',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only handle the recording_performance_logs table which is new
        // Other tables will be handled separately to avoid data conflicts

        if (Schema::hasTable('recording_performance_logs')) {
            Schema::table('recording_performance_logs', function (Blueprint $table) {
                // Ensure UUID fields are properly defined
                if (Schema::hasColumn('recording_performance_logs', 'livestock_id')) {
                    $table->uuid('livestock_id')->change();
                }
                if (Schema::hasColumn('recording_performance_logs', 'user_id')) {
                    $table->uuid('user_id')->nullable()->change();
                }
                if (Schema::hasColumn('recording_performance_logs', 'company_id')) {
                    $table->uuid('company_id')->nullable()->change();
                }
            });
        }

        // Add foreign key constraints for recording_performance_logs only if they don't exist
        if (Schema::hasTable('recording_performance_logs')) {
            $this->addForeignKeysIfNotExist();
        }
    }

    /**
     * Add foreign keys only if they don't already exist
     */
    private function addForeignKeysIfNotExist(): void
    {
        $foreignKeys = $this->getExistingForeignKeys('recording_performance_logs');

        Schema::table('recording_performance_logs', function (Blueprint $table) use ($foreignKeys) {
            // Add foreign key constraints only if they don't exist
            if (!in_array('recording_performance_logs_livestock_id_foreign', $foreignKeys) && Schema::hasTable('livestocks')) {
                $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            }
            if (!in_array('recording_performance_logs_user_id_foreign', $foreignKeys) && Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!in_array('recording_performance_logs_company_id_foreign', $foreignKeys) && Schema::hasTable('companies')) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });
    }

    /**
     * Get existing foreign key constraint names
     */
    private function getExistingForeignKeys(string $tableName): array
    {
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);

            return array_column($foreignKeys, 'CONSTRAINT_NAME');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('recording_performance_logs')) {
            Schema::table('recording_performance_logs', function (Blueprint $table) {
                // Drop foreign key constraints
                try {
                    $table->dropForeign(['livestock_id']);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist
                }
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist
                }
                try {
                    $table->dropForeign(['company_id']);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist
                }
            });
        }
    }
};
