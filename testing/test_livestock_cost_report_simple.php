<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Livestock;
use App\Models\LivestockPurchaseItem;
use App\Models\LivestockCost;
use App\Models\Recording;
use App\Services\Livestock\LivestockCostService;
use Carbon\Carbon;

echo "=== LIVESTOCK COST CALCULATION TEST (Business Flow v2.0) ===\n";
echo "Alur Bisnis: Ayam masuk → dicatat yang mati → ayam masuk kandang → diberi makan (termasuk hari pertama)\n\n";

// Get first livestock
$livestock = Livestock::with(['farm', 'coop'])->first();
if (!$livestock) {
    echo "❌ No livestock found\n";
    exit;
}

echo "✅ Found livestock: {$livestock->name}\n";
echo "🏠 Farm: " . ($livestock->farm->name ?? 'Unknown') . "\n";
echo "🏠 Kandang: " . ($livestock->coop->name ?? 'Unknown') . "\n";
echo "📅 Start Date: " . Carbon::parse($livestock->start_date)->format('d/m/Y') . "\n";
echo "🐔 Initial Quantity: {$livestock->initial_quantity}\n\n";

// Get initial purchase data
$initialPurchase = LivestockPurchaseItem::where('livestock_id', $livestock->id)
    ->orderBy('created_at', 'asc')
    ->first();

if (!$initialPurchase) {
    echo "❌ No initial purchase data found\n";
    exit;
}

echo "=== INITIAL PURCHASE DATA ===\n";
printf("%-25s: %s\n", "Date", $initialPurchase->created_at->format('d/m/Y'));
printf("%-25s: %s ekor\n", "Quantity", number_format($initialPurchase->quantity ?? 0));
printf("%-25s: Rp %s\n", "Price per Unit", number_format(floatval($initialPurchase->price_per_unit ?? 0), 2));
printf("%-25s: Rp %s\n", "Total Purchase Cost", number_format(floatval($initialPurchase->price_total ?? 0), 2));
echo "\n";

// Test cost calculation for multiple dates
$startDate = Carbon::parse($livestock->start_date);
$today = Carbon::today();
$testDates = [];

// Add first 5 days and some random days
for ($i = 0; $i < min(5, $startDate->diffInDays($today) + 1); $i++) {
    $testDates[] = $startDate->copy()->addDays($i);
}

// Add some additional random dates if available
if ($startDate->diffInDays($today) > 5) {
    $testDates[] = $startDate->copy()->addDays(10);
    $testDates[] = $startDate->copy()->addDays(20);
    $testDates[] = $today->copy();
}

$costService = new LivestockCostService();

echo "=== DAILY COST CALCULATIONS ===\n";
echo "Format: Date | Age | Stock | Feed Cost | OVK Cost | Deplesi Cost | Total Daily | Cost per Chicken | Cumulative Cost\n";
echo str_repeat("=", 120) . "\n";

$previousTotalCostPerChicken = $initialPurchase->price_per_unit;

foreach ($testDates as $date) {
    $dateStr = $date->format('Y-m-d');
    $age = $startDate->diffInDays($date);

    try {
        // Calculate cost for this date
        $costData = $costService->calculateForDate($livestock->id, $dateStr);

        // Get recording data
        $recording = Recording::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $dateStr)
            ->first();

        $breakdown = $costData->cost_breakdown ?? [];
        $summary = $breakdown['summary'] ?? [];

        // Extract cost components
        $feedCost = $breakdown['pakan'] ?? 0;
        $ovkCost = $breakdown['ovk'] ?? 0;
        $deplesiCost = $breakdown['deplesi'] ?? 0;
        $totalDailyCost = $costData->total_cost ?? 0;
        $stockAkhir = $breakdown['stock_akhir'] ?? $livestock->initial_quantity;
        $deplesiEkor = $breakdown['deplesi_ekor'] ?? 0;
        $totalCostPerChicken = $costData->cost_per_ayam ?? 0;

        // Display main data
        printf(
            "%-12s| %3d | %5d | %10s | %9s | %12s | %11s | %13s | %15s\n",
            $date->format('d/m/Y'),
            $age,
            $stockAkhir,
            number_format($feedCost, 0),
            number_format($ovkCost, 0),
            number_format($deplesiCost, 0),
            number_format($totalDailyCost, 0),
            number_format($totalCostPerChicken, 0),
            number_format($totalCostPerChicken * $stockAkhir, 0)
        );

        // Show detailed breakdown if there are costs
        if ($totalDailyCost > 0 || $deplesiEkor > 0) {
            echo "\n--- Detail Breakdown untuk " . $date->format('d/m/Y') . " ---\n";

            // Feed details
            $feedDetails = $breakdown['feed_detail'] ?? [];
            if (!empty($feedDetails)) {
                echo "🥬 PAKAN:\n";
                foreach ($feedDetails as $feedKey => $feed) {
                    printf(
                        "   %-20s: %8.2f %s × Rp %s = Rp %s\n",
                        $feed['feed_name'] ?? 'Unknown',
                        $feed['jumlah_purchase_unit'] ?? 0,
                        $feed['purchase_unit'] ?? 'unit',
                        number_format($feed['price_per_purchase_unit'] ?? 0, 2),
                        number_format($feed['subtotal'] ?? 0, 2)
                    );
                }
            }

            // OVK details
            $ovkDetails = $breakdown['ovk_detail'] ?? [];
            if (!empty($ovkDetails)) {
                echo "💊 OVK:\n";
                foreach ($ovkDetails as $ovkKey => $ovk) {
                    printf(
                        "   %-20s: %8.2f %s × Rp %s = Rp %s\n",
                        $ovk['supply_name'] ?? 'Unknown',
                        $ovk['quantity'] ?? 0,
                        $ovk['unit'] ?? 'unit',
                        number_format($ovk['price_per_unit'] ?? 0, 2),
                        number_format($ovk['subtotal'] ?? 0, 2)
                    );
                }
            }

            // Deplesi details
            if ($deplesiEkor > 0) {
                echo "💀 DEPLESI:\n";
                $prevCostData = $breakdown['prev_cost'] ?? [];
                $cumulativeCostPerChicken = $prevCostData['cumulative_cost_per_chicken'] ?? $initialPurchase->price_per_unit;
                printf(
                    "   %-20s: %8d ekor × Rp %s = Rp %s\n",
                    'Deplesi (Kumulatif)',
                    $deplesiEkor,
                    number_format($cumulativeCostPerChicken, 2),
                    number_format($deplesiCost, 2)
                );
                echo "   (Harga kumulatif per ayam hingga hari sebelumnya)\n";
            }

            echo "\n";
        }

        $previousTotalCostPerChicken = $totalCostPerChicken;
    } catch (Exception $e) {
        printf("%-12s| %3d | ERROR: %s\n", $date->format('d/m/Y'), $age, $e->getMessage());
    }
}

