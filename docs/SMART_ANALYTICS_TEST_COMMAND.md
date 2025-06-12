# Smart Analytics Test Command Documentation

## Deskripsi

Command `analytics:test` adalah tool comprehensive untuk menguji semua aspek Smart Analytics dashboard, termasuk pengambilan data, perhitungan analytics, performance, dan data integrity.

## Penggunaan

### Basic Command

```bash
php artisan analytics:test
```

### Command Options

#### `--calculate`

Menjalankan kalkulasi daily analytics sebelum testing

```bash
php artisan analytics:test --calculate
```

#### `--detailed`

Menampilkan output detail untuk debugging

```bash
php artisan analytics:test --detailed
```

#### `--farm=<farm_id>`

Test dengan filter farm tertentu

```bash
php artisan analytics:test --farm=9f1c7f5e-6579-4a8d-84c5-123456789
```

#### `--coop=<coop_id>`

Test dengan filter coop tertentu

```bash
php artisan analytics:test --coop=9f1c7f5e-6744-47fc-8986-c12be337b8a1
```

#### `--livestock=<livestock_id>`

Test dengan filter livestock tertentu

```bash
php artisan analytics:test --livestock=9f1c7f64-9dcd-4efe-91c7-3e463c6df03c
```

#### `--list-livestock`

Menampilkan daftar livestock yang tersedia dan keluar

```bash
php artisan analytics:test --list-livestock
```

#### Selective Testing Options

Test hanya komponen analysis tertentu:

```bash
# Test hanya mortality analysis
php artisan analytics:test --only-mortality

# Test hanya production dan rankings
php artisan analytics:test --only-production --only-rankings

# Test overview data saja
php artisan analytics:test --only-overview

# Test trends data saja
php artisan analytics:test --only-trends

# Test alerts saja
php artisan analytics:test --only-alerts

# Test sales analysis saja
php artisan analytics:test --only-sales
```

#### `--date-from=<date>` & `--date-to=<date>`

Test dengan custom date range

```bash
php artisan analytics:test --date-from=2025-05-10 --date-to=2025-06-09
```

#### Combined Options

```bash
# Full test dengan calculation dan detail
php artisan analytics:test --calculate --detailed --farm=9f1c7f5e-6579-4a8d-84c5-123456789

# Test livestock tertentu dengan mortality analysis saja
php artisan analytics:test --livestock=9f1c7f64-9dcd-4efe-91c7-3e463c6df03c --only-mortality --detailed

# Test selective analysis dengan custom date range
php artisan analytics:test --only-production --only-rankings --date-from=2025-05-01 --date-to=2025-06-01
```

## Test Coverage

### 1. Testing Scope

Menampilkan semua opsi yang dipilih untuk transparansi testing:

-   Farm ID, Coop ID, Livestock ID yang dipilih
-   Date range yang digunakan
-   Selective testing options yang aktif
-   Action options seperti calculate dan detailed

### 2. Database Overview

-   Menampilkan statistik database (Farms, Coops, Livestock, DailyAnalytics records)
-   Menampilkan date range data yang tersedia
-   Menampilkan coops dan livestock yang memiliki analytics data
-   Detail filter yang dipilih (farm, coop, livestock information)

### 3. Daily Analytics Calculation (dengan --calculate)

-   Menjalankan full calculation dari analytics service
-   Melaporkan execution time
-   Menampilkan jumlah analytics created dan alerts generated

### 4. Filter Scenarios Testing

Command menguji skenario filter berdasarkan opsi yang dipilih:

#### Custom Filters (jika --farm, --coop, atau --livestock dipilih)

-   Filter: sesuai parameter yang diberikan
-   Test untuk kondisi filter spesifik yang dipilih

#### Default Scenarios (jika tidak ada filter spesifik)

##### a. All Data

-   Filter: semua farm, semua coop, semua livestock, full date range
-   Test untuk kondisi load penuh

##### b. Single Farm

-   Filter: farm pertama dari database
-   Test untuk filter farm spesifik

##### c. Single Coop

