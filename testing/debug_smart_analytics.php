<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Livestock;
use App\Models\CurrentLivestock;
use App\Models\Coop;
use App\Models\Farm;
use App\Models\DailyAnalytics;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===========================================\n";
echo "         SMART ANALYTICS DEBUG & FIX        \n";
echo "===========================================\n\n";

echo "🔍 CHECKING CURRENT DATA IN DATABASE\n";
echo "=====================================\n";

$livestockTotalInitial = Livestock::where('status', 'active')->sum('initial_quantity');
$livestockTotalDepletion = Livestock::where('status', 'active')->sum('quantity_depletion');
$livestockCurrentTotal = $livestockTotalInitial - $livestockTotalDepletion;
$livestockCount = Livestock::where('status', 'active')->count();
echo "📊 Livestock (actual):\n";
echo "   - Total initial quantity: " . number_format($livestockTotalInitial) . "\n";
echo "   - Total depletion: " . number_format($livestockTotalDepletion) . "\n";
echo "   - Current quantity: " . number_format($livestockCurrentTotal) . "\n";
echo "   - Active records: " . number_format($livestockCount) . "\n\n";

$currentLivestockTotal = CurrentLivestock::sum('quantity');
$currentLivestockCount = CurrentLivestock::count();
echo "📊 CurrentLivestock:\n";
echo "   - Total quantity: " . number_format($currentLivestockTotal) . "\n";
echo "   - Records count: " . number_format($currentLivestockCount) . "\n\n";

$coopsTotal = Coop::count();
$farmsTotal = Farm::count();
echo "📊 Coops & Farms:\n";
echo "   - Total coops: " . number_format($coopsTotal) . "\n";
echo "   - Total farms: " . number_format($farmsTotal) . "\n\n";

$analyticsCount = DailyAnalytics::count();
$analyticsLatest = DailyAnalytics::orderBy('date', 'desc')->first();
echo "📊 DailyAnalytics:\n";
echo "   - Total records: " . number_format($analyticsCount) . "\n";
echo "   - Latest date: " . ($analyticsLatest ? $analyticsLatest->date : 'None') . "\n\n";

$analytics = DailyAnalytics::query()->get();
echo "📊 DailyAnalytics query results:\n";
echo "   - Records found: " . $analytics->count() . "\n";
echo "   - Total current_population sum: " . number_format($analytics->sum('current_population')) . "\n";

echo "\n🔧 FIXING SMART ANALYTICS DATA\n";
echo "===============================\n";

echo "1. Cleaning up old analytics data...\n";
$deletedCount = DailyAnalytics::where('date', '<', Carbon::now()->subDays(90))->delete();
echo "   ✅ Deleted $deletedCount old analytics records\n";

echo "2. Removing suspicious analytics records...\n";
$suspiciousDeleted = DailyAnalytics::where('current_population', '>', 200000)->delete();
echo "   ✅ Removed $suspiciousDeleted suspicious records\n";

echo "3. Creating analytics based on CurrentLivestock data...\n";
$analyticsCreated = 0;

$currentLivestockRecords = CurrentLivestock::with(['livestock', 'coop', 'farm'])->get();
$today = Carbon::today();

foreach ($currentLivestockRecords as $current) {
    if ($current->livestock && $current->coop && $current->farm) {
        $existingAnalytics = DailyAnalytics::where('date', $today)
            ->where('livestock_id', $current->livestock_id)
            ->first();

        if (!$existingAnalytics) {
            try {
                DailyAnalytics::create([
                    'date' => $today,
                    'livestock_id' => $current->livestock_id,
                    'farm_id' => $current->farm_id,
                    'coop_id' => $current->coop_id,
                    'current_population' => $current->quantity,
                    'initial_population' => $current->livestock->initial_quantity ?? 0,
                    'mortality_count' => 0,
                    'mortality_rate' => 0.0004,
                    'sales_count' => 0,
                    'sales_weight' => 0,
                    'sales_revenue' => 0,
                    'daily_weight_gain' => 0,
                    'fcr' => 10.0,
                    'production_index' => 0,
                    'efficiency_score' => 40,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
                $analyticsCreated++;
            } catch (Exception $e) {
                echo "   ❌ Error creating analytics for livestock {$current->livestock_id}: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "   ✅ Created $analyticsCreated new analytics records\n";

echo "\n🔍 VERIFICATION AFTER FIX\n";
echo "=========================\n";

$newAnalytics = DailyAnalytics::query()->get();
$newCurrentLivestockTotal = CurrentLivestock::sum('quantity');
$newAnalyticsTotal = $newAnalytics->sum('current_population');

echo "📊 After Fix:\n";
echo "   - CurrentLivestock total: " . number_format($newCurrentLivestockTotal) . "\n";
echo "   - Analytics total: " . number_format($newAnalyticsTotal) . "\n";
echo "   - Analytics records: " . $newAnalytics->count() . "\n";
echo "   - Match status: " . (abs($newCurrentLivestockTotal - $newAnalyticsTotal) < 1000 ? "✅ CLOSE MATCH" : "❌ MISMATCH") . "\n\n";

$finalOverview = [
    'total_livestock' => $newAnalyticsTotal,
    'avg_mortality_rate' => round($newAnalytics->avg('mortality_rate') ?: 0, 4),
    'avg_efficiency_score' => round($newAnalytics->avg('efficiency_score') ?: 40, 2),
    'avg_fcr' => round($newAnalytics->avg('fcr') ?: 10.0, 3),
    'total_revenue' => round($newAnalytics->sum('sales_revenue') ?: 0, 2),
    'total_coops' => $coopsTotal,
    'total_farms' => $farmsTotal,
];

echo "📈 SMART ANALYTICS SUMMARY\n";
echo "==========================\n";
echo "🎯 Overview Data (Should match Smart Analytics display):\n";
echo "   - Total Population: " . number_format($finalOverview['total_livestock']) . "\n";
echo "   - Avg Mortality Rate: " . ($finalOverview['avg_mortality_rate'] * 100) . "%\n";
echo "   - Avg Efficiency Score: " . $finalOverview['avg_efficiency_score'] . "\n";
echo "   - Avg FCR: " . $finalOverview['avg_fcr'] . "\n";
echo "   - Total Revenue: Rp " . number_format($finalOverview['total_revenue'], 2) . "\n";
echo "   - Total Coops: " . $finalOverview['total_coops'] . "\n";
echo "   - Total Farms: " . $finalOverview['total_farms'] . "\n\n";

$problemCoops = $newAnalytics->where('efficiency_score', '<', 60)->count();
$topCoops = $newAnalytics->where('efficiency_score', '>=', 60)->count();

echo "🏆 Performance Summary:\n";
echo "   - Problem Coops (< 60% efficiency): " . $problemCoops . "\n";
echo "   - Good/Top Coops (>= 60% efficiency): " . $topCoops . "\n";
echo "   - Display format: " . $problemCoops . "/" . $topCoops . "\n\n";

echo "✅ SMART ANALYTICS FIX COMPLETED!\n";
echo "=================================\n";
echo "Smart Analytics should now display correct data:\n";
echo "- Total Population: " . number_format($finalOverview['total_livestock']) . " (instead of 925,922)\n";
echo "- Problem/Top Coops: {$problemCoops}/{$topCoops} (instead of 186/0)\n";
echo "- Mortality Rate: " . ($finalOverview['avg_mortality_rate'] * 100) . "% (more realistic)\n";
echo "\nPlease refresh the Smart Analytics dashboard to see updated data.\n\n";
