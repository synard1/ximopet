<?php

require_once 'vendor/autoload.php';

use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Livestock;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing Supply Usage Data\n";
echo "============================\n\n";

$livestockId = '9f6acd12-7f57-41da-aef8-f9d0b91a7fbf';
$date = '2025-07-03';

echo "📅 Date: {$date}\n";
echo "🐔 Livestock ID: {$livestockId}\n\n";

// Check if livestock exists
$livestock = Livestock::find($livestockId);
if (!$livestock) {
    echo "❌ Livestock not found!\n";
    exit(1);
}

echo "✅ Livestock found: {$livestock->name}\n";
echo "🏭 Farm ID: {$livestock->farm_id}\n\n";

// Check supply usage for the date
echo "🔍 Checking Supply Usage...\n";
$supplyUsage = SupplyUsage::where('livestock_id', $livestockId)
    ->whereDate('usage_date', $date)
    ->first();

if ($supplyUsage) {
    echo "✅ Supply Usage found!\n";
    echo "   - ID: {$supplyUsage->id}\n";
    echo "   - Status: {$supplyUsage->status}\n";
    echo "   - Total Quantity: {$supplyUsage->total_quantity}\n";
    echo "   - Created: {$supplyUsage->created_at}\n";
    echo "   - Updated: {$supplyUsage->updated_at}\n\n";

    // Check details
    $details = $supplyUsage->details;
    echo "📋 Supply Usage Details ({$details->count()} items):\n";

    if ($details->count() > 0) {
        foreach ($details as $detail) {
            echo "   - Supply ID: {$detail->supply_id}\n";
            echo "     Quantity: {$detail->quantity}\n";
            echo "     Supply Name: " . ($detail->supply->name ?? 'Unknown') . "\n";
            echo "     Supply Code: " . ($detail->supply->code ?? 'Unknown') . "\n";
        }
    } else {
        echo "   ❌ No details found!\n";
    }
} else {
    echo "❌ No Supply Usage found for this date!\n\n";

    // Check if there are any supply usages for this livestock
    $allSupplyUsages = SupplyUsage::where('livestock_id', $livestockId)->get();
    echo "📊 All Supply Usages for this livestock ({$allSupplyUsages->count()} total):\n";

    foreach ($allSupplyUsages as $usage) {
        echo "   - Date: {$usage->usage_date}\n";
        echo "     Status: {$usage->status}\n";
        echo "     Total Quantity: {$usage->total_quantity}\n";
        echo "     Details: {$usage->details->count()} items\n";
    }
}

echo "\n🔍 Checking Supply Usage by Status...\n";
$validStatuses = ['pending', 'in_process', 'completed', 'partially_used'];

foreach ($validStatuses as $status) {
    $count = SupplyUsage::where('livestock_id', $livestockId)
        ->whereDate('usage_date', $date)
        ->where('status', $status)
        ->count();

    echo "   - {$status}: {$count}\n";
}

echo "\n🔍 Checking Supply Usage Details directly...\n";
$details = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($livestockId, $date) {
    $query->where('livestock_id', $livestockId)
        ->whereDate('usage_date', $date);
})->get();

echo "📋 Direct Details Query: {$details->count()} items found\n";

if ($details->count() > 0) {
    foreach ($details as $detail) {
        echo "   - Supply ID: {$detail->supply_id}\n";
        echo "     Quantity: {$detail->quantity}\n";
        echo "     Supply Usage ID: {$detail->supply_usage_id}\n";
    }
}

echo "\n✅ Test completed!\n";
