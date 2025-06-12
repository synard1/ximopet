<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\AnalyticsService;

echo "=== MORTALITY CHART DEBUG ===\n";

$service = new AnalyticsService();

echo "1. Testing All Farms Chart:\n";
$chart1 = $service->getMortalityChartData([]);
echo "   Type: " . $chart1['type'] . "\n";
echo "   Labels: " . json_encode($chart1['labels']) . "\n";
echo "   Datasets: " . count($chart1['datasets']) . "\n\n";

echo "2. Testing Single Farm Chart:\n";
$chart2 = $service->getMortalityChartData(['farm_id' => 1]);
echo "   Type: " . $chart2['type'] . "\n";
echo "   Labels: " . json_encode($chart2['labels']) . "\n";
echo "   Datasets: " . count($chart2['datasets']) . "\n\n";

if (!empty($chart2['datasets'])) {
    foreach ($chart2['datasets'] as $i => $dataset) {
        echo "   Dataset $i: " . $dataset['label'] . "\n";
        echo "   Data: " . json_encode($dataset['data']) . "\n";
    }
}

echo "✅ Chart data ready for frontend!\n";
