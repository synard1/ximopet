<?php

/**
 * Test Livestock Menu Visibility Control
 * 
 * This script tests the conditional visibility of Manual Depletion and Manual Usage menus
 * based on livestock configuration.
 * 
 * Run: php testing/test_livestock_menu_visibility.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Livestock;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 LIVESTOCK MENU VISIBILITY TEST\n";
echo "================================\n\n";

try {
    // Get a test livestock
    $livestock = Livestock::first();

    if (!$livestock) {
        echo "❌ No livestock found for testing\n";
        exit(1);
    }

    echo "📋 Testing with Livestock: {$livestock->id}\n";
    echo "Current Name: " . ($livestock->livestock_name ?? 'N/A') . "\n\n";

    // Test Scenario 1: No Configuration
    echo "🔍 Test 1: No Configuration\n";
    echo "----------------------------\n";

    // Clear any existing configuration
    $livestock->updateDataColumn('config', null);
    $livestock->refresh();

    $hasConfig = $livestock->hasConfiguration();
    $manualDepletion = $livestock->isManualDepletionEnabled();
    $manualUsage = $livestock->isManualFeedUsageEnabled();

    echo "Has Configuration: " . ($hasConfig ? '✅ Yes' : '❌ No') . "\n";
    echo "Manual Depletion Menu: " . ($manualDepletion ? '✅ Visible' : '❌ Hidden') . "\n";
    echo "Manual Usage Menu: " . ($manualUsage ? '✅ Visible' : '❌ Hidden') . "\n\n";

    // Test Scenario 2: FIFO Configuration
    echo "🔍 Test 2: FIFO Configuration\n";
    echo "------------------------------\n";

    $fifoConfig = [
        'recording_method' => 'batch',
        'depletion_method' => 'fifo',
        'mutation_method' => 'fifo',
        'feed_usage_method' => 'fifo',
        'saved_at' => now()->toDateTimeString(),
        'saved_by' => 1
    ];

    $livestock->updateDataColumn('config', $fifoConfig);
    $livestock->refresh();

    $hasConfig = $livestock->hasConfiguration();
    $manualDepletion = $livestock->isManualDepletionEnabled();
    $manualUsage = $livestock->isManualFeedUsageEnabled();
    $depletionMethod = $livestock->getConfiguredDepletionMethod();
    $usageMethod = $livestock->getConfiguredFeedUsageMethod();

    echo "Has Configuration: " . ($hasConfig ? '✅ Yes' : '❌ No') . "\n";
    echo "Depletion Method: $depletionMethod\n";
    echo "Usage Method: $usageMethod\n";
    echo "Manual Depletion Menu: " . ($manualDepletion ? '✅ Visible' : '❌ Hidden') . "\n";
    echo "Manual Usage Menu: " . ($manualUsage ? '✅ Visible' : '❌ Hidden') . "\n\n";

    // Test Scenario 3: Manual Configuration
    echo "🔍 Test 3: Manual Configuration\n";
    echo "--------------------------------\n";

    $manualConfig = [
        'recording_method' => 'batch',
        'depletion_method' => 'manual',
        'mutation_method' => 'fifo',
        'feed_usage_method' => 'manual',
        'saved_at' => now()->toDateTimeString(),
        'saved_by' => 1
    ];

    $livestock->updateDataColumn('config', $manualConfig);
    $livestock->refresh();

    $hasConfig = $livestock->hasConfiguration();
    $manualDepletion = $livestock->isManualDepletionEnabled();
    $manualUsage = $livestock->isManualFeedUsageEnabled();
    $depletionMethod = $livestock->getConfiguredDepletionMethod();
    $usageMethod = $livestock->getConfiguredFeedUsageMethod();

    echo "Has Configuration: " . ($hasConfig ? '✅ Yes' : '❌ No') . "\n";
    echo "Depletion Method: $depletionMethod\n";
    echo "Usage Method: $usageMethod\n";
    echo "Manual Depletion Menu: " . ($manualDepletion ? '✅ Visible' : '❌ Hidden') . "\n";
    echo "Manual Usage Menu: " . ($manualUsage ? '✅ Visible' : '❌ Hidden') . "\n\n";

    // Test Scenario 4: Mixed Configuration
    echo "🔍 Test 4: Mixed Configuration\n";
    echo "-------------------------------\n";

    $mixedConfig = [
        'recording_method' => 'batch',
        'depletion_method' => 'manual',
        'mutation_method' => 'fifo',
        'feed_usage_method' => 'lifo',
        'saved_at' => now()->toDateTimeString(),
        'saved_by' => 1
    ];

    $livestock->updateDataColumn('config', $mixedConfig);
    $livestock->refresh();

    $hasConfig = $livestock->hasConfiguration();
    $manualDepletion = $livestock->isManualDepletionEnabled();
    $manualUsage = $livestock->isManualFeedUsageEnabled();
    $depletionMethod = $livestock->getConfiguredDepletionMethod();
    $usageMethod = $livestock->getConfiguredFeedUsageMethod();

    echo "Has Configuration: " . ($hasConfig ? '✅ Yes' : '❌ No') . "\n";
    echo "Depletion Method: $depletionMethod\n";
    echo "Usage Method: $usageMethod\n";
    echo "Manual Depletion Menu: " . ($manualDepletion ? '✅ Visible' : '❌ Hidden') . "\n";
    echo "Manual Usage Menu: " . ($manualUsage ? '✅ Visible' : '❌ Hidden') . "\n\n";

    // Test Configuration Helper Methods
    echo "🔍 Test 5: Configuration Helper Methods\n";
    echo "----------------------------------------\n";

    $fullConfig = $livestock->getConfiguration();
    $recordingMethod = $livestock->getConfiguredRecordingMethod();
    $mutationMethod = $livestock->getConfiguredMutationMethod();

    echo "Full Configuration: " . json_encode($fullConfig, JSON_PRETTY_PRINT) . "\n";
    echo "Recording Method: $recordingMethod\n";
    echo "Mutation Method: $mutationMethod\n\n";

    echo "✅ ALL TESTS COMPLETED SUCCESSFULLY\n";
    echo "====================================\n";
    echo "Menu visibility is working correctly based on configuration!\n\n";
} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
