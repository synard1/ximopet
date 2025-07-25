<?php

namespace App\Config;

/**
 * LivestockNumberingConfig
 *
 * Konfigurasi sistem penomoran untuk seluruh tabel utama livestock management.
 * Versi awal: hardcoded, future-proof, siap migrasi ke DB.
 *
 * Untuk migrasi ke DB, cukup override getConfig() agar membaca dari DB.
 *
 * @version 1.0
 * @since 2025-07-01
 */
class LivestockNumberingConfig
{
    /**
     * Get all numbering config (future-proof, bisa di-migrate ke DB)
     */
    public static function getConfig(): array
    {
        return [
            'livestocks' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'yearly',
                'prefix' => 'LS',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'LS-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor unik untuk master ternak, reset tahunan.',
            ],
            'livestock_batches' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'yearly',
                'prefix' => 'LSB',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'LSB-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor batch ternak, reset tahunan.',
            ],
            'livestock_purchases' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'yearly',
                'prefix' => 'LSP',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'LSP-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor pembelian ternak, reset tahunan.',
            ],
            'livestock_purchase_items' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail pembelian ternak tidak memerlukan penomoran khusus.',
            ],
            'livestock_mutations' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:4}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'yearly',
                'prefix' => 'LSM',
                'number_padding' => 4,
                'date_format' => null,
                'separator' => '/',
                'example' => 'LSM-0001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor mutasi ternak, reset tahunan.',
            ],
            'livestock_mutation_items' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail mutasi ternak tidak memerlukan penomoran khusus.',
            ],
            'livestock_sales' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:4}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'monthly',
                'prefix' => 'LSS',
                'number_padding' => 4,
                'date_format' => null,
                'separator' => '/',
                'example' => 'LSS-0001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor penjualan ternak, reset bulanan.',
            ],
            'livestock_sales_items' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail penjualan ternak tidak memerlukan penomoran khusus.',
            ],
            'recordings' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Pencatatan harian tidak memerlukan penomoran khusus.',
            ],
            'livestock_depletions' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:4}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'monthly',
                'prefix' => 'LSD',
                'number_padding' => 4,
                'date_format' => null,
                'separator' => '/',
                'example' => 'LSD-0001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor deplesi ternak (mati/afkir), reset bulanan.',
            ],
            'livestock_costs' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Biaya harian tidak memerlukan penomoran khusus.',
            ],
            'current_livestocks' => [
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
