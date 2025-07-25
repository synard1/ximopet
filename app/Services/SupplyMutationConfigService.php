<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class SupplyMutationConfigService
{
    /**
     * Get configuration value with dot notation support
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "supply_mutation_config_{$key}";

        return Cache::remember($cacheKey, config('supply_mutation.performance.cache_ttl_seconds', 3600), function () use ($key, $default) {
            return Config::get("supply_mutation.{$key}", $default);
        });
    }

    /**
     * Get entire configuration array
     *
     * @return array
     */
    public static function getAll(): array
    {
        return Cache::remember('supply_mutation_config_all', config('supply_mutation.performance.cache_ttl_seconds', 3600), function () {
            return Config::get('supply_mutation', []);
        });
    }

    /**
     * Check if a feature is enabled
     *
     * @param string $feature
     * @return bool
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return self::get("features.{$feature}", false);
    }

    /**
     * Check if mutation type is enabled
     *
     * @param string $type
     * @return bool
     */
    public static function isMutationTypeEnabled(string $type): bool
    {
        return self::get("mutation_types.{$type}.enabled", false);
    }

    /**
     * Get default mutation type
     *
     * @return string|null
     */
    public static function getDefaultMutationType(): ?string
    {
        $types = self::get('mutation_types', []);

        foreach ($types as $type => $config) {
            if ($config['default'] ?? false) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Get enabled mutation types
     *
     * @return array
     */
    public static function getEnabledMutationTypes(): array
    {
        $types = self::get('mutation_types', []);
        $enabled = [];

        foreach ($types as $type => $config) {
            if ($config['enabled'] ?? false) {
                $enabled[$type] = $config;
            }
        }

        return $enabled;
    }

    /**
     * Validate destination configuration
     *
     * @param string $destinationType
     * @return bool
     */
    public static function isDestinationTypeAllowed(string $destinationType): bool
    {
        $allowedTypes = [];

        if (self::get('destination.allow_coop_destination', false)) {
            $allowedTypes[] = 'coop';
        }

        if (self::get('destination.allow_livestock_destination', false)) {
            $allowedTypes[] = 'livestock';
        }

        if (self::get('destination.allow_farm_destination', false)) {
            $allowedTypes[] = 'farm';
        }

        return in_array($destinationType, $allowedTypes);
    }

    /**
     * Get validation rules for quantity
     *
     * @return array
     */
    public static function getQuantityValidationRules(): array
    {
        return self::get('validation.quantity', []);
    }

    /**
     * Get validation rules for date
     *
     * @return array
     */
    public static function getDateValidationRules(): array
    {
        return self::get('validation.date', []);
    }

    /**
     * Get validation rules for stock
     *
     * @return array
     */
    public static function getStockValidationRules(): array
    {
        return self::get('validation.stock', []);
    }

    /**
     * Get validation rules for unit
     *
     * @return array
     */
    public static function getUnitValidationRules(): array
    {
        return self::get('validation.unit', []);
    }

    /**
     * Check if approval is required
     *
     * @param float $quantity
     * @param float $value
     * @return bool
     */
    public static function isApprovalRequired(float $quantity = 0, float $value = 0): bool
    {
        $workflow = self::get('workflow', []);

        // Check if approval is globally required
        if (!($workflow['require_approval'] ?? false)) {
            return false;
        }

        // Check auto-approval for small quantities
        if (($workflow['auto_approve_small_quantities'] ?? false)) {
            $threshold = $workflow['auto_approve_threshold'] ?? 0;
            if ($quantity <= $threshold) {
                return false;
            }
        }

        // Check if approval required for large quantities
        if (($workflow['require_approval_for_large_quantities'] ?? false)) {
            $threshold = $workflow['large_quantity_threshold'] ?? 0;
            if ($quantity > $threshold) {
                return true;
            }
        }

        // Check if approval required for high value
        if (($workflow['require_approval_for_high_value'] ?? false)) {
            $threshold = $workflow['high_value_threshold'] ?? 0;
            if ($value > $threshold) {
                return true;
            }
        }

        return $workflow['require_approval'] ?? false;
    }

    /**
     * Get approval level for quantity/value
     *
     * @param float $quantity
     * @param float $value
     * @return string|null
     */
    public static function getRequiredApprovalLevel(float $quantity = 0, float $value = 0): ?string
    {
        $levels = self::get('workflow.approval_levels', []);

        foreach ($levels as $level => $config) {
            if (!($config['enabled'] ?? false)) {
                continue;
            }

            $threshold = $config['threshold'] ?? null;

            // If no threshold, this is the highest level
            if ($threshold === null) {
                return $level;
            }

            // Check if quantity exceeds threshold
            if ($quantity > $threshold) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Get allowed status transitions
     *
     * @param string $currentStatus
     * @return array
     */
    public static function getAllowedStatusTransitions(string $currentStatus): array
    {
        $statusFlow = self::get('workflow.status_flow', []);
        return $statusFlow[$currentStatus] ?? [];
    }

    /**
     * Check if batch tracking is enabled
     *
     * @return bool
     */
    public static function isBatchTrackingEnabled(): bool
    {
        return self::get('batch.tracking_enabled', false);
    }

    /**
     * Get batch number format
     *
     * @return string
     */
    public static function getBatchNumberFormat(): string
    {
        return self::get('batch.batch_number_format', 'YEAR-SEQ');
    }

    /**
     * Generate batch number
     *
     * @param int $sequence
     * @return string
     */
    public static function generateBatchNumber(int $sequence): string
    {
        $format = self::getBatchNumberFormat();
        $prefix = self::get('batch.custom_batch_prefix', 'MUT');
        $config = self::get('batch.batch_number_sequence', []);

        $padding = $config['padding'] ?? 6;
        $startFrom = $config['start_from'] ?? 1;

        $actualSequence = $sequence + $startFrom - 1;

        switch ($format) {
            case 'YEAR-SEQ':
                $year = date('Y');
                $paddedSequence = str_pad($actualSequence, $padding, '0', STR_PAD_LEFT);
                return "{$prefix}-{$year}-{$paddedSequence}";

            case 'UUID':
                return (string) \Illuminate\Support\Str::uuid();

            case 'CUSTOM':
                $customPrefix = $config['prefix'] ?? '';
                $customSuffix = $config['suffix'] ?? '';
                $paddedSequence = str_pad($actualSequence, $padding, '0', STR_PAD_LEFT);
                return "{$customPrefix}{$paddedSequence}{$customSuffix}";

            default:
                $paddedSequence = str_pad($actualSequence, $padding, '0', STR_PAD_LEFT);
                return "{$prefix}-{$paddedSequence}";
        }
    }

    /**
     * Get performance settings
     *
     * @return array
     */
    public static function getPerformanceSettings(): array
    {
        return self::get('performance', []);
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    public static function isCachingEnabled(): bool
    {
        return self::get('performance.enable_caching', false);
    }

    /**
     * Get cache TTL
     *
     * @return int
     */
    public static function getCacheTtl(): int
    {
        return self::get('performance.cache_ttl_seconds', 3600);
    }

    /**
     * Get batch size for processing
     *
     * @return int
     */
    public static function getBatchSize(): int
    {
        return self::get('performance.batch_size', 100);
    }

    /**
     * Check if background jobs are enabled
     *
     * @return bool
     */
    public static function isBackgroundJobsEnabled(): bool
    {
        return self::get('performance.use_background_jobs', false);
    }

    /**
     * Get audit settings
     *
     * @return array
     */
    public static function getAuditSettings(): array
    {
        return self::get('audit', []);
    }

    /**
     * Check if audit trail is enabled
     *
     * @return bool
     */
    public static function isAuditTrailEnabled(): bool
    {
        return self::get('audit.enable_audit_trail', false);
    }

    /**
     * Get logging settings
     *
     * @return array
     */
    public static function getLoggingSettings(): array
    {
        return self::get('logging', []);
    }

    /**
     * Check if debug logging is enabled
     *
     * @return bool
     */
    public static function isDebugLoggingEnabled(): bool
    {
        return self::get('logging.enable_debug_logging', false);
    }

    /**
     * Get business rules
     *
     * @return array
     */
    public static function getBusinessRules(): array
    {
        return self::get('business_rules', []);
    }

    /**
     * Check if cost tracking is enabled
     *
     * @return bool
     */
    public static function isCostTrackingEnabled(): bool
    {
        return self::get('business_rules.cost_tracking.enable_cost_tracking', false);
    }

    /**
     * Get notification settings
     *
     * @return array
     */
    public static function getNotificationSettings(): array
    {
        return self::get('notifications', []);
    }

    /**
     * Check if notifications are enabled
     *
     * @return bool
     */
    public static function areNotificationsEnabled(): bool
    {
        return self::get('notifications.enable_notifications', false);
    }

    /**
     * Get security settings
     *
     * @return array
     */
    public static function getSecuritySettings(): array
    {
        return self::get('security', []);
    }

    /**
     * Get required permissions for action
     *
     * @param string $action
     * @return string|null
     */
    public static function getRequiredPermission(string $action): ?string
    {
        $permissions = self::get('security.permissions', []);
        return $permissions[$action] ?? null;
    }

    /**
     * Get integration settings
     *
     * @return array
     */
    public static function getIntegrationSettings(): array
    {
        return self::get('integrations', []);
    }

    /**
     * Check if API access is enabled
     *
     * @return bool
     */
    public static function isApiAccessEnabled(): bool
    {
        return self::get('integrations.enable_api_access', false);
    }

    /**
     * Clear configuration cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('supply_mutation_config_all');

        // Clear individual key caches
        $keys = [
            'enabled',
            'version',
            'debug_mode',
            'mutation_types',
            'destination',
            'validation',
            'workflow',
            'batch',
            'documents',
            'performance',
            'audit',
            'logging',
            'business_rules',
            'notifications',
            'security',
            'integrations',
            'features'
        ];

        foreach ($keys as $key) {
            Cache::forget("supply_mutation_config_{$key}");
        }

        Log::info('Supply mutation configuration cache cleared');
    }

    /**
     * Validate configuration
     *
     * @return array
     */
    public static function validateConfiguration(): array
    {
        $errors = [];
        $config = self::getAll();

        // Check required sections
        $requiredSections = [
            'enabled',
            'mutation_types',
            'destination',
            'validation',
            'workflow',
            'batch',
            'performance',
            'audit',
            'logging'
        ];

        foreach ($requiredSections as $section) {
            if (!isset($config[$section])) {
                $errors[] = "Missing required configuration section: {$section}";
            }
        }

        // Validate mutation types
        if (isset($config['mutation_types'])) {
            $hasDefault = false;
            foreach ($config['mutation_types'] as $type => $typeConfig) {
                if ($typeConfig['default'] ?? false) {
                    $hasDefault = true;
                    break;
                }
            }

            if (!$hasDefault) {
                $errors[] = 'No default mutation type specified';
            }
        }

        // Validate workflow
        if (isset($config['workflow'])) {
            $workflow = $config['workflow'];

            if (($workflow['auto_approve_threshold'] ?? 0) > ($workflow['large_quantity_threshold'] ?? 0)) {
                $errors[] = 'Auto approve threshold cannot be greater than large quantity threshold';
            }
        }

        // Validate performance settings
        if (isset($config['performance'])) {
            $performance = $config['performance'];

            if (($performance['cache_ttl_seconds'] ?? 0) <= 0) {
                $errors[] = 'Cache TTL must be greater than 0';
            }

            if (($performance['batch_size'] ?? 0) <= 0) {
                $errors[] = 'Batch size must be greater than 0';
            }
        }

        return $errors;
    }

    /**
     * Get configuration for database migration
     *
     * @return array
     */
    public static function getMigrationConfig(): array
    {
        return [
            'version' => self::get('migration.version', '1.0.0'),
            'database_table' => self::get('migration.database_table', 'supply_mutation_configs'),
            'company_specific' => self::get('migration.company_specific', true),
            'user_editable' => self::get('migration.user_editable', true),
            'admin_editable' => self::get('migration.admin_editable', true),
            'backup_before_migration' => self::get('migration.backup_before_migration', true),
            'validate_after_migration' => self::get('migration.validate_after_migration', true),
            'rollback_enabled' => self::get('migration.rollback_enabled', true),
        ];
    }

    /**
     * Log configuration access for debugging
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function logConfigAccess(string $key, $value): void
    {
        if (self::isDebugLoggingEnabled()) {
            Log::debug('Supply mutation config accessed', [
                'key' => $key,
                'value' => $value,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Get available destination types
     *
     * @return array
     */
    public static function getAvailableDestinationTypes(): array
    {
        $types = [];

        if (self::get('destination.allow_farm_destination', false)) {
            $types['farm'] = [
                'name' => 'Farm',
                'description' => 'Mutasi ke farm lain',
                'enabled' => true,
                'validation_rules' => self::get('destination.destination_validation_rules', [])
            ];
        }

        if (self::get('destination.allow_coop_destination', false)) {
            $types['coop'] = [
                'name' => 'Kandang',
                'description' => 'Mutasi ke kandang tertentu',
                'enabled' => true,
                'validation_rules' => self::get('destination.destination_validation_rules', [])
            ];
        }

        if (self::get('destination.allow_livestock_destination', false)) {
            $types['livestock'] = [
                'name' => 'Ternak',
                'description' => 'Mutasi ke ternak tertentu',
                'enabled' => true,
                'validation_rules' => self::get('destination.destination_validation_rules', [])
            ];
        }

        return $types;
    }

    /**
     * Validate destination type selection
     *
     * @param string $destinationType
     * @return bool
     */
    public static function validateDestinationType(string $destinationType): bool
    {
        $availableTypes = self::getAvailableDestinationTypes();
        return isset($availableTypes[$destinationType]) && ($availableTypes[$destinationType]['enabled'] ?? false);
    }

    /**
     * Get destination validation rules
     *
     * @param string $destinationType
     * @return array
     */
    public static function getDestinationValidationRules(string $destinationType): array
    {
        $rules = self::get('destination.destination_validation_rules', []);

        switch ($destinationType) {
            case 'farm':
                return array_merge($rules, [
                    'check_farm_availability' => true,
                    'validate_transfer_restrictions' => true,
                ]);
            case 'coop':
                return array_merge($rules, [
                    'check_coop_capacity' => true,
                    'validate_transfer_restrictions' => true,
                ]);
            case 'livestock':
                return array_merge($rules, [
                    'check_livestock_health' => true,
                    'validate_transfer_restrictions' => true,
                ]);
            default:
                return $rules;
        }
    }

    /**
     * Check if same source destination is allowed
     *
     * @return bool
     */
    public static function isSameSourceDestinationAllowed(): bool
    {
        return self::get('destination.allow_same_source_destination', false);
    }

    /**
     * Get maximum destination distance
     *
     * @return float
     */
    public static function getMaxDestinationDistance(): float
    {
        return self::get('destination.max_destination_distance_km', 100);
    }

    /**
     * Check if destination validation is required
     *
     * @return bool
     */
    public static function isDestinationValidationRequired(): bool
    {
        return self::get('destination.require_destination_validation', true);
    }

    /**
     * Check if auto validate destination capacity is enabled
     *
     * @return bool
     */
    public static function isAutoValidateDestinationCapacityEnabled(): bool
    {
        return self::get('destination.auto_validate_destination_capacity', true);
    }
}
