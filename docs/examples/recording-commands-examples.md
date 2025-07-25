# Recording Commands - Practical Examples

## 🎯 **Contoh Penggunaan Sehari-hari**

### **1. Cek Status Data Recording**

**Sebelum memproses recording, selalu cek dulu status data:**

```bash
# Cek livestock tertentu
php artisan recording:check --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf

# Cek semua livestock untuk hari ini
php artisan recording:check --all

# Cek untuk tanggal tertentu
php artisan recording:check --all --date=2025-07-01
```

**Output yang diharapkan:**

```
📊 Data Status:
   - Recording: ✅ Yes
   - Feed Usage: ✅ Yes
   - Supply Usage: ✅ Yes
   - Depletion: ✅ Yes
   - Can Process: ✅ Yes
   - Supply Statuses: in_process
   - Data Sources: recording, feed_usage, supply_usage, depletion
```

### **2. Proses Recording dengan Supply Usage Saja**

**Meskipun hanya ada data supply usage, recording tetap bisa diproses:**

```bash
# Proses single livestock
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf

# Proses dengan dry-run dulu untuk testing
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --dry-run --details
```

**Output yang diharapkan:**

```
📊 Aggregation Details:
   - Schema Version: 3.0
   - Data Sources: supply_usage
   - Supply Items: 1
   - Supply Cost: 24,000
   - Supply Statuses: in_process

✅ Successfully processed PR-Farm01-K1 F1-01062025
```

### **3. Batch Processing untuk Semua Livestock**

**Proses recording untuk semua livestock yang aktif:**

```bash
# Proses semua livestock hari ini
php artisan recording:process --all

# Proses dengan batch size yang lebih besar
php artisan recording:process --all --batch-size=100

# Proses dengan progress tracking
php artisan recording:process --all --details
```

### **4. Force Reprocessing**

**Reprocess recording yang sudah ada:**

```bash
# Force reprocess single livestock
php artisan recording:process --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --force

# Force reprocess semua livestock
php artisan recording:process --all --force
```

### **5. Export Data Status untuk Analisis**

**Export status data ke CSV untuk analisis:**

```bash
# Export ke CSV
php artisan recording:check --all --format=csv > recording_status_20250125.csv

# Export ke JSON untuk processing
php artisan recording:check --all --format=json > recording_status_20250125.json
```

## 🔄 **Workflow Lengkap**

### **Workflow Harian**

```bash
# 1. Cek status data hari ini
php artisan recording:check --all

# 2. Proses recording untuk semua livestock
php artisan recording:process --all

# 3. Cek hasil processing
php artisan recording:check --all
```

### **Workflow Backfill**

```bash
# 1. Cek data yang hilang untuk tanggal tertentu
php artisan recording:check --all --date=2025-07-01

# 2. Proses recording yang hilang
php artisan recording:process --all --date=2025-07-01

# 3. Verifikasi hasil
php artisan recording:check --all --date=2025-07-01
```

### **Workflow Validasi**

```bash
# 1. Dry run untuk validasi data
php artisan recording:process --all --dry-run --details

# 2. Cek error dan warning
# 3. Fix data jika diperlukan
# 4. Proses ulang dengan data yang sudah diperbaiki
php artisan recording:process --all
```

## 📊 **Monitoring dan Reporting**

### **Daily Monitoring Script**

```bash
#!/bin/bash
# daily_recording_monitor.sh

DATE=$(date +%Y-%m-%d)
LOG_FILE="recording_log_${DATE}.log"

echo "=== Daily Recording Monitor - ${DATE} ===" | tee -a $LOG_FILE

# Check data availability
echo "1. Checking data availability..." | tee -a $LOG_FILE
php artisan recording:check --all --format=csv > "recording_status_${DATE}.csv" 2>&1

# Process recordings
echo "2. Processing recordings..." | tee -a $LOG_FILE
php artisan recording:process --all >> $LOG_FILE 2>&1

# Final check
echo "3. Final verification..." | tee -a $LOG_FILE
php artisan recording:check --all >> $LOG_FILE 2>&1

echo "=== Monitoring completed ===" | tee -a $LOG_FILE
```

