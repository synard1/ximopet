# 📊 Comprehensive Farm Data Seeder

## 📋 **Overview**

Seeder komprehensif untuk membuat dummy data lengkap dari awal hingga akhir siklus peternakan, mulai dari pembelian DOC, pembelian pakan, hingga recording harian. Data ini dirancang khusus untuk mendukung **Smart Analytics Report** dengan data yang realistis.

---

## 🎯 **Features**

### **1. Complete Livestock Cycle**

-   ✅ **Livestock Purchase**: Pembelian DOC dengan data vendor dan ekspedisi
-   ✅ **Feed Purchase**: Pembelian pakan dengan berbagai jenis (Starter, Grower, Finisher)
-   ✅ **Supply Purchase**: Pembelian OVK (Vitamin, Antibiotik, Vaksin)
-   ✅ **Daily Recordings**: Recording harian dengan data realistis selama 45 hari

### **2. Realistic Data Generation**

-   ✅ **Mortality Curve**: Tingkat kematian realistis berdasarkan umur ayam
-   ✅ **Weight Gain**: Kurva pertambahan berat yang realistis
-   ✅ **Feed Consumption**: Konsumsi pakan berdasarkan berat dan umur
-   ✅ **Performance Metrics**: FCR, IP, dan metrik performa lainnya

### **3. Comprehensive Logging**

-   ✅ **Detailed Logs**: Setiap proses dicatat dengan detail lengkap
-   ✅ **Progress Tracking**: Monitoring progress setiap 7 hari
-   ✅ **Error Handling**: Transaction rollback jika ada error
-   ✅ **Audit Trail**: Metadata lengkap untuk audit

---

## 🚀 **Installation & Usage**

### **1. Run Seeder**

```bash
# Run seeder individually
php artisan db:seed --class=ComprehensiveFarmDataSeeder

# Or include in DatabaseSeeder
php artisan db:seed
```

### **2. Prerequisites**

Pastikan data dasar sudah ada:

-   ✅ Users (minimal 1 admin user)
-   ✅ Units (Gram, Kg, Karung, Botol)
-   ✅ Feed categories
-   ✅ Basic farm infrastructure

### **3. Expected Runtime**

```
⏱️ Estimated Time: 2-5 minutes
📊 Data Volume:
  - 6 Livestock batches
  - 18 Feed purchases
  - 18 Supply purchases
  - ~270 Daily recordings
  - ~50 Depletion records
  - ~270 Feed usage records
```

---

## 📦 **Generated Data Structure**

### **1. Basic Infrastructure**

```php
Farms (3) → Coops (6) → Suppliers (3)
├── Farm Demo 1 → Kandang A, Kandang B
├── Farm Demo 2 → Kandang A, Kandang B
└── Farm Demo 3 → Kandang A, Kandang B
```

### **2. Livestock Purchases**

```php
Per Farm-Coop Combination:
├── LivestockPurchase (invoice, vendor, expedition)
├── LivestockPurchaseItem (quantity: 4000-6000, price: 4500/ekor)
├── Livestock (master batch record)
├── LivestockBatch (detailed batch info)
└── CurrentLivestock (current population status)
```

### **3. Feed Purchases**

```php
Per Livestock:
├── FeedPurchaseBatch (supplier invoice)
├── FeedPurchase × 3 (Starter, Grower, Finisher)
├── FeedStock (FIFO inventory tracking)
└── CurrentFeed (current feed status)
```

### **4. Supply Purchases**

```php
Per Livestock:
├── SupplyPurchaseBatch (OVK supplier)
├── SupplyPurchase × 3 (Vitamin, Antibiotik, Vaksin)
├── SupplyStock (inventory tracking)
└── CurrentSupply (current supply status)
```

### **5. Daily Recordings (45 days)**

```php
Per Day Per Livestock:
├── Recording (comprehensive daily data)
├── LivestockDepletion (mortality/culling)
├── FeedUsage (total daily consumption)
└── FeedUsageDetail (per feed type usage)
```

