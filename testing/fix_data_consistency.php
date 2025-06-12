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

echo "🔧 MEMPERBAIKI KONSISTENSI DATA AYAM DAN KANDANG\n";
echo "================================================\n\n";

$fixCount = 0;

// 1. Fix missing relationships in Livestock PurchaseItems
echo "📊 1. MEMPERBAIKI RELASI LIVESTOCK PURCHASE ITEMS\n";
echo "---------------------------------------------------\n";

// Update livestock_purchase_items yang belum memiliki livestock_id
$orphanedPurchaseItems = LivestockPurchaseItem::whereNull('livestock_id')->get();

echo "Ditemukan " . $orphanedPurchaseItems->count() . " purchase items tanpa livestock_id\n\n";

foreach ($orphanedPurchaseItems as $item) {
    echo "Processing Purchase Item ID: {$item->id}\n";

    // Cari livestock berdasarkan purchase item di livestock_batches
    $batch = LivestockBatch::where('livestock_purchase_item_id', $item->id)->first();

    if ($batch && $batch->livestock_id) {
        echo "  - Ditemukan batch dengan livestock_id: {$batch->livestock_id}\n";
        $item->update(['livestock_id' => $batch->livestock_id]);
        echo "  - ✅ Purchase item updated dengan livestock_id\n";
        $fixCount++;
    } else {
        echo "  - ❌ Tidak ditemukan batch yang sesuai\n";
    }
    echo "\n";
}

// 2. Fix CurrentLivestock inconsistencies
echo "📊 2. MEMPERBAIKI INCONSISTENSI CURRENT LIVESTOCK\n";
echo "---------------------------------------------------\n";

$currentLivestocks = CurrentLivestock::with('livestock')->get();

foreach ($currentLivestocks as $current) {
    if (!$current->livestock) {
        echo "❌ CurrentLivestock {$current->id} tidak memiliki livestock - akan dihapus\n";
        $current->delete();
        $fixCount++;
        continue;
    }

    $livestock = $current->livestock;
    $expectedQuantity = $livestock->initial_quantity;
    $actualQuantity = $current->quantity;

    // Hitung depletion yang sudah ada
    $totalDepletion = $livestock->quantity_depletion ?? 0;
    $totalSales = $livestock->quantity_sales ?? 0;
    $totalMutated = $livestock->quantity_mutated ?? 0;

    $expectedCurrent = $expectedQuantity - $totalDepletion - $totalSales - $totalMutated;

    if ($actualQuantity != $expectedCurrent) {
        echo "Livestock: {$livestock->name}\n";
        echo "  - Expected Quantity: {$expectedCurrent} (Initial: {$expectedQuantity} - Depletion: {$totalDepletion} - Sales: {$totalSales} - Mutated: {$totalMutated})\n";
        echo "  - Actual Quantity: {$actualQuantity}\n";
        echo "  - Difference: " . ($expectedCurrent - $actualQuantity) . "\n";

        // Update current livestock quantity
        $current->update([
            'quantity' => $expectedCurrent,
            'berat_total' => $expectedCurrent * $current->avg_berat
        ]);

        echo "  - ✅ CurrentLivestock quantity diperbaiki\n";
        $fixCount++;
        echo "\n";
    }
}

// 3. Fix Coop capacity issues (over capacity)
echo "📊 3. MEMPERBAIKI MASALAH KAPASITAS KANDANG\n";
echo "--------------------------------------------\n";

$farms = Farm::with('kandangs')->get();

foreach ($farms as $farm) {
    foreach ($farm->kandangs as $coop) {
        $currentLivestockInCoop = CurrentLivestock::where('coop_id', $coop->id)->sum('quantity');

        if ($currentLivestockInCoop > $coop->capacity) {
            echo "Kandang: {$coop->name} (Farm: {$farm->name})\n";
            echo "  - Kapasitas: {$coop->capacity}\n";
            echo "  - Jumlah Ayam: {$currentLivestockInCoop}\n";
            echo "  - Over Capacity: " . ($currentLivestockInCoop - $coop->capacity) . "\n";

            // Option 1: Increase coop capacity
            $newCapacity = $currentLivestockInCoop + 1000; // Add 1000 buffer
            $coop->update(['capacity' => $newCapacity]);

            echo "  - ✅ Kapasitas kandang ditingkatkan menjadi: {$newCapacity}\n";
            $fixCount++;
            echo "\n";
        }
    }
}

