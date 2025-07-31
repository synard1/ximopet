# Simulasi Perhitungan Berat Batch Livestock

## Overview

Dokumentasi ini menjelaskan cara menjalankan simulasi untuk background job perhitungan berat batch livestock berdasarkan data recording historikal.

## 🎯 **Tujuan Simulasi**

1. **Testing Command**: Memverifikasi artisan command berfungsi dengan benar
2. **Performance Testing**: Mengukur waktu eksekusi dan resource usage
3. **Data Validation**: Memastikan data tersedia dan valid
4. **Logging Verification**: Memverifikasi sistem logging berfungsi
5. **Queue Testing**: Menguji dispatch dan processing job di queue
6. **Database Results**: Memverifikasi hasil update di database

## 📁 **File Simulasi**

### **Main Simulation Script**

-   **File**: `scripts/simulate_batch_weight_calculation.php`
-   **Fungsi**: Menjalankan semua simulasi secara berurutan
-   **Output**: Comprehensive test results dengan logging

### **Individual Test Script**

-   **File**: `scripts/simulate_individual_tests.php`
-   **Fungsi**: Menjalankan test individual berdasarkan parameter
-   **Usage**: `php scripts/simulate_individual_tests.php <test_name>`

### **Platform Scripts**

-   **Windows**: `scripts/run_simulation.bat`
-   **Linux/Mac**: `scripts/run_simulation.sh`

## 🚀 **Cara Menjalankan Simulasi**

### **1. Full Simulation (Semua Test)**

#### **Windows**

```cmd
# Double click file
scripts\run_simulation.bat

# Atau dari command prompt
cd C:\laragon\www\demo51
scripts\run_simulation.bat
```

#### **Linux/Mac**

```bash
# Make executable
chmod +x scripts/run_simulation.sh

# Run simulation
./scripts/run_simulation.sh

# Atau langsung
php scripts/simulate_batch_weight_calculation.php
```

### **2. Individual Tests**

```bash
# Test estimasi waktu
php scripts/simulate_individual_tests.php estimation

# Test single batch calculation
php scripts/simulate_individual_tests.php single-batch

# Test single livestock calculation
php scripts/simulate_individual_tests.php single-livestock

# Test queue dispatch
php scripts/simulate_individual_tests.php queue

# Test data validation
php scripts/simulate_individual_tests.php validation

# Test performance
php scripts/simulate_individual_tests.php performance

# Test log analysis
php scripts/simulate_individual_tests.php logs

# Test database results
php scripts/simulate_individual_tests.php results

# Run all individual tests
php scripts/simulate_individual_tests.php all
```

## 📊 **Test Cases**

### **1. Estimasi Waktu Perhitungan**

```bash
php scripts/simulate_individual_tests.php estimation
```

**Test yang dijalankan:**

-   Estimasi waktu untuk single batch
-   Estimasi waktu untuk single livestock
-   Estimasi waktu untuk global calculation

**Expected Output:**

```
==================================================
 TEST: ESTIMASI WAKTU PERHITUNGAN
==================================================
ℹ️  Testing time estimation for single batch
💻 Command: php artisan livestock:calculate-batch-weight --batch-id="..." --estimate
📋 Output:
   ⏱️  Time Estimate:
      Estimated time: 2m
      Batches to process: 1
      Average time per batch: 2s
✅ Command executed successfully
```

### **2. Single Batch Calculation (Sync)**

```bash
php scripts/simulate_individual_tests.php single-batch
```

**Test yang dijalankan:**

-   Perhitungan berat untuk single batch secara synchronous
-   Verifikasi hasil perhitungan
-   Log analysis untuk batch tersebut

**Expected Output:**

```
==================================================
 TEST: SINGLE BATCH CALCULATION (SYNC)
==================================================
ℹ️  Calculating weight for single batch synchronously
   {
     "batch_id": "9f7b1bc4-2509-491f-b7a1-9ff8971fc07c",
     "mode": "sync"
   }
💻 Command: php artisan livestock:calculate-batch-weight --batch-id="..." --sync --user-id="..."
📋 Output:
   🚀 Starting Livestock Batch Weight Calculation
   ==============================================
   📊 Calculation Scope:
      Type: single_batch
      Total Livestock: 1
      Total Batches: 1
      Date Range: 2025-06-01 to 2025-07-26
   🔄 Running calculation synchronously...
   ✅ Calculation completed synchronously
✅ Command executed successfully
```

### **3. Single Livestock Calculation (Sync)**

```bash
php scripts/simulate_individual_tests.php single-livestock
```

