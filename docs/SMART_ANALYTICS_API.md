# Smart Analytics - API Reference

## 📋 Daftar Isi

-   [Overview](#overview)
-   [AnalyticsService API](#analyticsservice-api)
-   [Model API](#model-api)
-   [Livewire Component API](#livewire-component-api)
-   [Console Commands API](#console-commands-api)
-   [Database Queries](#database-queries)
-   [Examples](#examples)

## 🎯 Overview

Smart Analytics API menyediakan interface programmatik untuk mengakses dan mengelola data analisis peternakan. API ini terdiri dari service classes, model methods, Livewire components, dan console commands.

---

## 🧠 AnalyticsService API

### Core Service: `App\Services\AnalyticsService`

#### `calculateDailyAnalytics($date = null): void`

Menghitung analytics harian untuk semua livestock aktif.

**Parameters:**

-   `$date` (string|Carbon|null): Tanggal untuk perhitungan (default: hari ini)

**Example:**

```php
use App\Services\AnalyticsService;

$service = app(AnalyticsService::class);
$service->calculateDailyAnalytics('2024-01-15');
$service->calculateDailyAnalytics(Carbon::yesterday());
$service->calculateDailyAnalytics(); // Today
```

---

#### `calculateDailyAnalyticsWithResults(Carbon $date, bool $force = false): array`

Menghitung analytics harian dengan return detailed results untuk CLI.

**Parameters:**

-   `$date` (Carbon): Tanggal untuk perhitungan
-   `$force` (bool): Paksa perhitungan ulang meski data sudah ada

**Returns:**

```php
[
    'analytics_created' => 15,     // Jumlah record analytics dibuat
    'alerts_created' => 3,         // Jumlah alert dibuat
    'livestock_processed' => 20,   // Jumlah livestock diproses
    'processing_time' => 2.45,     // Waktu eksekusi (seconds)
    'insights' => [                // Key insights
        'High mortality detected in Kandang A: 75 deaths',
        'Low efficiency in Kandang B: 45% efficiency score'
    ]
]
```

**Example:**

```php
$date = Carbon::parse('2024-01-15');
$results = $service->calculateDailyAnalyticsWithResults($date, true);

echo "Created {$results['analytics_created']} analytics records";
echo "Processing took {$results['processing_time']} seconds";
```

---

#### `getSmartInsights(array $filters = []): array`

Mendapatkan insights cerdas berdasarkan filter.

**Parameters:**

-   `$filters` (array): Filter untuk data analysis

**Filter Options:**

```php
$filters = [
    'farm_id' => 1,              // Filter by farm
    'coop_id' => 2,              // Filter by coop
    'date_from' => '2024-01-01', // Start date
    'date_to' => '2024-01-31',   // End date
    'livestock_id' => 123,       // Specific livestock
];
```

**Returns:**

```php
[
    'overview' => [
        'total_livestock' => 50,
        'avg_mortality_rate' => 2.5,
        'avg_efficiency_score' => 75.3,
        'total_revenue' => 1250000
    ],
    'mortality_analysis' => [
        'daily_average' => 12.5,
        'trend' => 'increasing',
        'high_risk_coops' => [...]
    ],
    'sales_analysis' => [
        'total_sales' => 500,
        'avg_weight' => 2.1,
        'revenue_trend' => 'stable'
    ],
    'production_analysis' => [
        'avg_fcr' => 1.85,
        'avg_daily_gain' => 45.2,
        'top_performers' => [...]
    ],
    'coop_rankings' => [
        ['coop_name' => 'Kandang A', 'rank' => 1, 'score' => 92.5],
        ['coop_name' => 'Kandang B', 'rank' => 2, 'score' => 87.3]
    ],
    'alerts' => [
        'critical' => 2,
        'high' => 5,
        'total_unresolved' => 7
    ]
]
```

**Example:**

```php
$filters = [
    'farm_id' => 1,
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
];

$insights = $service->getSmartInsights($filters);
echo "Average efficiency: {$insights['overview']['avg_efficiency_score']}%";
```

---

#### Private Methods (Internal Use)

#### `calculateMortalityMetrics($livestock, $date): array`

Menghitung metrik mortalitas untuk livestock tertentu.

#### `calculateSalesMetrics($livestock, $date): array`

Menghitung metrik penjualan untuk livestock tertentu.

#### `calculateFeedMetrics($livestock, $date): array`

Menghitung metrik pakan dan FCR.

#### `generateEfficiencyScore($analytics): float`

Generate efficiency score 0-100 dengan weighted algorithm.

#### `generateAlerts($analytic): void`

Generate alerts berdasarkan threshold dan conditions.

---

## 📊 Model API

### DailyAnalytics Model

#### Query Scopes

```php
use App\Models\DailyAnalytics;

// Filter by farm
$analytics = DailyAnalytics::byFarm($farmId)->get();

// Filter by date range
$analytics = DailyAnalytics::dateRange($startDate, $endDate)->get();

// Filter by low efficiency (< 60%)
$analytics = DailyAnalytics::lowEfficiency()->get();

// Filter by high mortality (> 5%)
$analytics = DailyAnalytics::highMortality()->get();

// Recent records (last 30 days)
$analytics = DailyAnalytics::recent()->get();
```

#### Relationships

```php
$analytic = DailyAnalytics::first();

// Get related livestock
$livestock = $analytic->livestock;

// Get farm through livestock
$farm = $analytic->livestock->farm;

// Get coop through livestock
$coop = $analytic->livestock->coop;

// Eager loading
$analytics = DailyAnalytics::with(['livestock.farm', 'livestock.coop'])->get();
```

#### Attributes & Accessors

```php
$analytic = DailyAnalytics::first();

// Get efficiency grade (A+, A, B, C, D)
$grade = $analytic->efficiency_grade;

// Get mortality status (normal, warning, critical)
$status = $analytic->mortality_status;

// Get formatted efficiency score
$score = $analytic->efficiency_score_formatted; // "85.3%"
```

---

### AnalyticsAlert Model

#### Query Scopes

```php
use App\Models\AnalyticsAlert;

// Unresolved alerts
$alerts = AnalyticsAlert::unresolved()->get();

// By severity
$criticalAlerts = AnalyticsAlert::bySeverity('critical')->get();

// Recent alerts (last 7 days)
$recentAlerts = AnalyticsAlert::recent()->get();

// By alert type
$mortalityAlerts = AnalyticsAlert::byType('high_mortality')->get();
```

#### Methods

```php
$alert = AnalyticsAlert::first();

// Resolve alert
$alert->resolve(auth()->id(), 'Issue has been addressed');

// Check if alert is critical
if ($alert->isCritical()) {
    // Handle critical alert
}

// Get formatted created time
$timeAgo = $alert->created_ago; // "2 hours ago"
```

---

### PerformanceBenchmark Model

```php
use App\Models\PerformanceBenchmark;

// Get benchmarks for specific strain and age
$benchmarks = PerformanceBenchmark::forStrainAndAge($strainId, $ageWeek)->get();

// Get active benchmarks
$activeBenchmarks = PerformanceBenchmark::active()->get();

// Compare with benchmark
$benchmark = PerformanceBenchmark::where('strain_id', $strainId)
    ->where('age_week', $ageWeek)
    ->first();

$performance = [
    'weight_status' => $actualWeight >= $benchmark->target_weight ? 'good' : 'below',
    'fcr_status' => $actualFcr <= $benchmark->target_fcr ? 'good' : 'poor'
];
```

---

## 🎨 Livewire Component API

### SmartAnalytics Component

#### Public Properties

```php
// Current active tab
public $selectedTab = 'overview';

// Filter settings
public $filters = [
    'farm_id' => null,
    'coop_id' => null,
    'date_from' => null,
    'date_to' => null
];

// Data containers
public $insights = [];
public $chartData = [];
public $alerts = [];
```

#### Public Methods

```php
// Refresh all data
$this->call('refreshData');

// Change tab
$this->call('setTab', 'mortality');

// Apply filters
$this->set('filters.farm_id', 1);
$this->call('applyFilters');

// Resolve alert
$this->call('resolveAlert', $alertId, 'Resolution notes');

// Export data
$this->call('exportData', 'excel'); // or 'pdf'
```

#### Events

```php
// Listen to events in JavaScript
Livewire.on('dataUpdated', function(data) {
    updateCharts(data.chartData);
});

Livewire.on('alertResolved', function(alertId) {
    showSuccessMessage('Alert resolved successfully');
});

Livewire.on('exportReady', function(downloadUrl) {
    window.location.href = downloadUrl;
});
```

---

## ⚡ Console Commands API

### Calculate Daily Analytics

```bash
# Basic usage
php artisan analytics:daily-calculate

# With options
php artisan analytics:daily-calculate --date=2024-01-15
php artisan analytics:daily-calculate --days=30
php artisan analytics:daily-calculate --force
php artisan analytics:daily-calculate --date=2024-01-15 --force
```

**Options:**

-   `--date`: Specific date (format: Y-m-d)
-   `--days`: Number of days from today (default: 1)
-   `--force`: Force recalculation

**Return Codes:**

-   `0`: Success
-   `1`: Error occurred
-   `2`: No data to process

---

### Cleanup Analytics Alerts

```bash
# Basic cleanup (30 days retention)
php artisan analytics:cleanup-alerts

# Custom retention period
php artisan analytics:cleanup-alerts --days=60

# Dry run to preview
php artisan analytics:cleanup-alerts --dry-run
```

**Options:**

-   `--days`: Retention period (default: 30)
-   `--dry-run`: Preview without deleting

---

## 🗃️ Database Queries

### Common Query Examples

#### 1. Get Top Performing Coops

```php
$topCoops = DailyAnalytics::select([
        'livestock_id',
        DB::raw('AVG(efficiency_score) as avg_efficiency'),
        DB::raw('AVG(mortality_rate) as avg_mortality')
    ])
    ->with(['livestock.coop'])
    ->where('date', '>=', Carbon::now()->subDays(30))
    ->groupBy('livestock_id')
    ->having('avg_efficiency', '>', 80)
    ->orderBy('avg_efficiency', 'desc')
    ->limit(10)
    ->get();
```

#### 2. Get Mortality Trends

```php
$mortalityTrend = DailyAnalytics::select([
        'date',
        DB::raw('SUM(mortality_count) as daily_deaths'),
        DB::raw('AVG(mortality_rate) as avg_rate')
    ])
    ->where('date', '>=', Carbon::now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date')
    ->get();
```

#### 3. Get FCR Performance

```php
$fcrAnalysis = DailyAnalytics::select([
        'livestock_id',
        DB::raw('AVG(fcr) as avg_fcr'),
        DB::raw('MIN(fcr) as best_fcr'),
        DB::raw('MAX(fcr) as worst_fcr')
    ])
    ->with(['livestock.farm', 'livestock.coop'])
    ->where('date', '>=', Carbon::now()->subDays(30))
    ->whereNotNull('fcr')
    ->where('fcr', '>', 0)
    ->groupBy('livestock_id')
    ->orderBy('avg_fcr')
    ->get();
```

#### 4. Get Active Alerts by Severity

```php
$alertsSummary = AnalyticsAlert::select([
        'severity',
        DB::raw('COUNT(*) as count')
    ])
    ->where('is_resolved', false)
    ->groupBy('severity')
    ->pluck('count', 'severity');
```

---

## 💡 Examples

### Example 1: Daily Analytics Calculation

```php
use App\Services\AnalyticsService;
use Carbon\Carbon;

// Initialize service
$analyticsService = app(AnalyticsService::class);

// Calculate for specific date
$date = Carbon::parse('2024-01-15');
$results = $analyticsService->calculateDailyAnalyticsWithResults($date, true);

// Display results
echo "Analytics Calculation Results:\n";
echo "- Analytics created: {$results['analytics_created']}\n";
echo "- Alerts generated: {$results['alerts_created']}\n";
echo "- Processing time: {$results['processing_time']}s\n";

foreach ($results['insights'] as $insight) {
    echo "- {$insight}\n";
}
```

### Example 2: Getting Farm Performance

```php
use App\Services\AnalyticsService;

$analyticsService = app(AnalyticsService::class);

// Get insights for specific farm
$insights = $analyticsService->getSmartInsights([
    'farm_id' => 1,
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
]);

// Display overview
$overview = $insights['overview'];
echo "Farm Performance Summary:\n";
echo "- Total Livestock: {$overview['total_livestock']}\n";
echo "- Average Efficiency: {$overview['avg_efficiency_score']}%\n";
echo "- Mortality Rate: {$overview['avg_mortality_rate']}%\n";
echo "- Total Revenue: Rp " . number_format($overview['total_revenue']) . "\n";

// Display top performers
echo "\nTop Performing Coops:\n";
foreach ($insights['coop_rankings'] as $rank => $coop) {
    echo ($rank + 1) . ". {$coop['coop_name']} - {$coop['score']}%\n";
}
```

### Example 3: Alert Management

```php
use App\Models\AnalyticsAlert;

// Get unresolved critical alerts
$criticalAlerts = AnalyticsAlert::unresolved()
    ->bySeverity('critical')
    ->with(['livestock.coop'])
    ->get();

foreach ($criticalAlerts as $alert) {
    echo "Critical Alert: {$alert->title}\n";
    echo "Coop: {$alert->livestock->coop->nama}\n";
    echo "Description: {$alert->description}\n";
    echo "Recommendation: {$alert->recommendation}\n";
    echo "Created: {$alert->created_ago}\n\n";
}

// Resolve an alert
$alert = AnalyticsAlert::find(1);
$alert->resolve(auth()->id(), 'Implemented recommended actions');
```

### Example 4: Chart Data Generation

```php
// Get mortality trend data for chart
$mortalityData = DailyAnalytics::select([
        'date',
        DB::raw('AVG(mortality_rate) as rate')
    ])
    ->where('farm_id', 1)
    ->where('date', '>=', Carbon::now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date')
    ->get();

$chartData = [
    'labels' => $mortalityData->pluck('date')->map(fn($date) =>
        Carbon::parse($date)->format('M d')
    )->toArray(),
    'datasets' => [
        [
            'label' => 'Mortality Rate (%)',
            'data' => $mortalityData->pluck('rate')->toArray(),
            'borderColor' => 'rgb(220, 53, 69)',
            'backgroundColor' => 'rgba(220, 53, 69, 0.1)'
        ]
    ]
];

// Return for JavaScript chart
return response()->json($chartData);
```

### Example 5: Performance Benchmarking

```php
use App\Models\PerformanceBenchmark;
use App\Models\DailyAnalytics;

// Compare livestock performance with benchmark
$livestock = Livestock::find(1);
$ageWeeks = ceil($livestock->age_days / 7);

$benchmark = PerformanceBenchmark::where('strain_id', $livestock->strain_id)
    ->where('age_week', $ageWeeks)
    ->first();

$recent = DailyAnalytics::where('livestock_id', $livestock->id)
    ->latest('date')
    ->first();

if ($benchmark && $recent) {
    $performance = [
        'weight' => [
            'actual' => $recent->average_weight,
            'target' => $benchmark->target_weight,
            'status' => $recent->average_weight >= $benchmark->target_weight ? 'good' : 'below'
        ],
        'fcr' => [
            'actual' => $recent->fcr,
            'target' => $benchmark->target_fcr,
            'status' => $recent->fcr <= $benchmark->target_fcr ? 'good' : 'poor'
        ],
        'mortality' => [
            'actual' => $recent->mortality_rate,
            'target' => $benchmark->target_mortality_rate,
            'status' => $recent->mortality_rate <= $benchmark->target_mortality_rate ? 'good' : 'high'
        ]
    ];

    echo "Performance vs Benchmark:\n";
    foreach ($performance as $metric => $data) {
        echo "- {$metric}: {$data['actual']} (target: {$data['target']}) - {$data['status']}\n";
    }
}
```

---

## 🔗 Related Documentation

-   [SMART_ANALYTICS.md](./SMART_ANALYTICS.md) - Main documentation
-   [SMART_ANALYTICS_QUICK_START.md](./SMART_ANALYTICS_QUICK_START.md) - Quick setup guide
-   [SMART_ANALYTICS_TROUBLESHOOTING.md](./SMART_ANALYTICS_TROUBLESHOOTING.md) - Debugging guide

---

**API Version**: 1.0.0  
**Last Updated**: January 2025  
**Compatibility**: Laravel 10.x, PHP 8.1+