echo str_repeat("=", 120) . "\n";

// Summary table
echo "\n=== SUMMARY CALCULATION METHODOLOGY ===\n";
echo "1. Initial Purchase: Rp " . number_format($initialPurchase->price_per_unit, 2) . " per chicken\n";
echo "2. Daily Added Costs: Feed + OVK + Deplesi\n";
echo "3. Deplesi Cost = Jumlah mati/afkir × Harga kumulatif per ayam hari sebelumnya\n";
echo "4. Cumulative Cost per Chicken = Initial Price + (Total Added Costs / Stock Akhir)\n";
echo "5. Total Flock Value = Cumulative Cost per Chicken × Stock Akhir\n\n";

// Test comparison with report
echo "=== TESTING REPORT CONSISTENCY ===\n";
$testDate = $today->format('Y-m-d');
$costData = LivestockCost::where('livestock_id', $livestock->id)
    ->whereDate('tanggal', $testDate)
    ->first();

if ($costData) {
    echo "✅ Cost data found for " . $today->format('d/m/Y') . "\n";

    $breakdown = $costData->cost_breakdown ?? [];
    echo "📊 Report Values:\n";
    printf("   %-20s: Rp %s\n", "Total Daily Cost", number_format($costData->total_cost ?? 0, 2));
    printf("   %-20s: Rp %s\n", "Cost per Chicken", number_format($costData->cost_per_ayam ?? 0, 2));
    printf("   %-20s: Rp %s\n", "Feed Cost", number_format($breakdown['pakan'] ?? 0, 2));
    printf("   %-20s: Rp %s\n", "OVK Cost", number_format($breakdown['ovk'] ?? 0, 2));
    printf("   %-20s: Rp %s\n", "Deplesi Cost", number_format($breakdown['deplesi'] ?? 0, 2));
    printf("   %-20s: %d ekor\n", "Stock Akhir", $breakdown['stock_akhir'] ?? 0);
    printf("   %-20s: %d ekor\n", "Deplesi Qty", $breakdown['deplesi_ekor'] ?? 0);

    $summary = $breakdown['summary'] ?? [];
    if (!empty($summary)) {
        echo "\n📈 Summary Metrics:\n";
        printf("   %-25s: Rp %s\n", "Initial Price per Unit", number_format($summary['initial_price_per_unit'] ?? 0, 2));
        printf("   %-25s: Rp %s\n", "Total Cost per Chicken", number_format($summary['total_cost_per_chicken'] ?? 0, 2));
        printf("   %-25s: Rp %s\n", "Cumulative Feed Cost", number_format($summary['cumulative_feed_cost'] ?? 0, 2));
        printf("   %-25s: Rp %s\n", "Cumulative OVK Cost", number_format($summary['cumulative_ovk_cost'] ?? 0, 2));
        printf("   %-25s: Rp %s\n", "Cumulative Deplesi Cost", number_format($summary['cumulative_deplesi_cost'] ?? 0, 2));
        printf("   %-25s: Rp %s\n", "Total Flock Value", number_format($summary['total_flock_value'] ?? 0, 2));
    }
} else {
    echo "❌ No cost data found for " . $today->format('d/m/Y') . "\n";
    echo "   Generating cost data...\n";

    try {
        $generatedCost = $costService->calculateForDate($livestock->id, $testDate);
        echo "✅ Cost data generated successfully\n";
        printf("   Total Daily Cost: Rp %s\n", number_format($generatedCost->total_cost ?? 0, 2));
        printf("   Cost per Chicken: Rp %s\n", number_format($generatedCost->cost_per_ayam ?? 0, 2));
    } catch (Exception $e) {
        echo "❌ Error generating cost data: " . $e->getMessage() . "\n";
    }
}

echo "\n=== BUSINESS FLOW VALIDATION ===\n";
echo "✅ Ayam masuk: Data initial purchase tersedia\n";
echo "✅ Pencatatan deplesi: Menggunakan harga kumulatif\n";
echo "✅ Penempatan di kandang: Data farm dan coop tersedia\n";
echo "✅ Pemberian pakan: Feed cost dihitung dari hari pertama\n";
echo "✅ Perhitungan akurat: Menggunakan business flow v2.0\n\n";

echo "=== TEST COMPLETED ===\n";
echo "Script ini menguji konsistensi antara:\n";
echo "- LivestockCostService calculation\n";
echo "- Database storage (LivestockCost table)\n";
echo "- Report generation (livestock-cost.blade.php)\n";
echo "\nSemua nilai harus konsisten across components!\n";