**Test yang dijalankan:**

-   Perhitungan berat untuk semua batch dalam satu livestock
-   Dengan date range tertentu
-   Verifikasi hasil perhitungan

### **4. Queue Job Dispatch**

```bash
php scripts/simulate_individual_tests.php queue
```

**Test yang dijalankan:**

-   Dispatch job ke queue `weight-calculation`
-   Verifikasi job berhasil di-dispatch
-   Instruksi untuk menjalankan queue worker

**Expected Output:**

```
==================================================
 TEST: QUEUE JOB DISPATCH
==================================================
ℹ️  Dispatching job to queue for single batch
   {
     "batch_id": "9f7b1bc4-2509-491f-b7a1-9ff8971fc07c",
     "queue": "weight-calculation"
   }
💻 Command: php artisan livestock:calculate-batch-weight --batch-id="..." --queue=weight-calculation --user-id="..."
📋 Output:
   ✅ Job dispatched successfully
   📋 Job Details:
      Queue: weight-calculation
      Scope: single_batch
      Batches: 1
   💡 Monitor progress with:
      tail -f storage/logs/bgjob.log
      php artisan queue:work --queue=weight-calculation
✅ Command executed successfully
```

### **5. Data Validation**

```bash
php scripts/simulate_individual_tests.php validation
```

**Test yang dijalankan:**

-   Cek jumlah livestock di database
-   Cek jumlah batch di database
-   Cek jumlah recording di database
-   Cek sample livestock data

**Expected Output:**

```
==================================================
 TEST: DATA VALIDATION
==================================================
ℹ️  Checking database for sample data
💻 Command: php artisan tinker --execute="echo 'Livestock count: ' . App\Models\Livestock::count();"
📋 Output:
   Livestock count: 15
✅ Command executed successfully
💻 Command: php artisan tinker --execute="echo 'Batch count: ' . App\Models\LivestockBatch::count();"
📋 Output:
   Batch count: 45
✅ Command executed successfully
```

### **6. Performance Test**

```bash
php scripts/simulate_individual_tests.php performance
```

**Test yang dijalankan:**

-   Perhitungan dengan dataset kecil (7 hari terakhir)
-   Pengukuran waktu eksekusi
-   Analisis performance

**Expected Output:**

```
==================================================
 TEST: PERFORMANCE TEST
==================================================
ℹ️  Running performance test with small dataset
💻 Command: php artisan livestock:calculate-batch-weight --start-date="2025-07-19" --sync --user-id="..."
📋 Output:
   🔄 Running calculation synchronously...
   ✅ Calculation completed synchronously
✅ Command executed successfully
ℹ️  Performance test completed
   {
     "execution_time_seconds": 15.23,
     "execution_time_formatted": "15s"
   }
```

### **7. Log Analysis**

```bash
php scripts/simulate_individual_tests.php logs
```

**Test yang dijalankan:**

-   Analisis log entries terbaru
-   Count log entries by type
-   Check error logs

**Expected Output:**

```
==================================================
 TEST: LOG ANALYSIS
==================================================
ℹ️  Analyzing recent bgjob logs
💻 Command: tail -n 10 storage/logs/bgjob.log
📋 Output:
   [2025-07-26 16:30:20] bgjob.INFO: 🚀 Starting livestock batch weight calculation job
   [2025-07-26 16:30:21] bgjob.INFO: ✅ Livestock batch weight calculation completed successfully
✅ Command executed successfully
💻 Command: grep -c 'calculate_livestock_batch_weight' storage/logs/bgjob.log
📋 Output:
   5
✅ Command executed successfully
```

### **8. Database Results**

```bash
php scripts/simulate_individual_tests.php results
```

**Test yang dijalankan:**

-   Cek jumlah batch yang sudah diupdate weight
-   Cek sample batch weight
-   Cek metadata calculation

**Expected Output:**

```
==================================================
 TEST: DATABASE RESULTS
==================================================
ℹ️  Checking updated batch weights in database
💻 Command: php artisan tinker --execute="echo 'Batches with weight: ' . App\Models\LivestockBatch::whereNotNull('weight')->count();"
📋 Output:
   Batches with weight: 12
✅ Command executed successfully
💻 Command: php artisan tinker --execute="echo 'Sample batch weight: ' . App\Models\LivestockBatch::whereNotNull('weight')->first()->weight ?? 'None';"
📋 Output:
   Sample batch weight: 42.5
✅ Command executed successfully
```

## 📋 **Sample Data**

