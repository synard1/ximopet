# Livestock Batch Weight Calculation Background Job

## Overview

Background job untuk menghitung dan mengupdate nilai weight pada tabel `livestock_batches` berdasarkan data recording historikal. Job ini menggunakan data dari tabel `recordings` untuk menghitung rata-rata berat dan menerapkan penyesuaian berdasarkan karakteristik batch.

## Features

### 🔧 **Job Features**

-   **Multiple Scope Support**: Single batch, single livestock, atau global calculation
-   **Historical Data Analysis**: Menggunakan data recording historikal untuk kalkulasi
-   **Batch-Specific Adjustments**: Penyesuaian berdasarkan umur batch, ukuran, dan tren
-   **Comprehensive Logging**: Logging detail dengan channel `bgjob`
-   **Performance Tracking**: Start time, finish time, dan estimasi waktu
-   **Error Handling**: Retry mechanism dengan exponential backoff
-   **Queue Support**: Dapat dijalankan di queue yang berbeda

### 📊 **Calculation Logic**

1. **Data Extraction**: Mengambil data berat dari multiple sources:

    - `berat_hari_ini` (priority 1)
    - `payload.production.weight.today` (priority 2)
    - `payload.weight.today` (priority 3)
    - Calculated from weight gain (priority 4)

2. **Weight Calculation**:

    - Rata-rata dari semua recording yang valid
    - Filter recording dengan weight > 0

3. **Batch Adjustments**:
    - **Age Factor**: Growth factor berdasarkan umur batch
    - **Size Factor**: Penyesuaian berdasarkan quantity_available
    - **Trend Factor**: Analisis tren dari 3 recording terakhir

## Installation & Setup

### 1. **Run Migration**

```bash
php artisan migrate
```

### 2. **Configure Logging Channel**

Channel `bgjob` sudah dikonfigurasi di `config/logging.php`:

```php
'bgjob' => [
    'driver' => 'daily',
    'path' => storage_path('logs/bgjob.log'),
    'level' => env('LOG_BGJOB_LEVEL', 'debug'),
    'days' => 30,
],
```

### 3. **Queue Configuration**

Pastikan queue worker berjalan:

```bash
php artisan queue:work --queue=default
```

## Usage

### **Command Line Interface**

#### **Basic Usage**

```bash
# Calculate for specific batch
php artisan livestock:calculate-batch-weight --batch-id="batch-uuid"

# Calculate for specific livestock
php artisan livestock:calculate-batch-weight --livestock-id="livestock-uuid"

# Calculate for all livestock (global)
php artisan livestock:calculate-batch-weight
```

#### **Advanced Options**

```bash
# With date range
php artisan livestock:calculate-batch-weight --start-date="2025-01-01" --end-date="2025-07-26"

# With custom queue
php artisan livestock:calculate-batch-weight --queue=weight-calculation

# Run synchronously (for small datasets)
php artisan livestock:calculate-batch-weight --sync

# Show time estimate without running
php artisan livestock:calculate-batch-weight --estimate
```

#### **Complete Example**

```bash
php artisan livestock:calculate-batch-weight \
    --livestock-id="9f6f82e5-5a91-4843-9164-5f547bbf111a" \
    --start-date="2025-01-01" \
    --end-date="2025-07-26" \
    --user-id="9f48b44b-2142-4b8b-ade3-97e44ea6599c" \
    --queue=weight-calculation
```

### **Programmatic Usage**

#### **Dispatch Job**

```php
use App\Jobs\CalculateLivestockBatchWeightJob;

// Single batch
dispatch(new CalculateLivestockBatchWeightJob(
    livestockId: null,
    batchId: 'batch-uuid',
    startDate: '2025-01-01',
    endDate: '2025-07-26',
    userId: 'user-uuid'
));

// Single livestock
dispatch(new CalculateLivestockBatchWeightJob(
    livestockId: 'livestock-uuid',
    batchId: null,
    startDate: '2025-01-01',
    endDate: '2025-07-26',
    userId: 'user-uuid'
));

// Global calculation
dispatch(new CalculateLivestockBatchWeightJob(
    livestockId: null,
    batchId: null,
    startDate: '2025-01-01',
    endDate: '2025-07-26',
    userId: 'user-uuid'
));
```

## Monitoring & Logging

### **Log Location**

```
storage/logs/bgjob.log
```

### **Log Format**

```json
{
    "message": "🚀 Starting livestock batch weight calculation job",
    "context": {
        "job_id": "weight_calc_64f1a2b3c4d5e",
        "livestock_id": "9f6f82e5-5a91-4843-9164-5f547bbf111a",
        "batch_id": "9f7b1bc4-2509-491f-b7a1-9ff8971fc07c",
        "start_date": "2025-01-01",
        "end_date": "2025-07-26",
        "user_id": "9f48b44b-2142-4b8b-ade3-97e44ea6599c",
        "start_time": "2025-07-26 16:30:20",
        "job_type": "calculate_livestock_batch_weight"
    },
    "level": "info",
    "level_name": "INFO",
    "channel": "bgjob",
    "datetime": "2025-07-26T16:30:20.123456+07:00"
}
```