-   Filter: coop pertama dari database
-   Test untuk filter coop spesifik

##### d. Single Livestock

-   Filter: livestock pertama dari database
-   Test untuk filter livestock spesifik

##### e. Recent Week

-   Filter: 7 hari terakhir dari date range
-   Test untuk filter date range terbatas

### 5. Selective Analysis Testing

Berdasarkan opsi selective testing yang dipilih, command hanya menjalankan komponen analysis yang diminta:

-   **--only-overview**: Hanya overview data (total livestock, avg mortality rate, etc.)
-   **--only-mortality**: Hanya mortality analysis by coop
-   **--only-production**: Hanya production analysis by coop
-   **--only-rankings**: Hanya coop performance rankings
-   **--only-sales**: Hanya sales analysis by coop
-   **--only-alerts**: Hanya active alerts
-   **--only-trends**: Hanya trends data (time-series)

Jika tidak ada selective testing option, semua komponen dijalankan.

### 6. Performance Testing (hanya jika tidak ada selective testing)

-   Menjalankan 5 iterasi test untuk measure consistency
-   Melaporkan Average, Minimum, Maximum execution time
-   Performance rating berdasarkan execution time

### 7. Data Integrity Checks (hanya jika tidak ada selective testing)

Memvalidasi data integrity dan konsistensi database.

## List Livestock Feature

### Output --list-livestock

```bash
🐄 Available Livestock
-------------------
Available livestock:
• Batch-Demo Farm-Kandang 1 - Demo Farm-2025-04 (ID: 9f1c7f64-9dcd-4efe-91c7-3e463c6df03c)
• Batch-Demo Farm-Kandang 2 - Demo Farm-2025-04 (ID: 9f1c7f64-a619-454a-9854-5fbc9ff6ec7c)
• Batch-Demo Farm 2-Kandang 1 - Demo Farm 2-2025-04 (ID: 9f1c7f64-ad15-4047-8176-a2ed9d4aa9a3)
```

### Database Filter Info

Ketika menggunakan filter livestock, command menampilkan detail lengkap:

```
📋 Selected Filters:
  • Livestock: Batch-Demo Farm-Kandang 1 - Demo Farm-2025-04
    - Farm: Demo Farm
    - Coop: Kandang 1 - Demo Farm
    - Status: active
    - Start Date: 2025-04-25 12:37:31
    - Population: 4204
```

## Selective Testing Benefits

### Performance Optimization

-   Eksekusi lebih cepat untuk testing spesifik
-   Penggunaan resource lebih efisien
-   Focus testing pada komponen tertentu

### Development Workflow

-   Debug komponen analysis tertentu
-   Test selective during development
-   Quick regression testing

### Example Usage Patterns

```bash
# Quick mortality check during development
php artisan analytics:test --only-mortality --detailed

# Test specific livestock batch production
php artisan analytics:test --livestock=<id> --only-production

# Debug rankings calculation
php artisan analytics:test --only-rankings --detailed

# Performance test for trends
php artisan analytics:test --only-trends
```

## Sample Output

### Normal Run

```
🚀 Smart Analytics Comprehensive Test
=====================================

📊 Database Overview
--------------------
• Farms: 3
• Coops: 6
• Daily Analytics Records: 186
• Date Range: 2025-05-10 to 2025-06-09
• Coops with Analytics Data: 6

🔍 Testing Filter Scenarios
----------------------------
🧪 Testing: All Data
  📊 Overview:
    • Total Livestock: 55690
    • Avg Mortality Rate: 0.06%
    • Avg Efficiency Score: 68.06
    • Avg FCR: 0.65
    • Total Revenue: Rp 0
  📈 Analysis Data:
    • Mortality Analysis: 6 records
    • Production Analysis: 6 records
    • Coop Rankings: 6 records
    • Sales Analysis: 6 records
    • Alerts: 18 active
  📊 Trends Data:
    • Mortality Trend: 30 points
    • Efficiency Trend: 30 points
    • FCR Trend: 30 points
    • Revenue Trend: 30 points
  ⏱️  Execution time: 150.94ms
  ✅ All Data - SUCCESS

⚡ Performance Tests
-------------------
Running 5 iterations...
  Iteration 1: 137.62ms
  Iteration 2: 125.26ms
  Iteration 3: 128.76ms
  Iteration 4: 126.19ms
  Iteration 5: 127.3ms
📊 Performance Summary:
  • Average: 129.02ms
  • Minimum: 125.26ms
  • Maximum: 137.62ms
  ✅ Good performance (< 500ms)

🔍 Data Integrity Checks
------------------------
✅ No data integrity issues found

✅ Test completed in 1166.23ms
```

