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
    CurrentLivestock,
    LivestockDepletion,
    Recording
};

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 MENYELARASKAN QUANTITY AYAM DENGAN RECORDING DAN DEPLETION\n";
echo "============================================================\n\n";

$fixCount = 0;

// Get all livestock with their current livestock records
$livestocks = Livestock::with(['currentLivestock'])->get();

foreach ($livestocks as $livestock) {
    echo "🐔 Processing Livestock: {$livestock->name} (ID: {$livestock->id})\n";
    echo "   - Initial Quantity: {$livestock->initial_quantity}\n";

    // Get all depletions for this livestock
    $totalDepletion = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');
    echo "   - Total Depletion (Kematian): {$totalDepletion}\n";

    // Get all recordings for this livestock (if any)
    $recordings = Recording::where('livestock_id', $livestock->id)->get();
    echo "   - Total Recordings: " . $recordings->count() . "\n";

    // Calculate expected current quantity
    $expectedQuantity = $livestock->initial_quantity - $totalDepletion;
    echo "   - Expected Current Quantity: {$expectedQuantity} (Initial: {$livestock->initial_quantity} - Depletion: {$totalDepletion})\n";

    // Update livestock depletion fields
    $livestock->update([
        'quantity_depletion' => $totalDepletion,
        'quantity_sales' => $livestock->quantity_sales ?? 0,
        'quantity_mutated' => $livestock->quantity_mutated ?? 0
    ]);

    // Update or create current livestock
    $currentLivestock = $livestock->currentLivestock;
    if ($currentLivestock) {
        $oldQuantity = $currentLivestock->quantity;
        echo "   - Current Record Quantity: {$oldQuantity}\n";

        if ($oldQuantity != $expectedQuantity) {
            $currentLivestock->update([
                'quantity' => $expectedQuantity,
                'berat_total' => $expectedQuantity * $currentLivestock->avg_berat
            ]);

            echo "   - ✅ DIPERBAIKI: {$oldQuantity} → {$expectedQuantity}\n";
            $fixCount++;
        } else {
            echo "   - ✅ SUDAH BENAR: {$expectedQuantity}\n";
        }
    } else {
        // Create new current livestock record
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

        echo "   - ✅ DIBUAT: CurrentLivestock dengan quantity {$expectedQuantity}\n";
        $fixCount++;
    }

    echo "\n";
}

// Update coop capacities if needed
echo "📊 MEMERIKSA KAPASITAS KANDANG\n";
echo "===============================\n";

$farms = Farm::with('kandangs')->get();

foreach ($farms as $farm) {
    echo "Farm: {$farm->name}\n";

    foreach ($farm->kandangs as $coop) {
        $currentLivestockInCoop = CurrentLivestock::where('coop_id', $coop->id)->sum('quantity');

        echo "  - Kandang: {$coop->name}\n";
        echo "    * Kapasitas: {$coop->capacity}\n";
        echo "    * Jumlah Ayam: {$currentLivestockInCoop}\n";
        echo "    * Persentase: " . round(($currentLivestockInCoop / max($coop->capacity, 1)) * 100, 2) . "%\n";

        if ($currentLivestockInCoop > $coop->capacity) {
            $newCapacity = $currentLivestockInCoop + 500; // Add 500 buffer
            $coop->update(['capacity' => $newCapacity]);
            echo "    * ⚠️  OVER CAPACITY - Kapasitas ditingkatkan menjadi: {$newCapacity}\n";
            $fixCount++;
        } else {
            echo "    * ✅ KAPASITAS OK\n";
        }
        echo "\n";
    }
}

// Final summary
echo "📊 RINGKASAN AKHIR\n";
echo "===================\n";

$totalCurrentLivestock = CurrentLivestock::sum('quantity');
$totalInitialLivestock = Livestock::sum('initial_quantity');
$totalDepletion = LivestockDepletion::sum('jumlah');

echo "Total Initial Livestock: {$totalInitialLivestock}\n";
echo "Total Depletion: {$totalDepletion}\n";
echo "Total Current Livestock: {$totalCurrentLivestock}\n";
echo "Expected Current: " . ($totalInitialLivestock - $totalDepletion) . "\n";

if ($totalCurrentLivestock == ($totalInitialLivestock - $totalDepletion)) {
    echo "✅ SEMUA QUANTITY SUDAH SELARAS!\n";
} else {
    echo "⚠️  ADA PERBEDAAN: " . (($totalInitialLivestock - $totalDepletion) - $totalCurrentLivestock) . "\n";
}

echo "\nTotal Perbaikan: {$fixCount}\n";
echo "🔧 Proses selesai!\n";
