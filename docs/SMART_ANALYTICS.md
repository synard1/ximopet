# Smart Analytics - Analisis Cerdas Peternakan

## 📋 Daftar Isi

-   [Overview](#overview)
-   [Fitur Utama](#fitur-utama)
-   [Arsitektur Sistem](#arsitektur-sistem)
-   [Database Schema](#database-schema)
-   [Instalasi & Setup](#instalasi--setup)
-   [Penggunaan](#penggunaan)
-   [API Reference](#api-reference)
-   [Console Commands](#console-commands)
-   [Troubleshooting](#troubleshooting)
-   [Changelog](#changelog)

## 🎯 Overview

Smart Analytics adalah sistem analisis cerdas yang dirancang untuk mengidentifikasi kandang dengan mortalitas tinggi, performa penjualan, dan metrik produksi berdasarkan bobot ayam dan faktor lainnya. Sistem ini menggunakan algoritma AI untuk memberikan insights yang dapat membantu dalam pengambilan keputusan manajemen peternakan.

### Tujuan Utama

-   **Deteksi Dini**: Mengidentifikasi masalah kesehatan dan performa kandang secara real-time
-   **Optimasi Produksi**: Memberikan rekomendasi untuk meningkatkan efisiensi produksi
-   **Analisis Prediktif**: Memprediksi tren dan potensi masalah di masa depan
-   **Dashboard Interaktif**: Visualisasi data yang mudah dipahami untuk pengambilan keputusan

## 🚀 Fitur Utama

### 1. Analisis Mortalitas

-   **Daily Mortality Tracking**: Pelacakan kematian harian per kandang
-   **Mortality Rate Calculation**: Perhitungan tingkat mortalitas dengan berbagai metrik
-   **Trend Analysis**: Analisis tren mortalitas dalam periode tertentu
-   **Alert System**: Sistem peringatan otomatis untuk mortalitas tinggi

### 2. Analisis Penjualan

-   **Sales Performance**: Analisis performa penjualan per kandang
-   **Revenue Tracking**: Pelacakan pendapatan dan profitabilitas
-   **Weight Analysis**: Analisis bobot rata-rata dan distribusi
-   **Price Optimization**: Rekomendasi optimasi harga berdasarkan data historis

### 3. Analisis Produksi

-   **Feed Conversion Ratio (FCR)**: Perhitungan efisiensi konversi pakan
-   **Daily Weight Gain**: Analisis pertambahan bobot harian
-   **Production Index**: Indeks produksi komprehensif
-   **Age Performance**: Analisis performa berdasarkan umur ternak

### 4. Sistem Peringatan Cerdas

-   **Multi-level Alerts**: Peringatan dengan tingkat keparahan berbeda
-   **Automated Recommendations**: Rekomendasi otomatis berdasarkan kondisi
-   **Alert Resolution**: Sistem resolusi dan follow-up peringatan
-   **Notification System**: Notifikasi real-time untuk stakeholder

### 5. Dashboard Interaktif

-   **Real-time Charts**: Grafik real-time dengan Chart.js
-   **Performance Rankings**: Ranking performa kandang
-   **Comparative Analysis**: Analisis perbandingan antar kandang
-   **Export Capabilities**: Ekspor data dan laporan

## 🏗️ Arsitektur Sistem

### Komponen Utama

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   Database      │
│   (Livewire)    │◄──►│   (Laravel)     │◄──►│   (MySQL)       │
│                 │    │                 │    │                 │
│ - Dashboard     │    │ - Services      │    │ - Analytics     │
│ - Charts        │    │ - Controllers   │    │ - Alerts        │
│ - Filters       │    │ - Commands      │    │ - Benchmarks    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └──────────────►│   Scheduler     │◄─────────────┘
                        │   (Cron Jobs)   │
                        │                 │
                        │ - Daily Calc    │
                        │ - Alerts Gen    │
                        │ - Data Cleanup  │
                        └─────────────────┘
```

### Layer Architecture

1. **Presentation Layer**

    - Livewire Components
    - Blade Templates
    - JavaScript (Chart.js)

2. **Business Logic Layer**

    - AnalyticsService
    - Alert Generation
    - Calculation Algorithms

3. **Data Access Layer**

    - Eloquent Models
    - Database Migrations
    - Seeders

4. **Infrastructure Layer**
    - Console Commands
    - Scheduled Tasks
    - Logging System

## 🗄️ Database Schema

### Tabel Utama

#### 1. daily_analytics

```sql
CREATE TABLE daily_analytics (
    id CHAR(36) PRIMARY KEY,
    date DATE NOT NULL,
    livestock_id CHAR(36) NOT NULL,
    farm_id CHAR(36) NOT NULL,
    coop_id CHAR(36) NOT NULL,

    -- Mortality Metrics
    mortality_count INT DEFAULT 0,
    mortality_rate DECIMAL(5,2) DEFAULT 0,
    cumulative_mortality INT DEFAULT 0,
    current_population INT DEFAULT 0,

    -- Sales Metrics
    sales_count INT DEFAULT 0,
    sales_weight DECIMAL(10,2) DEFAULT 0,
    sales_revenue DECIMAL(15,2) DEFAULT 0,

    -- Feed Metrics
    feed_consumption DECIMAL(10,2) DEFAULT 0,
    fcr DECIMAL(5,3) DEFAULT 0,

    -- Production Metrics
    average_weight DECIMAL(8,2) DEFAULT 0,
    daily_weight_gain DECIMAL(8,2) DEFAULT 0,
    age_days INT DEFAULT 0,
    production_index DECIMAL(8,2) DEFAULT 0,

    -- Efficiency Score
    efficiency_score DECIMAL(5,2) DEFAULT 0,

    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_date_livestock (date, livestock_id),
    INDEX idx_farm_date (farm_id, date),
    INDEX idx_coop_date (coop_id, date),
    INDEX idx_efficiency_score (efficiency_score),

    FOREIGN KEY (livestock_id) REFERENCES livestocks(id),
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (coop_id) REFERENCES coops(id)
);
```

#### 2. period_analytics

```sql
CREATE TABLE period_analytics (
    id CHAR(36) PRIMARY KEY,
    period_type ENUM('weekly', 'monthly') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    livestock_id CHAR(36) NOT NULL,
    farm_id CHAR(36) NOT NULL,
    coop_id CHAR(36) NOT NULL,

    -- Aggregated Metrics
    avg_mortality_rate DECIMAL(5,2) DEFAULT 0,
    total_mortality INT DEFAULT 0,
    avg_efficiency_score DECIMAL(5,2) DEFAULT 0,
    total_sales_revenue DECIMAL(15,2) DEFAULT 0,
    avg_fcr DECIMAL(5,3) DEFAULT 0,
    avg_daily_gain DECIMAL(8,2) DEFAULT 0,

    -- Performance Rankings
    mortality_rank INT DEFAULT 0,
    efficiency_rank INT DEFAULT 0,
    sales_rank INT DEFAULT 0,

    -- Profit Margins
    estimated_profit DECIMAL(15,2) DEFAULT 0,
    profit_margin DECIMAL(5,2) DEFAULT 0,

    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_period (period_type, period_start, period_end),
    INDEX idx_livestock_period (livestock_id, period_start),

    FOREIGN KEY (livestock_id) REFERENCES livestocks(id),
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (coop_id) REFERENCES coops(id)
);
```

#### 3. performance_benchmarks

```sql
CREATE TABLE performance_benchmarks (
    id CHAR(36) PRIMARY KEY,
    strain_id CHAR(36),
    age_week INT NOT NULL,

    -- Target Standards
    target_weight DECIMAL(8,2) NOT NULL,
    target_fcr DECIMAL(5,3) NOT NULL,
    target_mortality_rate DECIMAL(5,2) NOT NULL,
    target_daily_gain DECIMAL(8,2) NOT NULL,

    -- Acceptable Ranges
    weight_min DECIMAL(8,2),
    weight_max DECIMAL(8,2),
    fcr_min DECIMAL(5,3),
    fcr_max DECIMAL(5,3),
    mortality_max DECIMAL(5,2),

    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_strain_age (strain_id, age_week),

    FOREIGN KEY (strain_id) REFERENCES livestock_strains(id)
);
```

#### 4. analytics_alerts

```sql
CREATE TABLE analytics_alerts (
    id CHAR(36) PRIMARY KEY,
    livestock_id CHAR(36) NOT NULL,
    farm_id CHAR(36) NOT NULL,
    coop_id CHAR(36) NOT NULL,

    alert_type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    recommendation TEXT,

    metrics JSON,

    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolution_notes TEXT,

    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_livestock_unresolved (livestock_id, is_resolved),
    INDEX idx_severity_unresolved (severity, is_resolved),
    INDEX idx_alert_type (alert_type),

    FOREIGN KEY (livestock_id) REFERENCES livestocks(id),
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (coop_id) REFERENCES coops(id)
);
```

## 🛠️ Instalasi & Setup

### 1. Migrasi Database

```bash
# Jalankan migrasi untuk membuat tabel analytics
php artisan migrate

# Atau jalankan migrasi spesifik
php artisan migrate --path=database/migrations/2025_01_02_000000_create_analytics_tables.php
```

### 2. Seeder (Opsional)

```bash
# Jalankan seeder untuk data benchmark
php artisan db:seed --class=PerformanceBenchmarkSeeder
```

### 3. Konfigurasi Scheduler

Tambahkan ke `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Hitung analytics harian setiap hari jam 1 pagi
    $schedule->command('analytics:daily-calculate')
        ->dailyAt('01:00')
        ->withoutOverlapping()
        ->runInBackground();

    // Cleanup alerts lama setiap minggu
    $schedule->command('analytics:cleanup-alerts')
        ->weekly()
        ->sundays()
        ->at('02:00');
}
```

### 4. Setup Cron Job

```bash
# Tambahkan ke crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📖 Penggunaan

### 1. Akses Dashboard

Navigasi ke: `/report/smart-analytics`

### 2. Filter Data

```php
// Filter berdasarkan farm
$filters = ['farm_id' => 1];

// Filter berdasarkan rentang tanggal
$filters = [
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
];

// Filter kombinasi
$filters = [
    'farm_id' => 1,
    'coop_id' => 2,
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
];
```

### 3. Menggunakan Service

```php
use App\Services\AnalyticsService;

$analyticsService = app(AnalyticsService::class);

// Hitung analytics harian
$analyticsService->calculateDailyAnalytics('2024-01-15');

// Dapatkan insights
$insights = $analyticsService->getSmartInsights([
    'farm_id' => 1,
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
]);
```

### 4. Livewire Component

```php
// Di dalam Livewire component
public function refreshAnalytics()
{
    $this->analyticsService->calculateDailyAnalytics();
    $this->loadData();
    $this->dispatch('success', 'Analytics refreshed successfully');
}

public function resolveAlert($alertId)
{
    $alert = AnalyticsAlert::findOrFail($alertId);
    $alert->update([
        'is_resolved' => true,
        'resolved_at' => now(),
        'resolved_by' => auth()->id(),
        'resolution_notes' => $this->resolutionNotes
    ]);

    $this->dispatch('success', 'Alert resolved successfully');
}
```

## 🔌 API Reference

### Analytics Service Methods

#### `calculateDailyAnalytics($date = null): void`

Menghitung analytics harian untuk semua livestock aktif.

**Parameters:**

-   `$date` (string|Carbon|null): Tanggal untuk perhitungan (default: hari ini)

**Example:**

```php
$service->calculateDailyAnalytics('2024-01-15');
```

#### `calculateDailyAnalyticsWithResults(Carbon $date, bool $force = false): array`

Menghitung analytics harian dengan hasil detail untuk CLI.

**Parameters:**

-   `$date` (Carbon): Tanggal untuk perhitungan
-   `$force` (bool): Paksa perhitungan ulang meski data sudah ada

**Returns:**

```php
[
    'analytics_created' => 15,
    'alerts_created' => 3,
    'livestock_processed' => 20,
    'processing_time' => 2.45,
    'insights' => [
        'High mortality detected in Kandang A: 75 deaths',
        'Low efficiency in Kandang B: 45% efficiency score'
    ]
]
```

#### `getSmartInsights(array $filters = []): array`

Mendapatkan insights cerdas berdasarkan filter.

**Parameters:**

-   `$filters` (array): Filter untuk data

**Returns:**

```php
[
    'overview' => [...],
    'mortality_analysis' => [...],
    'sales_analysis' => [...],
    'production_analysis' => [...],
    'coop_rankings' => [...],
    'alerts' => [...],
    'trends' => [...]
]
```

### Model Scopes

#### DailyAnalytics Scopes

```php
// Filter berdasarkan farm
DailyAnalytics::byFarm($farmId)->get();

// Filter berdasarkan rentang tanggal
DailyAnalytics::dateRange($startDate, $endDate)->get();

// Filter berdasarkan efficiency score rendah
DailyAnalytics::lowEfficiency()->get();

// Filter berdasarkan mortalitas tinggi
DailyAnalytics::highMortality()->get();
```

#### AnalyticsAlert Scopes

```php
// Alert yang belum diselesaikan
AnalyticsAlert::unresolved()->get();

// Alert berdasarkan severity
AnalyticsAlert::bySeverity('critical')->get();

// Alert terbaru
AnalyticsAlert::recent()->get();
```

## ⚡ Console Commands

### 1. Calculate Daily Analytics

```bash
# Hitung analytics untuk 7 hari terakhir
php artisan analytics:daily-calculate

# Hitung analytics untuk tanggal spesifik
php artisan analytics:daily-calculate --date=2024-01-15

# Hitung analytics untuk 30 hari terakhir
php artisan analytics:daily-calculate --days=30

# Paksa perhitungan ulang
php artisan analytics:daily-calculate --force

# Kombinasi options
php artisan analytics:daily-calculate --date=2024-01-15 --force
```

**Output Example:**

```
🚀 Starting Daily Analytics Calculation...
📅 Calculating analytics for: 2024-01-15

+-------------------+-------+
| Metric            | Value |
+-------------------+-------+
| Analytics Created | 15    |
| Alerts Generated  | 3     |
| Livestock Processed| 20    |
| Processing Time   | 2.45 seconds |
+-------------------+-------+

🔍 Key Insights:
   • High mortality detected in Kandang A: 75 deaths
   • Low efficiency in Kandang B: 45% efficiency score

✅ Daily Analytics Calculation completed successfully!
```

### 2. Cleanup Old Alerts

```bash
# Cleanup alerts yang sudah resolved > 30 hari
php artisan analytics:cleanup-alerts

# Cleanup dengan custom days
php artisan analytics:cleanup-alerts --days=60
```

### 3. Generate Benchmark Data

```bash
# Generate benchmark data untuk strain tertentu
php artisan analytics:generate-benchmarks --strain=broiler

# Generate untuk semua strain
php artisan analytics:generate-benchmarks --all
```

## 🎨 Frontend Components

### 1. Livewire Component Structure

```php
class SmartAnalytics extends Component
{
    // Properties
    public $selectedTab = 'overview';
    public $filters = [];
    public $insights = [];

    // Methods
    public function mount() { ... }
    public function updatedFilters() { ... }
    public function refreshData() { ... }
    public function resolveAlert($alertId) { ... }
    public function exportData() { ... }
}
```

### 2. Chart.js Integration

```javascript
// Mortality Chart
const mortalityChart = new Chart(ctx, {
    type: "line",
    data: {
        labels: dates,
        datasets: [
            {
                label: "Mortality Rate (%)",
                data: mortalityData,
                borderColor: "rgb(220, 53, 69)",
                backgroundColor: "rgba(220, 53, 69, 0.1)",
                tension: 0.4,
            },
        ],
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: "Mortality Trend Analysis",
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: "Mortality Rate (%)",
                },
            },
        },
    },
});
```

### 3. Alert Cards

```html
<div class="alert alert-{{ $alert->severity }} alert-dismissible">
    <div class="d-flex align-items-center">
        <i class="ki-duotone ki-warning fs-2 me-3"></i>
        <div class="flex-grow-1">
            <h5 class="alert-heading">{{ $alert->title }}</h5>
            <p class="mb-2">{{ $alert->description }}</p>
            <small class="text-muted">{{ $alert->recommendation }}</small>
        </div>
        <button
            wire:click="resolveAlert({{ $alert->id }})"
            class="btn btn-sm btn-light-success"
        >
            Resolve
        </button>
    </div>
</div>
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Analytics Not Calculating

**Problem:** Daily analytics tidak terhitung otomatis

**Solution:**

```bash
# Check scheduler
php artisan schedule:list

# Run manually
php artisan analytics:daily-calculate --force

# Check logs
tail -f storage/logs/laravel.log
```

#### 2. Missing Data in Charts

**Problem:** Chart tidak menampilkan data

**Solution:**

```php
// Check if data exists
$analytics = DailyAnalytics::where('date', '>=', Carbon::now()->subDays(30))->get();
dd($analytics->count());

// Verify relationships
$livestock = Livestock::with(['farm', 'coop'])->first();
dd($livestock->farm, $livestock->coop);
```

#### 3. Performance Issues

**Problem:** Dashboard loading lambat

**Solution:**

```php
// Add database indexes
Schema::table('daily_analytics', function (Blueprint $table) {
    $table->index(['date', 'livestock_id']);
    $table->index(['farm_id', 'date']);
    $table->index('efficiency_score');
});

// Optimize queries
$analytics = DailyAnalytics::select(['date', 'mortality_rate', 'efficiency_score'])
    ->where('date', '>=', $startDate)
    ->orderBy('date')
    ->get();
```

#### 4. Alert Spam

**Problem:** Terlalu banyak alert yang sama

**Solution:**

```php
// Check existing alerts before creating
$existingAlert = AnalyticsAlert::where('livestock_id', $livestock->id)
    ->where('alert_type', $type)
    ->where('is_resolved', false)
    ->whereDate('created_at', '>=', Carbon::today()->subDays(3))
    ->first();

if (!$existingAlert) {
    // Create new alert
}
```

### Debug Commands

```bash
# Check analytics data
php artisan tinker
>>> DailyAnalytics::count()
>>> DailyAnalytics::latest()->first()

# Check alerts
>>> AnalyticsAlert::unresolved()->count()
>>> AnalyticsAlert::bySeverity('critical')->get()

# Check livestock data
>>> Livestock::where('status', 'active')->count()
>>> Livestock::with(['farm', 'coop'])->first()
```

### Log Analysis

```bash
# Filter analytics logs
grep "analytics" storage/logs/laravel.log

# Check error logs
grep "ERROR" storage/logs/laravel.log | grep "analytics"

# Monitor real-time
tail -f storage/logs/laravel.log | grep "analytics"
```

## 📊 Performance Optimization

### 1. Database Optimization

```sql
-- Add composite indexes
CREATE INDEX idx_analytics_farm_date ON daily_analytics(farm_id, date);
CREATE INDEX idx_analytics_efficiency ON daily_analytics(efficiency_score DESC);
CREATE INDEX idx_alerts_unresolved ON analytics_alerts(is_resolved, created_at);

-- Partition large tables (for high-volume data)
ALTER TABLE daily_analytics PARTITION BY RANGE (YEAR(date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### 2. Caching Strategy

```php
// Cache expensive calculations
$insights = Cache::remember("analytics_insights_{$farmId}_{$date}", 3600, function() use ($farmId, $date) {
    return $this->analyticsService->getSmartInsights(['farm_id' => $farmId, 'date' => $date]);
});

// Cache chart data
$chartData = Cache::remember("chart_data_{$filters_hash}", 1800, function() use ($filters) {
    return $this->getChartData($filters);
});
```

### 3. Query Optimization

```php
// Use eager loading
$analytics = DailyAnalytics::with(['livestock.farm', 'livestock.coop'])
    ->dateRange($startDate, $endDate)
    ->get();

// Use select to limit columns
$analytics = DailyAnalytics::select(['date', 'mortality_rate', 'efficiency_score', 'livestock_id'])
    ->where('farm_id', $farmId)
    ->get();

// Use chunking for large datasets
DailyAnalytics::chunk(1000, function($analytics) {
    foreach ($analytics as $analytic) {
        // Process each record
    }
});
```

## 🔄 Changelog

### Version 1.0.0 (2024-01-15)

-   ✅ Initial release
-   ✅ Daily analytics calculation
-   ✅ Smart alerts system
-   ✅ Interactive dashboard
-   ✅ Console commands
-   ✅ Performance benchmarks

### Version 1.1.0 (Planned)

-   🔄 Machine learning predictions
-   🔄 Advanced trend analysis
-   🔄 Mobile responsive improvements
-   🔄 Export to Excel/PDF
-   🔄 Email notifications
-   🔄 API endpoints for mobile app

### Version 1.2.0 (Planned)

-   🔄 Real-time WebSocket updates
-   🔄 Advanced filtering options
-   🔄 Custom dashboard widgets
-   🔄 Integration with IoT sensors
-   🔄 Multi-language support

## 📞 Support

Untuk bantuan teknis atau pertanyaan:

1. **Documentation**: Baca dokumentasi lengkap di `/docs`
2. **Logs**: Check application logs di `storage/logs/laravel.log`
3. **Debug**: Gunakan `php artisan tinker` untuk debugging
4. **Issues**: Report bugs melalui issue tracker

## 📄 License

Smart Analytics adalah bagian dari sistem manajemen peternakan dan mengikuti lisensi yang sama dengan aplikasi utama.

---

**Dibuat dengan ❤️ untuk optimasi manajemen peternakan modern**
