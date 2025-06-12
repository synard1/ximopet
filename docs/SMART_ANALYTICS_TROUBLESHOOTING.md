# Smart Analytics - Troubleshooting Guide

## 📋 Daftar Isi

-   [Overview](#overview)
-   [Common Issues](#common-issues)
-   [Installation Problems](#installation-problems)
-   [Data Issues](#data-issues)
-   [Performance Problems](#performance-problems)
-   [UI/Frontend Issues](#uifrontend-issues)
-   [Debug Tools](#debug-tools)
-   [Log Analysis](#log-analysis)
-   [FAQ](#faq)

## 🎯 Overview

Panduan troubleshooting ini membantu mendiagnosis dan mengatasi masalah umum pada sistem Smart Analytics. Ikuti langkah-langkah secara berurutan untuk resolusi yang efektif.

---

## 🚨 Common Issues

### 1. Analytics Data Not Calculating

**Symptoms:**

-   Dashboard menampilkan data kosong
-   Command `analytics:daily-calculate` tidak menghasilkan data
-   Chart tidak menampilkan informasi

**Diagnosis:**

```bash
# Check if livestock data exists
php artisan tinker --execute="
    echo 'Livestock count: ' . \App\Models\Livestock::count() . PHP_EOL;
    echo 'Active livestock: ' . \App\Models\Livestock::where('status', 'active')->count() . PHP_EOL;
"

# Check analytics data
php artisan tinker --execute="
    echo 'Daily analytics count: ' . \App\Models\DailyAnalytics::count() . PHP_EOL;
    echo 'Recent analytics: ' . \App\Models\DailyAnalytics::where('date', '>=', now()->subDays(7))->count() . PHP_EOL;
"
```

**Solutions:**

#### A. Missing Source Data

```bash
# Check required source tables
php artisan tinker --execute="
    echo 'LivestockDepletion: ' . \App\Models\LivestockDepletion::count() . PHP_EOL;
    echo 'FeedUsage: ' . \App\Models\FeedUsage::count() . PHP_EOL;
    echo 'SalesTransaction: ' . \App\Models\SalesTransaction::count() . PHP_EOL;
"
```

If any count is 0, you need to populate source data first.

#### B. Force Recalculation

```bash
# Force calculate for last 7 days
php artisan analytics:daily-calculate --days=7 --force
```

#### C. Check Field Mappings

```php
// Verify field names in your database match the service expectations
Schema::hasColumn('livestock_depletions', 'tanggal'); // should be true
Schema::hasColumn('livestock_depletions', 'jenis');   // should be true
Schema::hasColumn('feed_usages', 'total_quantity');   // should be true
```

---

### 2. Charts Not Displaying

**Symptoms:**

-   Blank chart areas
-   JavaScript errors in console
-   Chart.js not loading

**Diagnosis:**

```javascript
// Check in browser console
console.log("Chart.js loaded:", typeof Chart !== "undefined");
console.log("Livewire loaded:", typeof Livewire !== "undefined");
```

**Solutions:**

#### A. Check JavaScript Dependencies

```html
<!-- Ensure these are loaded in your layout -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@livewireScripts
```

#### B. Verify Chart Data Format

```php
// In Livewire component, check data format
public function getChartData()
{
    $data = [
        'labels' => ['Jan', 'Feb', 'Mar'],
        'datasets' => [[
            'label' => 'Mortality',
            'data' => [10, 15, 12],
            'borderColor' => '#dc3545'
        ]]
    ];

    \Log::info('Chart data:', $data); // Debug log
    return $data;
}
```

#### C. Common Chart.js Errors

```javascript
// Fix: Canvas element not found
if (document.getElementById('mortalityChart')) {
    const ctx = document.getElementById('mortalityChart').getContext('2d');
    // Create chart...
}

// Fix: Data format issues
const data = @json($chartData); // Proper JSON encoding
```

---

### 3. Alerts Not Generating

**Symptoms:**

-   No alerts despite high mortality/poor performance
-   Alert count always zero
-   Alerts table empty

**Diagnosis:**

```bash
# Check alert generation manually
php artisan tinker --execute="
    \$service = app(\App\Services\AnalyticsService::class);
    \$analytics = \App\Models\DailyAnalytics::latest()->first();
    if (\$analytics) {
        echo 'Latest analytics: ' . \$analytics->id . PHP_EOL;
        echo 'Mortality rate: ' . \$analytics->mortality_rate . PHP_EOL;
        echo 'Efficiency score: ' . \$analytics->efficiency_score . PHP_EOL;
    }
"
```

**Solutions:**

#### A. Check Alert Thresholds

```php
// In AnalyticsService, verify thresholds
private function generateAlerts($analytic)
{
    // Debug current values
    \Log::info('Checking alerts for analytics', [
        'mortality_count' => $analytic->mortality_count,
        'mortality_rate' => $analytic->mortality_rate,
        'efficiency_score' => $analytic->efficiency_score
    ]);

    // Critical mortality (adjust threshold if needed)
    if ($analytic->mortality_count > 100) {
        // Generate alert...
    }
}
```

#### B. Force Alert Generation

```bash
# Recalculate with force to trigger alerts
php artisan analytics:daily-calculate --force --days=1
```

---

### 4. Performance Issues (Slow Loading)

**Symptoms:**

-   Dashboard takes >5 seconds to load
-   Timeouts on data calculation
-   High database query count

**Diagnosis:**

```bash
# Enable query log
php artisan tinker --execute="
    \DB::enableQueryLog();
    // Run your problematic code
    \$queries = \DB::getQueryLog();
    echo 'Query count: ' . count(\$queries) . PHP_EOL;
    foreach (\$queries as \$query) {
        echo \$query['query'] . PHP_EOL;
    }
"
```

**Solutions:**

#### A. Add Database Indexes

```sql
-- Add missing indexes
CREATE INDEX idx_daily_analytics_farm_date ON daily_analytics(farm_id, date);
CREATE INDEX idx_daily_analytics_efficiency ON daily_analytics(efficiency_score);
CREATE INDEX idx_livestock_depletion_livestock_date ON livestock_depletions(livestock_id, tanggal);
```

#### B. Optimize Queries

```php
// Use eager loading
$analytics = DailyAnalytics::with(['livestock.farm', 'livestock.coop'])
    ->dateRange($startDate, $endDate)
    ->get();

// Select only needed columns
$analytics = DailyAnalytics::select(['date', 'mortality_rate', 'efficiency_score'])
    ->where('farm_id', $farmId)
    ->get();
```

#### C. Implement Caching

```php
// Cache expensive calculations
$insights = Cache::remember("analytics_insights_{$farmId}_{$date}", 3600, function() use ($farmId, $date) {
    return $this->analyticsService->getSmartInsights(['farm_id' => $farmId, 'date' => $date]);
});
```

---

## ⚙️ Installation Problems

### Migration Issues

**Error: Migration fails**

```bash
# Check migration status
php artisan migrate:status

# Reset and re-run
php artisan migrate:reset
php artisan migrate

# Or run specific migration
php artisan migrate --path=database/migrations/2025_01_02_000000_create_analytics_tables.php
```

### Seeder Issues

**Error: Class 'PerformanceBenchmarkSeeder' not found**

```bash
# Regenerate autoload
composer dump-autoload

# Run seeder with full path
php artisan db:seed --class=Database\\Seeders\\PerformanceBenchmarkSeeder
```

### Missing Models

**Error: Class 'DailyAnalytics' not found**

```bash
# Check if models exist
ls -la app/Models/DailyAnalytics.php
ls -la app/Models/AnalyticsAlert.php

# Regenerate autoload
composer dump-autoload
```

---

## 📊 Data Issues

### Incorrect Calculations

**Problem: FCR calculation wrong**

```php
// Debug FCR calculation
php artisan tinker --execute="
    \$livestock = \App\Models\Livestock::first();
    \$date = '2024-01-15';

    \$feedConsumption = \App\Models\FeedUsage::where('livestock_id', \$livestock->id)
        ->whereDate('tanggal', \$date)
        ->sum('total_quantity');

    \$weightGain = 45.5; // Your calculation
    \$fcr = \$feedConsumption > 0 && \$weightGain > 0 ? \$feedConsumption / \$weightGain : 0;

    echo 'Feed consumption: ' . \$feedConsumption . PHP_EOL;
    echo 'Weight gain: ' . \$weightGain . PHP_EOL;
    echo 'FCR: ' . \$fcr . PHP_EOL;
"
```

### Missing Relationships

**Problem: Farm/Coop data not showing**

```php
// Check relationships
php artisan tinker --execute="
    \$analytic = \App\Models\DailyAnalytics::first();
    if (\$analytic && \$analytic->livestock) {
        echo 'Livestock: ' . \$analytic->livestock->id . PHP_EOL;
        echo 'Farm: ' . (\$analytic->livestock->farm ? \$analytic->livestock->farm->nama : 'NULL') . PHP_EOL;
        echo 'Coop: ' . (\$analytic->livestock->coop ? \$analytic->livestock->coop->nama : 'NULL') . PHP_EOL;
    }
"
```

### Date Format Issues

**Problem: Date calculations inconsistent**

```php
// Standardize date format
$date = Carbon::parse($inputDate)->format('Y-m-d');

// Use consistent timezone
config(['app.timezone' => 'Asia/Jakarta']);
```

---

## 🎨 UI/Frontend Issues

### Livewire Component Problems

**Error: Component not found**

```bash
# Check component registration
php artisan livewire:list | grep Smart

# Clear Livewire cache
php artisan livewire:clear
```

### Styling Issues

**Problem: Bootstrap classes not applying**

```html
<!-- Check if Bootstrap is loaded -->
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
/>

<!-- Verify class usage -->
<div class="alert alert-danger">Test Bootstrap</div>
```

### JavaScript Errors

**Error: Livewire events not working**

```javascript
// Check Livewire initialization
document.addEventListener("livewire:init", function () {
    console.log("Livewire initialized");

    Livewire.on("dataUpdated", function (data) {
        console.log("Data updated:", data);
    });
});
```

---

## 🔧 Debug Tools

### Laravel Tinker Commands

```bash
# Check service availability
php artisan tinker --execute="
    \$service = app(\App\Services\AnalyticsService::class);
    echo 'Service available: ' . (is_object(\$service) ? 'Yes' : 'No') . PHP_EOL;
"

# Test calculation for one livestock
php artisan tinker --execute="
    \$livestock = \App\Models\Livestock::first();
    if (\$livestock) {
        \$service = app(\App\Services\AnalyticsService::class);
        \$result = \$service->calculateDailyAnalyticsWithResults(now(), true);
        print_r(\$result);
    }
"

# Check alert counts
php artisan tinker --execute="
    echo 'Total alerts: ' . \App\Models\AnalyticsAlert::count() . PHP_EOL;
    echo 'Unresolved: ' . \App\Models\AnalyticsAlert::unresolved()->count() . PHP_EOL;
    echo 'Critical: ' . \App\Models\AnalyticsAlert::bySeverity('critical')->count() . PHP_EOL;
"
```

### Database Queries

```sql
-- Check data distribution
SELECT
    DATE(date) as analytics_date,
    COUNT(*) as record_count,
    AVG(efficiency_score) as avg_efficiency
FROM daily_analytics
WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(date)
ORDER BY analytics_date DESC;

-- Find problematic records
SELECT * FROM daily_analytics
WHERE efficiency_score = 0
   OR mortality_rate > 50
   OR fcr > 10
LIMIT 10;

-- Check alert generation
SELECT
    severity,
    COUNT(*) as count,
    MAX(created_at) as latest
FROM analytics_alerts
GROUP BY severity;
```

### Console Command Debug

```bash
# Run with verbose output
php artisan analytics:daily-calculate --days=1 --force -vvv

# Check command registration
php artisan list | grep analytics
```

---

## 📝 Log Analysis

### Enable Debug Logging

```php
// In AnalyticsService, add logging
\Log::info('Starting analytics calculation', [
    'date' => $date,
    'livestock_count' => $livestockQuery->count()
]);

\Log::info('Analytics calculated', [
    'livestock_id' => $livestock->id,
    'mortality_count' => $mortalityCount,
    'efficiency_score' => $efficiencyScore
]);
```

### Log File Locations

```bash
# Main Laravel log
tail -f storage/logs/laravel.log

# Filter analytics logs
grep "analytics" storage/logs/laravel.log | tail -20

# Filter errors only
grep "ERROR.*analytics" storage/logs/laravel.log
```

### Common Log Patterns

```bash
# Successful calculation
grep "Analytics calculated" storage/logs/laravel.log

# Errors during calculation
grep "ERROR.*AnalyticsService" storage/logs/laravel.log

# Performance issues
grep "Query took longer" storage/logs/laravel.log
```

---

## ❓ FAQ

### Q: Why is efficiency score always 0?

**A:** Check if all required metrics are being calculated:

```php
// All these should have values > 0
- mortality_rate (should be >= 0)
- fcr (should be > 0)
- daily_weight_gain (should be > 0)
- production_index (should be > 0)
```

### Q: Charts show "No data available"?

**A:** Verify data exists for selected date range:

```bash
php artisan tinker --execute="
    \$count = \App\Models\DailyAnalytics::whereBetween('date', ['2024-01-01', '2024-01-31'])->count();
    echo 'Records in January: ' . \$count . PHP_EOL;
"
```

### Q: Command runs but creates no records?

**A:** Check if livestock are properly configured:

```bash
php artisan tinker --execute="
    \$livestock = \App\Models\Livestock::whereNull('farm_id')->count();
    echo 'Livestock without farm: ' . \$livestock . PHP_EOL;

    \$livestock = \App\Models\Livestock::whereNull('coop_id')->count();
    echo 'Livestock without coop: ' . \$livestock . PHP_EOL;
"
```

### Q: Alerts not showing in dashboard?

**A:** Check alert query in Livewire component:

```php
public function loadAlerts()
{
    $this->alerts = AnalyticsAlert::unresolved()
        ->with(['livestock.coop'])
        ->latest()
        ->limit(10)
        ->get();

    \Log::info('Loaded alerts count: ' . $this->alerts->count());
}
```

### Q: Performance very slow on large datasets?

**A:** Implement pagination and optimize queries:

```php
// Use pagination
$analytics = DailyAnalytics::dateRange($startDate, $endDate)
    ->paginate(100);

// Optimize with raw queries for aggregations
$summary = DB::table('daily_analytics')
    ->selectRaw('AVG(efficiency_score) as avg_efficiency, COUNT(*) as total')
    ->where('date', '>=', $startDate)
    ->first();
```

---

## 🆘 Emergency Fixes

### Reset Everything

```bash
# Nuclear option - reset all analytics data
php artisan migrate:refresh --path=database/migrations/2025_01_02_000000_create_analytics_tables.php
php artisan db:seed --class=PerformanceBenchmarkSeeder
php artisan analytics:daily-calculate --days=7 --force
```

### Quick Data Check

```bash
# One-liner to check system health
php artisan tinker --execute="
    echo 'System Health Check:' . PHP_EOL;
    echo '- Livestock: ' . \App\Models\Livestock::count() . PHP_EOL;
    echo '- Analytics: ' . \App\Models\DailyAnalytics::count() . PHP_EOL;
    echo '- Alerts: ' . \App\Models\AnalyticsAlert::count() . PHP_EOL;
    echo '- Recent analytics: ' . \App\Models\DailyAnalytics::where('date', '>=', now()->subDays(3))->count() . PHP_EOL;
"
```

### Force Refresh Dashboard

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

---

## 📞 Getting Help

1. **Check Logs**: Always start with `storage/logs/laravel.log`
2. **Use Tinker**: Debug data issues with `php artisan tinker`
3. **Test Commands**: Run analytics commands manually with verbose output
4. **Check Database**: Verify data integrity with SQL queries
5. **Review Documentation**: Refer to [SMART_ANALYTICS.md](./SMART_ANALYTICS.md) for implementation details

---

**Last Updated**: January 2025  
**Troubleshooting Version**: 1.0.0