### Run dengan --calculate

```
⚙️  Calculating Daily Analytics
------------------------------
✅ Daily analytics calculated successfully
⏱️  Execution time: 297.88ms
📈 Analytics created: 186
🚨 Alerts generated: 18
```

## Use Cases

### 1. Development Testing

```bash
# Quick test untuk development
php artisan analytics:test

# Full test dengan details untuk debugging
php artisan analytics:test --detailed
```

### 2. Data Refresh Testing

```bash
# Test setelah calculate analytics baru
php artisan analytics:test --calculate --detailed
```

### 3. Performance Monitoring

```bash
# Monitor performance analytics service
php artisan analytics:test
# Look at "Performance Summary" section
```

### 4. Specific Farm/Coop Testing

```bash
# Test farm tertentu
php artisan analytics:test --farm=9f1c7f5e-6579-4a8d-84c5-123456789 --detailed

# Test coop tertentu
php artisan analytics:test --coop=9f1c7f5e-6744-47fc-8986-c12be337b8a1 --detailed
```

### 5. Date Range Testing

```bash
# Test data minggu terakhir
php artisan analytics:test --date-from=2025-06-03 --date-to=2025-06-09

# Test data bulan tertentu
php artisan analytics:test --date-from=2025-05-01 --date-to=2025-05-31
```

### 6. Production Deployment Validation

```bash
# Complete validation sebelum production
php artisan analytics:test --calculate --detailed
```

### 7. Troubleshooting

```bash
# Debug issues dengan detailed output
php artisan analytics:test --detailed

# Test specific problematic farm
php artisan analytics:test --farm=<problematic_farm_id> --detailed
```

## Error Scenarios

### Failed Scenario

```
🧪 Testing: All Data
  ❌ All Data - FAILED: Call to a member function count() on null
  📝 Stack trace:
    App\Services\AnalyticsService->getSmartInsights() line 45
    ...
```

### Data Integrity Issues

```
🔍 Data Integrity Checks
------------------------
⚠️  Data integrity issues found:
  • 2 coops have no analytics data
  • 5 analytics records reference non-existent coops
  • 1 coops have no farm assignment
  • 10 analytics records have null efficiency scores
```

### Performance Issues

```
⚡ Performance Tests
-------------------
📊 Performance Summary:
  • Average: 1250.5ms
  • Minimum: 1100.2ms
  • Maximum: 1401.8ms
  ❌ Slow performance (> 1s)
```

## Best Practices

1. **Regular Testing**: Jalankan command ini secara berkala untuk monitor health analytics system
2. **Before Deployment**: Selalu test dengan `--calculate --detailed` sebelum deployment
3. **Performance Monitoring**: Monitor execution time untuk detect performance degradation
4. **Data Integrity**: Check data integrity issues dan resolve sebelum production
5. **Custom Testing**: Gunakan specific filters untuk isolate dan debug issues tertentu

## Troubleshooting

### Command tidak ditemukan

```bash
php artisan list | grep analytics
# Pastikan TestSmartAnalyticsCommand registered
```

### Memory issues

```bash
# Increase memory limit jika diperlukan
php -d memory_limit=512M artisan analytics:test
```

### Performance issues

```bash
# Check database indexes
# Optimize query di AnalyticsService
# Monitor database performance
```

### Data integrity issues

```bash
# Fix orphaned data
# Ensure proper relationships
# Validate seeder data
```
