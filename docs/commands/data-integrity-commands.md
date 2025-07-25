# Data Integrity Commands & Services

**Date:** 2025-07-14  
**Author:** System  
**Purpose:** Centralized data integrity management for supply management system

## Overview

Sistem perbaikan integritas data tersentral yang menggunakan service yang reusable untuk memperbaiki data yang tidak konsisten atau rusak. Sistem ini terdiri dari:

1. **DataIntegrityService** - Service utama untuk perbaikan integritas data
2. **FixSupplyStockMetadata** - Command khusus untuk perbaikan metadata supply stock
3. **DataIntegrityCheck** - Command komprehensif untuk semua jenis perbaikan integritas

## Architecture

### 1. DataIntegrityService

Service utama yang dapat digunakan oleh:

-   Artisan commands
-   Controllers
-   Livewire components
-   Scheduled jobs

**Features:**

-   Supply stock metadata integrity check & fix
-   Current supply quantity validation & correction
-   Supply purchase batch orphaned record cleanup
-   Comprehensive integrity check
-   Reusable across different contexts

### 2. FixSupplyStockMetadata Command

Command khusus untuk memperbaiki metadata yang hilang pada supply stock.

**Usage:**

```bash
# Dry run - lihat apa yang akan diperbaiki
php artisan supply:fix-metadata --dry-run

# Perbaiki metadata yang hilang
php artisan supply:fix-metadata

# Perbaiki metadata untuk purchase records saja
php artisan supply:fix-metadata --source-type=purchase

# Perbaiki metadata untuk farm tertentu
php artisan supply:fix-metadata --farm-id=123

# Force update semua metadata (termasuk yang sudah ada)
php artisan supply:fix-metadata --force

# Custom batch size untuk processing
php artisan supply:fix-metadata --batch-size=50
```

### 3. DataIntegrityCheck Command

Command komprehensif untuk semua jenis perbaikan integritas data.

**Usage:**

```bash
# Check semua jenis integritas (dry run)
php artisan data:integrity-check --dry-run

# Check metadata saja
php artisan data:integrity-check --type=metadata

# Check current supply saja
php artisan data:integrity-check --type=current-supply

# Check purchase batch saja
php artisan data:integrity-check --type=purchase-batch

# Check komprehensif dengan export hasil
php artisan data:integrity-check --export=results.json

# Check dengan filter
php artisan data:integrity-check --type=metadata --source-type=purchase --farm-id=123
```

## Service Integration

### 1. Menggunakan DataIntegrityService di Component

```php
// Di Livewire component atau controller
use App\Services\DataIntegrityService;

class SupplyController extends Controller
{
    public function checkIntegrity(DataIntegrityService $integrityService)
    {
        // Check metadata integrity
        $metadataResults = $integrityService->checkSupplyStockMetadataIntegrity([
            'dry_run' => true,
            'source_type' => 'purchase'
        ]);

        // Check current supply integrity
        $currentSupplyResults = $integrityService->checkCurrentSupplyIntegrity([
            'dry_run' => false
        ]);

        return response()->json([
            'metadata' => $metadataResults,
            'current_supply' => $currentSupplyResults
        ]);
    }
}
```

### 2. Menggunakan di Scheduled Job

```php
// Di routes/console.php atau job class
use App\Services\DataIntegrityService;

Schedule::daily()->call(function () {
    $integrityService = app(DataIntegrityService::class);

    $results = $integrityService->runComprehensiveIntegrityCheck([
        'dry_run' => false
    ]);

    Log::info('Daily data integrity check completed', $results);
});
```

## Metadata Structure

### 1. Purchase Metadata

