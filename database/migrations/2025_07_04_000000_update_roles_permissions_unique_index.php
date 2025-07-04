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
        // Update roles table unique index
        Schema::table('roles', function (Blueprint $table) {
            // Drop existing unique index on name & guard_name (if exists)
            try {
                $table->dropUnique(['name', 'guard_name']);
            } catch (\Exception $e) {
                // Index might have a custom name; ignore if it does not exist
            }
            // Add composite unique index including company_id
            $table->unique(['company_id', 'name', 'guard_name'], 'roles_company_name_guard_unique');
        });

        // Update permissions table unique index
        Schema::table('permissions', function (Blueprint $table) {
            try {
                $table->dropUnique(['name', 'guard_name']);
            } catch (\Exception $e) {
                // ignore missing index
            }
            $table->unique(['company_id', 'name', 'guard_name'], 'permissions_company_name_guard_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_company_name_guard_unique');
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_company_name_guard_unique');
            $table->unique(['name', 'guard_name']);
        });
    }
};
