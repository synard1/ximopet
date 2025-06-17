<?php

namespace App\Config;

class CompanyConfig
{
    /**
     * Get default configuration for all company settings
     */
    public static function getDefaultConfig(): array
    {
        return [
            'mutation' => self::getDefaultMutationConfig(),
            'livestock' => self::getDefaultLivestockConfig(),
            'feed' => self::getDefaultFeedConfig(),
            'supply' => self::getDefaultSupplyConfig(),
            'notification' => self::getDefaultNotificationConfig(),
            'reporting' => self::getDefaultReportingConfig(),
        ];
    }

    /**
     * Get default mutation configuration
     */
    public static function getDefaultMutationConfig(): array
    {
        return [
            'livestock_mutation' => [
                'type' => 'batch', // 'batch' or 'fifo'
                'batch_settings' => [
                    'tracking_enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                    'batch_number_format' => 'YEAR-SEQ', // YEAR-SEQ, CUSTOM, etc
                    'custom_format' => null,
                ],
                'fifo_settings' => [
                    'enabled' => true,
                    'track_age' => true,
                    'min_age_days' => 0,
                    'max_age_days' => null,
                ],
                'validation_rules' => [
                    'require_weight' => true,
                    'require_quantity' => true,
                    'allow_partial_mutation' => true,
                    'max_mutation_percentage' => 100,
                ],
            ],
            'feed_mutation' => [
                'type' => 'batch',
                'batch_settings' => [
                    'tracking_enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                ],
                'validation_rules' => [
                    'require_quantity' => true,
                    'allow_partial_mutation' => true,
                ],
            ],
            'supply_mutation' => [
                'type' => 'batch',
                'batch_settings' => [
                    'tracking_enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                ],
                'validation_rules' => [
                    'require_quantity' => true,
                    'allow_partial_mutation' => true,
                ],
            ],
        ];
    }

    /**
     * Get default livestock configuration
     */
    public static function getDefaultLivestockConfig(): array
    {
        return [
            'batch_management' => [
                'enabled' => true,
                'auto_generate_batch' => true,
                'batch_number_format' => 'YEAR-SEQ',
                'require_batch_number' => true,
            ],
            'age_tracking' => [
                'enabled' => true,
                'track_daily' => true,
                'notify_on_threshold' => true,
                'threshold_days' => 30,
            ],
            'weight_tracking' => [
                'enabled' => true,
                'track_daily' => true,
                'require_weight' => true,
            ],
        ];
    }

    /**
     * Get default feed configuration
     */
    public static function getDefaultFeedConfig(): array
    {
        return [
            'stock_management' => [
                'enabled' => true,
                'track_expiry' => true,
                'notify_on_low_stock' => true,
                'low_stock_threshold' => 20, // percentage
            ],
            'batch_tracking' => [
                'enabled' => true,
                'require_batch_number' => true,
                'auto_generate_batch' => true,
            ],
        ];
    }

    /**
     * Get default supply configuration
     */
    public static function getDefaultSupplyConfig(): array
    {
        return [
            'stock_management' => [
                'enabled' => true,
                'track_expiry' => true,
                'notify_on_low_stock' => true,
                'low_stock_threshold' => 20, // percentage
            ],
            'batch_tracking' => [
                'enabled' => true,
                'require_batch_number' => true,
                'auto_generate_batch' => true,
            ],
        ];
    }

    /**
     * Get default notification configuration
     */
    public static function getDefaultNotificationConfig(): array
    {
        return [
            'channels' => [
                'email' => true,
                'database' => true,
                'broadcast' => true,
            ],
            'events' => [
                'mutation' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                ],
                'batch_completion' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                ],
                'low_stock' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                ],
                'age_threshold' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                ],
            ],
        ];
    }

    /**
     * Get default reporting configuration
     */
    public static function getDefaultReportingConfig(): array
    {
        return [
            'default_period' => 'monthly',
            'available_periods' => ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'],
            'export_formats' => ['pdf', 'excel', 'csv'],
            'auto_generate' => [
                'enabled' => false,
                'schedule' => 'monthly',
            ],
            'retention_period' => 365, // days
        ];
    }
}
