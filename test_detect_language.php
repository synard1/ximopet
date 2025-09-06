<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use LanguageDetection\Language;

$ld = new Language;

$result = $ld->detect('tampilkan semua data dari perusahaan Demo Company')->whitelist('en', 'id')->close();

// Display the detection results
echo "Language detection results:\n";
print_r($result);

// Get the most likely language (first key in the sorted array)
if (!empty($result)) {
    $mostLikelyLanguage = array_key_first($result);
    $confidence = $result[$mostLikelyLanguage];
    echo "\nMost likely language: {$mostLikelyLanguage} (confidence: {$confidence})\n";
} else {
    echo "\nNo language detected\n";
}
