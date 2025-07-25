# Data Integrity Commands - Usage Examples

**Date:** 2025-07-14  
**Author:** System  
**Purpose:** Practical usage examples and testing guide

## Commands Overview

| Command                | Purpose                                      | Key Features                             |
| ---------------------- | -------------------------------------------- | ---------------------------------------- |
| `supply:fix-metadata`  | Fix missing metadata in supply stock records | Batch processing, filtering, export      |
| `data:integrity-check` | Comprehensive data integrity validation      | Multiple check types, detailed reporting |
| `supply:fix-numbering` | Fix missing number/number_full fields        | Numbering generation, date-based reset   |

## Quick Start

### 1. Check Available Commands

```bash
# List all available commands
php artisan list

# Check specific command help
php artisan supply:fix-metadata --help
php artisan data:integrity-check --help
php artisan supply:fix-numbering --help
```

### 2. Basic Usage Examples

#### Fix Supply Stock Metadata

```bash
# Dry run - see what would be fixed
php artisan supply:fix-metadata --dry-run

# Fix metadata for all records
php artisan supply:fix-metadata

# Fix metadata for purchase records only
php artisan supply:fix-metadata --source-type=purchase

# Fix metadata for specific farm
php artisan supply:fix-metadata --farm-id=9f46ed24-ed84-4746-ada7-ad0747328e92

# Force update all metadata (including existing ones)
php artisan supply:fix-metadata --force

# Custom batch size for large datasets
php artisan supply:fix-metadata --batch-size=50
```

#### Fix Supply Purchase Numbering

````bash
# Dry run - see what would be fixed
php artisan supply:fix-numbering --dry-run

# Fix numbering for all records
php artisan supply:fix-numbering

# Fix numbering for specific farm
php artisan supply:fix-numbering --farm-id=9f46ed24-ed84-4746-ada7-ad0747328e92

# Fix numbering for specific status
php artisan supply:fix-numbering --status=draft

# Fix numbering for date range
php artisan supply:fix-numbering --date-from=2025-01-01 --date-to=2025-01-31

# Force update all numbering (even if exists)
php artisan supply:fix-numbering --force

# Export results to JSON file
php artisan supply:fix-numbering --export=numbering_results.json

#### Comprehensive Data Integrity Check

```bash
# Check all integrity types (dry run)
php artisan data:integrity-check --dry-run

# Check metadata integrity only
php artisan data:integrity-check --type=metadata --dry-run

# Check current supply integrity only
php artisan data:integrity-check --type=current-supply --dry-run

# Check purchase batch integrity only
php artisan data:integrity-check --type=purchase-batch --dry-run

# Export results to JSON file
php artisan data:integrity-check --export=integrity_results.json

# Check with specific filters
php artisan data:integrity-check --type=metadata --source-type=purchase --farm-id=123
````

## Testing Scenarios

### 1. Test Metadata Fix for Purchase Records

```bash
# Step 1: Check current state
php artisan supply:fix-metadata --source-type=purchase --dry-run

# Step 2: Apply fixes
php artisan supply:fix-metadata --source-type=purchase

# Step 3: Verify fixes
php artisan supply:fix-metadata --source-type=purchase --dry-run
```

**Expected Output:**

```
🔧 Supply Stock Metadata Fix Tool
================================
📊 Found 4 records to process

Processing: 100% [████████████████████████████████████████] 4/4

📈 Processing Results:
====================
+-----------------+-------+
| Metric          | Count |
+-----------------+-------+
| Total Processed | 4     |
| Fixed           | 4     |
| Skipped         | 0     |
| Errors          | 0     |
+-----------------+-------+

✅ Metadata fix completed successfully!
```

### 2. Test Comprehensive Integrity Check

```bash
# Run comprehensive check
php artisan data:integrity-check --dry-run
```

**Expected Output:**

```
🔍 Data Integrity Check Tool
===========================
⚠️  DRY RUN MODE - No changes will be made
📋 Running all integrity check...

🔍 Running comprehensive data integrity check...

📈 Supply Stock Metadata Results:
==================================
+------------------+-------+
| Metric           | Count |
+------------------+-------+
| Total Checked    | 4     |
| Missing Metadata | 0     |
| Invalid Metadata | 0     |
| Fixed            | 0     |
| Errors           | 0     |
+------------------+-------+

📊 Current Supply Results:
==========================
+-------------+-------+
| Metric      | Count |
+-------------+-------+
| Total Checked| 2    |
| Mismatched  | 0     |
| Fixed       | 0     |
| Errors      | 0     |
+-------------+-------+