---

## 📈 **Realistic Algorithms**

### **1. Mortality Rate Curve**

```php
Week 1 (1-7 days):   0.3% per day
Week 2 (8-14 days):  0.15% per day
Week 3 (15-21 days): 0.1% per day
Week 4 (22-28 days): 0.05% per day
Week 5+ (29+ days):  0.02% per day
```

### **2. Weight Gain Curve**

```php
Week 1: 15g/day  (45g → 150g)
Week 2: 35g/day  (150g → 395g)
Week 3: 55g/day  (395g → 780g)
Week 4: 65g/day  (780g → 1235g)
Week 5: 70g/day  (1235g → 1725g)
Week 6: 50g/day  (1725g → 2075g)
```

### **3. Feed Consumption**

```php
Base Consumption = Body Weight × 8%
Age Multiplier = 1 + (Age × 0.015)
Daily Feed = Base × Age Multiplier
Minimum = 10g/bird/day
```

### **4. Performance Calculations**

```php
FCR = Total Feed Consumed / Total Weight Gain
IP = (Liveability × Final Weight × 100) / (Age × FCR)
Mortality Rate = (Total Deaths / Initial Population) × 100
```

---

## 🔍 **Data Quality Features**

### **1. Realistic Variations**

-   ✅ **Random Quantities**: Purchase quantities vary realistically
-   ✅ **Performance Variations**: Different performance per farm/coop
-   ✅ **Seasonal Effects**: Mortality and growth variations
-   ✅ **Market Prices**: Realistic pricing for all items

### **2. Data Integrity**

-   ✅ **Foreign Key Constraints**: All relations properly maintained
-   ✅ **Stock Tracking**: FIFO inventory management
-   ✅ **Balance Validation**: Stock in/out balance maintained
-   ✅ **Date Consistency**: Chronological data flow

### **3. Smart Analytics Ready**

-   ✅ **Performance Metrics**: FCR, IP, mortality rates calculated
-   ✅ **Trend Data**: 45-day historical performance data
-   ✅ **Comparative Analysis**: Multiple farms/coops for comparison
-   ✅ **Alert Triggers**: High mortality, poor FCR scenarios included

---

## 📊 **Sample Generated Data**

### **Recording Example**

```json
{
    "livestock_id": "uuid",
    "tanggal": "2024-11-15",
    "age": 25,
    "stock_awal": 4850,
    "stock_akhir": 4847,
    "berat_semalam": 1.235,
    "berat_hari_ini": 1.3,
    "kenaikan_berat": 0.065,
    "pakan_harian": 502.44,
    "payload": {
        "mortality": 3,
        "culling": 0,
        "fcr": 1.45,
        "age_days": 25,
        "population": 4847,
        "version": "2.0",
        "recorded_by": {
            "id": 1,
            "name": "Admin User",
            "role": "System Seeder"
        }
    }
}
```

### **Performance Summary After 45 Days**

```php
Initial Population: 5000 birds
Final Population: 4750 birds (95% liveability)
Final Weight: 2.1 kg/bird
Total Feed: 12,500 kg
FCR: 1.65
IP: 280
Mortality Rate: 5%
```

---

## 🛠️ **Customization Options**

### **1. Data Volume**

```php
// Modify in createLivestockPurchases()
$quantity = rand(4000, 6000); // Adjust flock size

// Modify recording period
$endDate = Carbon::now()->subDays(1); // Adjust period length
```

### **2. Performance Parameters**

```php
// Modify mortality rates
private function calculateMortalityRate($age) {
    // Adjust mortality percentages
}

// Modify weight gain
private function calculateWeightGain($age) {
    // Adjust growth rates
}
```

### **3. Farm Count**

```php
// Modify in createBasicData()
for ($i = $this->farms->count(); $i < 5; $i++) { // Change from 3 to 5
    // Creates more farms
}
```

