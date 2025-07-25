<?php

namespace App\Config;

/**
 * LivestockCostConfig
 *
 * Konfigurasi sistem kalkulasi biaya ternak (harian, batch, total, breakdown, dsb).
 * Versi awal: hardcoded, future-proof, siap migrasi ke DB.
 *
 * Untuk migrasi ke DB, cukup override getConfig() agar membaca dari DB.
 *
 * @version 1.0
 * @since 2025-07-01
 */
class LivestockCostConfig
{
    /**
     * Get all cost calculation config (future-proof, bisa di-migrate ke DB)
     */
    public static function getConfig(): array
    {
        return [
            'enabled' => true,
            'calculation_methods' => [
                'daily' => [
                    'enabled' => true,
                    'description' => 'Perhitungan biaya harian per batch',
                ],
                'batch' => [
                    'enabled' => true,
                    'description' => 'Perhitungan biaya per batch (akumulasi)',
                ],
                'total' => [
                    'enabled' => true,
                    'description' => 'Total biaya seluruh batch',
                ],
                'breakdown' => [
                    'enabled' => true,
                    'description' => 'Rincian biaya (pakan, OVK, supply, tenaga kerja, dll.)',
                ],
            ],
            'reset_rule' => 'monthly', // daily, monthly, yearly
            'audit_trail' => [
                'enabled' => true,
                'track_changes' => true,
                'max_history' => 12,
            ],
            'extensibility' => [
                'custom_handler' => null,
                'notes' => 'Override untuk custom kalkulasi jika diperlukan',
            ],
            'performance' => [
                'enable_caching' => true,
                'cache_ttl_seconds' => 3600,
                'batch_size' => 100,
            ],
            'notification' => [
                'enabled' => false,
                'notify_on_threshold' => true,
                'threshold_percentage' => 10,
            ],
        ];
    }

    /**
     * Get config for a specific calculation method
     */
    public static function getFor(string $method): array
    {
        $config = self::getConfig();
        return $config['calculation_methods'][$method] ?? [];
    }
}
