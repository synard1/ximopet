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
        // First, clean up any existing duplicate default admins
        $this->cleanupDuplicateDefaultAdmins();

        // MySQL doesn't support partial unique indexes like PostgreSQL
        // Instead, we'll rely on application-level constraint enforcement
        // through the CompanyUser model events and validation

        // Add a regular index for performance on queries filtering by these columns
        Schema::table('company_users', function (Blueprint $table) {
            $table->index(['company_id', 'isDefaultAdmin', 'status'], 'idx_company_default_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the index
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropIndex('idx_company_default_admin');
        });
    }

    /**
     * Clean up any existing duplicate default admins
     * Keep only the oldest default admin per company
     */
    private function cleanupDuplicateDefaultAdmins(): void
    {
        // Find companies with multiple default admins
        $duplicates = DB::select("
            SELECT company_id, COUNT(*) as count
            FROM company_users 
            WHERE isDefaultAdmin = true 
            AND status = 'active' 
            AND deleted_at IS NULL
            GROUP BY company_id
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $duplicate) {
            $companyId = $duplicate->company_id;

            // Get all default admins for this company, ordered by created_at
            $defaultAdmins = DB::select("
                SELECT id, user_id, created_at
                FROM company_users 
                WHERE company_id = ? 
                AND isDefaultAdmin = true 
                AND status = 'active' 
                AND deleted_at IS NULL
                ORDER BY created_at ASC
            ", [$companyId]);

            // Keep the first (oldest) one, remove isDefaultAdmin from others
            $keepFirst = true;
            foreach ($defaultAdmins as $admin) {
                if ($keepFirst) {
                    $keepFirst = false;
                    continue;
                }

                // Remove default admin status from duplicates
                DB::update("
                    UPDATE company_users 
                    SET isDefaultAdmin = false, 
                        updated_at = NOW()
                    WHERE id = ?
                ", [$admin->id]);

                // Log the cleanup
                \Illuminate\Support\Facades\Log::info('Cleaned up duplicate default admin', [
                    'company_id' => $companyId,
                    'user_id' => $admin->user_id,
                    'admin_id' => $admin->id
                ]);
            }
        }
    }
};
