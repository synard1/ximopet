<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup existing data
        $this->backupTables();

        // Drop and recreate security_violations table with UUID
        Schema::dropIfExists('security_violations');
        Schema::create('security_violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ip_address', 45)->index(); // Support IPv6
            $table->string('reason');
            $table->json('metadata')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            // Indexes for performance
            $table->index(['ip_address', 'created_at']);
            $table->index('created_at');
        });

        // Drop and recreate security_blacklist table with UUID
        Schema::dropIfExists('security_blacklist');
        Schema::create('security_blacklist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ip_address', 45)->index(); // Support IPv6
            $table->string('reason')->default('security_violation');
            $table->integer('violation_count')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Unique constraint on IP address
            $table->unique('ip_address');

            // Index for cleanup queries
            $table->index(['expires_at', 'created_at']);
        });

        // Restore backed up data with new UUIDs
        $this->restoreData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop UUID tables and recreate with auto-increment IDs
        Schema::dropIfExists('security_violations');
        Schema::create('security_violations', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index(); // Support IPv6
            $table->string('reason');
            $table->json('metadata')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            // Indexes for performance
            $table->index(['ip_address', 'created_at']);
            $table->index('created_at');
        });

        Schema::dropIfExists('security_blacklist');
        Schema::create('security_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index(); // Support IPv6
            $table->string('reason')->default('security_violation');
            $table->integer('violation_count')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Unique constraint on IP address
            $table->unique('ip_address');

            // Index for cleanup queries
            $table->index(['expires_at', 'created_at']);
        });
    }

    /**
     * Backup existing table data
     */
    private function backupTables(): void
    {
        try {
            // Create temporary tables to backup data
            if (Schema::hasTable('security_violations')) {
                DB::statement('CREATE TABLE security_violations_backup AS SELECT * FROM security_violations');
            }

            if (Schema::hasTable('security_blacklist')) {
                DB::statement('CREATE TABLE security_blacklist_backup AS SELECT * FROM security_blacklist');
            }
        } catch (\Exception $e) {
            // If backup fails, continue anyway (tables might be empty)
            logger('Security tables backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore data with UUIDs
     */
    private function restoreData(): void
    {
        try {
            // Restore security_violations data
            if (Schema::hasTable('security_violations_backup')) {
                $violations = DB::table('security_violations_backup')->get();
                foreach ($violations as $violation) {
                    DB::table('security_violations')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'ip_address' => $violation->ip_address,
                        'reason' => $violation->reason,
                        'metadata' => $violation->metadata,
                        'user_agent' => $violation->user_agent,
                        'created_at' => $violation->created_at,
                    ]);
                }
                Schema::dropIfExists('security_violations_backup');
            }

            // Restore security_blacklist data
            if (Schema::hasTable('security_blacklist_backup')) {
                $blacklists = DB::table('security_blacklist_backup')->get();
                foreach ($blacklists as $blacklist) {
                    DB::table('security_blacklist')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'ip_address' => $blacklist->ip_address,
                        'reason' => $blacklist->reason,
                        'violation_count' => $blacklist->violation_count,
                        'expires_at' => $blacklist->expires_at,
                        'created_at' => $blacklist->created_at,
                        'updated_at' => $blacklist->updated_at,
                    ]);
                }
                Schema::dropIfExists('security_blacklist_backup');
            }
        } catch (\Exception $e) {
            logger('Security tables data restore failed: ' . $e->getMessage());
        }
    }
};