---

## 🔧 **Troubleshooting**

### **Common Issues**

1. **Foreign Key Errors**

    ```bash
    # Ensure all prerequisite data exists
    php artisan db:seed --class=BasicDataSeeder
    ```

2. **Memory Limits**

    ```bash
    # Increase PHP memory limit
    php -d memory_limit=512M artisan db:seed --class=ComprehensiveFarmDataSeeder
    ```

3. **Transaction Timeouts**
    ```php
    // Check database connection timeout settings
    // Reduce batch size if needed
    ```

### **Verification Commands**

```bash
# Check generated data
php artisan tinker

# Verify livestock count
>>> App\Models\Livestock::count()

# Verify recordings
>>> App\Models\Recording::count()

# Check date range
>>> App\Models\Recording::select('tanggal')->orderBy('tanggal')->first()
>>> App\Models\Recording::select('tanggal')->orderBy('tanggal', 'desc')->first()
```

---

## 📝 **Logging & Monitoring**

### **Log Levels**

```php
🚀 INFO: Seeder start/completion
📦 INFO: Basic data creation
🐔 INFO: Livestock purchases
🌾 INFO: Feed purchases
💊 INFO: Supply purchases
📊 INFO: Daily recordings start
📈 INFO: Individual livestock progress
📅 INFO: Weekly progress updates
✅ INFO: Completion summaries
```

### **Log Examples**

```
[2024-12-09 10:00:00] local.INFO: 🚀 Starting Comprehensive Farm Data Seeder
[2024-12-09 10:00:05] local.INFO: ✅ Basic data created {"farms":3,"coops":6,"suppliers":3,"feeds":3}
[2024-12-09 10:00:30] local.INFO: ✅ Livestock purchase created {"farm":"Farm Demo 1","coop":"Kandang A","livestock_id":"uuid","quantity":5000}
[2024-12-09 10:02:15] local.INFO: 📅 Recording progress {"livestock":"Batch-Farm Demo 1-Kandang A","age":14,"population":4920,"weight":0.395,"total_feed":2580,"mortality_total":80}
[2024-12-09 10:04:50] local.INFO: ✅ Comprehensive Farm Data Seeder completed successfully
```

---

## 🎯 **Smart Analytics Integration**

### **Ready-to-Use Metrics**

-   ✅ **Mortality Analysis**: High mortality periods identified
-   ✅ **Performance Comparison**: Farm/coop performance ranking
-   ✅ **Feed Efficiency**: FCR trends and optimization
-   ✅ **Growth Tracking**: Weight gain consistency
-   ✅ **Cost Analysis**: Feed cost per kg gain

### **Dashboard KPIs**

-   ✅ **Current Performance**: Real-time metrics
-   ✅ **Trend Analysis**: 45-day performance trends
-   ✅ **Benchmark Comparison**: Industry standard comparison
-   ✅ **Alert System**: Performance threshold alerts

---

## 🔄 **Maintenance**

### **Re-running Seeder**

```bash
# Clear existing data first (optional)
php artisan migrate:fresh

# Run fresh seeder
php artisan db:seed --class=ComprehensiveFarmDataSeeder
```

### **Partial Updates**

```php
// Modify seeder to run only specific sections
public function run() {
    // Comment out unwanted sections
    // $this->createBasicData();
    $this->createDailyRecordings(); // Only run recordings
}
```

---

## 📞 **Support**

-   **Documentation**: `/docs/COMPREHENSIVE_DATA_SEEDER.md`
-   **Logs**: `storage/logs/laravel.log`
-   **Debug**: Use `Log::info()` statements throughout seeder
-   **Performance**: Monitor with `php artisan telescope:work`

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: Laravel 10.x, MySQL 8.x  
**Estimated Runtime**: 2-5 minutes  
**Generated Records**: ~600+ records across all tables
