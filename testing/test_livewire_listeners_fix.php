<?php

/**
 * Test untuk validasi perbaikan Livewire Dynamic Event Listeners
 * 
 * @author AI Assistant
 * @date 2024-12-11
 */

class LivewireListenersFixTest
{
    public function runValidation()
    {
        echo "🔧 Livewire Dynamic Event Listeners Fix Validation\n";
        echo str_repeat("=", 60) . "\n\n";

        $tests = [
            'Static Listeners Array' => $this->testStaticListeners(),
            'Dynamic getListeners Method' => $this->testDynamicListeners(),
            'No Template Placeholders' => $this->testNoTemplatePlaceholders(),
            'JavaScript User Info' => $this->testJavaScriptUserInfo(),
            'DataTable Integration' => $this->testDataTableFix()
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $name => $result) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "📋 {$name}: {$status}\n";
            if ($result) $passed++;
        }

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 FIX VALIDATION SUMMARY\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: " . ($total - $passed) . "\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 1) . "%\n";
        echo "Status: " . ($passed === $total ? '✅ FIXED' : '⚠️ NEEDS ATTENTION') . "\n";

        return $passed === $total;
    }

    private function testStaticListeners()
    {
        $file = __DIR__ . '/../app/Livewire/SupplyPurchases/Create.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);

        // Check that static listeners array doesn't contain template placeholders
        $staticListenersPattern = '/protected \$listeners = \[(.*?)\];/s';
        if (preg_match($staticListenersPattern, $content, $matches)) {
            $listenersContent = $matches[1];
            // Should not contain {{ auth()->id() }}
            if (strpos($listenersContent, '{{ auth()->id() }}') !== false) {
                echo "  ❌ Static listeners still contains template placeholder\n";
                return false;
            }
            echo "  ✓ Static listeners clean from template placeholders\n";
            return true;
        }

        echo "  ❌ Could not find static listeners array\n";
        return false;
    }

    private function testDynamicListeners()
    {
        $file = __DIR__ . '/../app/Livewire/SupplyPurchases/Create.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);

        // Check for getListeners method
        if (strpos($content, 'protected function getListeners()') === false) {
            echo "  ❌ getListeners method not found\n";
            return false;
        }

        // Check for dynamic user ID handling
        if (strpos($content, 'auth()->id()') === false) {
            echo "  ❌ Dynamic auth()->id() not found in getListeners\n";
            return false;
        }

        // Check for auth check
        if (strpos($content, 'auth()->check()') === false) {
            echo "  ❌ Auth check not found\n";
            return false;
        }

        echo "  ✓ Dynamic getListeners method implemented correctly\n";
        return true;
    }

    private function testNoTemplatePlaceholders()
    {
        $file = __DIR__ . '/../app/Livewire/SupplyPurchases/Create.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);

        // Should not contain any {{ }} placeholders
        if (preg_match('/\{\{.*?\}\}/', $content)) {
            echo "  ❌ Template placeholders still found in file\n";
            return false;
        }

        echo "  ✓ No template placeholders found\n";
        return true;
    }

    private function testJavaScriptUserInfo()
    {
        $file = __DIR__ . '/../app/DataTables/SupplyPurchaseDataTable.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);

        // Check for user info setup in JavaScript
        if (strpos($content, 'window.Laravel.user = { id:') === false) {
            echo "  ❌ JavaScript user info setup not found\n";
            return false;
        }

        // Check for auth check in JavaScript generation
        if (strpos($content, 'auth()->check()') === false) {
            echo "  ❌ Auth check not found in JavaScript\n";
            return false;
        }

        echo "  ✓ JavaScript user info setup correctly\n";
        return true;
    }

    private function testDataTableFix()
    {
        $file = __DIR__ . '/../app/DataTables/SupplyPurchaseDataTable.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);

        // Check for improved user info validation
        if (strpos($content, 'window.Laravel.user && window.Laravel.user.id') === false) {
            echo "  ❌ Improved user validation not found\n";
            return false;
        }

        // Check for fallback handling
        if (strpos($content, 'User info not available for private channel') === false) {
            echo "  ❌ Fallback handling not found\n";
            return false;
        }

        echo "  ✓ DataTable fix implemented correctly\n";
        return true;
    }
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new LivewireListenersFixTest();
    $result = $test->runValidation();

    echo "\n🎯 Fix Status: " . ($result ? "✅ SUCCESSFULLY FIXED" : "❌ NEEDS MORE WORK") . "\n";

    if ($result) {
        echo "\n✅ Livewire dynamic event name issue has been resolved!\n";
        echo "The system now uses dynamic getListeners() method instead of static array.\n";
    }
}
