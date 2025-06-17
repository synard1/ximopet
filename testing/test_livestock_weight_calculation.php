<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Livestock;

function hitungBeratAyam($livestockId)
{
    $livestock = \App\Models\Livestock::find($livestockId);
    if (!$livestock) return 0;

    $initialWeight = $livestock->initial_weight ?? 0;
    $initialQty = $livestock->initial_quantity ?? 0;

    $totalKenaikan = \App\Models\Recording::where('livestock_id', $livestockId)->sum('kenaikan_berat');
    $beratRata2 = $initialWeight + $totalKenaikan;

    $totalDeplesi = \App\Models\LivestockDepletion::where('livestock_id', $livestockId)->sum('jumlah');
    $populasiSaatIni = $initialQty - $totalDeplesi;

    $beratTotal = $beratRata2 * $populasiSaatIni;

    return [
        'berat_rata2' => $beratRata2,
        'populasi' => $populasiSaatIni,
        'berat_total' => $beratTotal
    ];
}

echo "=== LIVESTOCK WEIGHT CALCULATION TEST ===\n";
$livestocks = Livestock::all();

foreach ($livestocks as $livestock) {
    $result = hitungBeratAyam($livestock->id);
    echo "Livestock: {$livestock->name} (ID: {$livestock->id})\n";
    echo "  - Initial Weight: {$livestock->initial_weight}g\n";
    echo "  - Initial Qty: {$livestock->initial_quantity}\n";
    echo "  - Berat Rata-rata Sekarang: " . round($result['berat_rata2'], 2) . "g\n";
    echo "  - Populasi Sekarang: {$result['populasi']}\n";
    echo "  - Berat Total Sekarang: " . round($result['berat_total'], 2) . "g\n";
    echo "-----------------------------\n";
}
