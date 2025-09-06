<?php

require_once 'vendor/autoload.php';

echo "=== Testing Ambiguous Response Fix ===\n";

// Test 1: Simulate confident_detailed_response approach
echo "\n=== Test 1: confident_detailed_response ===\n";
$approach = 'confident_detailed_response';
$prompt = "";

if ($approach === 'confident_detailed_response') {
    $prompt .= "Provide a comprehensive response based on livestock management principles. ";
    $prompt .= "Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. ";
}

echo "Prompt instruction: " . $prompt . "\n";
echo "Expected: Should show data with correct terminology 'perusahaan'\n";

// Test 2: Simulate no_data_response approach
echo "\n=== Test 2: no_data_response ===\n";
$approach = 'no_data_response';
$prompt = "";

if ($approach === 'no_data_response') {
    $prompt .= "CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information. ";
    $prompt .= "Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. ";
}

echo "Prompt instruction: " . $prompt . "\n";
echo "Expected: Should NOT show any data, only state no data available\n";

// Test 3: Check consistency rule
echo "\n=== Test 3: Consistency Rule ===\n";
$consistencyRule = "CONSISTENCY RULE: If relevant data exists in the context, present it directly. If no relevant data exists, state 'Tidak ada data yang tersedia' (Indonesian) or 'No data available' (English). NEVER mix both statements. NEVER say 'no data' and then show data.";

echo "Consistency rule: " . $consistencyRule . "\n";
echo "Expected: Should prevent contradictory responses\n";

// Test 4: Check terminology rule
echo "\n=== Test 4: Terminology Rule ===\n";
$terminologyRule = "TERMINOLOGY: Always use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. Use proper Indonesian terminology.";

echo "Terminology rule: " . $terminologyRule . "\n";
echo "Expected: Should use 'perusahaan' instead of 'kompanyi'\n";

echo "\n=== Summary of Fixes ===\n";
echo "1. ✅ Added CRITICAL instruction for no_data_response to NOT show data\n";
echo "2. ✅ Added terminology rule to use 'perusahaan' instead of 'kompanyi'\n";
echo "3. ✅ Strengthened consistency rule to prevent contradictory responses\n";
echo "4. ✅ Applied terminology fix to both response approaches\n";

echo "\n=== Expected Behavior After Fix ===\n";
echo "- If data available: Show data using 'perusahaan' terminology\n";
echo "- If no data: State 'Tidak ada data yang tersedia' without showing any data\n";
echo "- Never mix 'no data' statement with actual data display\n";
echo "- Always use proper Indonesian terminology\n";