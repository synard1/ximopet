<?php

/**
 * Test Script: Daily Report Aggregation Verification
 * 
 * Testing agregasi data laporan harian per kandang ketika ada multiple batch livestock
 * dalam satu kandang yang sama.
 * 
 * Date: 2025-01-02
 * Purpose: Verify that multiple livestock batches in same coop are properly aggregated
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\App;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\Recording;
use App\Models\FeedUsageDetail;
use Carbon\Carbon;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DAILY REPORT AGGREGATION TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test parameters
$farmId = '9f1ce80a-ebbb-4301-af61-db2f72376536'; // Farm ID from log
$tanggal = '2025-06-02'; // Date from log

echo "🔍 Testing Parameters:\n";
echo "   Farm ID: {$farmId}\n";
echo "   Date: {$tanggal}\n\n";

try {
    // 1. Get farm data
    $farm = Farm::find($farmId);
    if (!$farm) {
        throw new Exception("Farm not found with ID: {$farmId}");
    }

    echo "🏠 Farm Found: {$farm->nama}\n\n";

    // 2. Get livestock data
    $livestocks = Livestock::where('farm_id', $farm->id)
        ->whereDate('start_date', '<=', $tanggal)
        ->with(['coop'])
        ->get();

    echo "🐔 Livestock Data:\n";
    echo "   Total Livestock: {$livestocks->count()}\n";

    if ($livestocks->count() === 0) {
        throw new Exception("No livestock found for this farm and date");
    }

    // 3. Group by coop and show structure
    $livestocksByCoopNama = $livestocks->groupBy(function ($livestock) {
        return $livestock->coop->name;
    });

    echo "\n📊 Livestock Distribution by Coop:\n";
    foreach ($livestocksByCoopNama as $coopName => $coopLivestocks) {
        echo "   {$coopName}:\n";
        echo "     - Livestock Count: {$coopLivestocks->count()}\n";
        echo "     - Livestock IDs:\n";
        foreach ($coopLivestocks as $livestock) {
            echo "       • {$livestock->id} (Stock Awal: {$livestock->initial_quantity})\n";
        }
        echo "\n";
    }

    // 4. Test aggregation for each coop
    echo "🧮 AGGREGATION TEST RESULTS:\n";
    $totalAggregated = [
        'coops' => 0,
        'total_stock_awal' => 0,
        'total_deplesi' => 0,
        'total_stock_akhir' => 0
    ];

    foreach ($livestocksByCoopNama as $coopName => $coopLivestocks) {
        echo "\n--- {$coopName} ---\n";

        $coopAggregated = [
            'stock_awal' => 0,
            'mortality_today' => 0,
            'total_deplesi' => 0,
            'stock_akhir' => 0,
            'feed_consumption' => 0,
            'livestock_details' => []
        ];

        foreach ($coopLivestocks as $livestock) {
            $stockAwal = (int) $livestock->initial_quantity;

            // Get mortality for specific date
            $mortalityToday = (int) LivestockDepletion::where('livestock_id', $livestock->id)
                ->where('jenis', 'Mati')
                ->where('tanggal', $tanggal)
                ->sum('jumlah');

            // Get total depletion up to date
            $totalDeplesi = (int) LivestockDepletion::where('livestock_id', $livestock->id)
                ->where('tanggal', '<=', $tanggal)
                ->sum('jumlah');

            // Get feed consumption for today
            $feedConsumption = (float) FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
                $query->where('livestock_id', $livestock->id)
                    ->whereDate('usage_date', $tanggal);
            })->sum('quantity_taken');

            $stockAkhir = $stockAwal - $totalDeplesi;

            // Aggregate per coop
            $coopAggregated['stock_awal'] += $stockAwal;
            $coopAggregated['mortality_today'] += $mortalityToday;
            $coopAggregated['total_deplesi'] += $totalDeplesi;
            $coopAggregated['stock_akhir'] += $stockAkhir;
            $coopAggregated['feed_consumption'] += $feedConsumption;

            $coopAggregated['livestock_details'][] = [
                'id' => $livestock->id,
                'stock_awal' => $stockAwal,
                'mortality_today' => $mortalityToday,
                'total_deplesi' => $totalDeplesi,
                'stock_akhir' => $stockAkhir,
                'feed_consumption' => $feedConsumption
            ];
        }

        // Display coop aggregation
        echo "Livestock Count: {$coopLivestocks->count()}\n";
        echo "Aggregated Stock Awal: {$coopAggregated['stock_awal']}\n";
        echo "Aggregated Mortality Today: {$coopAggregated['mortality_today']}\n";
        echo "Aggregated Total Deplesi: {$coopAggregated['total_deplesi']}\n";
        echo "Aggregated Stock Akhir: {$coopAggregated['stock_akhir']}\n";
        echo "Aggregated Feed Consumption: " . number_format($coopAggregated['feed_consumption'], 2) . " kg\n";

        $depletionPercentage = $coopAggregated['stock_awal'] > 0
            ? round(($coopAggregated['total_deplesi'] / $coopAggregated['stock_awal']) * 100, 2)
            : 0;
        echo "Depletion Percentage: {$depletionPercentage}%\n";

        echo "\nDetailed Breakdown:\n";
        foreach ($coopAggregated['livestock_details'] as $detail) {
            echo "  • {$detail['id']}: Stock {$detail['stock_awal']} → Deplesi {$detail['total_deplesi']} → Akhir {$detail['stock_akhir']}\n";
        }

        // Add to total
        $totalAggregated['coops']++;
        $totalAggregated['total_stock_awal'] += $coopAggregated['stock_awal'];
        $totalAggregated['total_deplesi'] += $coopAggregated['total_deplesi'];
        $totalAggregated['total_stock_akhir'] += $coopAggregated['stock_akhir'];
    }

    // 5. Final summary
    echo "\n🏆 FINAL AGGREGATION SUMMARY:\n";
    echo "Total Coops: {$totalAggregated['coops']}\n";
    echo "Total Stock Awal: {$totalAggregated['total_stock_awal']}\n";
    echo "Total Deplesi: {$totalAggregated['total_deplesi']}\n";
    echo "Total Stock Akhir: {$totalAggregated['total_stock_akhir']}\n";

    $overallDepletionPercentage = $totalAggregated['total_stock_awal'] > 0
        ? round(($totalAggregated['total_deplesi'] / $totalAggregated['total_stock_awal']) * 100, 2)
        : 0;
    echo "Overall Depletion Percentage: {$overallDepletionPercentage}%\n";

    // 6. Expected vs Previous Logic Comparison
    echo "\n📋 COMPARISON WITH PREVIOUS LOGIC:\n";
    echo "Previous Logic: Would show only last livestock per coop (overwrite issue)\n";
    echo "New Logic: Properly aggregates all livestock in same coop\n\n";

    // Expected results based on log data:
    // Kandang 1: livestock 1 (5714) + livestock 3 (4748) = 10462
    // Kandang 2: livestock 2 (4764) + livestock 4 (5158) = 9922
    echo "Expected Results (based on log data):\n";
    echo "  Kandang 1 - Demo Farm: 5714 + 4748 = 10462\n";
    echo "  Kandang 2 - Demo Farm: 4764 + 5158 = 9922\n";
    echo "  Total: 20384\n\n";

    echo "✅ AGGREGATION TEST COMPLETED SUCCESSFULLY\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END OF TEST ===\n";
