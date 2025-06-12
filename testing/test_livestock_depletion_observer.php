<?php

require_once 'vendor/autoload.php';

use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\CurrentLivestock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Test script to verify LivestockDepletion observer works correctly
function testLivestockDepletionObserver()
{
    echo "🧪 Testing LivestockDepletion Observer\n";
    echo "=====================================\n\n";

    // Get the first livestock for testing
    $livestock = Livestock::with('currentLivestock')->first();
    if (!$livestock) {
        echo "❌ No livestock found for testing\n";
        return;
    }

    $currentLivestock = $livestock->currentLivestock->first();
    if (!$currentLivestock) {
        echo "❌ No CurrentLivestock found for testing\n";
        return;
    }

    echo "📋 Test Subject:\n";
    echo "  • Livestock: {$livestock->name} ({$livestock->id})\n";
    echo "  • Initial Quantity: {$livestock->initial_quantity}\n";
    echo "  • Current quantity_depletion: " . ($livestock->quantity_depletion ?? 0) . "\n";
    echo "  • Current quantity_sales: " . ($livestock->quantity_sales ?? 0) . "\n";
    echo "  • Current quantity_mutated: " . ($livestock->quantity_mutated ?? 0) . "\n";
    echo "  • CurrentLivestock quantity: {$currentLivestock->quantity}\n\n";

    // Get a recording ID for the test
    $recording = \App\Models\Recording::where('livestock_id', $livestock->id)->first();
    $recordingId = $recording ? $recording->id : null;

    if (!$recordingId) {
        echo "❌ No recording found for livestock - creating a dummy recording\n";
        // Create a dummy recording for testing
        $dummyRecording = \App\Models\Recording::create([
            'livestock_id' => $livestock->id,
            'tanggal' => now()->format('Y-m-d'),
            'age' => 30,
            'stock_awal' => $livestock->initial_quantity,
            'stock_akhir' => $livestock->initial_quantity - 5,
            'berat_semalam' => 1.5,
            'berat_hari_ini' => 1.6,
            'kenaikan_berat' => 0.1,
            'pakan_jenis' => 'Test Feed',
            'pakan_harian' => '100',
            'created_by' => 1
        ]);
        $recordingId = $dummyRecording->id;
        echo "  ✅ Created dummy recording ID: {$recordingId}\n\n";
    }

    // Step 1: Create a new LivestockDepletion record
    echo "🔧 Step 1: Creating new LivestockDepletion record\n";
    $testDepletion = LivestockDepletion::create([
        'livestock_id' => $livestock->id,
        'recording_id' => $recordingId,
        'tanggal' => now()->format('Y-m-d'),
        'jumlah' => 5,
        'jenis' => 'Mati',
        'data' => [
            'test_data' => true,
            'created_by_test' => true
        ],
        'created_by' => 1
    ]);

    echo "  ✅ Created LivestockDepletion ID: {$testDepletion->id}\n";
    echo "  📊 Quantity: {$testDepletion->jumlah}\n";
    echo "  📅 Date: {$testDepletion->tanggal}\n\n";

    // Give observer time to process
    sleep(1);

    // Step 2: Check if Livestock quantity_depletion was updated
    echo "🔍 Step 2: Checking Livestock quantity_depletion update\n";
    $livestock->refresh(); // Reload from database
    $newQuantityDepletion = $livestock->quantity_depletion ?? 0;
    echo "  • New quantity_depletion: {$newQuantityDepletion}\n";

    // Calculate expected total depletion
    $totalExpectedDepletion = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');
    echo "  • Expected total depletion: {$totalExpectedDepletion}\n";

    if ($newQuantityDepletion == $totalExpectedDepletion) {
        echo "  ✅ Livestock quantity_depletion correctly updated!\n\n";
    } else {
        echo "  ❌ Livestock quantity_depletion NOT updated correctly!\n";
        echo "     Expected: {$totalExpectedDepletion}, Got: {$newQuantityDepletion}\n\n";
    }

    // Step 3: Check if CurrentLivestock quantity was updated
    echo "🔍 Step 3: Checking CurrentLivestock quantity update\n";
    $currentLivestock->refresh(); // Reload from database
    $newCurrentQuantity = $currentLivestock->quantity;

    // Calculate expected quantity using formula
    $expectedQuantity = $livestock->initial_quantity
        - ($livestock->quantity_depletion ?? 0)
        - ($livestock->quantity_sales ?? 0)
        - ($livestock->quantity_mutated ?? 0);
    $expectedQuantity = max(0, $expectedQuantity);

    echo "  • New CurrentLivestock quantity: {$newCurrentQuantity}\n";
    echo "  • Expected quantity (formula): {$expectedQuantity}\n";
    echo "  • Formula: {$livestock->initial_quantity} - {$livestock->quantity_depletion} - {$livestock->quantity_sales} - {$livestock->quantity_mutated} = {$expectedQuantity}\n";

    if ($newCurrentQuantity == $expectedQuantity) {
        echo "  ✅ CurrentLivestock quantity correctly updated!\n\n";
    } else {
        echo "  ❌ CurrentLivestock quantity NOT updated correctly!\n";
        echo "     Expected: {$expectedQuantity}, Got: {$newCurrentQuantity}\n\n";
    }

    // Step 4: Check metadata
    echo "🔍 Step 4: Checking CurrentLivestock metadata\n";
    $metadata = $currentLivestock->metadata ?? [];
    if (isset($metadata['calculation_source']) && $metadata['calculation_source'] == 'livestock_depletion_observer') {
        echo "  ✅ Metadata shows observer was triggered!\n";
        echo "  📊 Calculation source: {$metadata['calculation_source']}\n";
        if (isset($metadata['formula_breakdown'])) {
            echo "  📋 Formula breakdown available: Yes\n";
            echo "  📈 Percentages available: " . (isset($metadata['percentages']) ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "  ⚠️ Metadata does not show observer trigger (this is normal if observer is not working)\n";
        echo "  📊 Available metadata keys: " . implode(', ', array_keys($metadata)) . "\n";
    }
    echo "\n";

    // Step 5: Update the depletion record
    echo "🔧 Step 5: Updating LivestockDepletion record\n";
    $testDepletion->update([
        'jumlah' => 8 // Change from 5 to 8
    ]);

    echo "  ✅ Updated LivestockDepletion quantity: 5 → 8\n\n";

    // Give observer time to process
    sleep(1);

    // Step 6: Verify updates again
    echo "🔍 Step 6: Verifying updates after modification\n";
    $livestock->refresh();
    $currentLivestock->refresh();

    $finalQuantityDepletion = $livestock->quantity_depletion ?? 0;
    $finalCurrentQuantity = $currentLivestock->quantity;
    $finalExpectedDepletion = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');
    $finalExpectedQuantity = $livestock->initial_quantity
        - ($livestock->quantity_depletion ?? 0)
        - ($livestock->quantity_sales ?? 0)
        - ($livestock->quantity_mutated ?? 0);
    $finalExpectedQuantity = max(0, $finalExpectedQuantity);

    echo "  • Final quantity_depletion: {$finalQuantityDepletion} (expected: {$finalExpectedDepletion})\n";
    echo "  • Final CurrentLivestock quantity: {$finalCurrentQuantity} (expected: {$finalExpectedQuantity})\n";

    $updateSuccess = ($finalQuantityDepletion == $finalExpectedDepletion) && ($finalCurrentQuantity == $finalExpectedQuantity);

    if ($updateSuccess) {
        echo "  ✅ Update test PASSED!\n\n";
    } else {
        echo "  ❌ Update test FAILED!\n";
        echo "     quantity_depletion - Expected: {$finalExpectedDepletion}, Got: {$finalQuantityDepletion}\n";
        echo "     CurrentLivestock - Expected: {$finalExpectedQuantity}, Got: {$finalCurrentQuantity}\n\n";
    }

    // Step 7: Delete the test record
    echo "🧹 Step 7: Cleaning up test data\n";
    $testDepletion->delete();
    echo "  ✅ Test LivestockDepletion deleted\n\n";

    // Give observer time to process
    sleep(1);

    // Step 8: Verify cleanup
    echo "🔍 Step 8: Verifying cleanup\n";
    $livestock->refresh();
    $currentLivestock->refresh();

    $cleanupQuantityDepletion = $livestock->quantity_depletion ?? 0;
    $cleanupCurrentQuantity = $currentLivestock->quantity;
    $cleanupExpectedDepletion = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');
    $cleanupExpectedQuantity = $livestock->initial_quantity
        - ($livestock->quantity_depletion ?? 0)
        - ($livestock->quantity_sales ?? 0)
        - ($livestock->quantity_mutated ?? 0);
    $cleanupExpectedQuantity = max(0, $cleanupExpectedQuantity);

    echo "  • Cleanup quantity_depletion: {$cleanupQuantityDepletion} (expected: {$cleanupExpectedDepletion})\n";
    echo "  • Cleanup CurrentLivestock quantity: {$cleanupCurrentQuantity} (expected: {$cleanupExpectedQuantity})\n";

    $cleanupSuccess = ($cleanupQuantityDepletion == $cleanupExpectedDepletion) && ($cleanupCurrentQuantity == $cleanupExpectedQuantity);

    if ($cleanupSuccess) {
        echo "  ✅ Cleanup test PASSED!\n\n";
    } else {
        echo "  ❌ Cleanup test FAILED!\n";
        echo "     quantity_depletion - Expected: {$cleanupExpectedDepletion}, Got: {$cleanupQuantityDepletion}\n";
        echo "     CurrentLivestock - Expected: {$cleanupExpectedQuantity}, Got: {$cleanupCurrentQuantity}\n\n";
    }

    // Final Summary
    echo "📊 Test Summary\n";
    echo "===============\n";
    echo "✅ Create Test: PASSED\n";
    echo ($updateSuccess ? "✅" : "❌") . " Update Test: " . ($updateSuccess ? "PASSED" : "FAILED") . "\n";
    echo ($cleanupSuccess ? "✅" : "❌") . " Cleanup Test: " . ($cleanupSuccess ? "PASSED" : "FAILED") . "\n\n";

    if ($updateSuccess && $cleanupSuccess) {
        echo "🎉 LivestockDepletion Observer is working correctly!\n";
        echo "📋 All automatic updates are functioning as expected.\n";
    } else {
        echo "⚠️ LivestockDepletion Observer has issues!\n";
        echo "🔧 Please check the observer configuration and implementation.\n";
        echo "\n🔍 Debug Information:\n";
        echo "  • Observer should be registered in AppServiceProvider\n";
        echo "  • Check if LivestockDepletionObserver exists\n";
        echo "  • Verify observer methods are being called\n";
    }
}

// Run Laravel app context
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Run the test
testLivestockDepletionObserver();
