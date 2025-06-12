<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use App\Models\{
    Farm,
    Coop,
    Livestock,
    LivestockPurchase,
    LivestockPurchaseItem,
    LivestockBatch,
    CurrentLivestock
};

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 ANALISIS KONSISTENSI DATA AYAM DAN KANDANG\n";
echo "==============================================\n\n";

// 1. Check Farm vs Coop consistency
echo "📊 1. KONSISTENSI FARM DAN KANDANG\n";
echo "-----------------------------------\n";

$farms = Farm::with('kandangs')->get();
$totalInconsistencies = 0;

foreach ($farms as $farm) {
    echo "Farm: {$farm->name} (ID: {$farm->id})\n";
    echo "  - Code: {$farm->code}\n";
    echo "  - Jumlah Kandang: " . $farm->kandangs->count() . "\n";

    $farmTotalCapacity = 0;
    $farmTotalLivestock = 0;

    foreach ($farm->kandangs as $coop) {
        echo "  - Kandang: {$coop->name} (ID: {$coop->id}) \n";
        echo "    * Code: {$coop->code}\n";
        echo "    * Kapasitas: {$coop->capacity}\n";

        // Check livestock in this coop
        $currentLivestock = CurrentLivestock::where('coop_id', $coop->id)->get();
        $totalQuantity = $currentLivestock->sum('quantity');

        echo "    * Jumlah Ayam Saat Ini: {$totalQuantity}\n";
        echo "    * Persentase Penggunaan: " . round(($totalQuantity / max($coop->capacity, 1)) * 100, 2) . "%\n";

        $farmTotalCapacity += $coop->capacity;
        $farmTotalLivestock += $totalQuantity;

        // Check inconsistencies
        if ($totalQuantity > $coop->capacity) {
            echo "    * ⚠️  OVER CAPACITY: {$totalQuantity} > {$coop->capacity}\n";
            $totalInconsistencies++;
        }
    }

    echo "  - TOTAL FARM - Kapasitas: {$farmTotalCapacity}, Ayam: {$farmTotalLivestock}\n";
    echo "  - Persentase Penggunaan Farm: " . round(($farmTotalLivestock / max($farmTotalCapacity, 1)) * 100, 2) . "%\n\n";
}

// 2. Check Purchase vs Current Livestock consistency
echo "📊 2. KONSISTENSI PEMBELIAN VS STOK SAAT INI\n";
echo "---------------------------------------------\n";

$livestocks = Livestock::with(['currentLivestock', 'farm', 'coop'])->get();

foreach ($livestocks as $livestock) {
    echo "Livestock: {$livestock->name} (ID: {$livestock->id})\n";
    echo "  - Farm: " . ($livestock->farm->name ?? 'N/A') . "\n";
    echo "  - Kandang: " . ($livestock->coop->name ?? 'N/A') . "\n";
    echo "  - Jumlah Awal (Initial): {$livestock->initial_quantity}\n";
    echo "  - Berat Awal: {$livestock->initial_weight}g\n";
    echo "  - Status: {$livestock->status}\n";

    // Current livestock data
    $currentData = $livestock->currentLivestock;
    if ($currentData) {
        echo "  - Jumlah Saat Ini: {$currentData->quantity}\n";
        echo "  - Berat Rata-rata: {$currentData->avg_berat}g\n";
        echo "  - Berat Total: {$currentData->berat_total}g\n";
        echo "  - Umur: {$currentData->age} hari\n";
        echo "  - Status Current: {$currentData->status}\n";
    } else {
        echo "  - ❌ TIDAK ADA DATA CURRENT LIVESTOCK!\n";
        $totalInconsistencies++;
    }

    // Purchase items data - menggunakan query manual karena relationship belum ada
    $purchaseItems = LivestockPurchaseItem::where('livestock_id', $livestock->id)->get();
    $totalPurchased = 0;
    if ($purchaseItems->count() > 0) {
        echo "  - Data Pembelian:\n";
        foreach ($purchaseItems as $item) {
            echo "    * Quantity: {$item->quantity}\n";
            echo "    * Weight per Unit: {$item->weight_per_unit}g\n";
            echo "    * Total Weight: {$item->weight_total}g\n";
            echo "    * Price per Unit: Rp " . number_format($item->price_per_unit) . "\n";
            $totalPurchased += $item->quantity;
        }
    } else {
        echo "  - ❌ TIDAK ADA DATA PURCHASE ITEMS!\n";
        $totalInconsistencies++;
    }

    // Check consistency
    $expectedQuantity = $livestock->initial_quantity;
    $actualQuantity = $currentData ? $currentData->quantity : 0;

    if ($expectedQuantity != $actualQuantity) {
        echo "  - ⚠️  INKONSISTENSI QUANTITY: Expected {$expectedQuantity}, Actual {$actualQuantity}, Difference: " . ($expectedQuantity - $actualQuantity) . "\n";
        $totalInconsistencies++;
    } else {
        echo "  - ✅ Quantity KONSISTEN\n";
    }

    // Check purchase vs initial consistency
    if ($totalPurchased != $livestock->initial_quantity) {
        echo "  - ⚠️  INKONSISTENSI PURCHASE: Total Purchased {$totalPurchased}, Initial {$livestock->initial_quantity}\n";
        $totalInconsistencies++;
    } else {
        echo "  - ✅ Purchase KONSISTEN dengan Initial\n";
    }

    echo "\n";
}

