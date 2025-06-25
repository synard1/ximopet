<?php

/**
 * Simple Test Script untuk FIFO Integration
 * 
 * Script ini akan membantu debugging masalah "tidak ada respons" 
 * saat menyimpan record dengan FIFO depletion
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Livestock;
use App\Services\Livestock\FIFODepletionService;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;

echo "=== FIFO Integration Test ===\n\n";

// Test 1: Check if FIFODepletionService class exists
echo "1. Testing FIFODepletionService availability...\n";
if (class_exists('App\Services\Livestock\FIFODepletionService')) {
    echo "   ✅ FIFODepletionService class exists\n";

    try {
        $service = new FIFODepletionService();
        echo "   ✅ FIFODepletionService can be instantiated\n";
    } catch (Exception $e) {
        echo "   ❌ Error instantiating FIFODepletionService: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ FIFODepletionService class not found\n";
}

// Test 2: Check Livestock model methods
echo "\n2. Testing Livestock model methods...\n";
if (class_exists('App\Models\Livestock')) {
    echo "   ✅ Livestock model exists\n";

    $methods = ['getRecordingMethodConfig', 'getActiveBatchesCount'];
    foreach ($methods as $method) {
        if (method_exists('App\Models\Livestock', $method)) {
            echo "   ✅ Method {$method} exists\n";
        } else {
            echo "   ❌ Method {$method} missing\n";
        }
    }
} else {
    echo "   ❌ Livestock model not found\n";
}

// Test 3: Check CompanyConfig
echo "\n3. Testing CompanyConfig...\n";
if (class_exists('App\Config\CompanyConfig')) {
    echo "   ✅ CompanyConfig class exists\n";

    try {
        $config = CompanyConfig::getDefaultLivestockConfig();
        echo "   ✅ getDefaultLivestockConfig() works\n";

        // Check FIFO configuration
        $fifoConfig = $config['recording_method']['batch_settings']['depletion_methods']['fifo'] ?? null;
        if ($fifoConfig) {
            echo "   ✅ FIFO configuration found\n";
            echo "   📋 FIFO enabled: " . ($fifoConfig['enabled'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "   ❌ FIFO configuration not found\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error getting livestock config: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ CompanyConfig class not found\n";
}

// Test 4: Check database tables
echo "\n4. Testing database structure...\n";
try {
    // This would require database connection
    echo "   ⚠️  Database tests require active Laravel application\n";
    echo "   💡 Run this from Laravel artisan command for database tests\n";
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Test 5: Dependency Injection Test
echo "\n5. Testing Dependency Injection...\n";
echo "   💡 In Livewire Records component:\n";
echo "   - Check if FIFODepletionService is properly injected in mount()\n";
echo "   - Verify \$this->fifoDepletionService is not null\n";
echo "   - Add debug log in shouldUseFifoDepletion() method\n";

echo "\n=== DEBUGGING SUGGESTIONS ===\n\n";

echo "1. Add debug logging in Records.php save() method:\n";
echo "   Log::info('🐛 Save method called', ['livestock_id' => \$this->livestockId]);\n\n";

echo "2. Check if storeDeplesiWithDetails() is called:\n";
echo "   Add log at the beginning of storeDeplesiWithDetails()\n\n";

echo "3. Verify FIFO conditions:\n";
echo "   Add logs in shouldUseFifoDepletion() to see which condition fails\n\n";

echo "4. Check error handling:\n";
echo "   Look for exceptions in Laravel logs\n";
echo "   Check browser console for JavaScript errors\n\n";

echo "5. Test with simple data:\n";
echo "   - Try with livestock that has only 1 batch (should use traditional)\n";
echo "   - Try with livestock that has multiple batches (should use FIFO)\n\n";

echo "=== COMMON ISSUES & FIXES ===\n\n";

echo "Issue 1: FIFODepletionService not injected\n";
echo "Fix: Ensure service is registered in Laravel container\n\n";

echo "Issue 2: Livestock methods missing\n";
echo "Fix: Implement getRecordingMethodConfig() and getActiveBatchesCount() in Livestock model\n\n";

echo "Issue 3: Configuration not found\n";
echo "Fix: Verify CompanyConfig has proper FIFO configuration\n\n";

echo "Issue 4: Database transaction rollback\n";
echo "Fix: Check for exceptions in save() method that cause rollback\n\n";

echo "Issue 5: Silent failures\n";
echo "Fix: Add comprehensive logging to track execution flow\n\n";

echo "=== RECOMMENDED DEBUGGING STEPS ===\n\n";

echo "1. Add this to Records.php save() method (after DB::beginTransaction()):\n";
echo "   Log::info('🚀 Starting save process', [\n";
echo "       'livestock_id' => \$this->livestockId,\n";
echo "       'mortality' => \$this->mortality,\n";
echo "       'culling' => \$this->culling,\n";
echo "       'fifo_service_available' => \$this->fifoDepletionService ? 'yes' : 'no'\n";
echo "   ]);\n\n";

echo "2. Add this to storeDeplesiWithDetails():\n";
echo "   Log::info('📝 Processing depletion', [\n";
echo "       'type' => \$jenis,\n";
echo "       'quantity' => \$jumlah,\n";
echo "       'livestock_id' => \$this->livestockId\n";
echo "   ]);\n\n";

echo "3. Check Laravel logs for these messages and see where execution stops\n\n";

echo "4. If no logs appear, the issue might be:\n";
echo "   - Validation failure (check \$this->validate())\n";
echo "   - Exception before depletion processing\n";
echo "   - JavaScript/Livewire communication issue\n\n";

echo "=== END OF TEST ===\n";
