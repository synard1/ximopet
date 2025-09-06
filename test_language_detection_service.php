<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\LanguageDetectionService;

// Create instance of the service
$languageService = new LanguageDetectionService();

// Test cases
$testCases = [
    // Indonesian test cases
    'ada berapa jumlah farm',
    'tampilkan semua perusahaan yang terdaftar',
    'bagaimana cara menambah data ternak ayam',
    'berapa total kandang yang aktif',
    'saya ingin melihat laporan keuangan',
    
    // English test cases
    'show me all active farms',
    'how many companies are registered',
    'display the financial report',
    'can you list all livestock data',
    'what is the total number of farms',
    
    // Mixed/ambiguous cases
    'farm data',
    'total',
    'hello',
    'terima kasih',
    'thank you'
];

echo "=== Language Detection Service Test ===\n\n";

foreach ($testCases as $index => $text) {
    echo "Test " . ($index + 1) . ": \"$text\"\n";
    
    // Get full detection result
    $result = $languageService->detect($text);
    
    echo "  Language: " . $result['language'] . "\n";
    echo "  Confidence: " . $result['confidence'] . "\n";
    echo "  Method: " . $result['method'] . "\n";
    echo "  Is Indonesian: " . ($languageService->isIndonesian($text) ? 'Yes' : 'No') . "\n";
    echo "  Is English: " . ($languageService->isEnglish($text) ? 'Yes' : 'No') . "\n";
    echo "\n";
}

// Test cache functionality
echo "=== Cache Test ===\n";
$testText = 'ada berapa jumlah farm yang aktif';

// First call (should cache)
$start = microtime(true);
$result1 = $languageService->detect($testText, true);
$time1 = microtime(true) - $start;

// Second call (should use cache)
$start = microtime(true);
$result2 = $languageService->detect($testText, true);
$time2 = microtime(true) - $start;

echo "First call (with caching): " . round($time1 * 1000, 2) . "ms\n";
echo "Second call (from cache): " . round($time2 * 1000, 2) . "ms\n";
echo "Cache speedup: " . round($time1 / $time2, 2) . "x faster\n\n";

// Test cache stats
echo "=== Cache Statistics ===\n";
$stats = $languageService->getCacheStats();
print_r($stats);

echo "\n=== Configuration Test ===\n";
echo "Supported Languages: " . implode(', ', config('chat.language_detection.supported_languages', ['en', 'id'])) . "\n";
echo "Cache Enabled: " . (config('chat.language_detection.cache_enabled', true) ? 'Yes' : 'No') . "\n";
echo "Cache TTL: " . config('chat.language_detection.cache_ttl', 3600) . " seconds\n";
echo "Default Language: " . config('chat.language_detection.default_language', 'en') . "\n";
echo "Confidence Threshold: " . config('chat.language_detection.confidence_threshold', 0.3) . "\n";

echo "\n=== Test Completed ===\n";