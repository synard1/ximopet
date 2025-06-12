<?php

/**
 * Livestock Purchase Auto Reload Testing Script
 * 
 * Date: 19 December 2024
 * Purpose: Test auto reload functionality after fixes applied
 */

echo "=== LIVESTOCK PURCHASE AUTO RELOAD TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Check if notification system files exist
echo "1. CHECKING NOTIFICATION SYSTEM FILES:\n";
$files_to_check = [
    'app/DataTables/LivestockPurchaseDataTable.php',
    'resources/views/pages/transaction/livestock-purchases/_draw-scripts.js',
    'resources/views/pages/transaction/livestock-purchases/index.blade.php',
    'app/Livewire/LivestockPurchase/Create.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file - EXISTS\n";
    } else {
        echo "   ❌ $file - MISSING\n";
    }
}

// Test 2: Check for key functions in DataTable
echo "\n2. CHECKING DATATABLE NOTIFICATION FUNCTIONS:\n";
$datatable_content = file_get_contents('app/DataTables/LivestockPurchaseDataTable.php');

$functions_to_check = [
    'isLivestockPurchaseRelated' => 'Notification detection logic',
    'showStatusChangeNotification' => 'Status change feedback function',
    'refreshDataTable' => 'Table refresh function',
    'livestock-purchases-table' => 'Correct table ID'
];

foreach ($functions_to_check as $function => $description) {
    if (strpos($datatable_content, $function) !== false) {
        echo "   ✅ $function - FOUND ($description)\n";
    } else {
        echo "   ❌ $function - MISSING ($description)\n";
    }
}

// Test 3: Check for notification integration in draw-scripts
echo "\n3. CHECKING DRAW-SCRIPTS NOTIFICATION INTEGRATION:\n";
$drawscripts_content = file_get_contents('resources/views/pages/transaction/livestock-purchases/_draw-scripts.js');

$integrations_to_check = [
    'LivestockPurchaseDataTableNotifications.showStatusChangeNotification' => 'Status change notification integration',
    'updateStatusLivestockPurchase' => 'Livewire dispatch function',
    'Status Change Processing' => 'Immediate feedback message'
];

foreach ($integrations_to_check as $integration => $description) {
    if (strpos($drawscripts_content, $integration) !== false) {
        echo "   ✅ $integration - FOUND ($description)\n";
    } else {
        echo "   ❌ $integration - MISSING ($description)\n";
    }
}

// Test 4: Check for proper variable naming
echo "\n4. CHECKING VARIABLE NAMING CONSISTENCY:\n";
if (strpos($datatable_content, 'isSupplyPurchaseRelated') !== false) {
    echo "   ❌ OLD VARIABLE NAME FOUND - isSupplyPurchaseRelated (should be isLivestockPurchaseRelated)\n";
} else {
    echo "   ✅ OLD VARIABLE NAME REMOVED - isSupplyPurchaseRelated\n";
}

if (strpos($datatable_content, 'isLivestockPurchaseRelated') !== false) {
    echo "   ✅ NEW VARIABLE NAME FOUND - isLivestockPurchaseRelated\n";
} else {
    echo "   ❌ NEW VARIABLE NAME MISSING - isLivestockPurchaseRelated\n";
}

// Test 5: Check for HTTP client integration
echo "\n5. CHECKING HTTP CLIENT INTEGRATION:\n";
$create_content = file_get_contents('app/Livewire/LivestockPurchase/Create.php');

$http_features = [
    'use Illuminate\Support\Facades\Http;' => 'HTTP Client import',
    'Http::timeout(5)->post' => 'HTTP POST request',
    'getBridgeUrl()' => 'Bridge URL detection',
    'sendToProductionNotificationBridge' => 'Bridge integration function'
];

foreach ($http_features as $feature => $description) {
    if (strpos($create_content, $feature) !== false) {
        echo "   ✅ $feature - FOUND ($description)\n";
    } else {
        echo "   ❌ $feature - MISSING ($description)\n";
    }
}

// Test 6: Manual testing instructions
echo "\n6. MANUAL TESTING INSTRUCTIONS:\n";
echo "   1. Open livestock purchase page in browser\n";
echo "   2. Open browser console (F12)\n";
echo "   3. Change status of any purchase\n";
echo "   4. Check console for these logs:\n";
echo "      - '[DataTable] Status change initiated'\n";
echo "      - '[DataTable] Auto-refreshing table due to livestock purchase notification'\n";
echo "      - '[DataTable] ✅ DataTable refreshed via specific ID'\n";
echo "   5. Verify table refreshes automatically\n";
echo "   6. Verify notification appears and disappears after 3 seconds\n";

// Test 7: Debug commands
echo "\n7. DEBUG COMMANDS (Run in browser console):\n";
echo "   // Check table initialization\n";
echo "   console.log('DataTable status:', $.fn.DataTable.isDataTable('#livestock-purchases-table'));\n\n";
echo "   // Test notification system\n";
echo "   window.LivestockPurchaseDataTableNotifications.testPageNotification();\n\n";
echo "   // Check bridge connectivity\n";
echo "   window.LivestockPurchaseDataTableNotifications.getBridgeUrl();\n\n";
echo "   // Manual table refresh\n";
echo "   window.LivestockPurchaseDataTableNotifications.refreshDataTable();\n";

echo "\n=== TEST COMPLETED ===\n";
echo "If all checks pass, the auto reload should work correctly.\n";
echo "If issues persist, check browser console for error messages.\n";
