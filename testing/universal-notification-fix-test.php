<?php

/**
 * UNIVERSAL NOTIFICATION SYSTEM FIX TEST
 * Testing script untuk memverifikasi perbaikan timing issue
 * 
 * @author AI Assistant
 * @date 2024-12-19
 * @updated 2024-12-19 - Fix timing issue
 */

echo "🔧 UNIVERSAL NOTIFICATION SYSTEM FIX TEST\n";
echo "==========================================\n\n";

// Test 1: Verify enhanced detection features
echo "📋 Test 1: Enhanced Detection Features\n";
echo "--------------------------------------\n";

$scriptPath = __DIR__ . '/../public/assets/js/browser-notification.js';
$scriptExists = file_exists($scriptPath);

$tests = [
    'Script file exists' => $scriptExists ? '✅ PASS' : '❌ FAIL',
];

if ($scriptExists) {
    $scriptContent = file_get_contents($scriptPath);

    // Check for timing fix features
    $timingFeatures = [
        'setupDelayedDetection function' => strpos($scriptContent, 'setupDelayedDetection:') !== false,
        'attemptFallbackRefresh function' => strpos($scriptContent, 'attemptFallbackRefresh:') !== false,
        'Delayed detection (1s)' => strpos($scriptContent, 'delayed table detection (1s)') !== false,
        'Delayed detection (3s)' => strpos($scriptContent, 'delayed table detection (3s)') !== false,
        'Delayed detection (5s)' => strpos($scriptContent, 'delayed table detection (5s)') !== false,
        'Immediate re-detection' => strpos($scriptContent, 'immediate re-detection') !== false,
        'Enhanced debugging' => strpos($scriptContent, 'Environment check') !== false,
    ];

    foreach ($timingFeatures as $feature => $exists) {
        $tests[$feature] = $exists ? '✅ PASS' : '❌ FAIL';
    }

    // Check for fallback methods
    $fallbackMethods = [
        'Known table IDs fallback' => strpos($scriptContent, 'Try known table IDs directly') !== false,
        'LaravelDataTables fallback' => strpos($scriptContent, 'Try LaravelDataTables registry') !== false,
        'DOM scanning fallback' => strpos($scriptContent, 'Try any DataTable on the page') !== false,
        'Livewire fallback' => strpos($scriptContent, 'Final fallback: Refreshing Livewire') !== false,
    ];

    foreach ($fallbackMethods as $method => $exists) {
        $tests[$method] = $exists ? '✅ PASS' : '❌ FAIL';
    }
}

foreach ($tests as $test => $result) {
    echo "   $result $test\n";
}

echo "\n";

// Test 2: Generate JavaScript debugging commands
echo "📋 Test 2: JavaScript Debugging Commands\n";
echo "-----------------------------------------\n";

echo "Copy and paste these commands in browser console to debug:\n\n";

echo "// Test 1: Check environment and detection\n";
echo "console.log('Environment check:', {\n";
echo "    jQueryAvailable: typeof $ !== 'undefined',\n";
echo "    dataTableAvailable: typeof $ !== 'undefined' && $.fn.DataTable,\n";
echo "    laravelDataTablesAvailable: !!window.LaravelDataTables,\n";
echo "    laravelDataTablesKeys: window.LaravelDataTables ? Object.keys(window.LaravelDataTables) : []\n";
echo "});\n\n";

echo "// Test 2: Force immediate table detection\n";
echo "window.NotificationSystem.autoDetectTables();\n";
echo "console.log('Detected tables:', window.NotificationSystem.tableConfig.detectedTables);\n\n";

echo "// Test 3: Test fallback refresh methods\n";
echo "window.NotificationSystem.attemptFallbackRefresh();\n";
echo "console.log('Detected tables:', window.NotificationSystem.tableConfig.detectedTables);\n\n";

echo "// Test 4: Check specific table existence\n";
echo "console.log('Supply table exists:', document.getElementById('supplyPurchasing-table'));\n";
echo "console.log('Supply table is DataTable:', $.fn.DataTable.isDataTable('#supplyPurchasing-table'));\n\n";

echo "// Test 5: Manual refresh test\n";
echo "if ($.fn.DataTable.isDataTable('#supplyPurchasing-table')) {\n";
echo "    $('#supplyPurchasing-table').DataTable().ajax.reload(null, false);\n";
echo "    console.log('✅ Manual refresh successful');\n";
echo "} else {\n";
echo "    console.log('❌ Table not found or not DataTable');\n";
echo "}\n\n";

