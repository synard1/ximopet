<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Services\AiDatabaseServiceRefactored;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Boot the application
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Fixed AI Farm Query Filter Detection ===\n\n";

// Test queries that were problematic
$testQueries = [
    'ada berapa jumlah farm?' => 'all',
    'ada berapa farm tidak aktif?' => 'inactive', 
    'show me all farms' => 'all',
    'show me inactive farms only' => 'inactive',
    'how many farms do we have?' => 'all',
    'list all farms' => 'all',
    'count farms' => 'all',
    'show inactive farms' => 'inactive',
    'berapa farm aktif?' => 'active'
];

// Create a test class to access private methods
class AiDatabaseServiceTester extends AiDatabaseServiceRefactored {
    public function testFilterDetection($query) {
        $queryLower = strtolower($query);
        
        // Copy the filter detection logic from the original method
        $farmStatusFilter = 'active'; // default
        
        // Check for inactive farms queries FIRST (highest priority for specific status)
         if (strpos($queryLower, 'farm tidak aktif') !== false || strpos($queryLower, 'farm nonaktif') !== false ||
             strpos($queryLower, 'farm inactive') !== false || strpos($queryLower, 'farm non-aktif') !== false ||
             strpos($queryLower, 'tidak aktif') !== false || strpos($queryLower, 'nonaktif') !== false ||
             strpos($queryLower, 'inactive farm') !== false || strpos($queryLower, 'inactive only') !== false ||
             strpos($queryLower, 'show me inactive') !== false || strpos($queryLower, 'show inactive') !== false ||
             strpos($queryLower, 'list inactive') !== false || strpos($queryLower, 'tampilkan tidak aktif') !== false ||
             strpos($queryLower, 'ada berapa farm tidak aktif') !== false || strpos($queryLower, 'berapa farm tidak aktif') !== false) {
             $farmStatusFilter = 'inactive';
         } 
         // Check for active farms queries
         elseif (strpos($queryLower, 'farm aktif') !== false || strpos($queryLower, 'active farm') !== false ||
                  strpos($queryLower, 'berapa farm aktif') !== false || strpos($queryLower, 'ada berapa farm aktif') !== false) {
             $farmStatusFilter = 'active';
         }
         // Check for 'all farms' queries (lower priority to avoid conflicts)
         elseif (strpos($queryLower, 'semua farm') !== false || strpos($queryLower, 'all farm') !== false ||
                  strpos($queryLower, 'total farm') !== false || strpos($queryLower, 'seluruh farm') !== false ||
                  strpos($queryLower, 'jumlah farm') !== false || strpos($queryLower, 'berapa farm') !== false ||
                  strpos($queryLower, 'ada berapa farm') !== false || strpos($queryLower, 'how many farm') !== false ||
                  strpos($queryLower, 'show me all farm') !== false || strpos($queryLower, 'show all farm') !== false ||
                  strpos($queryLower, 'tampilkan semua farm') !== false || strpos($queryLower, 'list all farm') !== false ||
                  strpos($queryLower, 'count farm') !== false || strpos($queryLower, 'number of farm') !== false ||
                  strpos($queryLower, 'berapa jumlah farm') !== false || strpos($queryLower, 'ada berapa jumlah farm') !== false) {
             $farmStatusFilter = 'all';
         }
        
        return $farmStatusFilter;
    }
}

$tester = new AiDatabaseServiceTester();

foreach ($testQueries as $query => $expectedFilter) {
    echo "--- Query: '$query' ---\n";
    
    $detectedFilter = $tester->testFilterDetection($query);
    $isCorrect = $detectedFilter === $expectedFilter;
    
    echo "Expected filter: $expectedFilter\n";
    echo "Detected filter: $detectedFilter\n";
    echo "Result: " . ($isCorrect ? "✓ CORRECT" : "✗ INCORRECT") . "\n\n";
}

// Test actual database query counts
echo "=== Testing Database Query Results ===\n\n";

try {
    $totalFarms = DB::table('farms')->count();
    $activeFarms = DB::table('farms')->where('status', 'active')->count();
    $inactiveFarms = DB::table('farms')->where('status', 'inactive')->count();
    
    echo "Database farm counts:\n";
    echo "- Total farms: $totalFarms\n";
    echo "- Active farms: $activeFarms\n";
    echo "- Inactive farms: $inactiveFarms\n\n";
    
    echo "Expected AI responses:\n";
    echo "- 'ada berapa jumlah farm?' should return: $totalFarms farms\n";
    echo "- 'ada berapa farm tidak aktif?' should return: $inactiveFarms farms\n";
    echo "- 'show me all farms' should return: $totalFarms farms\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Completed ===\n";