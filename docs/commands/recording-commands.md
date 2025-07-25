# Recording Commands

## 📋 Overview

Dokumentasi untuk Artisan Commands yang terkait dengan proses recording data menggunakan RecordingDataAggregatorService.

## 🛠️ Available Commands

### 1. `recording:check`

Command untuk mengecek ketersediaan data recording untuk livestock.

#### **Signature**

```bash
php artisan recording:check
    {--livestock-id= : ID livestock yang akan dicek}
    {--date= : Tanggal yang akan dicek (format: Y-m-d, default: hari ini)}
    {--all : Cek semua livestock yang aktif}
    {--format=table : Output format (table, json, csv)}
```

#### **Usage Examples**

**Check single livestock:**

```bash
php artisan recording:check --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf
```

**Check specific date:**

```bash
php artisan recording:check --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --date=2025-07-02
```

**Check all livestock:**

```bash
php artisan recording:check --all
```

**Check all livestock with JSON output:**

```bash
php artisan recording:check --all --format=json
```

**Check all livestock with CSV output:**

```bash
php artisan recording:check --all --format=csv > recording_status.csv
```

#### **Output Example**

```
🔍 Checking Recording Data Availability...
📅 Date: 2025-07-02

🎯 Checking livestock: PR-Farm01-K1 F1-01062025 (9f6acd12-7f57-41da-aef8-f9d0b91a7fbf)
📅 Date: 2025-07-02

📊 Data Status:
   - Recording: ✅ Yes
   - Feed Usage: ✅ Yes
   - Supply Usage: ✅ Yes
   - Depletion: ✅ Yes
   - Can Process: ✅ Yes
   - Supply Statuses: in_process
   - Data Sources: recording, feed_usage, supply_usage, depletion

💡 Recommendation:
   - Recording already exists. Use --force to reprocess.
```

### 2. `recording:process`

Command untuk memproses recording data menggunakan RecordingDataAggregatorService.

#### **Signature**

```bash
php artisan recording:process
    {--livestock-id= : ID livestock yang akan diproses}
    {--date= : Tanggal recording (format: Y-m-d, default: hari ini)}
    {--force : Force processing meskipun sudah ada recording}
    {--dry-run : Simulasi tanpa menyimpan ke database}
    {--details : Output detail proses}
    {--all : Proses semua livestock yang aktif}
    {--batch-size=50 : Jumlah livestock per batch untuk --all}
```

#### **Usage Examples**

**Process single livestock:**

```bash
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf
```

**Process with specific date:**

```bash
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --date=2025-07-02
```

**Dry run (simulation):**

```bash
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --dry-run
```

**Force reprocess existing recording:**

```bash
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --force
```

**Process with detailed output:**

```bash
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --details
```

**Process all livestock:**

```bash
php artisan recording:process --all
```

**Process all livestock with custom batch size:**

```bash
php artisan recording:process --all --batch-size=100
```

#### **Output Example**

```
🔄 Starting Recording Processing...
📅 Date: 2025-07-02
🔧 Force: No
🧪 Dry Run: Yes
📊 Verbose: Yes

🔄 Processing PR-Farm01-K1 F1-01062025...

📊 Aggregation Details:
   - Schema Version: 3.0
   - Data Sources: recording, feed_usage, supply_usage, depletion
   - Feed Items: 1
   - Feed Cost: 6,500
   - Supply Items: 1
   - Supply Cost: 24,000
   - Mortality: 25
   - Culling: 0
   - Total Depletion: 25
   - IP: 647.25
   - FCR: 0.015
   - Liveability: 99.58

✅ Successfully processed PR-Farm01-K1 F1-01062025

📈 Processing Results:
   - Total Processed: 1
   - Success: 1
   - Failed: 0
   - Skipped: 0

🧪 DRY RUN MODE: No data was saved to database

✅ Recording processing completed!
```

## 🔧 Features

### **Data Availability Check**

