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
        Schema::disableForeignKeyConstraints();

        // Add company_id to all relevant tables
        $tables = [
            // Core Tables
            'users',
            'farms',
            'coops',
            'kandangs',
            'items',
            'inventories',
            'app_configs',
            'company_users',
            'admins',

            // Transaction Tables
            'transaksi_beli',
            'transaksi_beli_details',
            'transaksi_jual',
            'transaksi_jual_details',
            'transaksi_harians',
            'mutations',
            'mutation_items',

            // Livestock Tables
            'ternaks',
            'ternak_depletions',
            'livestock_strains',
            'livestock_strain_standards',
            'livestock_management',
            'standar_bobots',
            'livestock_purchase_status_histories',

            // Supply & Feed Tables
            'supply_categories',
            'supplies',
            'supply_purchase_batches',
            'supply_purchases',
            'supply_stocks',
            'supply_usages',
            'supply_usage_details',
            'supply_mutations',
            'supply_mutation_items',
            'current_supplies',
            'supply_status_histories',
            'feed_management',
            'feed_status_histories',

            // Partner & Expedition Tables
            'partners',
            'expeditions',
            'expedition_tariffs',

            // Unit & Worker Tables
            'units',
            'unit_conversions',
            'workers',
            'batch_workers',

            // Audit & Security Tables
            'audit_trails',
            'data_audit_trails',
            'security_blacklist',
            'security_violations',
            'login_logs',

            // OVK Tables
            'ovk_records',
            'ovk_record_items',

            // QA Tables
            'qa_checklists',
            'qa_todo_lists',
            'qa_todo_comments',

            // Verification Tables
            'verification_rules',
            'model_verifications',
            'verification_logs',

            // Menu & Route Tables
            'menus',
            'route_permissions',
            // 'menu_role',
            // 'menu_permission',

            // Report & Analytics Tables
            'reports',
            'analytics_tables',

            // Temp Auth Tables
            'temp_auth_authorizers',
            'temp_auth_logs',

            // Feed & Livestock Tables
            'feeds',
            'feed_purchase_batches',
            'feed_purchases',
            'feed_stocks',
            'feed_usages',
            'feed_usage_details',
            'feed_mutations',
            'feed_mutation_items',
            'current_feeds',
            'feed_status_histories',
            'feed_rollbacks',
            'feed_rollback_items',
            'feed_rollback_logs',
            'livestocks',
            'livestock_batches',
            'livestock_purchases',
            'livestock_purchase_items',
            'livestock_mutations',
            'livestock_mutation_items',
            'livestock_sales',
            'livestock_sales_items',
            'livestock_depletions',
            'livestock_costs',
            'current_livestocks',
            'livestock_strains',
            'livestock_strain_standards',
            'livestock_purchase_status_histories',
            'recordings',
            'daily_analytics',
            'period_analytics',
            'performance_benchmarks',
            'analytics_alerts',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->uuid('company_id')->nullable()->after('id');
                    $table->foreign('company_id')
                        ->references('id')
                        ->on('companies')
                        ->onDelete('cascade');
                });
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            // Core Tables
            'users',
            'farms',
            'coops',
            'kandangs',
            'items',
            'inventories',
            'app_configs',
            'company_users',
            'admins',

            // Transaction Tables
            'transaksi_beli',
            'transaksi_beli_details',
            'transaksi_jual',
            'transaksi_jual_details',
            'transaksi_harians',
            'mutations',
            'mutation_items',

            // Livestock Tables
            'ternaks',
            'ternak_depletions',
            'livestock_strains',
            'livestock_strain_standards',
            'livestock_management',
            'standar_bobots',
            'livestock_purchase_status_histories',

            // Supply & Feed Tables
            'supply_categories',
            'supplies',
            'supply_purchase_batches',
            'supply_purchases',
            'supply_stocks',
            'supply_usages',
            'supply_usage_details',
            'supply_mutations',
            'supply_mutation_items',
            'current_supplies',
            'supply_status_histories',
            'feed_management',
            'feed_status_histories',

            // Partner & Expedition Tables
            'partners',
            'expeditions',
            'expedition_tariffs',

            // Unit & Worker Tables
            'units',
            'unit_conversions',
            'workers',
            'batch_workers',

            // Audit & Security Tables
            'audit_trails',
            'data_audit_trails',
            'security_blacklist',
            'security_violations',
            'login_logs',

            // OVK Tables
            'ovk_records',
            'ovk_record_items',

            // QA Tables
            'qa_checklists',
            'qa_todo_lists',
            'qa_todo_comments',

            // Verification Tables
            'verification_rules',
            'model_verifications',
            'verification_logs',

            // Menu & Route Tables
            'menus',
            'route_permissions',
            // 'menu_role',
            // 'menu_permission',

            // Report & Analytics Tables
            'reports',
            'analytics_tables',

            // Temp Auth Tables
            'temp_auth_authorizers',
            'temp_auth_logs',

            // Feed & Livestock Tables
            'feeds',
            'feed_purchase_batches',
            'feed_purchases',
            'feed_stocks',
            'feed_usages',
            'feed_usage_details',
            'feed_mutations',
            'feed_mutation_items',
            'current_feeds',
            'feed_status_histories',
            'feed_rollbacks',
            'feed_rollback_items',
            'feed_rollback_logs',
            'livestocks',
            'livestock_batches',
            'livestock_purchases',
            'livestock_purchase_items',
            'livestock_mutations',
            'livestock_mutation_items',
            'livestock_sales',
            'livestock_sales_items',
            'livestock_depletions',
            'livestock_costs',
            'current_livestocks',
            'livestock_strains',
            'livestock_strain_standards',
            'livestock_purchase_status_histories',
            'recordings',
            'daily_analytics',
            'period_analytics',
            'performance_benchmarks',
            'analytics_alerts',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }

        Schema::enableForeignKeyConstraints();
    }
};
