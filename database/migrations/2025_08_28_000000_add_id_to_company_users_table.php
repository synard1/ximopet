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
        // Add missing id column to company_users table if not exists
        if (!Schema::hasColumn('company_users', 'id')) {
            Schema::table('company_users', function (Blueprint $table) {
                // Add UUID primary key
                $table->uuid('id')->first();

                // Remove auto-increment if exists
                $table->dropPrimary();

                // Set UUID as primary key
                $table->primary('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('company_users', 'id')) {
            Schema::table('company_users', function (Blueprint $table) {
                $table->dropPrimary();
                $table->dropColumn('id');
            });
        }
    }
};
