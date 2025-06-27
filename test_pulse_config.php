<?php

// Test script untuk memverifikasi config pulse.php
echo "Testing Pulse Configuration...\n";

// Load Laravel environment
require_once 'vendor/autoload.php';

// Test 1: Check if config can be loaded without Pulse classes
echo "Test 1: Loading config without Pulse classes...\n";
try {
    // Simulate production environment
    putenv('APP_ENV=production');
    putenv('APP_DEBUG=false');

    $config = require 'config/pulse.php';
    echo "✓ Config loaded successfully\n";
    echo "  - Enabled: " . ($config['enabled'] ? 'true' : 'false') . "\n";
    echo "  - Recorders count: " . count($config['recorders']) . "\n";
} catch (Exception $e) {
    echo "✗ Error loading config: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if config works in local environment
echo "\nTest 2: Loading config with Pulse classes (local)...\n";
try {
    // Simulate local environment
    putenv('APP_ENV=local');
    putenv('APP_DEBUG=true');

    $config = require 'config/pulse.php';
    echo "✓ Config loaded successfully in local\n";
    echo "  - Enabled: " . ($config['enabled'] ? 'true' : 'false') . "\n";
    echo "  - Recorders count: " . count($config['recorders']) . "\n";
} catch (Exception $e) {
    echo "✗ Error loading config in local: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ All tests passed! Pulse configuration is environment-safe.\n";
