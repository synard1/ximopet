<?php

namespace App\Config;

/**
 * FeedNumberingConfig
 *
 * Konfigurasi sistem penomoran untuk seluruh tabel utama feed management.
 * Versi awal: hardcoded, future-proof, siap migrasi ke DB.
 *
 * Untuk migrasi ke DB, cukup override getConfig() agar membaca dari DB.
 *
 * @version 1.0
 * @since 2025-07-01
 */

class FeedNumberingConfig
{
    /**
     * Get all numbering config (future-proof, bisa di-migrate ke DB)
     */
    public static function getConfig(): array
    {
        return [
            'feeds' => [
                'enabled' => false,
                'format' => '{prefix}-{urut:4}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'yearly',
                'prefix' => 'FD',
                'number_padding' => 4,
                'date_format' => null,
                'separator' => '/',
                'example' => 'FD-0001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor unik untuk master feed, reset tahunan.',
            ],
            'feed_purchases' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'yearly',
                'prefix' => 'FDP',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'FDP-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor pembelian feed, reset tahunan.',
            ],
            'feed_purchase_batches' => [
                'enabled' => false,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'monthly',
                'prefix' => 'FDPB',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'FDPB-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor batch pembelian feed, reset bulanan.',
            ],
            'feed_purchase_items' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail pembelian feed tidak memerlukan penomoran khusus.',
            ],
            'feed_stocks' => [
                'enabled' => false,
                'format' => '{prefix}-{urut:5}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => 'FDS',
                'number_padding' => 5,
                'date_format' => null,
                'separator' => '/',
                'example' => 'FDS-00001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor unik untuk setiap stok feed, reset harian.',
            ],
            'feed_usages' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'daily',
                'prefix' => 'FDU',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'FDU-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor penggunaan feed, reset harian.',
            ],
            'feed_usage_details' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail penggunaan feed tidak memerlukan penomoran khusus.',
            ],
            'feed_mutations' => [
                'enabled' => true,
                'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
                'reset_rule' => 'yearly',
                'prefix' => 'FDM',
                'number_padding' => 3,
                'date_format' => null,
                'separator' => '/',
                'example' => 'FDM-001/14/07/2025',
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Nomor mutasi feed, reset tahunan.',
            ],
            'feed_mutation_items' => [
                'enabled' => false,
                'format' => null,
                'prefix' => null,
                'number_padding' => null,
                'date_format' => null,
                'separator' => null,
                'example' => null,
                'source' => 'hardcoded',
                'custom_handler' => null,
                'notes' => 'Detail mutasi feed tidak memerlukan penomoran khusus.',
            ],
            'current_feeds' => [
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