```json
{
    "source_type": "purchase",
    "purchase_id": "9f62cf62-3137-4e1b-99e9-8ce91da9bc92",
    "invoice_number": "INV-2025-001",
    "supplier_id": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
    "supplier_name": "Supplier Name",
    "batch_code": "PUR-20250714-001",
    "purchase_date": "2025-07-01",
    "delivery_date": "2025-07-01",
    "quality_check": {
        "passed": true,
        "checked_by": "9f48b44b-2142-4b8b-ade3-97e44ea6599c",
        "checked_at": "2025-07-14T11:30:00.000000Z"
    },
    "history": [
        {
            "date": "2025-07-14T11:30:00.000000Z",
            "action": "purchase_created",
            "user_id": "9f48b44b-2142-4b8b-ade3-97e44ea6599c",
            "quantity": 100.0,
            "notes": "Initial purchase record"
        }
    ],
    "tags": ["purchase", "fixed"],
    "custom_fields": {
        "batch_id": "9f62cf62-3137-4e1b-99e9-8ce91da9bc92",
        "do_number": "DO-2025-001",
        "expedition_id": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
        "expedition_fee": 50000,
        "unit_id": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
        "converted_unit": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
        "price_per_unit": 100000,
        "price_per_converted_unit": 100000,
        "fix_applied_at": "2025-07-14T11:30:00.000000Z",
        "fix_method": "data_integrity_service"
    }
}
```

### 2. Mutation Metadata

```json
{
    "source_type": "mutation",
    "mutation_id": "9f62cf62-3137-4e1b-99e9-8ce91da9bc92",
    "batch_code": "MUT-20250714-001",
    "mutation_date": "2025-07-01",
    "quantity": 50.0,
    "quality_check": {
        "passed": true,
        "checked_by": "9f48b44b-2142-4b8b-ade3-97e44ea6599c",
        "checked_at": "2025-07-14T11:30:00.000000Z",
        "notes": "Auto-fixed by data integrity service"
    },
    "history": [
        {
            "date": "2025-07-14T11:30:00.000000Z",
            "action": "metadata_fixed",
            "user_id": "9f48b44b-2142-4b8b-ade3-97e44ea6599c",
            "quantity": 50.0,
            "notes": "Metadata repaired by data integrity service"
        }
    ],
    "tags": ["mutation", "fixed"],
    "custom_fields": {
        "fix_applied_at": "2025-07-14T11:30:00.000000Z",
        "fix_method": "data_integrity_service",
        "original_source_type": "mutation",
        "original_source_id": "9f62cf62-3137-4e1b-99e9-8ce91da9bc92"
    }
}
```

## Integrity Check Types

### 1. Supply Stock Metadata Integrity

**Checks:**

-   Metadata yang hilang (NULL, empty, invalid structure)
-   Metadata validation (required fields, structure)
-   Metadata consistency dengan source data

**Fixes:**

-   Build metadata berdasarkan source type
-   Validate metadata structure
-   Add fix tracking information

### 2. Current Supply Integrity

**Checks:**

-   Quantity mismatch antara CurrentSupply dan SupplyStock
-   Calculation accuracy dari supply stocks

**Fixes:**

-   Recalculate quantity dari supply stocks
-   Update CurrentSupply dengan nilai yang benar

### 3. Supply Purchase Batch Integrity

**Checks:**

-   Orphaned purchases (purchases tanpa batch)
-   Batch-purchase relationship consistency

**Fixes:**

-   Clean up orphaned purchases
-   Ensure proper relationships

## Command Options

### FixSupplyStockMetadata Options

| Option          | Description                                      | Default |
| --------------- | ------------------------------------------------ | ------- |
| `--dry-run`     | Show what would be fixed without making changes  | false   |
| `--batch-size`  | Number of records to process in each batch       | 100     |
| `--force`       | Force update even if metadata already exists     | false   |
| `--source-type` | Filter by source type (purchase, mutation, etc.) | all     |
| `--farm-id`     | Filter by farm ID                                | all     |
| `--supply-id`   | Filter by supply ID                              | all     |

### DataIntegrityCheck Options

| Option          | Description                                                             | Default |
| --------------- | ----------------------------------------------------------------------- | ------- |
| `--dry-run`     | Show what would be fixed without making changes                         | false   |
| `--type`        | Type of integrity check (all, metadata, current-supply, purchase-batch) | all     |
| `--source-type` | Filter by source type for metadata check                                | all     |
| `--farm-id`     | Filter by farm ID                                                       | all     |
| `--supply-id`   | Filter by supply ID                                                     | all     |
| `--force`       | Force update even if data seems valid                                   | false   |
| `--export`      | Export results to JSON file                                             | none    |

## Output Examples

### 1. Dry Run Output

