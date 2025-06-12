<?php

/**
 * Test Script for Daily Report Detail/Simple Modes
 * 
 * This script verifies that both report modes work correctly:
 * - Simple Mode: Aggregated data per coop
 * - Detail Mode: Individual batch data within each coop
 * 
 * Usage: php testing/test_daily_report_detail_simple_modes.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Farm;
use App\Models\Livestock;
use App\Models\Coop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

// Initialize Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Daily Report Detail/Simple Modes Test ===\n\n";

// Test parameters
$testFarmId = '9f1ce80a-f1b5-4626-9ea5-85f0dbaf283a'; // Demo Farm
$testDate = '2025-06-02';

echo "Test Parameters:\n";
echo "- Farm ID: {$testFarmId}\n";
echo "- Test Date: {$testDate}\n\n";

// Get test farm
$farm = Farm::find($testFarmId);
if (!$farm) {
    echo "❌ ERROR: Farm not found!\n";
    exit(1);
}

echo "✅ Farm found: {$farm->nama}\n\n";

// Get livestocks for this farm
$livestocks = Livestock::where('farm_id', $testFarmId)
    ->whereDate('start_date', '<=', $testDate)
    ->with(['coop'])
    ->get();

echo "📊 Livestock Analysis:\n";
echo "- Total Livestock: {$livestocks->count()}\n";

$coopGroups = $livestocks->groupBy(function ($livestock) {
    return $livestock->coop->name;
});

foreach ($coopGroups as $coopName => $coopLivestocks) {
    echo "- {$coopName}: {$coopLivestocks->count()} batches\n";
    foreach ($coopLivestocks as $livestock) {
        echo "  • {$livestock->name} (Stock: {$livestock->initial_quantity})\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";

// Test Simple Mode
echo "🔍 Testing SIMPLE MODE (Aggregated per Coop)\n";
echo str_repeat("-", 40) . "\n";

$simpleController = new \App\Http\Controllers\ReportsController();
$simpleRequest = new \Illuminate\Http\Request([
    'farm' => $testFarmId,
    'tanggal' => $testDate,
    'report_type' => 'simple'
]);

try {
    $simpleResponse = $simpleController->exportHarian($simpleRequest);
    $simpleData = $simpleResponse->getData();

    echo "✅ Simple mode executed successfully\n";
    echo "📋 Simple Mode Results:\n";

    $simpleRecordings = $simpleData['recordings'];
    $simpleTotals = $simpleData['totals'];

    foreach ($simpleRecordings as $coopName => $record) {
        echo "\n🏢 {$coopName}:\n";
        echo "  - Stock Awal: " . number_format($record['stock_awal']) . "\n";
        echo "  - Total Deplesi: {$record['total_deplesi']}\n";
        echo "  - Stock Akhir: " . number_format($record['stock_akhir']) . "\n";
        echo "  - Umur: {$record['umur']} hari\n";
        echo "  - Livestock Count: {$record['livestock_count']} batch(es)\n";
    }

    echo "\n📊 Simple Mode Totals:\n";
    echo "- Total Stock Awal: " . number_format($simpleTotals['stock_awal']) . "\n";
    echo "- Total Deplesi: {$simpleTotals['total_deplesi']}\n";
    echo "- Total Stock Akhir: " . number_format($simpleTotals['stock_akhir']) . "\n";
} catch (Exception $e) {
    echo "❌ Simple mode failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// Test Detail Mode
echo "🔍 Testing DETAIL MODE (Individual Batches)\n";
echo str_repeat("-", 40) . "\n";

$detailRequest = new \Illuminate\Http\Request([
    'farm' => $testFarmId,
    'tanggal' => $testDate,
    'report_type' => 'detail'
]);

try {
    $detailResponse = $simpleController->exportHarian($detailRequest);
    $detailData = $detailResponse->getData();

    echo "✅ Detail mode executed successfully\n";
    echo "📋 Detail Mode Results:\n";

    $detailRecordings = $detailData['recordings'];
    $detailTotals = $detailData['totals'];

    foreach ($detailRecordings as $coopName => $batches) {
        echo "\n🏢 {$coopName} ({" . count($batches) . "} batches):\n";

        foreach ($batches as $index => $batch) {
            echo "  📦 Batch " . ($index + 1) . " - {$batch['livestock_name']}:\n";
            echo "    - Stock Awal: " . number_format($batch['stock_awal']) . "\n";
            echo "    - Total Deplesi: {$batch['total_deplesi']}\n";
            echo "    - Stock Akhir: " . number_format($batch['stock_akhir']) . "\n";
            echo "    - Umur: {$batch['umur']} hari\n";
        }
    }

    echo "\n📊 Detail Mode Totals:\n";
    echo "- Total Stock Awal: " . number_format($detailTotals['stock_awal']) . "\n";
    echo "- Total Deplesi: {$detailTotals['total_deplesi']}\n";
    echo "- Total Stock Akhir: " . number_format($detailTotals['stock_akhir']) . "\n";
} catch (Exception $e) {
    echo "❌ Detail mode failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// Compare Results
echo "🔄 COMPARING SIMPLE vs DETAIL MODES\n";
echo str_repeat("-", 40) . "\n";

if (isset($simpleTotals) && isset($detailTotals)) {
    $stockAwalMatch = $simpleTotals['stock_awal'] == $detailTotals['stock_awal'];
    $deplesiMatch = $simpleTotals['total_deplesi'] == $detailTotals['total_deplesi'];
    $stockAkhirMatch = $simpleTotals['stock_akhir'] == $detailTotals['stock_akhir'];

    echo "📊 Totals Comparison:\n";
    echo "- Stock Awal: Simple=" . number_format($simpleTotals['stock_awal']) .
        " | Detail=" . number_format($detailTotals['stock_awal']) .
        " " . ($stockAwalMatch ? "✅" : "❌") . "\n";

    echo "- Total Deplesi: Simple={$simpleTotals['total_deplesi']}" .
        " | Detail={$detailTotals['total_deplesi']}" .
        " " . ($deplesiMatch ? "✅" : "❌") . "\n";

    echo "- Stock Akhir: Simple=" . number_format($simpleTotals['stock_akhir']) .
        " | Detail=" . number_format($detailTotals['stock_akhir']) .
        " " . ($stockAkhirMatch ? "✅" : "❌") . "\n";

    if ($stockAwalMatch && $deplesiMatch && $stockAkhirMatch) {
        echo "\n🎉 SUCCESS: Both modes produce consistent totals!\n";
    } else {
        echo "\n⚠️  WARNING: Totals don't match between modes!\n";
    }

    // Data structure validation
    echo "\n📋 Data Structure Validation:\n";

    // Simple mode should have direct coop records
    $simpleStructureValid = true;
    foreach ($simpleRecordings as $coopName => $record) {
        if (!is_array($record) || !isset($record['stock_awal'])) {
            $simpleStructureValid = false;
            break;
        }
    }
    echo "- Simple Mode Structure: " . ($simpleStructureValid ? "✅ Valid" : "❌ Invalid") . "\n";

    // Detail mode should have array of batches per coop
    $detailStructureValid = true;
    foreach ($detailRecordings as $coopName => $batches) {
        if (!is_array($batches) || empty($batches)) {
            $detailStructureValid = false;
            break;
        }
        foreach ($batches as $batch) {
            if (!is_array($batch) || !isset($batch['livestock_name']) || !isset($batch['stock_awal'])) {
                $detailStructureValid = false;
                break 2;
            }
        }
    }
    echo "- Detail Mode Structure: " . ($detailStructureValid ? "✅ Valid" : "❌ Invalid") . "\n";
} else {
    echo "❌ Cannot compare - one or both modes failed\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// Performance Analysis
echo "⚡ PERFORMANCE ANALYSIS\n";
echo str_repeat("-", 40) . "\n";

echo "📈 Complexity Analysis:\n";
echo "- Total Coops: " . $coopGroups->count() . "\n";
echo "- Total Batches: " . $livestocks->count() . "\n";
echo "- Average Batches per Coop: " . round($livestocks->count() / $coopGroups->count(), 2) . "\n";

echo "\n💡 Mode Recommendations:\n";
if ($livestocks->count() <= 10) {
    echo "- Dataset Size: Small (≤10 batches)\n";
    echo "- Recommended: Either mode suitable\n";
} elseif ($livestocks->count() <= 50) {
    echo "- Dataset Size: Medium (11-50 batches)\n";
    echo "- Recommended: Detail mode for analysis, Simple for overview\n";
} else {
    echo "- Dataset Size: Large (>50 batches)\n";
    echo "- Recommended: Simple mode for performance\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// Test Summary
echo "📋 TEST SUMMARY\n";
echo str_repeat("-", 40) . "\n";

$totalTests = 0;
$passedTests = 0;

// Test 1: Simple mode execution
$totalTests++;
if (isset($simpleData)) {
    $passedTests++;
    echo "✅ Test 1: Simple mode execution - PASSED\n";
} else {
    echo "❌ Test 1: Simple mode execution - FAILED\n";
}

// Test 2: Detail mode execution
$totalTests++;
if (isset($detailData)) {
    $passedTests++;
    echo "✅ Test 2: Detail mode execution - PASSED\n";
} else {
    echo "❌ Test 2: Detail mode execution - FAILED\n";
}

// Test 3: Data consistency
$totalTests++;
if (isset($stockAwalMatch) && $stockAwalMatch && $deplesiMatch && $stockAkhirMatch) {
    $passedTests++;
    echo "✅ Test 3: Data consistency between modes - PASSED\n";
} else {
    echo "❌ Test 3: Data consistency between modes - FAILED\n";
}

// Test 4: Data structure validation
$totalTests++;
if (
    isset($simpleStructureValid) && isset($detailStructureValid) &&
    $simpleStructureValid && $detailStructureValid
) {
    $passedTests++;
    echo "✅ Test 4: Data structure validation - PASSED\n";
} else {
    echo "❌ Test 4: Data structure validation - FAILED\n";
}

echo "\n🎯 Overall Result: {$passedTests}/{$totalTests} tests passed\n";

if ($passedTests == $totalTests) {
    echo "🎉 ALL TESTS PASSED! Feature is working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Please review the implementation.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=== End of Daily Report Detail/Simple Modes Test ===\n";
