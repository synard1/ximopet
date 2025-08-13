<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing foreign key constraint
        Schema::table('expedition_transactions', function (Blueprint $table) {
            $table->dropForeign(['expedition_id']);
        });

        // Add new foreign key constraint to partners table
        Schema::table('expedition_transactions', function (Blueprint $table) {
            $table->foreign('expedition_id')
                ->references('id')
                ->on('partners')
                ->onDelete('cascade');
        });

        // Log the migration
        Log::info('=== MIGRATION: Fixed expedition_transactions foreign key ===', [
            'old_reference' => 'expeditions',
            'new_reference' => 'partners',
            'timestamp' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new foreign key constraint
        Schema::table('expedition_transactions', function (Blueprint $table) {
            $table->dropForeign(['expedition_id']);
        });

        // Restore old foreign key constraint (if expeditions table exists)
        if (Schema::hasTable('expeditions')) {
            Schema::table('expedition_transactions', function (Blueprint $table) {
                $table->foreign('expedition_id')
                    ->references('id')
                    ->on('expeditions')
                    ->onDelete('cascade');
            });
        }
    }
};