// 4. Create missing CurrentLivestock records
echo "📊 4. MEMBUAT CURRENT LIVESTOCK YANG HILANG\n";
echo "--------------------------------------------\n";

$livestocksWithoutCurrent = Livestock::whereDoesntHave('currentLivestock')->get();

echo "Ditemukan " . $livestocksWithoutCurrent->count() . " livestock tanpa current record\n\n";

foreach ($livestocksWithoutCurrent as $livestock) {
    echo "Creating CurrentLivestock for: {$livestock->name}\n";

    $expectedQuantity = $livestock->initial_quantity -
        ($livestock->quantity_depletion ?? 0) -
        ($livestock->quantity_sales ?? 0) -
        ($livestock->quantity_mutated ?? 0);

    CurrentLivestock::create([
        'livestock_id' => $livestock->id,
        'farm_id' => $livestock->farm_id,
        'coop_id' => $livestock->coop_id,
        'quantity' => $expectedQuantity,
        'berat_total' => $expectedQuantity * ($livestock->initial_weight ?? 40),
        'avg_berat' => $livestock->initial_weight ?? 40,
        'age' => now()->diffInDays($livestock->start_date),
        'status' => 'active',
        'created_by' => $livestock->created_by
    ]);

    echo "  - ✅ CurrentLivestock created dengan quantity: {$expectedQuantity}\n";
    $fixCount++;
}

// 5. Fix Batch inconsistencies
echo "\n📊 5. MEMPERBAIKI INCONSISTENSI BATCH\n";
echo "--------------------------------------\n";

$batches = LivestockBatch::with(['livestock', 'purchaseItem'])->get();

foreach ($batches as $batch) {
    $needsUpdate = false;
    $updates = [];

    if ($batch->livestock && $batch->initial_quantity != $batch->livestock->initial_quantity) {
        echo "Batch: {$batch->name}\n";
        echo "  - Batch Quantity: {$batch->initial_quantity}\n";
        echo "  - Livestock Quantity: {$batch->livestock->initial_quantity}\n";

        $updates['initial_quantity'] = $batch->livestock->initial_quantity;
        $updates['weight_total'] = $batch->livestock->initial_quantity * $batch->weight_per_unit;
        $needsUpdate = true;
    }

    if ($batch->purchaseItem && $batch->initial_quantity != $batch->purchaseItem->quantity) {
        if (!$needsUpdate) {
            echo "Batch: {$batch->name}\n";
            echo "  - Batch Quantity: {$batch->initial_quantity}\n";
        }
        echo "  - Purchase Item Quantity: {$batch->purchaseItem->quantity}\n";

        $updates['initial_quantity'] = $batch->purchaseItem->quantity;
        $updates['weight_total'] = $batch->purchaseItem->quantity * $batch->weight_per_unit;
        $needsUpdate = true;
    }

    if ($needsUpdate) {
        $batch->update($updates);
        echo "  - ✅ Batch quantity diperbaiki\n";
        $fixCount++;
        echo "\n";
    }
}

// 6. Update farm-level statistics
echo "📊 6. UPDATE STATISTIK FARM\n";
echo "----------------------------\n";

foreach ($farms as $farm) {
    $totalLivestock = CurrentLivestock::where('farm_id', $farm->id)->sum('quantity');
    $totalCapacity = $farm->kandangs->sum('capacity');

    echo "Farm: {$farm->name}\n";
    echo "  - Total Livestock: {$totalLivestock}\n";
    echo "  - Total Capacity: {$totalCapacity}\n";
    echo "  - Utilization: " . round(($totalLivestock / max($totalCapacity, 1)) * 100, 2) . "%\n\n";
}

// Summary
echo "🎯 RINGKASAN PERBAIKAN\n";
echo "======================\n";
echo "Total Perbaikan: {$fixCount}\n";

if ($fixCount > 0) {
    echo "✅ DATA BERHASIL DIPERBAIKI!\n";
} else {
    echo "✅ SEMUA DATA SUDAH KONSISTEN!\n";
}

echo "\n🔧 Perbaikan selesai!\n";
