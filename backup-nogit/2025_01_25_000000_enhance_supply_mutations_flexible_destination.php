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

        // Enhance supply_mutations table
        Schema::table('supply_mutations', function (Blueprint $table) {
            // Add missing fields for flexible destination
            if (!Schema::hasColumn('supply_mutations', 'company_id')) {
                $table->uuid('company_id')->nullable()->index()->after('id');
            }

            if (!Schema::hasColumn('supply_mutations', 'from_livestock_id')) {
                $table->uuid('from_livestock_id')->nullable()->index()->after('from_farm_id');
            }

            if (!Schema::hasColumn('supply_mutations', 'to_livestock_id')) {
                $table->uuid('to_livestock_id')->nullable()->index()->after('to_farm_id');
            }

            if (!Schema::hasColumn('supply_mutations', 'from_coop_id')) {
                $table->uuid('from_coop_id')->nullable()->index()->after('from_livestock_id');
            }

            if (!Schema::hasColumn('supply_mutations', 'to_coop_id')) {
                $table->uuid('to_coop_id')->nullable()->index()->after('to_livestock_id');
            }

            // Add workflow and approval fields
            if (!Schema::hasColumn('supply_mutations', 'status')) {
                $table->string('status')->default('pending')->index()->after('to_coop_id');
            }

            if (!Schema::hasColumn('supply_mutations', 'approved_by')) {
                $table->uuid('approved_by')->nullable()->index()->after('status');
            }

            if (!Schema::hasColumn('supply_mutations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('supply_mutations', 'rejected_by')) {
                $table->uuid('rejected_by')->nullable()->index()->after('approved_at');
            }

            if (!Schema::hasColumn('supply_mutations', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (!Schema::hasColumn('supply_mutations', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('rejected_at');
            }

            // Add business logic fields
            if (!Schema::hasColumn('supply_mutations', 'mutation_type')) {
                $table->string('mutation_type')->default('internal')->index()->after('rejected_reason');
            }

            if (!Schema::hasColumn('supply_mutations', 'business_purpose')) {
                $table->string('business_purpose')->nullable()->after('mutation_type');
            }

            if (!Schema::hasColumn('supply_mutations', 'cost_center')) {
                $table->string('cost_center')->nullable()->after('business_purpose');
            }

            if (!Schema::hasColumn('supply_mutations', 'budget_code')) {
                $table->string('budget_code')->nullable()->after('cost_center');
            }

            // Add compliance and quality fields
            if (!Schema::hasColumn('supply_mutations', 'compliance_status')) {
                $table->string('compliance_status')->default('pending_review')->after('budget_code');
            }

            if (!Schema::hasColumn('supply_mutations', 'regulatory_requirements')) {
                $table->json('regulatory_requirements')->nullable()->after('compliance_status');
            }

            if (!Schema::hasColumn('supply_mutations', 'quality_check_status')) {
                $table->string('quality_check_status')->default('pending')->after('regulatory_requirements');
            }

            if (!Schema::hasColumn('supply_mutations', 'quality_check_by')) {
                $table->uuid('quality_check_by')->nullable()->after('quality_check_status');
            }

            if (!Schema::hasColumn('supply_mutations', 'quality_check_at')) {
                $table->timestamp('quality_check_at')->nullable()->after('quality_check_by');
            }

            // Add metadata fields
            if (!Schema::hasColumn('supply_mutations', 'metadata')) {
                $table->json('metadata')->nullable()->after('quality_check_at');
            }

            if (!Schema::hasColumn('supply_mutations', 'audit_trail')) {
                $table->json('audit_trail')->nullable()->after('metadata');
            }

            if (!Schema::hasColumn('supply_mutations', 'business_rules_applied')) {
                $table->json('business_rules_applied')->nullable()->after('audit_trail');
            }

            if (!Schema::hasColumn('supply_mutations', 'risk_assessment')) {
                $table->json('risk_assessment')->nullable()->after('business_rules_applied');
            }

            // Add operational planning fields
            if (!Schema::hasColumn('supply_mutations', 'scheduled_date')) {
                $table->date('scheduled_date')->nullable()->after('risk_assessment');
            }

            if (!Schema::hasColumn('supply_mutations', 'execution_window')) {
                $table->string('execution_window')->nullable()->after('scheduled_date');
            }

            if (!Schema::hasColumn('supply_mutations', 'resource_requirements')) {
                $table->json('resource_requirements')->nullable()->after('execution_window');
            }

            if (!Schema::hasColumn('supply_mutations', 'dependencies')) {
                $table->json('dependencies')->nullable()->after('resource_requirements');
            }

            if (!Schema::hasColumn('supply_mutations', 'constraints')) {
                $table->json('constraints')->nullable()->after('dependencies');
            }

            if (!Schema::hasColumn('supply_mutations', 'optimization_goals')) {
                $table->json('optimization_goals')->nullable()->after('constraints');
            }

            // Add quality management fields
            if (!Schema::hasColumn('supply_mutations', 'quality_checklist')) {
                $table->json('quality_checklist')->nullable()->after('optimization_goals');
            }

            if (!Schema::hasColumn('supply_mutations', 'acceptance_criteria')) {
                $table->json('acceptance_criteria')->nullable()->after('quality_checklist');
            }

            if (!Schema::hasColumn('supply_mutations', 'inspection_results')) {
                $table->json('inspection_results')->nullable()->after('acceptance_criteria');
            }

            if (!Schema::hasColumn('supply_mutations', 'corrective_actions')) {
                $table->json('corrective_actions')->nullable()->after('inspection_results');
            }

            if (!Schema::hasColumn('supply_mutations', 'preventive_measures')) {
                $table->json('preventive_measures')->nullable()->after('corrective_actions');
            }

            // Add financial management fields
            if (!Schema::hasColumn('supply_mutations', 'budget_allocation')) {
                $table->json('budget_allocation')->nullable()->after('preventive_measures');
            }

            if (!Schema::hasColumn('supply_mutations', 'cost_analysis')) {
                $table->json('cost_analysis')->nullable()->after('budget_allocation');
            }

            if (!Schema::hasColumn('supply_mutations', 'roi_calculation')) {
                $table->json('roi_calculation')->nullable()->after('cost_analysis');
            }

            if (!Schema::hasColumn('supply_mutations', 'cost_center_impact')) {
                $table->json('cost_center_impact')->nullable()->after('roi_calculation');
            }

            if (!Schema::hasColumn('supply_mutations', 'profitability_metrics')) {
                $table->json('profitability_metrics')->nullable()->after('cost_center_impact');
            }

            // Add inventory optimization fields
            if (!Schema::hasColumn('supply_mutations', 'stock_level_analysis')) {
                $table->json('stock_level_analysis')->nullable()->after('profitability_metrics');
            }

            if (!Schema::hasColumn('supply_mutations', 'reorder_point_calculation')) {
                $table->json('reorder_point_calculation')->nullable()->after('stock_level_analysis');
            }

            if (!Schema::hasColumn('supply_mutations', 'safety_stock_impact')) {
                $table->json('safety_stock_impact')->nullable()->after('reorder_point_calculation');
            }

            if (!Schema::hasColumn('supply_mutations', 'inventory_turnover')) {
                $table->json('inventory_turnover')->nullable()->after('safety_stock_impact');
            }

            if (!Schema::hasColumn('supply_mutations', 'carrying_cost_analysis')) {
                $table->json('carrying_cost_analysis')->nullable()->after('inventory_turnover');
            }

            // Add integration fields
            if (!Schema::hasColumn('supply_mutations', 'external_system_id')) {
                $table->string('external_system_id')->nullable()->after('carrying_cost_analysis');
            }

            if (!Schema::hasColumn('supply_mutations', 'api_endpoint')) {
                $table->string('api_endpoint')->nullable()->after('external_system_id');
            }

            if (!Schema::hasColumn('supply_mutations', 'integration_status')) {
                $table->string('integration_status')->default('pending')->after('api_endpoint');
            }

            if (!Schema::hasColumn('supply_mutations', 'sync_timestamp')) {
                $table->timestamp('sync_timestamp')->nullable()->after('integration_status');
            }

            if (!Schema::hasColumn('supply_mutations', 'error_logs')) {
                $table->json('error_logs')->nullable()->after('sync_timestamp');
            }

            // Add automation fields
            if (!Schema::hasColumn('supply_mutations', 'auto_approval_rules')) {
                $table->json('auto_approval_rules')->nullable()->after('error_logs');
            }

            if (!Schema::hasColumn('supply_mutations', 'auto_processing_rules')) {
                $table->json('auto_processing_rules')->nullable()->after('auto_approval_rules');
            }

            if (!Schema::hasColumn('supply_mutations', 'notification_triggers')) {
                $table->json('notification_triggers')->nullable()->after('auto_processing_rules');
            }

            if (!Schema::hasColumn('supply_mutations', 'escalation_rules')) {
                $table->json('escalation_rules')->nullable()->after('notification_triggers');
            }

            if (!Schema::hasColumn('supply_mutations', 'batch_processing_config')) {
                $table->json('batch_processing_config')->nullable()->after('escalation_rules');
            }

            // Add analytics fields
            if (!Schema::hasColumn('supply_mutations', 'kpi_metrics')) {
                $table->json('kpi_metrics')->nullable()->after('batch_processing_config');
            }

            if (!Schema::hasColumn('supply_mutations', 'trend_analysis')) {
                $table->json('trend_analysis')->nullable()->after('kpi_metrics');
            }

            if (!Schema::hasColumn('supply_mutations', 'comparative_analysis')) {
                $table->json('comparative_analysis')->nullable()->after('trend_analysis');
            }

            if (!Schema::hasColumn('supply_mutations', 'forecasting_data')) {
                $table->json('forecasting_data')->nullable()->after('comparative_analysis');
            }

            if (!Schema::hasColumn('supply_mutations', 'dashboard_metrics')) {
                $table->json('dashboard_metrics')->nullable()->after('forecasting_data');
            }
        });

        // Enhance supply_mutation_items table
        Schema::table('supply_mutation_items', function (Blueprint $table) {
            // Add enhanced tracking fields
            if (!Schema::hasColumn('supply_mutation_items', 'unit_id')) {
                $table->uuid('unit_id')->nullable()->index()->after('quantity');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->nullable()->after('unit_id');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'total_value')) {
                $table->decimal('total_value', 12, 2)->nullable()->after('unit_price');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'batch_number')) {
                $table->string('batch_number', 100)->nullable()->after('total_value');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_number');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'quality_grade')) {
                $table->string('quality_grade')->nullable()->after('expiry_date');
            }

            // Add business metrics fields
            if (!Schema::hasColumn('supply_mutation_items', 'efficiency_rating')) {
                $table->decimal('efficiency_rating', 3, 2)->nullable()->after('quality_grade');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'cost_impact')) {
                $table->decimal('cost_impact', 12, 2)->nullable()->after('efficiency_rating');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'sustainability_score')) {
                $table->decimal('sustainability_score', 3, 2)->nullable()->after('cost_impact');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'risk_level')) {
                $table->string('risk_level')->default('low')->after('sustainability_score');
            }

            // Add compliance and quality fields
            if (!Schema::hasColumn('supply_mutation_items', 'compliance_checks')) {
                $table->json('compliance_checks')->nullable()->after('risk_level');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'quality_metrics')) {
                $table->json('quality_metrics')->nullable()->after('compliance_checks');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'certification_status')) {
                $table->string('certification_status')->nullable()->after('quality_metrics');
            }

            // Add metadata fields
            if (!Schema::hasColumn('supply_mutation_items', 'item_metadata')) {
                $table->json('item_metadata')->nullable()->after('certification_status');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'processing_notes')) {
                $table->text('processing_notes')->nullable()->after('item_metadata');
            }

            if (!Schema::hasColumn('supply_mutation_items', 'special_handling')) {
                $table->text('special_handling')->nullable()->after('processing_notes');
            }
        });

        // Add foreign key constraints
        Schema::table('supply_mutations', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('from_livestock_id')->references('id')->on('livestocks')->onDelete('set null');
            $table->foreign('to_livestock_id')->references('id')->on('livestocks')->onDelete('set null');
            $table->foreign('from_coop_id')->references('id')->on('coops')->onDelete('set null');
            $table->foreign('to_coop_id')->references('id')->on('coops')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('quality_check_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('supply_mutation_items', function (Blueprint $table) {
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Remove foreign key constraints
        Schema::table('supply_mutations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['from_livestock_id']);
            $table->dropForeign(['to_livestock_id']);
            $table->dropForeign(['from_coop_id']);
            $table->dropForeign(['to_coop_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['quality_check_by']);
        });

        Schema::table('supply_mutation_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
        });

        // Remove columns from supply_mutations
        Schema::table('supply_mutations', function (Blueprint $table) {
            $columns = [
                'company_id',
                'from_livestock_id',
                'to_livestock_id',
                'from_coop_id',
                'to_coop_id',
                'status',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejected_reason',
                'mutation_type',
                'business_purpose',
                'cost_center',
                'budget_code',
                'compliance_status',
                'regulatory_requirements',
                'quality_check_status',
                'quality_check_by',
                'quality_check_at',
                'metadata',
                'audit_trail',
                'business_rules_applied',
                'risk_assessment',
                'scheduled_date',
                'execution_window',
                'resource_requirements',
                'dependencies',
                'constraints',
                'optimization_goals',
                'quality_checklist',
                'acceptance_criteria',
                'inspection_results',
                'corrective_actions',
                'preventive_measures',
                'budget_allocation',
                'cost_analysis',
                'roi_calculation',
                'cost_center_impact',
                'profitability_metrics',
                'stock_level_analysis',
                'reorder_point_calculation',
                'safety_stock_impact',
                'inventory_turnover',
                'carrying_cost_analysis',
                'external_system_id',
                'api_endpoint',
                'integration_status',
                'sync_timestamp',
                'error_logs',
                'auto_approval_rules',
                'auto_processing_rules',
                'notification_triggers',
                'escalation_rules',
                'batch_processing_config',
                'kpi_metrics',
                'trend_analysis',
                'comparative_analysis',
                'forecasting_data',
                'dashboard_metrics'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('supply_mutations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Remove columns from supply_mutation_items
        Schema::table('supply_mutation_items', function (Blueprint $table) {
            $columns = [
                'unit_id',
                'unit_price',
                'total_value',
                'batch_number',
                'expiry_date',
                'quality_grade',
                'efficiency_rating',
                'cost_impact',
                'sustainability_score',
                'risk_level',
                'compliance_checks',
                'quality_metrics',
                'certification_status',
                'item_metadata',
                'processing_notes',
                'special_handling'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('supply_mutation_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::enableForeignKeyConstraints();
    }
};