### **Weekly Report Script**

```bash
#!/bin/bash
# weekly_recording_report.sh

WEEK_START=$(date -d "monday this week" +%Y-%m-%d)
WEEK_END=$(date -d "sunday this week" +%Y-%m-%d)

echo "=== Weekly Recording Report - ${WEEK_START} to ${WEEK_END} ==="

# Generate weekly summary
for date in $(seq -f "%g" $(date -d "$WEEK_START" +%s) 86400 $(date -d "$WEEK_END" +%s)); do
    current_date=$(date -d "@$date" +%Y-%m-%d)
    echo "Processing date: $current_date"

    php artisan recording:check --all --date=$current_date --format=csv >> "weekly_report_${WEEK_START}_${WEEK_END}.csv"
done

echo "Weekly report generated: weekly_report_${WEEK_START}_${WEEK_END}.csv"
```

## 🛠️ **Troubleshooting Examples**

### **Masalah: "No data available"**

```bash
# Cek detail data yang tersedia
php artisan recording:check --livestock-id=<id> --details

# Cek supply usage status
php artisan recording:check --all --format=json | jq '.[] | select(.has_supply_usage == false)'

# Cek feed usage
php artisan recording:check --all --format=json | jq '.[] | select(.has_feed_usage == false)'
```

### **Masalah: "Recording already exists"**

```bash
# Cek recording yang sudah ada
php artisan recording:check --all | grep "Recording: ✅"

# Force reprocess jika diperlukan
php artisan recording:process --all --force

# Atau reprocess selective
php artisan recording:process --livestock-id=<id> --force
```

### **Masalah: "Failed to aggregate data"**

```bash
# Dry run dengan detail untuk debugging
php artisan recording:process --livestock-id=<id> --dry-run --details

# Cek log error
tail -f storage/logs/laravel.log

# Test service secara manual
php artisan tinker
>>> $service = new App\Services\Recording\RecordingDataAggregatorService();
>>> $result = $service->aggregateData('livestock-id', '2025-07-02');
>>> dd($result);
```

## 📈 **Performance Optimization**

### **Batch Processing Optimization**

```bash
# Untuk data besar, gunakan batch size yang optimal
php artisan recording:process --all --batch-size=50   # Default
php artisan recording:process --all --batch-size=100  # Untuk server dengan memory besar
php artisan recording:process --all --batch-size=25   # Untuk server dengan memory kecil
```

### **Memory Management**

```bash
# Monitor memory usage
php artisan recording:process --all --details 2>&1 | grep "Memory"

# Jika memory habis, kurangi batch size
php artisan recording:process --all --batch-size=10
```

## 🔮 **Advanced Usage**

### **Scheduled Processing dengan Cron**

```bash
# Tambahkan ke crontab
# 0 2 * * * /path/to/your/project/daily_recording_monitor.sh

# Atau gunakan Laravel scheduler di routes/console.php
Schedule::command('recording:process --all')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();
```

### **Integration dengan CI/CD**

```bash
# Pre-deployment validation
php artisan recording:process --all --dry-run --details

# Post-deployment verification
php artisan recording:check --all --format=json > post_deployment_status.json
```

### **Data Migration Script**

```bash
#!/bin/bash
# migrate_recordings.sh

START_DATE="2025-01-01"
END_DATE="2025-01-25"

echo "Migrating recordings from $START_DATE to $END_DATE"

for date in $(seq -f "%g" $(date -d "$START_DATE" +%s) 86400 $(date -d "$END_DATE" +%s)); do
    current_date=$(date -d "@$date" +%Y-%m-%d)
    echo "Processing: $current_date"

    php artisan recording:process --all --date=$current_date --force
done

echo "Migration completed!"
```

---

**Tips:**

-   Selalu gunakan `--dry-run` untuk testing
-   Monitor log files untuk error
-   Gunakan `--details` untuk debugging
-   Export data status untuk analisis
-   Optimize batch size sesuai server capacity