### **Monitor Progress**

```bash
# Real-time log monitoring
tail -f storage/logs/bgjob.log

# Filter by job type
tail -f storage/logs/bgjob.log | grep "calculate_livestock_batch_weight"

# Monitor queue
php artisan queue:work --queue=weight-calculation --verbose
```

## Database Schema

### **New Columns in `livestock_batches`**

| Column                        | Type            | Description                                          |
| ----------------------------- | --------------- | ---------------------------------------------------- |
| `weight`                      | `decimal(10,2)` | Calculated weight based on historical recording data |
| `weight_calculated_at`        | `timestamp`     | When the weight was last calculated                  |
| `weight_calculation_method`   | `string`        | Method used for weight calculation                   |
| `weight_calculation_metadata` | `json`          | Metadata about weight calculation process            |

### **Example Metadata**

```json
{
    "job_id": "weight_calc_64f1a2b3c4d5e",
    "calculation_date": "2025-07-26T16:30:20+07:00",
    "source": "recording_historical",
    "date_range": {
        "start": "2025-01-01",
        "end": "2025-07-26"
    },
    "recording_count": 15,
    "weight_history": [
        {
            "date": "2025-07-01",
            "weight": 40.0,
            "source": "berat_hari_ini",
            "recording_id": "9f7b1bc4-2509-491f-b7a1-9ff8971fc07c"
        }
    ]
}
```

## Performance Considerations

### **Time Estimates**

-   **Base time per batch**: ~2 seconds
-   **Global calculation overhead**: +30 seconds
-   **Memory usage**: ~50MB per 1000 batches

### **Optimization Tips**

1. **Use specific scope**: Avoid global calculation unless necessary
2. **Set date ranges**: Limit calculation to relevant time periods
3. **Use sync mode**: For small datasets (< 50 batches)
4. **Monitor queue**: Ensure queue workers are running

### **Queue Configuration**

```bash
# For weight calculation queue
php artisan queue:work --queue=weight-calculation --timeout=3600 --tries=3

# For multiple workers
php artisan queue:work --queue=weight-calculation --timeout=3600 --tries=3 &
php artisan queue:work --queue=weight-calculation --timeout=3600 --tries=3 &
```

## Error Handling

### **Retry Configuration**

-   **Max tries**: 3 attempts
-   **Backoff delays**: 60s, 300s, 600s
-   **Timeout**: 3600 seconds (1 hour)

### **Common Errors**

1. **Batch not found**: Invalid batch ID provided
2. **Livestock not found**: Invalid livestock ID provided
3. **No recordings**: No recording data in specified date range
4. **Database timeout**: Large datasets causing timeout

### **Error Recovery**

```bash
# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Restart queue workers
php artisan queue:restart
```

## Examples

### **Calculate Weight for Recent Batches**

```bash
# Calculate for batches created in last 30 days
php artisan livestock:calculate-batch-weight \
    --start-date="$(date -d '30 days ago' +%Y-%m-%d)" \
    --end-date="$(date +%Y-%m-%d)" \
    --queue=weight-calculation
```

### **Recalculate Specific Livestock**

```bash
# Recalculate for specific livestock with date range
php artisan livestock:calculate-batch-weight \
    --livestock-id="9f6f82e5-5a91-4843-9164-5f547bbf111a" \
    --start-date="2025-06-01" \
    --end-date="2025-07-26" \
    --user-id="9f48b44b-2142-4b8b-ade3-97e44ea6599c"
```

### **Quick Test with Sync Mode**

```bash
# Test calculation for single batch synchronously
php artisan livestock:calculate-batch-weight \
    --batch-id="9f7b1bc4-2509-491f-b7a1-9ff8971fc07c" \
    --sync
```

## Troubleshooting

### **Job Not Starting**

1. Check queue workers: `php artisan queue:work --verbose`
2. Check logs: `tail -f storage/logs/bgjob.log`
3. Verify queue configuration in `.env`

### **Job Taking Too Long**

1. Use `--estimate` to check time estimate
2. Consider using `--sync` for small datasets
3. Break down into smaller chunks with specific livestock IDs

### **Memory Issues**

1. Increase PHP memory limit in `php.ini`
2. Use smaller date ranges
3. Process in batches with specific livestock IDs

### **Database Timeout**

1. Increase database timeout settings
2. Use smaller date ranges
3. Optimize database indexes

## Related Files

-   **Job**: `app/Jobs/CalculateLivestockBatchWeightJob.php`
-   **Service**: `app/Services/Livestock/BatchWeightCalculationService.php`
-   **Command**: `app/Console/Commands/CalculateLivestockBatchWeightCommand.php`
-   **Migration**: `database/migrations/2025_07_26_000000_add_weight_columns_to_livestock_batches_table.php`
-   **Logging Config**: `config/logging.php` (bgjob channel)
