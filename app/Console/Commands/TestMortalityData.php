<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LivestockDepletion;
use App\Models\DailyAnalytics;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use App\Services\AnalyticsService;
use Carbon\Carbon;

class TestMortalityData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:mortality-data 
                            {--farm= : Filter by specific farm ID}
                            {--coop= : Filter by specific coop ID}
                            {--livestock= : Filter by specific livestock ID}
                            {--from= : Date from (Y-m-d format)}
                            {--to= : Date to (Y-m-d format)}
                            {--chart-type=auto : Chart type (auto, line, bar)}
                            {--view-type=livestock : View type (livestock, daily)}
                            {--show-raw : Show raw data samples}
                            {--show-chart : Show chart data structure}
                            {--export-json : Export mortality data as JSON}
                            {--save-json : Save JSON data to a file in storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mortality data availability and chart generation with livestock filter support';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 MORTALITY DATA TESTING COMMAND');
        $this->line('=========================================');

        // Parse options
        $farmId = $this->option('farm');
        $coopId = $this->option('coop');
        $livestockId = $this->option('livestock');
        $dateFrom = $this->option('from') ?? '2025-05-10';
        $dateTo = $this->option('to') ?? '2025-06-09';
        $chartType = $this->option('chart-type') ?? 'auto';
        $viewType = $this->option('view-type') ?? 'livestock';
        $showRaw = $this->option('show-raw');
        $showChart = $this->option('show-chart');

        $this->table(['Parameter', 'Value'], [
            ['Farm ID', $farmId ?? 'All'],
            ['Coop ID', $coopId ?? 'All'],
            ['Livestock ID', $livestockId ?? 'All'],
            ['Date From', $dateFrom],
            ['Date To', $dateTo],
            ['Chart Type', $chartType],
            ['View Type', $viewType],
        ]);

        // Test 1: Database Counts
        $this->newLine();
        $this->info('📊 DATABASE COUNTS');
        $this->line('===================');

        $totalFarms = Farm::count();
        $totalCoops = Coop::count();
        $totalLivestock = Livestock::count();
        $totalMortalityRecords = LivestockDepletion::where('jenis', 'Mati')->count();
        $totalAnalyticsRecords = DailyAnalytics::count();

        $this->table(['Entity', 'Total Count'], [
            ['Farms', $totalFarms],
            ['Coops', $totalCoops],
            ['Livestock', $totalLivestock],
            ['Mortality Records (LivestockDepletion)', $totalMortalityRecords],
            ['Daily Analytics Records', $totalAnalyticsRecords],
        ]);

        // Test 2: Filtered Data Availability
        $this->newLine();
        $this->info('🎯 FILTERED DATA AVAILABILITY');
        $this->line('================================');

        // Build base queries
        $mortalityQuery = LivestockDepletion::where('jenis', 'Mati')
            ->whereBetween('tanggal', [$dateFrom, $dateTo]);

        $analyticsQuery = DailyAnalytics::whereBetween('date', [$dateFrom, $dateTo]);

        if ($farmId) {
            $mortalityQuery->whereHas('livestock', function ($q) use ($farmId) {
                $q->where('farm_id', $farmId);
            });
            $analyticsQuery->where('farm_id', $farmId);
        }

        if ($coopId) {
            $mortalityQuery->whereHas('livestock', function ($q) use ($coopId) {
                $q->where('coop_id', $coopId);
            });
            $analyticsQuery->where('coop_id', $coopId);
        }

        if ($livestockId) {
            $mortalityQuery->where('livestock_id', $livestockId);
            $analyticsQuery->where('livestock_id', $livestockId);
        }

        $filteredMortality = $mortalityQuery->count();
        $filteredAnalytics = $analyticsQuery->count();

        $this->table(['Data Type', 'Filtered Count', 'Date Range'], [
            ['Mortality Records', $filteredMortality, "$dateFrom to $dateTo"],
            ['Analytics Records', $filteredAnalytics, "$dateFrom to $dateTo"],
        ]);

        // Test 3: Raw Data Samples
        if ($showRaw) {
            $this->newLine();
            $this->info('📋 RAW DATA SAMPLES');
            $this->line('=====================');

            // Sample mortality data
            $mortalitySample = $mortalityQuery->with(['livestock.farm', 'livestock.coop'])
                ->latest('tanggal')
                ->limit(5)
                ->get(['id', 'livestock_id', 'tanggal', 'jumlah', 'jenis']);

            if ($mortalitySample->count() > 0) {
                $this->line('Recent Mortality Records:');
                $mortalityData = $mortalitySample->map(function ($record) {
                    return [
                        'Date' => $record->tanggal->format('Y-m-d'),
                        'Farm' => $record->livestock->farm->name ?? 'N/A',
                        'Coop' => $record->livestock->coop->name ?? 'N/A',
                        'Livestock' => substr($record->livestock->name ?? 'N/A', 0, 20),
                        'Deaths' => $record->jumlah,
                    ];
                })->toArray();
                $this->table(['Date', 'Farm', 'Coop', 'Livestock', 'Deaths'], $mortalityData);
            } else {
                $this->warn('No mortality records found for the specified filters.');
            }

            // Sample analytics data
            $analyticsSample = $analyticsQuery->with(['farm', 'coop', 'livestock'])
                ->latest('date')
                ->limit(5)
                ->get(['id', 'date', 'farm_id', 'coop_id', 'livestock_id', 'mortality_count', 'mortality_rate', 'current_population']);

            if ($analyticsSample->count() > 0) {
                $this->newLine();
                $this->line('Recent Analytics Records:');
                $analyticsData = $analyticsSample->map(function ($record) {
                    return [
                        'Date' => $record->date->format('Y-m-d'),
                        'Farm' => $record->farm->name ?? 'N/A',
                        'Coop' => $record->coop->name ?? 'N/A',
                        'Livestock' => substr($record->livestock->name ?? 'N/A', 0, 15),
                        'Deaths' => $record->mortality_count,
                        'Rate %' => number_format($record->mortality_rate * 100, 2),
                        'Population' => $record->current_population,
                    ];
                })->toArray();
                $this->table(['Date', 'Farm', 'Coop', 'Livestock', 'Deaths', 'Rate %', 'Population'], $analyticsData);
            } else {
                $this->warn('No analytics records found for the specified filters.');
            }
        }

        // Test 4: Chart Data Structure
        if ($showChart) {
            $this->newLine();
            $this->info('📈 CHART DATA STRUCTURE TEST');
            $this->line('===============================');

            try {
                $analyticsService = app(AnalyticsService::class);

                $filters = [
                    'farm_id' => $farmId,
                    'coop_id' => $coopId,
                    'livestock_id' => $livestockId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'chart_type' => $chartType,
                    'view_type' => $viewType,
                ];

                $chartData = $analyticsService->getMortalityChartData($filters);

                $this->line('Chart Data Structure:');
                $this->table(['Property', 'Value'], [
                    ['Type', $chartData['type'] ?? 'N/A'],
                    ['Title', $chartData['title'] ?? 'N/A'],
                    ['Labels Count', count($chartData['labels'] ?? [])],
                    ['Datasets Count', count($chartData['datasets'] ?? [])],
                    ['Has Options', isset($chartData['options']) ? 'Yes' : 'No'],
                ]);

                if (!empty($chartData['labels'])) {
                    $this->line('Sample Labels (first 10):');
                    $sampleLabels = array_slice($chartData['labels'], 0, 10);
                    $this->line(implode(', ', $sampleLabels));
                }

                if (!empty($chartData['datasets'])) {
                    $this->newLine();
                    $this->line('Datasets Information:');
                    $datasetInfo = [];
                    foreach ($chartData['datasets'] as $index => $dataset) {
                        $datasetInfo[] = [
                            'Index' => $index,
                            'Label' => $dataset['label'] ?? 'N/A',
                            'Data Points' => count($dataset['data'] ?? []),
                            'Has Color' => (isset($dataset['backgroundColor']) || isset($dataset['borderColor'])) ? 'Yes' : 'No',
                            'Sample Data' => implode(', ', array_slice($dataset['data'] ?? [], 0, 5)),
                        ];
                    }
                    $this->table(['Index', 'Label', 'Data Points', 'Has Color', 'Sample Data'], $datasetInfo);
                }
            } catch (\Exception $e) {
                $this->error('Failed to generate chart data: ' . $e->getMessage());
                $this->line('Error trace: ' . $e->getTraceAsString());
            }
        }

        // Test 5: Data Quality Checks
        $this->newLine();
        $this->info('✅ DATA QUALITY CHECKS');
        $this->line('========================');

        $qualityChecks = [];

        // Check for missing data
        $datesInRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo))->count();
        $uniqueAnalyticsDates = $analyticsQuery->distinct('date')->count('date');
        $qualityChecks[] = [
            'Check' => 'Analytics Coverage',
            'Result' => "$uniqueAnalyticsDates/$datesInRange days",
            'Status' => $uniqueAnalyticsDates >= ($datesInRange * 0.8) ? '✅ Good' : '⚠️ Incomplete'
        ];

        // Check for zero mortality days
        $zeroMortalityDays = $analyticsQuery->where('mortality_count', 0)->count();
        $qualityChecks[] = [
            'Check' => 'Zero Mortality Days',
            'Result' => $zeroMortalityDays,
            'Status' => $zeroMortalityDays > 0 ? '✅ Normal' : '⚠️ Always deaths'
        ];

        // Check for high mortality days
        $highMortalityDays = $analyticsQuery->where('mortality_count', '>', 10)->count();
        $qualityChecks[] = [
            'Check' => 'High Mortality Days (>10)',
            'Result' => $highMortalityDays,
            'Status' => $highMortalityDays < ($filteredAnalytics * 0.1) ? '✅ Normal' : '⚠️ Frequent'
        ];

        // Check data consistency
        $analyticsTotal = $analyticsQuery->sum('mortality_count');
        $depletionTotal = $mortalityQuery->sum('jumlah');
        $consistency = $analyticsTotal > 0 ? (($depletionTotal / $analyticsTotal) * 100) : 0;
        $qualityChecks[] = [
            'Check' => 'Data Consistency',
            'Result' => number_format($consistency, 1) . '%',
            'Status' => $consistency >= 80 ? '✅ Good' : '⚠️ Inconsistent'
        ];

        $this->table(['Check', 'Result', 'Status'], $qualityChecks);

        // Test 6: Recommendations
        $this->newLine();
        $this->info('💡 RECOMMENDATIONS');
        $this->line('====================');

        if ($filteredMortality === 0 && $filteredAnalytics === 0) {
            $this->warn('No data found for the specified filters. Try:');
            $this->line('• Expanding date range');
            $this->line('• Removing farm/coop/livestock filters');
            $this->line('• Check if data exists: php artisan test:mortality-data --show-raw');
        } elseif ($filteredMortality === 0) {
            $this->warn('No mortality records found. Check LivestockDepletion table.');
        } elseif ($filteredAnalytics === 0) {
            $this->warn('No analytics records found. Run daily analytics calculation.');
        } else {
            $this->info('✅ Data looks good for chart generation!');
            if (!$showChart) {
                $this->line('Run with --show-chart to see chart data structure.');
            }
        }

        // New feature: Export data as JSON if the option is set
        if ($this->option('export-json') || $this->option('save-json')) {
            $this->newLine();
            $this->info('📤 EXPORTING MORTALITY DATA AS JSON');
            $this->line('====================================');

            $mortalityData = $mortalityQuery->with(['livestock.farm', 'livestock.coop'])
                ->get(['id', 'livestock_id', 'tanggal', 'jumlah', 'jenis']);

            if ($mortalityData->count() > 0) {
                $jsonData = $mortalityData->map(function ($record) {
                    return [
                        'date' => $record->tanggal->format('Y-m-d'),
                        'farm' => $record->livestock->farm->name ?? 'N/A',
                        'coop' => $record->livestock->coop->name ?? 'N/A',
                        'livestock' => $record->livestock->name ?? 'N/A',
                        'deaths' => $record->jumlah,
                    ];
                });

                // Output JSON data
                $jsonOutput = json_encode($jsonData, JSON_PRETTY_PRINT);

                // If save-json option is provided, save to file
                if ($this->option('save-json')) {
                    // Create a directory for JSON files if it doesn't exist
                    $directoryPath = storage_path('app/mortality_data');
                    if (!is_dir($directoryPath)) {
                        mkdir($directoryPath, 0755, true);
                    }

                    // Generate a descriptive filename
                    $filename = sprintf(
                        'mortality_data_%s_to_%s_farm_%s_coop_%s.json',
                        $dateFrom,
                        $dateTo,
                        $farmId ?? 'all',
                        $coopId ?? 'all'
                    );

                    $filePath = $directoryPath . '/' . $filename; // Get the full file path
                    file_put_contents($filePath, $jsonOutput); // Save JSON to file
                    $this->info("Data saved to: $filePath");
                } else {
                    // Output JSON data to console
                    $this->line($jsonOutput);
                }
            } else {
                $this->warn('No mortality records found for the specified filters.');
            }
        }

        $this->newLine();
        $this->info('📋 USAGE EXAMPLES:');
        $this->line('• Test all data: php artisan test:mortality-data --show-raw --show-chart');
        $this->line('• Test single farm: php artisan test:mortality-data --farm=1 --show-chart');
        $this->line('• Test single coop: php artisan test:mortality-data --coop=1 --show-chart');
        $this->line('• Test daily view: php artisan test:mortality-data --coop=1 --view-type=daily --show-chart');

        return 0;
    }
}
