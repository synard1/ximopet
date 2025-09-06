<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Services\AiDatabaseServiceRefactored;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Boot the application
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug Inactive Farm Query ===\n\n";

// Test direct database query
echo "--- Direct Database Query ---\n";
$totalFarms = DB::table('farms')->count();
$activeFarms = DB::table('farms')->where('status', 'active')->count();
$inactiveFarms = DB::table('farms')->where('status', 'inactive')->count();

echo "Total farms: $totalFarms\n";
echo "Active farms: $activeFarms\n";
echo "Inactive farms: $inactiveFarms\n\n";

// Show actual farm data
echo "--- Farm Data Details ---\n";
$farms = DB::table('farms')->select('id', 'name', 'code', 'status', 'company_id')->get();
foreach ($farms as $farm) {
    echo "Farm: {$farm->name} ({$farm->code}) - Status: {$farm->status} - Company: {$farm->company_id}\n";
}
echo "\n";

// Test direct database queries with different filters
echo "--- Direct Database Queries with Filters ---\n";

$allFarms = DB::table('farms')->whereNull('deleted_at')->get();
echo "All farms (direct query): " . count($allFarms) . "\n";

$activeFarmsQuery = DB::table('farms')->whereNull('deleted_at')->where('status', 'active')->get();
echo "Active farms (direct query): " . count($activeFarmsQuery) . "\n";

$inactiveFarmsQuery = DB::table('farms')->whereNull('deleted_at')->where('status', 'inactive')->get();
echo "Inactive farms (direct query): " . count($inactiveFarmsQuery) . "\n";
foreach ($inactiveFarmsQuery as $farm) {
    echo "  - Inactive: {$farm->name} ({$farm->status})\n";
}
echo "\n";

// Test getFarmDetails method directly with reflection
echo "--- Testing getFarmDetails Method Directly ---\n";

try {
    // Create a real user from database for testing
    $testUser = DB::table('users')->first();
    if (!$testUser) {
        echo "No users found in database. Creating test scenario...\n";
        // Continue with null user to test SuperAdmin bypass
    }
    
    $aiService = $app->make(AiDatabaseServiceRefactored::class);
    
    // Use reflection to access private getFarmDetails method
    $reflection = new ReflectionClass($aiService);
    $getFarmDetailsMethod = $reflection->getMethod('getFarmDetails');
    $getFarmDetailsMethod->setAccessible(true);
    
    // Mock Auth::user() to return our test user
    if ($testUser) {
        $mockUser = new User();
        $mockUser->id = $testUser->id;
        $mockUser->name = $testUser->name;
        $mockUser->email = $testUser->email;
        $mockUser->company_id = $testUser->company_id;
        
        // Mock the isSuperAdmin method to return true for testing
        $isSuperAdminMethod = $reflection->getMethod('isSuperAdmin');
        $isSuperAdminMethod->setAccessible(true);
        
        Auth::shouldReceive('user')->andReturn($mockUser);
        Auth::shouldReceive('id')->andReturn($testUser->id);
        
        echo "Using test user: {$mockUser->name} (ID: {$mockUser->id}, Company: {$mockUser->company_id})\n\n";
    }
    
    $statusFilters = ['all', 'active', 'inactive'];
    
    foreach ($statusFilters as $status) {
        echo "Testing filter: $status\n";
        try {
            // Test with null company_id to get all farms (SuperAdmin behavior)
            $result = $getFarmDetailsMethod->invoke($aiService, null, $status);
            
            if (is_array($result)) {
                echo "Result count: " . (isset($result['total_farms']) ? $result['total_farms'] : 'N/A') . "\n";
                
                if (isset($result['farm_details']) && is_array($result['farm_details'])) {
                    echo "Farm details found: " . count($result['farm_details']) . "\n";
                    foreach ($result['farm_details'] as $farm) {
                        echo "  - {$farm['name']} ({$farm['status']})\n";
                    }
                } else {
                    echo "No farm details in result\n";
                }
                
                echo "Full result structure: " . json_encode(array_keys($result)) . "\n";
            } else {
                echo "Result: " . (is_string($result) ? $result : 'No data') . "\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error in getFarmDetails testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test filter detection logic directly
echo "--- Testing Filter Detection Logic ---\n";

class FilterDetectionTester {
    public function detectFarmFilter($query) {
        $query = strtolower($query);
        
        // Check for inactive filter first (highest priority)
        if (strpos($query, 'tidak aktif') !== false || 
            strpos($query, 'inactive') !== false ||
            strpos($query, 'non aktif') !== false ||
            strpos($query, 'non-aktif') !== false ||
            strpos($query, 'ada berapa farm tidak aktif') !== false ||
            strpos($query, 'berapa farm tidak aktif') !== false ||
            strpos($query, 'show me inactive') !== false ||
            strpos($query, 'show inactive') !== false ||
            strpos($query, 'list inactive') !== false ||
            strpos($query, 'tampilkan tidak aktif') !== false) {
            return 'inactive';
        }
        
        // Check for active filter
        if (strpos($query, 'aktif') !== false || 
            strpos($query, 'active') !== false ||
            strpos($query, 'berapa farm aktif') !== false ||
            strpos($query, 'ada berapa farm aktif') !== false) {
            return 'active';
        }
        
        // Check for all filter (lowest priority)
        if (strpos($query, 'semua') !== false || 
            strpos($query, 'all') !== false ||
            strpos($query, 'total') !== false ||
            strpos($query, 'jumlah') !== false ||
            strpos($query, 'berapa') !== false ||
            strpos($query, 'count') !== false ||
            strpos($query, 'number') !== false ||
            strpos($query, 'show all') !== false ||
            strpos($query, 'list all') !== false) {
            return 'all';
        }
        
        return 'active'; // default
    }
}

$tester = new FilterDetectionTester();
$testQueries = [
    'ada berapa farm tidak aktif?',
    'show me inactive farms only',
    'ada berapa jumlah farm?',
    'show me all farms',
    'berapa farm aktif?'
];

foreach ($testQueries as $query) {
    $filter = $tester->detectFarmFilter($query);
    echo "Query: '$query' -> Filter: $filter\n";
}

echo "\n=== Debug Completed ===\n";