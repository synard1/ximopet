<?php

/**
 * Test Script untuk SupplyStatusHistory System
 * This script tests the new SupplyStatusHistory model and HasSupplyStatusHistory trait
 * Run: php testing/test_supply_status_history.php
 * 
 * Date: 2025-06-11 16:59:00
 * Author: System
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyStatusHistory;
use App\Models\Partner;
use App\Models\Farm;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Clear cache to ensure fresh data
Artisan::call('config:clear');

echo "=== Supply Status History System Test ===\n";
echo "Test started at: " . now()->format('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Test creating a SupplyPurchaseBatch with initial status
    echo "1. Testing SupplyPurchaseBatch creation with initial status...\n";

    $farm = Farm::first();
    $supplier = Partner::where('type', 'supplier')->first();

    if (!$farm || !$supplier) {
        echo "ERROR: Farm or Supplier not found. Please create them first.\n";
        exit(1);
    }

    $batch = SupplyPurchaseBatch::create([
        'invoice_number' => 'TEST-SUP-' . time(),
        'date' => now(),
        'farm_id' => $farm->id,
        'supplier_id' => $supplier->id,
        'status' => SupplyPurchaseBatch::STATUS_DRAFT,
        'created_by' => 1,
        'updated_by' => 1
    ]);

    echo "✅ Created SupplyPurchaseBatch: {$batch->id}\n";
    echo "   Initial status: {$batch->status}\n";

    // Check if initial status history was created automatically
    $initialHistoryCount = $batch->supplyStatusHistories()->count();
    echo "   Initial history count: {$initialHistoryCount}\n";

    if ($initialHistoryCount > 0) {
        $latestHistory = $batch->getLatestSupplyStatusHistory();
        echo "   Latest history: {$latestHistory->status_from} → {$latestHistory->status_to}\n";
        echo "   Notes: {$latestHistory->notes}\n";
    }

    echo "\n";

    // 2. Test status update using new system
    echo "2. Testing status update using new SupplyStatusHistory system...\n";

    $newStatus = SupplyPurchaseBatch::STATUS_PENDING;
    $notes = 'Updated to pending status via testing script';

    $batch->updateSupplyStatus($newStatus, $notes, [
        'test_mode' => true,
        'testing_script' => 'test_supply_status_history.php'
    ]);

    $newHistoryCount = $batch->supplyStatusHistories()->count();
    echo "✅ Status updated from DRAFT to PENDING\n";
    echo "   New history count: {$newHistoryCount}\n";

    // 3. Test another status change that requires notes
    echo "\n3. Testing status change that requires notes...\n";

    try {
        $batch->updateSupplyStatus(SupplyPurchaseBatch::STATUS_CANCELLED);
        echo "❌ Should have failed - notes required for CANCELLED status\n";
    } catch (\Illuminate\Validation\ValidationException $e) {
        echo "✅ Correctly rejected status change without notes\n";
        echo "   Error: " . $e->getMessage() . "\n";
    }

    // Now with notes
    $batch->updateSupplyStatus(
        SupplyPurchaseBatch::STATUS_CANCELLED,
        'Cancelled for testing purposes',
        ['reason' => 'testing']
    );
    echo "✅ Successfully updated to CANCELLED with notes\n";

    echo "\n";

    // 4. Test status history queries
    echo "4. Testing status history queries...\n";

    $allHistories = $batch->getSupplyStatusTimeline();
    echo "   Total status changes: " . $allHistories->count() . "\n";

    foreach ($allHistories as $history) {
        echo "   - {$history->created_at}: {$history->status_transition}\n";
        if ($history->notes) {
            echo "     Notes: {$history->notes}\n";
        }
    }

    echo "\n";

    // 5. Test scope queries
    echo "5. Testing SupplyStatusHistory scope queries...\n";

    $batchHistoriesCount = SupplyStatusHistory::forModel(SupplyPurchaseBatch::class)->count();
    echo "   Total histories for SupplyPurchaseBatch: {$batchHistoriesCount}\n";

    $recentChangesCount = SupplyStatusHistory::where('created_at', '>=', now()->subDays(7))->count();
    echo "   Recent changes (last 7 days): {$recentChangesCount}\n";

    $transitionStats = SupplyStatusHistory::selectRaw('status_from, status_to, COUNT(*) as count')
        ->whereNotNull('status_from')
        ->groupBy('status_from', 'status_to')
        ->get();

    echo "   Status transition statistics:\n";
    foreach ($transitionStats as $stat) {
        echo "     {$stat->status_from} → {$stat->status_to}: {$stat->count} times\n";
    }

    echo "\n";

    // 6. Test backward compatibility
    echo "6. Testing backward compatibility with old updateStatus method...\n";

    $batch->updateStatus(
        SupplyPurchaseBatch::STATUS_PENDING,
        'Back to pending using old method'
    );
    echo "✅ Old updateStatus method still works\n";

    echo "\n";

    // 7. Test available statuses
    echo "7. Testing available statuses method...\n";

    $availableStatuses = $batch->getAvailableSupplyStatuses();
    echo "   Available statuses: " . implode(', ', $availableStatuses) . "\n";

    echo "\n";

    // 8. Cleanup (optional - comment out if you want to keep test data)
    echo "8. Cleaning up test data...\n";

    // Delete all histories for this batch
    $batch->supplyStatusHistories()->forceDelete();

    // Delete the batch
    $batch->forceDelete();

    echo "✅ Test data cleaned up\n";

    echo "\n=== All tests completed successfully! ===\n";
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";

    // Cleanup on error
    if (isset($batch) && $batch->exists) {
        $batch->supplyStatusHistories()->forceDelete();
        $batch->forceDelete();
        echo "Test data cleaned up after error.\n";
    }
}

echo "\nTest completed at: " . now()->format('Y-m-d H:i:s') . "\n";
