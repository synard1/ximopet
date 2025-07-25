<?php

namespace App\Config;

/**
 * SupplyNumberingConfig
 *
 * Konfigurasi sistem penomoran untuk seluruh tabel utama supply management.
 * Versi awal: hardcoded, future-proof, siap migrasi ke DB.
 *
 * Untuk migrasi ke DB, cukup override getConfig() agar membaca dari DB.
 *
 * @version 1.0
 * @since 2025-07-01
 */
class SupplyNumberingConfig
{
    /**
     * Get all numbering config (future-proof, bisa di-migrate ke DB)
     */
    public static function getConfig(): array
    {
        return [
            'supply_categories' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Kategori supply tidak memerlukan penomoran khusus.',
            ],
            'supplies' => [
                'enabled' => true,
                'format' => '{urut:5}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => null,
                'number_padding' => 5,
                'date_format' => null,
                'separator' => '/',
                'example' => '00001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor unik untuk setiap item supply, reset harian.',
            ],
            'supply_purchase_batches' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => 'SPB',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'SPB-00001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor batch pembelian supply, reset harian.',
            ],
            'supply_purchases' => [
                'enabled' => false,
                'format' => '{urut:5}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => null,
                'number_padding' => 5,
                'date_format' => null,
                'separator' => '/',
                'example' => '00001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor detail pembelian supply, reset harian.',
            ],
            'supply_stocks' => [
                'enabled' => true,
                'format' => '{urut:5}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => null,
                'number_padding' => 5,
                'date_format' => null,
                'separator' => '/',
                'example' => '00001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor unik untuk setiap stok supply, reset harian.',
            ],
            'supply_usages' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => 'SU',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'SU-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor penggunaan supply, reset harian.',
            ],
            'supply_usage_details' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail penggunaan supply tidak memerlukan penomoran khusus.',
            ],
            'supply_mutations' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => 'SMUT',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'SMUT-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor mutasi supply, reset harian.',
            ],
            'supply_mutation_items' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail mutasi supply tidak memerlukan penomoran khusus.',
            ],
            'current_supplies' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Tabel agregat, tidak memerlukan penomoran.',
            ],
        ];
    }

    /**
     * Get numbering config for a specific table/model
     */
    public static function getFor(string $table): array
    {
        $config = self::getConfig();
        return $config[$table] ?? [];
    }
}
