<?php

namespace App\Config\company;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class CompanyConfigService
{
    // ===== Default loaders (modular files) =====
    public static function getDefaultTemplateConfig(): array
    {
        return [
            'purchasing' => self::getDefaultPurchasingConfig(),
            'sales' => self::getDefaultSalesConfig(),
            'mutation' => self::getDefaultMutationConfig(),
            'usage' => self::getDefaultUsageConfig(),
            'notification' => self::getDefaultNotificationConfig(),
            'reporting' => self::getDefaultReportingConfig(),
            'integration' => [
                'external_api' => [
                    'enabled' => false,
                    'api_key' => '',
                    'endpoint' => '',
                ],
                'future_feature' => [
                    'enabled' => false,
                ],
            ],
        ];
    }

    public static function getDefaultActiveConfig(): array
    {
        return [
            'purchasing' => self::getDefaultPurchasingConfig(),
            'sales' => self::getDefaultSalesConfig(),
            'livestock' => self::getDefaultLivestockConfig(),
        ];
    }

    public static function getDefaultConfig(): array
    {
        return self::getDefaultActiveConfig();
    }

    // ===== Section defaults (from modular files) =====
    public static function getDefaultPurchasingConfig(): array
    {
        return include __DIR__ . '/sections/purchasing.php';
    }

    public static function getDefaultSalesConfig(): array
    {
        return include __DIR__ . '/sections/sales.php';
    }

    public static function getDefaultLivestockConfig(): array
    {
        return include __DIR__ . '/sections/livestock.php';
    }

    public static function getDefaultMutationConfig(): array
    {
        return include __DIR__ . '/sections/mutation.php';
    }

    public static function getDefaultUsageConfig(): array
    {
        return include __DIR__ . '/sections/usage.php';
    }

    public static function getDefaultNotificationConfig(): array
    {
        return include __DIR__ . '/sections/notification.php';
    }

    public static function getDefaultReportingConfig(): array
    {
        return include __DIR__ . '/sections/reporting.php';
    }

    // ===== Active section/subsection =====
    public static function getActiveConfigSection(string $section, ?string $subSection): array
    {
        $config = self::getDefaultActiveConfig();
        if (!isset($config[$section])) {
            return [];
        }
        if ($subSection === null) {
            return $config[$section];
        }
        return $config[$section][$subSection] ?? [];
    }

    // ===== Helper accessors for Manual Feed Usage (kept for BC) =====
    public static function getManualFeedUsageConfig(): array
    {
        return self::getActiveConfigSection('livestock', 'feed_usage_methods')['manual'] ?? [];
    }

    public static function getManualFeedUsageInputRestrictions(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['input_restrictions'] ?? [];
    }

    public static function getManualFeedUsageValidationRules(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['validation_rules'] ?? [];
    }

    public static function getManualFeedUsageWorkflowSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['workflow_settings'] ?? [];
    }

    public static function getManualFeedUsageBatchSelectionSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['batch_selection'] ?? [];
    }

    public static function getManualFeedUsageStockSelectionSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['stock_selection'] ?? [];
    }

    public static function getManualFeedUsageEditModeSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['edit_mode_settings'] ?? [
            'edit_strategy' => 'update',
            'delete_strategy' => 'soft',
            'create_backup_before_edit' => true,
            'track_edit_operations' => true,
            'soft_delete_settings' => [
                'increment_usage_count' => true,
                'default_delete_reason' => 'edited',
                'preserve_original_data' => true,
            ],
            'hard_delete_settings' => [
                'validate_references' => true,
                'restore_stock_quantities' => true,
                'update_livestock_totals' => true,
            ],
            'update_settings' => [
                'track_field_changes' => true,
                'validate_business_rules' => true,
                'update_timestamps' => true,
            ],
            'notifications' => [
                'notify_on_edit' => true,
                'notify_on_delete_recreate' => true,
                'include_change_summary' => true,
            ],
        ];
    }

    // ===== Manual Depletion helpers (BC) =====
    public static function getManualDepletionConfig(): array
    {
        $config = self::getActiveConfigSection('livestock', 'recording');
        return $config['depletion_methods']['manual'] ?? [
            'enabled' => true,
            'status' => 'ready',
            'history_enabled' => false,
            'track_age' => true,
            'auto_select' => false,
            'show_batch_details' => true,
            'require_selection' => true,
        ];
    }

    public static function getManualDepletionHistorySettings(): array
    {
        $config = self::getManualDepletionConfig();
        return [
            'history_enabled' => $config['history_enabled'] ?? false,
            'preserve_original_records' => $config['preserve_original_records'] ?? false,
            'track_edit_history' => $config['track_edit_history'] ?? true,
            'max_history_entries' => $config['max_history_entries'] ?? 10,
        ];
    }

    // ===== Manual/FIFO Mutation helpers (multi-tenant ready, optional $companyId) =====
    public static function getManualMutationConfig(?string $companyId = null): array
    {
        $companyConfig = self::getCompanyConfig($companyId);
        $livestockConfig = $companyConfig['livestock'] ?? [];
        return $livestockConfig['manual_mutation'] ?? [
            'enabled' => true,
            'default_method' => 'manual',
            'supported_methods' => ['manual', 'fifo', 'lifo'],
            'validation_rules' => [
                'require_destination' => true,
                'require_reason' => false,
                'min_quantity' => 1,
                'max_quantity_percentage' => 100,
                'validate_batch_availability' => true,
                'allow_partial_mutation' => true
            ],
            'batch_settings' => [
                'track_age' => true,
                'show_batch_details' => true,
                'show_utilization_rate' => true,
                'auto_assign_batch' => true,
                'require_batch_selection' => false
            ],
            'workflow_settings' => [
                'require_confirmation' => true,
                'show_preview' => true,
                'auto_close_modal' => true,
                'notification_enabled' => true
            ],
            'edit_mode_settings' => [
                'enabled' => true,
                'auto_load_existing' => true,
                'show_edit_banner' => true,
                'allow_cancel' => true
            ],
            'history_settings' => [
                'history_enabled' => false,
                'strategy' => 'update_existing',
                'audit_trail' => true,
                'backup_before_edit' => true,
                'max_backups' => 10
            ]
        ];
    }

    public static function getFifoMutationConfig(?string $companyId = null): array
    {
        $companyConfig = self::getCompanyConfig($companyId);
        $livestockConfig = $companyConfig['livestock'] ?? [];
        return $livestockConfig['fifo_mutation'] ?? [
            'enabled' => true,
            'default_method' => 'fifo',
            'supported_methods' => ['manual', 'fifo', 'lifo'],
            'input_restrictions' => [
                'allow_same_day_repeated_input' => true,
                'allow_same_livestock_repeated_input' => true,
                'allow_same_livestock_same_day' => false,
                'allow_same_batch_same_day' => false,
            ],
            'validation_rules' => [
                'require_destination' => true,
                'require_reason' => false,
                'min_quantity' => 1,
                'max_quantity_percentage' => 100,
                'validate_batch_availability' => true,
                'allow_partial_mutation' => true
            ],
            'batch_settings' => [
                'track_age' => true,
                'show_batch_details' => true,
                'show_utilization_rate' => true,
                'auto_assign_batch' => true,
                'require_batch_selection' => false
            ],
            'workflow_settings' => [
                'require_confirmation' => true,
                'show_preview' => true,
                'auto_close_modal' => true,
                'notification_enabled' => true
            ],
            'edit_mode_settings' => [
                'enabled' => true,
                'auto_load_existing' => true,
                'show_edit_banner' => true,
                'allow_cancel' => true
            ],
            'history_settings' => [
                'history_enabled' => false,
                'strategy' => 'update_existing',
                'audit_trail' => true,
                'backup_before_edit' => true,
                'max_backups' => 10
            ]
        ];
    }

    public static function getManualMutationHistorySettings(?string $companyId = null): array
    {
        $config = self::getManualMutationConfig($companyId);
        return $config['history_settings'] ?? [
            'history_enabled' => false,
            'strategy' => 'update_existing',
            'audit_trail' => true,
            'backup_before_edit' => true,
            'max_backups' => 10
        ];
    }

    public static function getManualMutationValidationRules(?string $companyId = null): array
    {
        $config = self::getManualMutationConfig($companyId);
        return $config['validation_rules'] ?? [];
    }

    public static function getFifoMutationValidationRules(?string $companyId = null): array
    {
        $config = self::getFifoMutationConfig($companyId);
        return $config['validation_rules'] ?? [];
    }

    public static function getManualMutationWorkflowSettings(?string $companyId = null): array
    {
        $config = self::getManualMutationConfig($companyId);
        return $config['workflow_settings'] ?? [];
    }

    public static function getFifoMutationWorkflowSettings(?string $companyId = null): array
    {
        $config = self::getFifoMutationConfig($companyId);
        return $config['workflow_settings'] ?? [];
    }

    public static function getManualMutationBatchSettings(?string $companyId = null): array
    {
        $config = self::getManualMutationConfig($companyId);
        return $config['batch_settings'] ?? [];
    }

    public static function getManualMutationEditModeSettings(): array
    {
        return [
            'allow_edit_finalized' => false,
            'allow_edit_draft' => true,
            'require_approval_for_edit' => false,
            'track_edit_history' => true,
            'max_edit_attempts' => 3,
        ];
    }

    // ===== Sales helpers =====
    public static function getSalesConfig(): array
    {
        return self::getActiveConfigSection('sales', 'livestock_sales') ?? [];
    }

    public static function getSalesRecordingConfig(): array
    {
        return self::getActiveConfigSection('sales', 'sales_recording') ?? [];
    }

    public static function getSalesHistoryConfig(): array
    {
        return self::getActiveConfigSection('sales', 'sales_history') ?? [];
    }

    public static function getSalesBatchAllocationMethod(): string
    {
        $salesConfig = self::getSalesConfig();
        return $salesConfig['batch_allocation']['method'] ?? 'fifo';
    }

    public static function getSalesValidationRules(): array
    {
        $salesConfig = self::getSalesConfig();
        return $salesConfig['validation_rules'] ?? [];
    }

    public static function isSalesRecordingEnabled(): bool
    {
        $salesRecordingConfig = self::getSalesRecordingConfig();
        return $salesRecordingConfig['enabled'] ?? true;
    }

    public static function isTwoStageSalesEnabled(): bool
    {
        $salesConfig = self::getSalesConfig();
        $recordingMethod = $salesConfig['recording_method']['type'] ?? 'two_stage';
        return $recordingMethod === 'two_stage';
    }

    public static function getSalesWorkflowSettings(): array
    {
        $salesConfig = self::getSalesConfig();
        return $salesConfig['workflow'] ?? [];
    }

    public static function isSalesApprovalRequired(): bool
    {
        $workflowSettings = self::getSalesWorkflowSettings();
        return $workflowSettings['approval_required'] ?? false;
    }

    public static function getSalesAutoApproveThreshold(): float
    {
        $workflowSettings = self::getSalesWorkflowSettings();
        return $workflowSettings['auto_approve_threshold'] ?? 1000000;
    }

    // ===== User-configurable and developer-only metadata =====
    public static function getUserConfigurableSettings(): array
    {
        return include __DIR__ . '/sections/user_configurable.php';
    }

    public static function getDeveloperOnlySettings(): array
    {
        return include __DIR__ . '/sections/developer_only.php';
    }

    public static function getConfigMetadata(): array
    {
        return include __DIR__ . '/sections/metadata.php';
    }

    // ===== Company config loader (optional companyId, otherwise current user) =====
    public static function getCompanyConfig(?string $companyId = null): array
    {
        $company = null;
        if ($companyId) {
            $company = Company::find($companyId);
        } elseif (Auth::check()) {
            $company = Auth::user()->company ?? null;
        }
        return $company?->config ?? [];
    }
}
