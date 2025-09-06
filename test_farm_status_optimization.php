<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\AiDatabaseServiceRefactored;
use App\Services\AiPlanningService;
use App\Services\DataAccessService;
use Illuminate\Support\Facades\Auth;

// Login user for testing
$user = User::where('email', 'synard1@gmail.com')->first();
if (!$user) {
    throw new Exception('User not found');
}
Auth::login($user);
echo "✅ User logged in: {$user->name}\n\n";

echo "=== TESTING FARM STATUS OPTIMIZATION ===\n\n";

// Test scenarios for different farm status queries
$testQueries = [
    'berapa jumlah farm aktif',
    'berapa farm tidak aktif',
    'berapa total farm',
    'jumlah semua farm',
    'tampilkan semua farm',
    'daftar farm aktif',
    'farm yang tidak aktif',
    'total farm dari semua status'
];

$aiDatabaseService = app(\App\Services\AiDatabaseServiceRefactored::class);
$aiPlanningService = app(\App\Services\AiPlanningService::class);

// Test each query
foreach ($testQueries as $index => $query) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "TEST " . ($index + 1) . ": $query\n";
    echo str_repeat('=', 60) . "\n";
    
    try {
        // Test AiDatabaseService query processing
        echo "\n1. TESTING AiDatabaseServiceRefactored Query Processing:\n";
        $companyId = null; // Test as SuperAdmin
        $result = $aiDatabaseService->searchData($query, []);
        
        if (isset($result['farms'])) {
            echo "   ✓ Farm data retrieved successfully\n";
            echo "   - Status filter applied: " . ($result['farms']['status_filter_applied'] ?? 'none') . "\n";
            echo "   - Active farms: " . ($result['farms']['active_farms'] ?? 0) . "\n";
            echo "   - Inactive farms: " . ($result['farms']['inactive_farms'] ?? 0) . "\n";
            echo "   - Total farms: " . ($result['farms']['all_farms_count'] ?? 0) . "\n";
        } else {
            echo "   ✗ No farm data in result\n";
        }
        
        if (isset($result['counts'])) {
            echo "   ✓ Count data retrieved successfully\n";
            echo "   - Status filter applied: " . ($result['counts']['status_filter_applied'] ?? 'none') . "\n";
            echo "   - Farm count: " . ($result['counts']['farms'] ?? 0) . "\n";
        }
        
        // Test AiPlanningService pattern recognition
        echo "\n2. TESTING AiPlanningService Pattern Recognition:\n";
        $planningResult = $aiPlanningService->executePRR($query, $result);
        
        echo "   - Query type detected: " . ($planningResult['planning']['query_type'] ?? 'unknown') . "\n";
        echo "   - Language detected: " . ($planningResult['planning']['language'] ?? 'unknown') . "\n";
        echo "   - Complexity: " . ($planningResult['planning']['complexity'] ?? 'unknown') . "\n";
        
        // Check if optimized prompt contains status-specific instructions
        $optimizedPrompt = $planningResult['response']['optimized_prompt'] ?? '';
        if (strpos($optimizedPrompt, 'STATUS') !== false) {
            echo "   ✓ Status-specific instructions found in prompt\n";
        } else {
            echo "   ✗ No status-specific instructions in prompt\n";
        }
        
        if (strpos($optimizedPrompt, 'farm aktif') !== false || 
            strpos($optimizedPrompt, 'farm tidak aktif') !== false || 
            strpos($optimizedPrompt, 'total farm') !== false) {
            echo "   ✓ Farm status keywords detected in prompt\n";
        }
        
        echo "\n3. SAMPLE OPTIMIZED PROMPT (first 200 chars):\n";
        echo "   " . substr($optimizedPrompt, 0, 200) . "...\n";
        
    } catch (Exception $e) {
        echo "   ✗ ERROR: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "TESTING COMPLETED\n";
echo str_repeat('=', 60) . "\n";

// Test specific methods directly
echo "\n=== DIRECT METHOD TESTING ===\n\n";

try {
    // Test getFarmDetails with different status filters
    echo "Testing getFarmDetails with different status filters:\n";
    
    $reflection = new ReflectionClass($aiDatabaseService);
    $getFarmDetailsMethod = $reflection->getMethod('getFarmDetails');
    $getFarmDetailsMethod->setAccessible(true);
    
    $statusFilters = ['active', 'inactive', 'all'];
    
    foreach ($statusFilters as $status) {
        echo "\n  Status: $status\n";
        $result = $getFarmDetailsMethod->invoke($aiDatabaseService, null, $status);
        echo "    - Farms returned: " . ($result['total_farms'] ?? 0) . "\n";
        echo "    - Active farms: " . ($result['active_farms'] ?? 0) . "\n";
        echo "    - Inactive farms: " . ($result['inactive_farms'] ?? 0) . "\n";
        echo "    - Status filter applied: " . ($result['status_filter_applied'] ?? 'none') . "\n";
    }
    
    // Test getCounts with different status filters
    echo "\nTesting getCounts with different status filters:\n";
    
    $getCountsMethod = $reflection->getMethod('getCounts');
    $getCountsMethod->setAccessible(true);
    
    foreach ($statusFilters as $status) {
        echo "\n  Status: $status\n";
        $result = $getCountsMethod->invoke($aiDatabaseService, null, $status);
        echo "    - Farm count: " . ($result['farms'] ?? 0) . "\n";
        echo "    - Active farms: " . ($result['active_farms'] ?? 0) . "\n";
        echo "    - Inactive farms: " . ($result['inactive_farms'] ?? 0) . "\n";
        echo "    - Status filter applied: " . ($result['status_filter_applied'] ?? 'none') . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR in direct method testing: " . $e->getMessage() . "\n";
}

echo "\n=== ALL TESTS COMPLETED ===\n";