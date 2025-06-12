<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Mortality Chart Testing Script
 * Usage: php artisan tinker < debug_mortality_chart_testing.php
 */

// Test 1: Backend Service Testing
Log::info('[Chart Test] ========== BACKEND SERVICE TESTING ==========');

use App\Livewire\SmartAnalytics;
use App\Services\AnalyticsService;

try {
    $service = new AnalyticsService();
    Log::info('[Chart Test] AnalyticsService instance created successfully');

    // Test with different filter scenarios
    $testScenarios = [
        'All Farms' => [],
        'Single Farm' => ['farm_id' => 1],
        'Single Coop' => ['farm_id' => 1, 'coop_id' => 1],
        'Date Range' => ['date_from' => '2024-01-01', 'date_to' => '2024-12-31']
    ];

    foreach ($testScenarios as $scenarioName => $filters) {
        Log::info("[Chart Test] Testing scenario: {$scenarioName}");
        Log::info("[Chart Test] Filters: " . json_encode($filters));

        $chartData = $service->getMortalityChartData($filters);

        Log::info("[Chart Test] Chart data for {$scenarioName}:", [
            'type' => $chartData['type'] ?? 'unknown',
            'title' => $chartData['title'] ?? 'no title',
            'labels_count' => count($chartData['labels'] ?? []),
            'datasets_count' => count($chartData['datasets'] ?? []),
            'has_data' => !empty($chartData['labels'])
        ]);

        if (!empty($chartData['labels'])) {
            Log::info("[Chart Test] First 3 labels: " . json_encode(array_slice($chartData['labels'], 0, 3)));
            if (!empty($chartData['datasets'])) {
                foreach ($chartData['datasets'] as $index => $dataset) {
                    Log::info("[Chart Test] Dataset {$index}: " . ($dataset['label'] ?? 'unnamed') . " - " . count($dataset['data'] ?? []) . " data points");
                }
            }
        }

        Log::info("[Chart Test] {$scenarioName} test completed");
    }
} catch (Exception $e) {
    Log::error('[Chart Test] Backend Service Error: ' . $e->getMessage());
    Log::error('[Chart Test] Stack trace: ' . $e->getTraceAsString());
}

// Test 2: Livewire Component Testing
Log::info('[Chart Test] ========== LIVEWIRE COMPONENT TESTING ==========');

try {
    $component = new SmartAnalytics();
    Log::info('[Chart Test] SmartAnalytics component instance created successfully');

    // Test getMortalityChartData method
    $componentChartData = $component->getMortalityChartData();

    Log::info('[Chart Test] Component chart data:', [
        'type' => $componentChartData['type'] ?? 'unknown',
        'title' => $componentChartData['title'] ?? 'no title',
        'labels_count' => count($componentChartData['labels'] ?? []),
        'datasets_count' => count($componentChartData['datasets'] ?? []),
        'has_options' => isset($componentChartData['options'])
    ]);

    // Test with different component filters
    $component->selectedFarm = 1;
    $componentFilteredData = $component->getMortalityChartData();

    Log::info('[Chart Test] Component with farm filter:', [
        'type' => $componentFilteredData['type'] ?? 'unknown',
        'title' => $componentFilteredData['title'] ?? 'no title',
        'labels_count' => count($componentFilteredData['labels'] ?? [])
    ]);
} catch (Exception $e) {
    Log::error('[Chart Test] Livewire Component Error: ' . $e->getMessage());
    Log::error('[Chart Test] Stack trace: ' . $e->getTraceAsString());
}

// Test 3: Database Data Validation
Log::info('[Chart Test] ========== DATABASE DATA VALIDATION ==========');

try {
    $livestockCount = DB::table('livestock')->count();
    $mortalityCount = DB::table('livestock_mutation')
        ->where('mutation_type', 'mortality')
        ->count();
    $farmsCount = DB::table('farms')->count();
    $coopsCount = DB::table('coops')->count();

    Log::info('[Chart Test] Database counts:', [
        'livestock' => $livestockCount,
        'mortality_records' => $mortalityCount,
        'farms' => $farmsCount,
        'coops' => $coopsCount
    ]);

    // Check recent mortality data
    $recentMortality = DB::table('livestock_mutation')
        ->where('mutation_type', 'mortality')
        ->where('mutation_date', '>=', now()->subDays(30))
        ->count();

    Log::info('[Chart Test] Recent mortality (last 30 days): ' . $recentMortality);

    if ($mortalityCount === 0) {
        Log::warning('[Chart Test] No mortality data found in database!');
    }
} catch (Exception $e) {
    Log::error('[Chart Test] Database Validation Error: ' . $e->getMessage());
}

// Test 4: Chart Data Structure Validation
Log::info('[Chart Test] ========== CHART DATA STRUCTURE VALIDATION ==========');

try {
    $service = new AnalyticsService();
    $chartData = $service->getMortalityChartData();

    // Validate required chart properties
    $validationResults = [
        'has_type' => isset($chartData['type']),
        'has_labels' => isset($chartData['labels']) && is_array($chartData['labels']),
        'has_datasets' => isset($chartData['datasets']) && is_array($chartData['datasets']),
        'labels_not_empty' => !empty($chartData['labels']),
        'datasets_not_empty' => !empty($chartData['datasets'])
    ];

    Log::info('[Chart Test] Chart data structure validation:', $validationResults);

    // Validate Chart.js compatibility
    if (!empty($chartData['datasets'])) {
        foreach ($chartData['datasets'] as $index => $dataset) {
            $datasetValidation = [
                'has_label' => isset($dataset['label']),
                'has_data' => isset($dataset['data']) && is_array($dataset['data']),
                'data_count' => count($dataset['data'] ?? []),
                'has_color' => isset($dataset['backgroundColor']) || isset($dataset['borderColor'])
            ];
            Log::info("[Chart Test] Dataset {$index} validation:", $datasetValidation);
        }
    }
} catch (Exception $e) {
    Log::error('[Chart Test] Chart Structure Validation Error: ' . $e->getMessage());
}

// Test 5: API Response Testing
Log::info('[Chart Test] ========== API RESPONSE TESTING ==========');

try {
    // Simulate API call
    $component = new SmartAnalytics();
    $apiResponse = $component->getMortalityChartData();

    // Check if response is JSON serializable
    $jsonString = json_encode($apiResponse);
    $isValidJson = json_last_error() === JSON_ERROR_NONE;

    Log::info('[Chart Test] API Response validation:', [
        'is_array' => is_array($apiResponse),
        'json_serializable' => $isValidJson,
        'json_size_bytes' => strlen($jsonString),
        'json_error' => $isValidJson ? 'none' : json_last_error_msg()
    ]);

    if ($isValidJson) {
        Log::info('[Chart Test] Sample JSON structure: ' . substr($jsonString, 0, 200) . '...');
    }
} catch (Exception $e) {
    Log::error('[Chart Test] API Response Testing Error: ' . $e->getMessage());
}

Log::info('[Chart Test] ========== TESTING COMPLETED ==========');
Log::info('[Chart Test] Check Laravel log file for detailed results');
Log::info('[Chart Test] Next step: Test frontend with browser console');
