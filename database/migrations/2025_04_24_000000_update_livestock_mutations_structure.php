<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration updates the livestock_mutations table structure to align with
     * the new header-detail pattern and ensures compatibility with both legacy
     * and new column naming conventions.
     */
    public function up(): void
    {
        // Step 1: Backup existing data if livestock_mutations table exists
        $this->backupExistingData();

        // Step 2: Drop foreign key constraints first, then drop tables
        $this->dropForeignKeyConstraints();

        // Step 3: Drop existing tables (will be recreated)
        Schema::dropIfExists('livestock_mutation_items');
        Schema::dropIfExists('livestock_mutations');

        // Step 4: Create the new livestock_mutations table with complete structure
        Schema::create('livestock_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Company relationship
            $table->uuid('company_id')->index();

            // Source and destination livestock (with legacy support)
            $table->uuid('source_livestock_id')->index();
            $table->uuid('destination_livestock_id')->nullable()->index();

            // Legacy column support (for backward compatibility)
            $table->uuid('from_livestock_id')->nullable()->index();
            $table->uuid('to_livestock_id')->nullable()->index();

            // Mutation details
            $table->datetime('tanggal')->index();
            $table->integer('jumlah')->default(0)->comment('Total quantity (calculated from items)');
            $table->string('jenis')->index()->comment('Mutation type: internal, external, farm_transfer, etc.');
            $table->string('direction')->index()->comment('Direction: in, out');
            $table->text('keterangan')->nullable()->comment('Notes/description');

            // JSON data fields
            $table->json('data')->nullable()->comment('Additional data like batch info, notes, etc.');
            $table->json('metadata')->nullable()->comment('Processing metadata, audit trail, etc.');

            // Audit fields (bigint to match User model)
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('source_livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('destination_livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('from_livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('to_livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Performance indexes with custom names to avoid MySQL length limits
            $table->index(['company_id', 'tanggal'], 'lm_co_date_idx');
            $table->index(['source_livestock_id', 'direction'], 'lm_src_dir_idx');
            $table->index(['destination_livestock_id', 'direction'], 'lm_dest_dir_idx');
            $table->index(['jenis', 'direction'], 'lm_type_dir_idx');
            $table->index(['tanggal', 'company_id'], 'lm_date_co_idx');
            $table->index(['created_at'], 'lm_created_idx');
            $table->index(['deleted_at'], 'lm_deleted_idx');

            // Composite indexes for common queries
            $table->index(['company_id', 'source_livestock_id', 'direction'], 'idx_company_source_direction');
            $table->index(['company_id', 'destination_livestock_id', 'direction'], 'idx_company_dest_direction');
            $table->index(['source_livestock_id', 'tanggal', 'direction'], 'idx_source_date_direction');

            // Legacy support indexes
            $table->index(['from_livestock_id', 'direction'], 'idx_from_direction');
            $table->index(['to_livestock_id', 'direction'], 'idx_to_direction');
        });

        // Step 5: Update livestock_mutation_items table if needed
        if (Schema::hasTable('livestock_mutation_items')) {
            Schema::table('livestock_mutation_items', function (Blueprint $table) {
                // Add batch_id if it doesn't exist
                if (!Schema::hasColumn('livestock_mutation_items', 'batch_id')) {
                    $table->uuid('batch_id')->nullable()->after('livestock_mutation_id')->index();
                    $table->foreign('batch_id')->references('id')->on('livestock_batches')->onDelete('set null');
                }

                // Ensure created_by and updated_by are proper bigint type
                if (Schema::hasColumn('livestock_mutation_items', 'created_by')) {
                    // Check if foreign keys exist and drop them
                    try {
                        $table->dropForeign(['created_by']);
                        $table->dropForeign(['updated_by']);
                    } catch (\Exception $e) {
                        // Continue if foreign keys don't exist
                    }
                }

                // Ensure proper indexes exist
                if (!$this->indexExists('livestock_mutation_items', 'livestock_mutation_items_batch_id_index')) {
                    $table->index('batch_id', 'lmi_batch_id_idx');
                }
            });
        } else {
            // Create livestock_mutation_items table if it doesn't exist
            Schema::create('livestock_mutation_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('livestock_mutation_id')->index('lmi_mutation_id_idx');
                $table->uuid('batch_id')->nullable()->index('lmi_batch_id_idx');
                $table->integer('quantity');
                $table->decimal('weight', 10, 2)->nullable();
                $table->text('keterangan')->nullable();
                $table->json('payload')->nullable();
                $table->uuid('created_by')->nullable()->index('lmi_created_by_idx');
                $table->uuid('updated_by')->nullable()->index('lmi_updated_by_idx');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('livestock_mutation_id')->references('id')->on('livestock_mutations')->onDelete('cascade');
                $table->foreign('batch_id')->references('id')->on('livestock_batches')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Step 6: Restore backed up data with proper mapping
        $this->restoreBackedUpData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the updated tables
        Schema::dropIfExists('livestock_mutation_items');
        Schema::dropIfExists('livestock_mutations');

        // Recreate the original simple structure
        Schema::create('livestock_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tanggal');
            $table->foreignUuid('from_livestock_id')->constrained('livestocks')->onDelete('cascade');
            $table->foreignUuid('to_livestock_id')->constrained('livestocks')->onDelete('cascade');
            $table->string('keterangan')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('livestock_mutation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('livestock_mutation_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('keterangan')->nullable();
            $table->json('payload')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Drop foreign key constraints before dropping tables
     */
    private function dropForeignKeyConstraints(): void
    {
        try {
            Schema::disableForeignKeyConstraints();

            // Drop foreign keys from livestock_mutation_items if table exists
            if (Schema::hasTable('livestock_mutation_items')) {
                Schema::table('livestock_mutation_items', function (Blueprint $table) {
                    // Check if foreign key exists before dropping
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'livestock_mutation_items' 
                        AND REFERENCED_TABLE_NAME = 'livestock_mutations'
                    ");

                    foreach ($foreignKeys as $fk) {
                        try {
                            $table->dropForeign($fk->CONSTRAINT_NAME);
                        } catch (\Exception $e) {
                            // Continue if foreign key doesn't exist
                        }
                    }
                });
            }

            Schema::enableForeignKeyConstraints();
        } catch (\Exception $e) {
            logger('Foreign key constraint drop failed: ' . $e->getMessage());
        }
    }

    /**
     * Backup existing livestock_mutations data
     */
    private function backupExistingData(): void
    {
        try {
            if (Schema::hasTable('livestock_mutations')) {
                // Create backup table
                DB::statement('CREATE TABLE livestock_mutations_backup AS SELECT * FROM livestock_mutations');

                // Also backup items if they exist
                if (Schema::hasTable('livestock_mutation_items')) {
                    DB::statement('CREATE TABLE livestock_mutation_items_backup AS SELECT * FROM livestock_mutation_items');
                }
            }
        } catch (\Exception $e) {
            // Log error but continue - tables might be empty
            logger('Livestock mutations backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore backed up data with proper field mapping
     */
    private function restoreBackedUpData(): void
    {
        try {
            // Restore livestock_mutations data
            if (Schema::hasTable('livestock_mutations_backup')) {
                $mutations = DB::table('livestock_mutations_backup')->get();

                foreach ($mutations as $mutation) {
                    $data = [
                        'id' => $mutation->id,
                        'company_id' => auth()->user()->company_id ?? '00000000-0000-0000-0000-000000000000',
                        'tanggal' => $mutation->tanggal,
                        'keterangan' => $mutation->keterangan ?? null,
                        'created_at' => $mutation->created_at,
                        'updated_at' => $mutation->updated_at,
                        'deleted_at' => $mutation->deleted_at ?? null,
                    ];

                    // Handle different column naming conventions
                    if (isset($mutation->source_livestock_id)) {
                        $data['source_livestock_id'] = $mutation->source_livestock_id;
                        $data['destination_livestock_id'] = $mutation->destination_livestock_id ?? null;
                    } else {
                        $data['source_livestock_id'] = $mutation->from_livestock_id;
                        $data['destination_livestock_id'] = $mutation->to_livestock_id;
                        $data['from_livestock_id'] = $mutation->from_livestock_id;
                        $data['to_livestock_id'] = $mutation->to_livestock_id;
                    }

                    // Set default values for new fields
                    $data['jumlah'] = $mutation->jumlah ?? 0;
                    $data['jenis'] = $mutation->jenis ?? 'internal';
                    $data['direction'] = $mutation->direction ?? 'out';
                    $data['data'] = $mutation->data ?? null;
                    $data['metadata'] = $mutation->metadata ?? null;

                    // Handle user IDs (keep as bigint)
                    $data['created_by'] = $mutation->created_by ?? null;
                    $data['updated_by'] = $mutation->updated_by ?? null;

                    DB::table('livestock_mutations')->insert($data);
                }

                // Drop backup table
                Schema::dropIfExists('livestock_mutations_backup');
            }

            // Restore livestock_mutation_items data
            if (Schema::hasTable('livestock_mutation_items_backup')) {
                $items = DB::table('livestock_mutation_items_backup')->get();

                foreach ($items as $item) {
                    $data = [
                        'id' => $item->id,
                        'livestock_mutation_id' => $item->livestock_mutation_id,
                        'batch_id' => null, // Will be set later if needed
                        'quantity' => $item->quantity,
                        'weight' => $item->weight ?? null,
                        'keterangan' => $item->keterangan ?? null,
                        'payload' => $item->payload ?? null,
                        'created_by' => $item->created_by ?? null,
                        'updated_by' => $item->updated_by ?? null,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                        'deleted_at' => $item->deleted_at ?? null,
                    ];

                    DB::table('livestock_mutation_items')->insert($data);
                }

                // Drop backup table
                Schema::dropIfExists('livestock_mutation_items_backup');
            }
        } catch (\Exception $e) {
            logger('Livestock mutations data restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};
