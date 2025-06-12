<?php

/**
 * UNIVERSAL NOTIFICATION SYSTEM TEST
 * Testing script untuk memverifikasi refactor browser-notification.js
 * 
 * @author AI Assistant
 * @date 2024-12-19
 * @updated 2024-12-19 - Testing universal system
 */

echo "🧪 UNIVERSAL NOTIFICATION SYSTEM TEST\n";
echo "=====================================\n\n";

// Test 1: Verify universal notification script exists
echo "📋 Test 1: Universal Notification Script\n";
echo "----------------------------------------\n";

$scriptPath = __DIR__ . '/../public/assets/js/browser-notification.js';
$scriptExists = file_exists($scriptPath);

$tests = [
    'Script file exists' => $scriptExists ? '✅ PASS' : '❌ FAIL',
];

if ($scriptExists) {
    $scriptContent = file_get_contents($scriptPath);

    // Check for universal features
    $universalFeatures = [
        'UNIVERSAL BROWSER NOTIFICATION HANDLER' => strpos($scriptContent, 'UNIVERSAL BROWSER NOTIFICATION HANDLER') !== false,
        'tableConfig object' => strpos($scriptContent, 'tableConfig:') !== false,
        'autoDetectTables function' => strpos($scriptContent, 'autoDetectTables:') !== false,
        'attemptUniversalAutoRefresh function' => strpos($scriptContent, 'attemptUniversalAutoRefresh:') !== false,
        'getTableType function' => strpos($scriptContent, 'getTableType:') !== false,
        'isRefreshableNotification function' => strpos($scriptContent, 'isRefreshableNotification:') !== false,
    ];

    foreach ($universalFeatures as $feature => $exists) {
        $tests[$feature] = $exists ? '✅ PASS' : '❌ FAIL';
    }

    // Check for known table IDs
    $knownTables = [
        'supplyPurchasing-table' => strpos($scriptContent, 'supplyPurchasing-table') !== false,
        'feedPurchasing-table' => strpos($scriptContent, 'feedPurchasing-table') !== false,
        'livestock-purchases-table' => strpos($scriptContent, 'livestock-purchases-table') !== false,
    ];

    foreach ($knownTables as $table => $exists) {
        $tests["Known table: $table"] = $exists ? '✅ PASS' : '❌ FAIL';
    }

    // Check for refresh keywords
    $refreshKeywords = [
        'purchase' => strpos($scriptContent, '"purchase"') !== false,
        'supply' => strpos($scriptContent, '"supply"') !== false,
        'feed' => strpos($scriptContent, '"feed"') !== false,
        'livestock' => strpos($scriptContent, '"livestock"') !== false,
        'status' => strpos($scriptContent, '"status"') !== false,
    ];

    foreach ($refreshKeywords as $keyword => $exists) {
        $tests["Refresh keyword: $keyword"] = $exists ? '✅ PASS' : '❌ FAIL';
    }
}

foreach ($tests as $test => $result) {
    echo "   $result $test\n";
}

echo "\n";

// Test 2: Verify DataTable files exist
echo "📋 Test 2: DataTable Files\n";
echo "---------------------------\n";

$dataTableFiles = [
    'SupplyPurchaseDataTable.php' => __DIR__ . '/../app/DataTables/SupplyPurchaseDataTable.php',
    'FeedPurchaseDataTable.php' => __DIR__ . '/../app/DataTables/FeedPurchaseDataTable.php',
    'LivestockPurchaseDataTable.php' => __DIR__ . '/../app/DataTables/LivestockPurchaseDataTable.php',
];

$dataTableTests = [];
foreach ($dataTableFiles as $name => $path) {
    $exists = file_exists($path);
    $dataTableTests[$name] = $exists ? '✅ PASS' : '❌ FAIL';

    if ($exists) {
        $content = file_get_contents($path);
        $hasTableId = strpos($content, 'setTableId') !== false;
        $dataTableTests["$name has table ID"] = $hasTableId ? '✅ PASS' : '❌ FAIL';
    }
}

foreach ($dataTableTests as $test => $result) {
    echo "   $result $test\n";
}

