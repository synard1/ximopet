<?php

/**
 * SECURITY BLACKLIST SYSTEM TEST
 * Script untuk testing sistem blacklist IP security
 * 
 * @author AI Assistant
 * @date 2024-12-19
 * @version 1.0.0
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

echo "🔒 SECURITY BLACKLIST SYSTEM TEST\n";
echo "================================\n\n";

// Test 1: Database Tables
echo "📊 Testing Database Tables...\n";

try {
    $blacklistExists = Schema::hasTable('security_blacklist');
    echo $blacklistExists ? "✅ PASS: security_blacklist table exists\n" : "❌ FAIL: security_blacklist table missing\n";

    $violationsExists = Schema::hasTable('security_violations');
    echo $violationsExists ? "✅ PASS: security_violations table exists\n" : "❌ FAIL: security_violations table missing\n";
} catch (Exception $e) {
    echo "❌ FAIL: Database connection error - " . $e->getMessage() . "\n";
}

// Test 2: Middleware Class
echo "\n🛡️ Testing Middleware Class...\n";

try {
    $middleware = new App\Http\Middleware\SecurityBlacklistMiddleware();
    echo "✅ PASS: SecurityBlacklistMiddleware instantiated\n";

    // Test static methods exist
    if (method_exists('App\Http\Middleware\SecurityBlacklistMiddleware', 'recordViolation')) {
        echo "✅ PASS: recordViolation method exists\n";
    } else {
        echo "❌ FAIL: recordViolation method missing\n";
    }

    if (method_exists('App\Http\Middleware\SecurityBlacklistMiddleware', 'addToBlacklist')) {
        echo "✅ PASS: addToBlacklist method exists\n";
    } else {
        echo "❌ FAIL: addToBlacklist method missing\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Middleware error - " . $e->getMessage() . "\n";
}

// Test 3: Controller Class
echo "\n🎮 Testing Controller Class...\n";

try {
    $controller = new App\Http\Controllers\SecurityController();
    echo "✅ PASS: SecurityController instantiated\n";

    if (method_exists('App\Http\Controllers\SecurityController', 'recordViolation')) {
        echo "✅ PASS: recordViolation method exists\n";
    } else {
        echo "❌ FAIL: recordViolation method missing\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Controller error - " . $e->getMessage() . "\n";
}

// Test 4: Basic Functionality
echo "\n🔧 Testing Basic Functionality...\n";

try {
    $testIp = '192.168.1.100';

    // Clean test data
    DB::table('security_violations')->where('ip_address', $testIp)->delete();
    DB::table('security_blacklist')->where('ip_address', $testIp)->delete();

    // Test violation recording
    App\Http\Middleware\SecurityBlacklistMiddleware::recordViolation($testIp, 'test_violation');

    $violationCount = DB::table('security_violations')
        ->where('ip_address', $testIp)
        ->where('created_at', '>=', Carbon\Carbon::now()->subHours(24))
        ->count();

    echo $violationCount > 0 ? "✅ PASS: Violation recorded successfully\n" : "❌ FAIL: Violation not recorded\n";

    // Test blacklist after 3 violations
    App\Http\Middleware\SecurityBlacklistMiddleware::recordViolation($testIp, 'test_violation_2');
    App\Http\Middleware\SecurityBlacklistMiddleware::recordViolation($testIp, 'test_violation_3');

    $isBlacklisted = DB::table('security_blacklist')
        ->where('ip_address', $testIp)
        ->where('expires_at', '>', Carbon\Carbon::now())
        ->exists();

    echo $isBlacklisted ? "✅ PASS: IP blacklisted after 3 violations\n" : "❌ FAIL: IP not blacklisted\n";

    // Clean test data
    DB::table('security_violations')->where('ip_address', $testIp)->delete();
    DB::table('security_blacklist')->where('ip_address', $testIp)->delete();
} catch (Exception $e) {
    echo "❌ FAIL: Functionality test error - " . $e->getMessage() . "\n";
}

// Test 5: Console Command
echo "\n⚡ Testing Console Command...\n";

try {
    if (class_exists('App\Console\Commands\CleanSecurityBlacklist')) {
        echo "✅ PASS: CleanSecurityBlacklist command class exists\n";
    } else {
        echo "❌ FAIL: CleanSecurityBlacklist command class missing\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Console command error - " . $e->getMessage() . "\n";
}

// Test 6: API Routes
echo "\n🌐 Testing API Routes...\n";

try {
    $routes = Route::getRoutes();
    $securityRoutes = 0;

    foreach ($routes as $route) {
        if (strpos($route->uri(), 'api/security') !== false) {
            $securityRoutes++;
        }
    }

    echo $securityRoutes > 0 ? "✅ PASS: Security API routes registered ({$securityRoutes} routes)\n" : "❌ FAIL: No security API routes found\n";
} catch (Exception $e) {
    echo "❌ FAIL: API routes test error - " . $e->getMessage() . "\n";
}

echo "\n🎉 TESTING COMPLETED!\n";
echo "Check the results above for any failures.\n";
echo "If all tests pass, the security blacklist system is ready for production.\n\n";
