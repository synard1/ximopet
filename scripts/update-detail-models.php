<?php

/**
 * Script untuk mengupdate model detail dengan pattern company_id handling baru
 * 
 * Usage: php scripts/update-detail-models.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Daftar model detail yang perlu diupdate
$detailModels = [
    // Feed Related
    'FeedMutationItem' => [
        'parent' => 'FeedMutation',
        'reason' => 'Item detail dari mutasi pakan, company_id diwarisi dari FeedMutation'
    ],
    'FeedRollbackItem' => [
        'parent' => 'FeedRollback',
        'reason' => 'Item detail dari rollback pakan, company_id diwarisi dari FeedRollback'
    ],
    'FeedUsageDetail' => [
        'parent' => 'FeedUsage',
        'reason' => 'Detail penggunaan pakan, company_id diwarisi dari FeedUsage'
    ],
    'FeedStatusHistory' => [
        'parent' => 'Feed',
        'reason' => 'History status pakan, company_id diwarisi dari Feed'
    ],

    // Livestock Related
    'LivestockPurchaseItem' => [
        'parent' => 'LivestockPurchase',
        'reason' => 'Item detail pembelian ternak, company_id diwarisi dari LivestockPurchase'
    ],
    'LivestockSalesItem' => [
        'parent' => 'LivestockSales',
        'reason' => 'Item detail penjualan ternak, company_id diwarisi dari LivestockSales'
    ],
    'LivestockPurchaseStatusHistory' => [
        'parent' => 'LivestockPurchase',
        'reason' => 'History status pembelian ternak, company_id diwarisi dari LivestockPurchase'
    ],

    // Supply Related
    'SupplyMutationItem' => [
        'parent' => 'SupplyMutation',
        'reason' => 'Item detail mutasi supply, company_id diwarisi dari SupplyMutation'
    ],
    'SupplyUsageDetail' => [
        'parent' => 'SupplyUsage',
        'reason' => 'Detail penggunaan supply, company_id diwarisi dari SupplyUsage'
    ],
    'SupplyStatusHistory' => [
        'parent' => 'Supply',
        'reason' => 'History status supply, company_id diwarisi dari Supply'
    ],
    'SupplyPurchaseBatch' => [
        'parent' => 'SupplyPurchase',
        'reason' => 'Batch pembelian supply, company_id diwarisi dari SupplyPurchase'
    ],

    // Transaction Related
    'TransaksiBeliDetail' => [
        'parent' => 'TransaksiBeli',
        'reason' => 'Detail transaksi beli, company_id diwarisi dari TransaksiBeli'
    ],
    'TransaksiHarianDetail' => [
        'parent' => 'TransaksiHarian',
        'reason' => 'Detail transaksi harian, company_id diwarisi dari TransaksiHarian'
    ],
    'TransaksiJualDetail' => [
        'parent' => 'TransaksiJual',
        'reason' => 'Detail transaksi jual, company_id diwarisi dari TransaksiJual'
    ],

    // OVK Related
    'OVKRecordItem' => [
        'parent' => 'OVKRecord',
        'reason' => 'Item detail record OVK, company_id diwarisi dari OVKRecord'
    ],
];

echo "=== BaseModel Company ID Handling Update Script ===\n\n";

foreach ($detailModels as $modelName => $config) {
    $modelPath = __DIR__ . "/../app/Models/{$modelName}.php";

    if (!file_exists($modelPath)) {
        echo "❌ {$modelName}: File tidak ditemukan\n";
        continue;
    }

    echo "📝 Processing {$modelName}...\n";

    $content = file_get_contents($modelPath);

    // Check if already updated
    if (strpos($content, '$requiresCompanyId = false') !== false) {
        echo "   ✅ {$modelName}: Sudah diupdate\n";
        continue;
    }

    // Check if extends BaseModel
    if (strpos($content, 'extends BaseModel') === false) {
        echo "   ⚠️  {$modelName}: Tidak extend BaseModel\n";
        continue;
    }

    // Add requiresCompanyId property
    $requiresCompanyIdProperty = <<<PHP

    /**
     * This is a detail model that inherits company_id from {$config['parent']} parent
     * {$config['reason']}
     */
    protected \$requiresCompanyId = false;

PHP;

    // Find the class opening and add the property
    $pattern = '/(class\s+' . $modelName . '\s+extends\s+BaseModel\s*\{[^}]*use\s+[^;]+;)/s';
    if (preg_match($pattern, $content, $matches)) {
        $replacement = $matches[1] . $requiresCompanyIdProperty;
        $content = str_replace($matches[1], $replacement, $content);
    }

    // Convert $fillable to $additionalFillable
    if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\];/s', $content, $matches)) {
        $fillableContent = $matches[1];

        // Remove created_by and updated_by from fillable
        $fillableContent = preg_replace('/\s*[\'"]created_by[\'"],?\s*/', '', $fillableContent);
        $fillableContent = preg_replace('/\s*[\'"]updated_by[\'"],?\s*/', '', $fillableContent);

        // Clean up extra commas
        $fillableContent = preg_replace('/,\s*,/', ',', $fillableContent);
        $fillableContent = preg_replace('/,\s*\]/', ']', $fillableContent);
        $fillableContent = preg_replace('/\[\s*,/', '[', $fillableContent);

        $additionalFillable = <<<PHP

    /**
     * Additional fillable fields specific to this model
     */
    protected \$additionalFillable = [{$fillableContent}];

PHP;

        // Replace $fillable with $additionalFillable
        $content = preg_replace('/protected\s+\$fillable\s*=\s*\[.*?\];/s', $additionalFillable, $content);
    }

    // Write back to file
    if (file_put_contents($modelPath, $content)) {
        echo "   ✅ {$modelName}: Berhasil diupdate\n";
    } else {
        echo "   ❌ {$modelName}: Gagal menulis file\n";
    }
}

echo "\n=== Update Summary ===\n";
echo "Script selesai. Silakan review perubahan yang dibuat.\n";
echo "Pastikan untuk:\n";
echo "1. Test model creation dan update\n";
echo "2. Cek relationship integrity\n";
echo "3. Run migration jika diperlukan\n";
echo "4. Update dokumentasi jika ada\n";