echo "// Test 6: Check LaravelDataTables registry\n";
echo "if (window.LaravelDataTables) {\n";
echo "    console.log('LaravelDataTables registry:', Object.keys(window.LaravelDataTables));\n";
echo "    Object.keys(window.LaravelDataTables).forEach(tableId => {\n";
echo "        console.log(`Table ${tableId}:`, {\n";
echo "            exists: !!document.getElementById(tableId),\n";
echo "            isDataTable: $.fn.DataTable.isDataTable('#' + tableId)\n";
echo "        });\n";
echo "    });\n";
echo "} else {\n";
echo "    console.log('❌ LaravelDataTables registry not available');\n";
echo "}\n\n";

// Test 3: Timing simulation
echo "📋 Test 3: Timing Simulation Commands\n";
echo "--------------------------------------\n";

echo "// Simulate delayed table initialization (run in console)\n";
echo "setTimeout(() => {\n";
echo "    console.log('=== 1 Second Delay Test ===');\n";
echo "    window.NotificationSystem.autoDetectTables();\n";
echo "}, 1000);\n\n";

echo "setTimeout(() => {\n";
echo "    console.log('=== 3 Second Delay Test ===');\n";
echo "    window.NotificationSystem.autoDetectTables();\n";
echo "}, 3000);\n\n";

echo "setTimeout(() => {\n";
echo "    console.log('=== 5 Second Delay Test ===');\n";
echo "    window.NotificationSystem.autoDetectTables();\n";
echo "    console.log('Final detected tables:', window.NotificationSystem.tableConfig.detectedTables);\n";
echo "}, 5000);\n\n";

// Test 4: Expected behavior verification
echo "📋 Test 4: Expected Behavior Verification\n";
echo "------------------------------------------\n";

echo "Expected behavior after fix:\n";
echo "1. ✅ System should detect tables even if they load after initial page load\n";
echo "2. ✅ Fallback refresh methods should work when detection fails\n";
echo "3. ✅ Enhanced debugging should show detailed detection process\n";
echo "4. ✅ Multiple detection attempts should handle timing issues\n";
echo "5. ✅ Manual refresh should work even when auto-detection fails\n\n";

// Test 5: Summary
echo "📋 Test Summary\n";
echo "---------------\n";

$totalTests = count($tests);
$passedTests = 0;

foreach ($tests as $result) {
    if (strpos($result, '✅') !== false) {
        $passedTests++;
    }
}

$percentage = round(($passedTests / $totalTests) * 100, 1);

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: $percentage%\n\n";

if ($percentage >= 90) {
    echo "🎉 EXCELLENT! Timing fix is properly implemented.\n";
} elseif ($percentage >= 75) {
    echo "✅ GOOD! Minor issues may need attention.\n";
} elseif ($percentage >= 50) {
    echo "⚠️ WARNING! Several issues need fixing.\n";
} else {
    echo "❌ CRITICAL! Major issues need immediate attention.\n";
}

echo "\n";

// Test 6: Troubleshooting guide
echo "📋 Test 6: Troubleshooting Guide\n";
echo "---------------------------------\n";

echo "If tables still not detected:\n\n";

echo "1. **Check timing**: Tables might load very late\n";
echo "   - Increase delay in setupDelayedDetection\n";
echo "   - Add more detection attempts\n\n";

echo "2. **Check DataTable initialization**: \n";
echo "   - Verify DataTable is properly initialized\n";
echo "   - Check for JavaScript errors in console\n\n";

echo "3. **Check LaravelDataTables registry**:\n";
echo "   - Verify window.LaravelDataTables exists\n";
echo "   - Check if table IDs match registry\n\n";

echo "4. **Manual fallback**:\n";
echo "   - Use window.refreshAllTables() manually\n";
echo "   - Check if specific table refresh works\n\n";

echo "5. **Debug commands**:\n";
echo "   - window.NotificationSystem.showStatus()\n";
echo "   - window.NotificationSystem.autoDetectTables()\n";
echo "   - window.NotificationSystem.attemptFallbackRefresh()\n\n";

echo "🔚 Fix test completed at " . date('Y-m-d H:i:s') . "\n";