📦 Purchase Batch Results:
==========================
+-------------------+-------+
| Metric            | Count |
+-------------------+-------+
| Total Checked     | 1     |
| Orphaned Purchases| 0     |
| Fixed             | 0     |
| Errors            | 0     |
+-------------------+-------+

📋 Summary:
===========
+-------------+-------+
| Metric      | Count |
+-------------+-------+
| Total Fixed | 0     |
| Total Errors| 0     |
+-------------+-------+

⚠️  This was a dry run. Use --force to apply changes.
```

### 3. Test Supply Purchase Numbering Fix

```bash
# Step 1: Check current state
php artisan supply:fix-numbering --dry-run

# Step 2: Apply fixes
php artisan supply:fix-numbering

# Step 3: Verify fixes
php artisan supply:fix-numbering --dry-run
```

**Expected Output:**

```
🔧 Starting Supply Purchase Numbering Fix
📊 This will fix missing number and number_full fields for SupplyPurchaseBatch records

+----------------+-------+
| Setting        | Value |
+----------------+-------+
| Dry Run        | No    |
| Batch Size     | 100   |
| Force Update   | No    |
| Farm ID Filter | All   |
| Status Filter  | All   |
| Date From      | All   |
| Date To        | All   |
| Export Results | No    |
+----------------+-------+

🔍 Performing pre-flight checks...
✅ Pre-flight checks passed
📋 Found 3 records to process

████████████████████████████████████████ 3/3 [100%]

📊 Processing Report
+-----------------+-------+
| Metric          | Count |
+-----------------+-------+
| Total Processed | 3     |
| Fixed           | 3     |
| Skipped         | 0     |
| Errors          | 0     |
| Duration        | 0.8 seconds |
+-----------------+-------+

✅ Sample of Fixed Records
+------------+----------+----------+------------+------------+----------------------+
| ID         | Invoice  | Date     | Old Number | New Number | New Full Number     |
+------------+----------+----------+------------+------------+----------------------+
| 9f5d6f1... | 00001    | 2025-07-01| NULL       | 1          | SPB-00001/01/07/2025 |
| 9f62cf6... | 00001    | 2025-07-01| NULL       | 1          | SPB-00001/01/07/2025 |
| 9f615cc... | 0002     | 2025-07-05| NULL       | 1          | SPB-00001/05/07/2025 |
+------------+----------+----------+------------+------------+----------------------+

✅ Supply Purchase Numbering Fix completed successfully
```

### 4. Test with Export Results

```bash
# Export results to JSON file
php artisan data:integrity-check --export=test_results.json
```

**Generated JSON Structure:**

```json
{
    "type": "comprehensive",
    "results": {
        "supply_stock_metadata": {
            "total_checked": 4,
            "missing_metadata": 0,
            "invalid_metadata": 0,
            "fixed": 0,
            "errors": 0,
            "dry_run": false
        },
        "current_supply": {
            "total_checked": 2,
            "mismatched": 0,
            "fixed": 0,
            "errors": 0,
            "dry_run": false
        },
        "supply_purchase_batch": {
            "total_checked": 1,
            "orphaned_purchases": 0,
            "fixed": 0,
            "errors": 0,
            "dry_run": false
        },
        "timestamp": "2025-07-14T11:30:00.000000Z",
        "options": {
            "dry_run": false,
            "force": false,
            "source_type": null,
            "farm_id": null,
            "supply_id": null
        }
    },
    "timestamp": "2025-07-14T11:30:00.000000Z"
}
```

## Service Integration Examples

### 1. Using DataIntegrityService in Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\DataIntegrityService;
use Illuminate\Http\Request;

class DataIntegrityController extends Controller
{
    public function checkIntegrity(Request $request, DataIntegrityService $integrityService)
    {
        $options = [
            'dry_run' => $request->boolean('dry_run', true),
            'source_type' => $request->get('source_type'),
            'farm_id' => $request->get('farm_id'),
            'supply_id' => $request->get('supply_id'),
        ];

        $results = $integrityService->runComprehensiveIntegrityCheck($options);

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Integrity check completed'
        ]);
    }

    public function fixMetadata(Request $request, DataIntegrityService $integrityService)
    {
        $options = [
            'dry_run' => false,
            'source_type' => $request->get('source_type'),
            'farm_id' => $request->get('farm_id'),
        ];

        $results = $integrityService->checkSupplyStockMetadataIntegrity($options);

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Metadata fix completed'
        ]);
    }
}
```