```
🔧 Supply Stock Metadata Fix Tool
================================
⚠️  DRY RUN MODE - No changes will be made
📊 Found 25 records to process

Processing: 100% [████████████████████████████████████████] 25/25

📈 Processing Results:
====================
+------------------+-------+
| Metric           | Count |
+------------------+-------+
| Total Processed  | 25    |
| Fixed            | 20    |
| Skipped          | 5     |
| Errors           | 0     |
+------------------+-------+

⚠️  This was a dry run. Use --force to apply changes.
```

### 2. Comprehensive Check Output

```
🔍 Data Integrity Check Tool
===========================
📋 Running all integrity check...

🔍 Running comprehensive data integrity check...

📈 Supply Stock Metadata Results:
==================================
+------------------+-------+
| Metric           | Count |
+------------------+-------+
| Total Checked    | 100   |
| Missing Metadata | 15    |
| Invalid Metadata | 5     |
| Fixed            | 20    |
| Errors           | 0     |
+------------------+-------+

📊 Current Supply Results:
==========================
+-------------+-------+
| Metric      | Count |
+-------------+-------+
| Total Checked| 50   |
| Mismatched  | 8     |
| Fixed       | 8     |
| Errors      | 0     |
+-------------+-------+

📦 Purchase Batch Results:
==========================
+-------------------+-------+
| Metric            | Count |
+-------------------+-------+
| Total Checked     | 30    |
| Orphaned Purchases| 2     |
| Fixed             | 2     |
| Errors            | 0     |
+-------------------+-------+

📋 Summary:
===========
+-------------+-------+
| Metric      | Count |
+-------------+-------+
| Total Fixed | 30    |
| Total Errors| 0     |
+-------------+-------+

✅ Data integrity check completed successfully!
```

## Error Handling

### 1. Logging

Semua operasi di-log dengan detail:

```php
Log::info('Supply stock metadata fixed', [
    'stock_id' => $stock->id,
    'source_type' => $stock->source_type,
    'metadata_keys' => array_keys($metadata)
]);
```

### 2. Error Recovery

-   Transaction rollback pada error
-   Partial success tracking
-   Detailed error reporting
-   Graceful degradation

### 3. Validation

-   Metadata structure validation
-   Data consistency checks
-   Relationship integrity validation
-   Business rule validation

## Performance Considerations

### 1. Batch Processing

-   Process records in configurable batches
-   Memory efficient for large datasets
-   Progress tracking for long operations

### 2. Database Optimization

-   Efficient queries dengan proper joins
-   Index usage optimization
-   Transaction management

### 3. Monitoring

-   Execution time tracking
-   Memory usage monitoring
-   Success/failure rate tracking

## Best Practices

### 1. Usage Guidelines

1. **Always run dry-run first** untuk melihat impact
2. **Use specific filters** untuk targeted fixes
3. **Monitor logs** untuk error tracking
4. **Export results** untuk audit trail
5. **Schedule regular checks** untuk maintenance

### 2. Service Reusability

1. **Use DataIntegrityService** di components/controllers
2. **Extend service methods** untuk custom checks
3. **Maintain backward compatibility**
4. **Document custom implementations**

### 3. Data Safety

1. **Backup before major operations**
2. **Test on staging environment**
3. **Use transactions for data consistency**
4. **Validate results after fixes**

## Files Structure

```
app/
├── Console/Commands/
│   ├── FixSupplyStockMetadata.php
│   └── DataIntegrityCheck.php
├── Services/
│   ├── DataIntegrityService.php
│   ├── SupplyMetadataService.php
│   └── SupplyStockManagementService.php
└── Models/
    ├── SupplyStock.php
    ├── SupplyPurchase.php
    ├── SupplyPurchaseBatch.php
    └── CurrentSupply.php

docs/
└── commands/
    └── data-integrity-commands.md
```

## Conclusion

Sistem perbaikan integritas data ini memberikan:

✅ **Centralized Management** - Semua perbaikan integritas di satu tempat  
✅ **Service Reusability** - Service dapat digunakan di berbagai context  
✅ **Comprehensive Coverage** - Cakupan semua jenis integritas data  
✅ **Safety Features** - Dry run, validation, error handling  
✅ **Monitoring & Logging** - Complete audit trail dan monitoring  
✅ **Performance Optimized** - Batch processing dan efficient queries  
✅ **Extensible Design** - Mudah diperluas untuk kebutuhan baru

**Status:** ✅ Implemented  
**Testing:** Ready for production  
**Documentation:** Complete