echo "\n";

// Test 3: Check for global helper functions
echo "📋 Test 3: Global Helper Functions\n";
echo "-----------------------------------\n";

if ($scriptExists) {
    $helperFunctions = [
        'getNotificationStatus' => strpos($scriptContent, 'window.getNotificationStatus') !== false,
        'testUniversalNotification' => strpos($scriptContent, 'window.testUniversalNotification') !== false,
        'clearAllNotifications' => strpos($scriptContent, 'window.clearAllNotifications') !== false,
        'refreshAllTables' => strpos($scriptContent, 'window.refreshAllTables') !== false,
    ];

    foreach ($helperFunctions as $func => $exists) {
        echo "   " . ($exists ? '✅ PASS' : '❌ FAIL') . " $func function\n";
    }
}

echo "\n";

// Test 4: Generate JavaScript test commands
echo "📋 Test 4: JavaScript Test Commands\n";
echo "------------------------------------\n";

echo "Copy and paste these commands in browser console to test:\n\n";

echo "// Test 1: Check system status\n";
echo "window.NotificationSystem.showStatus();\n\n";

echo "// Test 2: Test universal notification\n";
echo "window.testUniversalNotification();\n\n";

echo "// Test 3: Check detected tables\n";
echo "console.log('Detected tables:', window.NotificationSystem.tableConfig.detectedTables);\n\n";

echo "// Test 4: Force refresh all tables\n";
echo "window.refreshAllTables();\n\n";

echo "// Test 5: Check table detection methods\n";
echo "console.log('Known tables:', window.NotificationSystem.tableConfig.knownTables);\n";
echo "console.log('Refresh keywords:', window.NotificationSystem.tableConfig.refreshKeywords);\n\n";

echo "// Test 6: Manual table detection\n";
echo "window.NotificationSystem.autoDetectTables();\n";
echo "console.log('Re-detected tables:', window.NotificationSystem.tableConfig.detectedTables);\n\n";

// Test 5: Summary
echo "📋 Test Summary\n";
echo "---------------\n";

$totalTests = count($tests) + count($dataTableTests) + 4; // +4 for helper functions
$passedTests = 0;

foreach (array_merge($tests, $dataTableTests) as $result) {
    if (strpos($result, '✅') !== false) {
        $passedTests++;
    }
}

if ($scriptExists) {
    foreach ($helperFunctions as $exists) {
        if ($exists) $passedTests++;
    }
}

$percentage = round(($passedTests / $totalTests) * 100, 1);

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: $percentage%\n\n";

if ($percentage >= 90) {
    echo "🎉 EXCELLENT! Universal notification system is ready.\n";
} elseif ($percentage >= 75) {
    echo "✅ GOOD! Minor issues need attention.\n";
} elseif ($percentage >= 50) {
    echo "⚠️ WARNING! Several issues need fixing.\n";
} else {
    echo "❌ CRITICAL! Major issues need immediate attention.\n";
}

echo "\n";

// Test 6: Browser compatibility check
echo "📋 Test 6: Browser Compatibility Features\n";
echo "------------------------------------------\n";

if ($scriptExists) {
    $compatibilityFeatures = [
        'DOM ready check' => strpos($scriptContent, 'DOMContentLoaded') !== false,
        'jQuery fallback' => strpos($scriptContent, 'typeof $ !== "undefined"') !== false,
        'DataTable check' => strpos($scriptContent, '$.fn.DataTable') !== false,
        'Livewire fallback' => strpos($scriptContent, 'typeof Livewire !== "undefined"') !== false,
        'SweetAlert check' => strpos($scriptContent, 'typeof Swal !== "undefined"') !== false,
        'Error handling' => strpos($scriptContent, 'try {') !== false && strpos($scriptContent, 'catch') !== false,
    ];

    foreach ($compatibilityFeatures as $feature => $exists) {
        echo "   " . ($exists ? '✅ PASS' : '❌ FAIL') . " $feature\n";
    }
}

echo "\n🔚 Test completed at " . date('Y-m-d H:i:s') . "\n";
 