Simulasi menggunakan sample data berikut:

```php
[
    'livestock_id' => '9f6f82e5-5a91-4843-9164-5f547bbf111a',
    'batch_id' => '9f7b1bc4-2509-491f-b7a1-9ff8971fc07c',
    'user_id' => '9f48b44b-2142-4b8b-ade3-97e44ea6599c',
    'start_date' => '2025-06-01',
    'end_date' => '2025-07-26'
]
```

## 🔧 **Konfigurasi**

### **Environment Requirements**

-   PHP 8.1+
-   Laravel 11+
-   Database connection aktif
-   Queue driver configured (database/redis)

### **Logging Configuration**

```php
// config/logging.php
'bgjob' => [
    'driver' => 'daily',
    'path' => storage_path('logs/bgjob.log'),
    'level' => env('LOG_BGJOB_LEVEL', 'debug'),
    'days' => 30,
],
```

### **Queue Configuration**

```bash
# .env
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
```

## 📊 **Expected Results**

### **Performance Metrics**

-   **Single batch**: ~2-5 seconds
-   **Single livestock**: ~10-30 seconds
-   **Global calculation**: ~5-15 minutes (tergantung jumlah data)

### **Database Updates**

-   Kolom `weight` terisi dengan nilai calculated
-   Kolom `weight_calculated_at` terisi dengan timestamp
-   Kolom `weight_calculation_method` terisi dengan 'historical_recording'
-   Kolom `weight_calculation_metadata` terisi dengan JSON metadata

### **Log Entries**

-   Job start/end entries
-   Calculation progress entries
-   Error entries (jika ada)
-   Performance metrics

## 🚨 **Troubleshooting**

### **Common Issues**

#### **1. Command Not Found**

```bash
# Error: Command "livestock:calculate-batch-weight" is not defined
# Solution: Pastikan command sudah terdaftar
php artisan list | grep livestock
```

#### **2. Database Connection Error**

```bash
# Error: SQLSTATE[HY000] [2002] Connection refused
# Solution: Cek database connection
php artisan tinker --execute="echo 'DB connected: ' . (DB::connection()->getPdo() ? 'Yes' : 'No');"
```

#### **3. Queue Not Working**

```bash
# Error: Job not processed
# Solution: Start queue worker
php artisan queue:work --queue=weight-calculation --verbose
```

#### **4. Log File Not Found**

```bash
# Error: Log file not accessible
# Solution: Create log file
touch storage/logs/bgjob.log
chmod 644 storage/logs/bgjob.log
```

#### **5. Permission Issues**

```bash
# Error: Permission denied
# Solution: Set proper permissions
chmod +x scripts/run_simulation.sh
chmod +x scripts/run_simulation.bat
```

### **Debug Commands**

```bash
# Check Laravel environment
php artisan env

# Check database connection
php artisan tinker --execute="echo 'DB: ' . config('database.default');"

# Check queue status
php artisan queue:failed

# Check log files
ls -la storage/logs/

# Monitor real-time logs
tail -f storage/logs/bgjob.log
```

## 📈 **Monitoring & Analysis**

### **Real-time Monitoring**

```bash
# Monitor logs
tail -f storage/logs/bgjob.log

# Monitor queue
php artisan queue:work --queue=weight-calculation --verbose

# Monitor database
php artisan tinker --execute="echo 'Updated batches: ' . App\Models\LivestockBatch::whereNotNull('weight')->count();"
```

### **Performance Analysis**

```bash
# Analyze execution time
grep "execution_time" storage/logs/bgjob.log

# Count successful calculations
grep "completed successfully" storage/logs/bgjob.log | wc -l

# Count errors
grep "error\|failed\|exception" storage/logs/bgjob.log | wc -l
```

## 🎯 **Next Steps**

Setelah simulasi berhasil:

1. **Run Migration**: `php artisan migrate`
2. **Test dengan Real Data**: Gunakan ID livestock/batch yang ada
3. **Monitor Production**: Deploy dan monitor di environment production
4. **Optimize**: Sesuaikan parameter berdasarkan hasil performance test
5. **Scale**: Implementasi untuk dataset yang lebih besar

## 📚 **Related Documentation**

-   [Background Job Documentation](../features/livestock-batch-weight-calculation.md)
-   [Command Line Interface](../features/livestock-batch-weight-calculation.md#command-line-interface)
-   [Queue Management](../features/livestock-batch-weight-calculation.md#queue-configuration)
-   [Logging Configuration](../features/livestock-batch-weight-calculation.md#monitoring--logging)
