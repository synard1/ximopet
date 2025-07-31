# Script Simulasi Perhitungan Berat Batch Livestock

## Overview

Script-script ini digunakan untuk mensimulasikan dan menguji background job perhitungan berat batch livestock berdasarkan data recording historikal.

## 📁 **File Structure**

```
scripts/
├── README.md                                    # Dokumentasi ini
├── simulate_batch_weight_calculation.php        # Main simulation script
├── simulate_individual_tests.php                # Individual test runner
├── simulate_with_real_data.php                  # Real data simulation
├── run_simulation.bat                          # Windows batch script
├── run_simulation.sh                           # Linux/Mac shell script
├── run_real_data_simulation.bat                # Windows real data script
└── run_real_data_simulation.sh                 # Linux/Mac real data script
```

## 🚀 **Quick Start**

### **1. Basic Simulation (Sample Data)**

#### **Windows**

```cmd
# Double click atau run dari command prompt
scripts\run_simulation.bat
```

#### **Linux/Mac**

```bash
# Make executable
chmod +x scripts/run_simulation.sh

# Run simulation
./scripts/run_simulation.sh
```

### **2. Real Data Simulation**

#### **Windows**

```cmd
# Double click atau run dari command prompt
scripts\run_real_data_simulation.bat
```

#### **Linux/Mac**

```bash
# Make executable
chmod +x scripts/run_real_data_simulation.sh

# Run simulation
./scripts/run_real_data_simulation.sh
```

### **3. Individual Tests**

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

## 📊 **Test Types**

### **1. Basic Simulation (`simulate_batch_weight_calculation.php`)**

-   **Purpose**: Menjalankan semua simulasi dengan sample data
-   **Scope**: 8 test cases berurutan
-   **Data**: Menggunakan sample data hardcoded
-   **Output**: Comprehensive test results

### **2. Individual Tests (`simulate_individual_tests.php`)**

-   **Purpose**: Menjalankan test individual berdasarkan parameter
-   **Scope**: 1 test case per execution
-   **Data**: Menggunakan sample data hardcoded
-   **Output**: Focused test results

### **3. Real Data Simulation (`simulate_with_real_data.php`)**

-   **Purpose**: Menjalankan simulasi dengan data real dari database
-   **Scope**: 5 test cases dengan data real
-   **Data**: Mengambil data dari database (Livestock, Batch, Recording)
-   **Output**: Real-world test results

## 🔧 **Prerequisites**

### **Environment Requirements**

-   PHP 8.1+
-   Laravel 11+
-   Database connection aktif
-   Queue driver configured

### **Database Requirements**

-   Tabel `livestocks` dengan data
-   Tabel `livestock_batches` dengan data
-   Tabel `recordings` dengan data
-   Migration untuk kolom weight sudah dijalankan

### **Configuration**

```bash
# .env
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
LOG_BGJOB_LEVEL=debug
```

## 📋 **Test Cases**

### **Basic Simulation Tests**

1. **Estimasi Waktu**: Test time estimation untuk berbagai scope
2. **Data Validation**: Cek ketersediaan data di database
3. **Single Batch**: Perhitungan untuk single batch (sync)
4. **Single Livestock**: Perhitungan untuk single livestock (sync)
5. **Queue Dispatch**: Dispatch job ke queue
6. **Performance Test**: Test performance dengan dataset kecil
7. **Log Analysis**: Analisis log entries
8. **Database Results**: Verifikasi hasil di database

### **Real Data Tests**

1. **Database Analysis**: Analisis data real di database
2. **Real Livestock**: Perhitungan dengan livestock real
3. **Real Batch**: Perhitungan dengan batch real
4. **Recent Data**: Perhitungan dengan data 30 hari terakhir
5. **Queue Real Data**: Dispatch job dengan data real
6. **Results Verification**: Verifikasi hasil perhitungan
7. **Performance Analysis**: Analisis performance dengan data real

## 📊 **Expected Output**

### **Success Indicators**

-   ✅ Command executed successfully
-   📊 Calculation scope determined
-   🔄 Running calculation synchronously
-   ✅ Calculation completed synchronously
-   📋 Job dispatched successfully

### **Performance Metrics**

-   **Single batch**: ~2-5 seconds
-   **Single livestock**: ~10-30 seconds
-   **Global calculation**: ~5-15 minutes

### **Database Updates**

-   Kolom `weight` terisi dengan nilai calculated
-   Kolom `weight_calculated_at` terisi dengan timestamp
-   Kolom `weight_calculation_method` terisi dengan 'historical_recording'
-   Kolom `weight_calculation_metadata` terisi dengan JSON metadata

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

#### **3. Models Not Found**

```bash
# Error: Class 'App\Models\Livestock' not found
# Solution: Run migrations
php artisan migrate
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
chmod +x scripts/run_real_data_simulation.sh
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

## 🎯 **Usage Examples**

### **Development Testing**

```bash
# Quick test dengan sample data
php scripts/simulate_individual_tests.php single-batch

# Test dengan data real
php scripts/simulate_with_real_data.php
```

### **Production Testing**

```bash
# Test estimasi sebelum production run
php scripts/simulate_individual_tests.php estimation

# Test dengan subset data real
php scripts/simulate_individual_tests.php performance
```

### **Debugging**

```bash
# Test data validation
php scripts/simulate_individual_tests.php validation

# Test log analysis
php scripts/simulate_individual_tests.php logs

# Test database results
php scripts/simulate_individual_tests.php results
```

## 📚 **Related Documentation**

-   [Background Job Documentation](../docs/features/livestock-batch-weight-calculation.md)
-   [Simulation Documentation](../docs/simulation/batch-weight-calculation-simulation.md)
-   [Command Line Interface](../docs/features/livestock-batch-weight-calculation.md#command-line-interface)
-   [Queue Management](../docs/features/livestock-batch-weight-calculation.md#queue-configuration)

## 🔄 **Next Steps**

Setelah simulasi berhasil:

1. **Run Migration**: `php artisan migrate`
2. **Test dengan Real Data**: Gunakan ID livestock/batch yang ada
3. **Monitor Production**: Deploy dan monitor di environment production
4. **Optimize**: Sesuaikan parameter berdasarkan hasil performance test
5. **Scale**: Implementasi untuk dataset yang lebih besar

## 📞 **Support**

Jika mengalami masalah:

1. **Check Logs**: `tail -f storage/logs/bgjob.log`
2. **Check Database**: `php artisan tinker`
3. **Check Queue**: `php artisan queue:failed`
4. **Check Environment**: `php artisan env`

## 📝 **Changelog**

### **Version 1.0 (2025-07-26)**

-   Initial release
-   Basic simulation scripts
-   Individual test runner
-   Real data simulation
-   Platform-specific scripts (Windows/Linux/Mac)
-   Comprehensive documentation