### 2. Using in Livewire Component

```php
<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Services\DataIntegrityService;

class DataIntegrityPanel extends Component
{
    public $checkResults = [];
    public $isRunning = false;

    public function runIntegrityCheck(DataIntegrityService $integrityService)
    {
        $this->isRunning = true;

        try {
            $this->checkResults = $integrityService->runComprehensiveIntegrityCheck([
                'dry_run' => true
            ]);

            $this->dispatch('integrity-check-completed', $this->checkResults);
        } catch (\Exception $e) {
            $this->addError('integrity', 'Error running integrity check: ' . $e->getMessage());
        } finally {
            $this->isRunning = false;
        }
    }

    public function fixMetadata(DataIntegrityService $integrityService)
    {
        $this->isRunning = true;

        try {
            $results = $integrityService->checkSupplyStockMetadataIntegrity([
                'dry_run' => false
            ]);

            $this->checkResults = $results;
            $this->dispatch('metadata-fix-completed', $results);
        } catch (\Exception $e) {
            $this->addError('integrity', 'Error fixing metadata: ' . $e->getMessage());
        } finally {
            $this->isRunning = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.data-integrity-panel');
    }
}
```

### 3. Scheduled Job Integration

```php
// In routes/console.php
use App\Services\DataIntegrityService;

Schedule::daily()->at('02:00')->call(function () {
    $integrityService = app(DataIntegrityService::class);

    $results = $integrityService->runComprehensiveIntegrityCheck([
        'dry_run' => false
    ]);

    Log::info('Daily data integrity check completed', $results);

    // Send notification if errors found
    if ($results['supply_stock_metadata']['errors'] > 0 ||
        $results['current_supply']['errors'] > 0 ||
        $results['supply_purchase_batch']['errors'] > 0) {

        // Send notification to admin
        Notification::route('mail', 'admin@example.com')
            ->notify(new DataIntegrityAlert($results));
    }
});
```

## Troubleshooting

### 1. Common Issues

#### Command Not Found

```bash
# Clear cache and try again
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Check if command exists
php artisan list | grep -i "supply\|data"
```

#### Service Not Found

```bash
# Check service registration
php artisan tinker
>>> app('App\Services\DataIntegrityService')
```

#### Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
```

### 2. Debug Mode

```bash
# Enable debug mode for detailed output
php artisan supply:fix-metadata --dry-run -v

# Check logs for detailed information
tail -f storage/logs/laravel.log
```

### 3. Performance Issues

```bash
# Use smaller batch size for large datasets
php artisan supply:fix-metadata --batch-size=10

# Check memory usage
php artisan supply:fix-metadata --dry-run --batch-size=10
```

## Best Practices

### 1. Production Usage

1. **Always run dry-run first**

    ```bash
    php artisan supply:fix-metadata --dry-run
    ```

2. **Use specific filters**

    ```bash
    php artisan supply:fix-metadata --source-type=purchase --farm-id=123
    ```

3. **Monitor logs**

    ```bash
    tail -f storage/logs/laravel.log | grep "Supply stock metadata"
    ```

4. **Export results for audit**
    ```bash
    php artisan data:integrity-check --export=audit_$(date +%Y%m%d).json
    ```

### 2. Scheduled Maintenance

```bash
# Add to crontab for daily checks
0 2 * * * cd /path/to/project && php artisan data:integrity-check --export=/var/log/integrity_$(date +\%Y\%m\%d).json
```

### 3. Monitoring Integration

```php
// In monitoring system
$results = $integrityService->runComprehensiveIntegrityCheck();

if ($results['supply_stock_metadata']['errors'] > 0) {
    // Alert monitoring system
    MonitoringService::alert('Data integrity issues detected');
}
```

## Conclusion

Command dan service ini memberikan solusi lengkap untuk:

✅ **Automated Data Repair** - Perbaikan otomatis data yang rusak  
✅ **Comprehensive Coverage** - Cakupan semua jenis integritas data  
✅ **Safe Operations** - Dry run dan validation untuk keamanan  
✅ **Monitoring & Logging** - Complete audit trail  
✅ **Service Reusability** - Dapat digunakan di berbagai context  
✅ **Production Ready** - Error handling dan performance optimization

**Status:** ✅ Tested and Ready  
**Production:** Safe to deploy  
**Documentation:** Complete
 