-   ✅ Recording data
-   ✅ Feed usage data
-   ✅ Supply usage data (dengan status filtering)
-   ✅ Depletion data
-   ✅ Status supply usage yang valid

### **Processing Capabilities**

-   ✅ Single livestock processing
-   ✅ Batch processing untuk semua livestock
-   ✅ Force reprocessing
-   ✅ Dry run mode
-   ✅ Detailed output
-   ✅ Progress tracking
-   ✅ Error handling

### **Output Formats**

-   ✅ Table format (default)
-   ✅ JSON format
-   ✅ CSV format

### **Status Filtering**

Command hanya memproses supply usage dengan status valid:

-   `pending`
-   `in_process`
-   `completed`
-   `partially_used`

Status yang diabaikan:

-   `draft`
-   `cancelled`
-   `rejected`
-   `expired`
-   `damaged`

## 🚀 Use Cases

### **1. Daily Recording Processing**

```bash
# Process all livestock for today
php artisan recording:process --all

# Check what can be processed first
php artisan recording:check --all
```

### **2. Backfill Missing Recordings**

```bash
# Check missing recordings for specific date
php artisan recording:check --all --date=2025-07-01

# Process missing recordings
php artisan recording:process --all --date=2025-07-01
```

### **3. Data Validation**

```bash
# Dry run to validate data without saving
php artisan recording:process --all --dry-run --details
```

### **4. Force Reprocessing**

```bash
# Force reprocess existing recordings
php artisan recording:process --all --force
```

### **5. Export Data Status**

```bash
# Export status to CSV for analysis
php artisan recording:check --all --format=csv > recording_status_$(date +%Y%m%d).csv
```

## 📊 Business Logic

### **Processing Rules**

1. **Data Availability**: Command hanya memproses jika ada data yang tersedia
2. **Status Filtering**: Supply usage hanya diproses jika status valid
3. **Existing Recording**: Skip jika recording sudah ada (kecuali --force)
4. **Batch Processing**: Proses dalam batch untuk performa optimal

### **Error Handling**

-   ✅ Livestock tidak ditemukan
-   ✅ Data tidak tersedia
-   ✅ Service aggregation error
-   ✅ Database error
-   ✅ Validation error

### **Performance Optimization**

-   ✅ Batch processing
-   ✅ Progress tracking
-   ✅ Memory management
-   ✅ Error logging

## 🔍 Troubleshooting

### **Common Issues**

1. **"Livestock ID is required"**

    - Solusi: Tambahkan `--livestock-id=<id>` atau gunakan `--all`

2. **"No data available"**

    - Solusi: Pastikan ada feed usage, supply usage, atau depletion data

3. **"Recording already exists"**

    - Solusi: Gunakan `--force` untuk reprocess

4. **"Failed to aggregate data"**
    - Solusi: Cek log error dan pastikan service berfungsi

### **Debug Mode**

```bash
# Enable detailed output
php artisan recording:process --livestock-id=<id> --details

# Dry run untuk testing
php artisan recording:process --livestock-id=<id> --dry-run --details
```

### **Log Files**

-   Error logs: `storage/logs/laravel.log`
-   Command logs: `storage/logs/commands/`

## 📈 Monitoring

### **Success Metrics**

-   Total processed
-   Success count
-   Failed count
-   Skipped count

### **Performance Metrics**

-   Execution time
-   Memory usage
-   Batch processing time

### **Data Quality Metrics**

-   Data completeness
-   Validation results
-   Error rates

## 🔮 Future Enhancements

### **Planned Features**

1. **Scheduled Processing**

    - Cron job integration
    - Automated daily processing

2. **Advanced Filtering**

    - Date range processing
    - Status-based filtering
    - Company-based filtering

3. **Reporting**

    - Processing reports
    - Data quality reports
    - Performance reports

4. **Integration**
    - Email notifications
    - Slack notifications
    - Dashboard integration

---

**Version**: 1.0  
**Last Updated**: 2025-01-25  
**Maintainer**: Development Team