// 3. Check Batch Data
echo "📊 3. KONSISTENSI DATA BATCH\n";
echo "----------------------------\n";

$batches = LivestockBatch::with(['livestock', 'purchaseItem', 'farm', 'kandang'])->get();

foreach ($batches as $batch) {
    echo "Batch: {$batch->name} (ID: {$batch->id})\n";
    echo "  - Livestock ID: {$batch->livestock_id}\n";
    echo "  - Farm: " . ($batch->farm->name ?? 'N/A') . "\n";
    echo "  - Kandang: " . ($batch->kandang->name ?? 'N/A') . "\n";
    echo "  - Initial Quantity: {$batch->initial_quantity}\n";
    echo "  - Weight per Unit: {$batch->weight_per_unit}g\n";
    echo "  - Total Weight: {$batch->weight_total}g\n";
    echo "  - Status: {$batch->status}\n";

    // Check linked purchase item
    if ($batch->purchaseItem) {
        $item = $batch->purchaseItem;
        if ($batch->initial_quantity != $item->quantity) {
            echo "  - ⚠️  INKONSISTENSI dengan Purchase Item: Batch {$batch->initial_quantity} vs Purchase {$item->quantity}\n";
            $totalInconsistencies++;
        } else {
            echo "  - ✅ KONSISTEN dengan Purchase Item\n";
        }
    } else {
        echo "  - ❌ TIDAK ADA PURCHASE ITEM TERKAIT!\n";
        $totalInconsistencies++;
    }

    // Check consistency with livestock
    if ($batch->livestock) {
        if ($batch->initial_quantity != $batch->livestock->initial_quantity) {
            echo "  - ⚠️  INKONSISTENSI dengan Livestock: Batch {$batch->initial_quantity} vs Livestock {$batch->livestock->initial_quantity}\n";
            $totalInconsistencies++;
        } else {
            echo "  - ✅ KONSISTEN dengan Livestock\n";
        }
    }

    echo "\n";
}

// 4. Summary Statistics
echo "📊 4. RINGKASAN STATISTIK\n";
echo "-------------------------\n";

$totalFarms = Farm::count();
$totalCoops = Coop::count();
$totalLivestock = Livestock::count();
$totalCurrentLivestock = CurrentLivestock::sum('quantity');
$totalPurchases = LivestockPurchase::count();
$totalPurchaseItems = LivestockPurchaseItem::count();
$totalBatches = LivestockBatch::count();

echo "Total Farms: {$totalFarms}\n";
echo "Total Kandang: {$totalCoops}\n";
echo "Total Livestock Records: {$totalLivestock}\n";
echo "Total Ayam Saat Ini: {$totalCurrentLivestock}\n";
echo "Total Purchase Records: {$totalPurchases}\n";
echo "Total Purchase Items: {$totalPurchaseItems}\n";
echo "Total Batches: {$totalBatches}\n";

// Check for orphaned records
$orphanedCurrentLivestock = CurrentLivestock::whereNotExists(function ($query) {
    $query->select(\DB::raw(1))
        ->from('livestocks')
        ->whereRaw('livestocks.id = current_livestocks.livestock_id');
})->count();

$orphanedBatches = LivestockBatch::whereNotExists(function ($query) {
    $query->select(\DB::raw(1))
        ->from('livestocks')
        ->whereRaw('livestocks.id = livestock_batches.livestock_id');
})->count();

$orphanedPurchaseItems = LivestockPurchaseItem::whereNull('livestock_id')->count();

echo "\n🔍 ORPHANED RECORDS:\n";
echo "Current Livestock tanpa Livestock: {$orphanedCurrentLivestock}\n";
echo "Batches tanpa Livestock: {$orphanedBatches}\n";
echo "Purchase Items tanpa Livestock: {$orphanedPurchaseItems}\n";

// Data quality summary
echo "\n🎯 RINGKASAN KUALITAS DATA:\n";
echo "Total Inkonsistensi Ditemukan: {$totalInconsistencies}\n";

if ($totalInconsistencies == 0) {
    echo "✅ SEMUA DATA KONSISTEN!\n";
} else {
    echo "⚠️  ADA MASALAH KONSISTENSI - PERLU DIPERBAIKI\n";
}

echo "\n✅ Analisis selesai!\n";
