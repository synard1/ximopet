# Supply Purchase Numbering Fix Command

## Overview

The `supply:fix-numbering` command is designed to fix missing or invalid `number` and `number_full` fields for `SupplyPurchaseBatch` records. This command uses the existing `SupplyNumberGeneratorService` to generate proper numbering according to the configured format.

## Command Signature

```bash
php artisan supply:fix-numbering [options]
```

## Options

| Option             | Description                                     | Default      |
| ------------------ | ----------------------------------------------- | ------------ |
| `--dry-run`        | Show what would be fixed without making changes | false        |
| `--batch-size=100` | Number of records to process per batch          | 100          |
| `--force`          | Force update even if numbering exists           | false        |
| `--farm-id=`       | Filter by specific farm ID                      | All farms    |
| `--status=`        | Filter by specific status                       | All statuses |
| `--date-from=`     | Filter by date from (Y-m-d format)              | All dates    |
| `--date-to=`       | Filter by date to (Y-m-d format)                | All dates    |
| `--export=`        | Export results to JSON file                     | No export    |

## Usage Examples

### Basic Usage

```bash
# Check what would be fixed without making changes
php artisan supply:fix-numbering --dry-run

# Fix all records with missing numbering
php artisan supply:fix-numbering

# Force update all records (even if numbering exists)
php artisan supply:fix-numbering --force
```

### Filtered Usage

```bash
# Fix only records for a specific farm
php artisan supply:fix-numbering --farm-id=uuid-here

# Fix only draft status records
php artisan supply:fix-numbering --status=draft

# Fix records from a specific date range
php artisan supply:fix-numbering --date-from=2025-01-01 --date-to=2025-01-31

# Combine multiple filters
php artisan supply:fix-numbering --farm-id=uuid-here --status=arrived --date-from=2025-01-01
```

### Export Results

```bash
# Export results to JSON file
php artisan supply:fix-numbering --export=results.json

# Dry run with export
php artisan supply:fix-numbering --dry-run --export=dry-run-results.json
```

### Performance Tuning

```bash
# Process in smaller batches for memory-constrained environments
php artisan supply:fix-numbering --batch-size=50

# Process in larger batches for better performance
php artisan supply:fix-numbering --batch-size=200
```

## Numbering Format

The command uses the `SupplyNumberGeneratorService` with the following configuration for `supply_purchase_batches`:

-   **Format**: `{prefix}-{urut:5}/{TGL}/{BLN}/{THN}`
-   **Prefix**: `SPB`
-   **Reset Rule**: Daily
-   **Example**: `SPB-00001/14/07/2025`

### Format Breakdown

-   `{prefix}`: Always "SPB" for Supply Purchase Batch
-   `{urut:5}`: Sequential number padded to 5 digits, resets daily
-   `{TGL}`: Day (2 digits)
-   `{BLN}`: Month (2 digits)
-   `{THN}`: Year (4 digits)

## How It Works

### 1. Pre-flight Checks

The command performs several validation checks before processing:

-   Verifies `SupplyNumberGeneratorService` class exists
-   Verifies `SupplyPurchaseBatch` model exists
-   Tests database connection
-   Checks if `supply_purchase_batches` table exists

### 2. Record Filtering

Records are filtered based on:

-   **Default**: Only records with missing or empty `number`/`number_full` fields
-   **With `--force`**: All records regardless of existing numbering
-   **Additional filters**: Farm ID, status, date range

### 3. Batch Processing

Records are processed in configurable batches to:

-   Manage memory usage
-   Provide progress feedback
-   Allow for graceful interruption

### 4. Numbering Generation

For each record:

1. Uses the record's `date` field (falls back to `created_at`)
2. Calls `SupplyNumberGeneratorService::generateNumber()`
3. Updates both `number` and `number_full` fields
4. Logs the changes for audit trail

## Output and Reporting

### Console Output

The command provides detailed console output including:

-   Configuration summary
-   Progress bar during processing
-   Processing report with statistics
-   Sample of fixed records
-   Sample of errors (if any)

### Example Output

```
🔧 Starting Supply Purchase Numbering Fix
📊 This will fix missing number and number_full fields for SupplyPurchaseBatch records

┌─────────────────┬─────────────┐
│ Setting         │ Value       │
├─────────────────┼─────────────┤
│ Dry Run         │ No          │
│ Batch Size      │ 100         │
│ Force Update    │ No          │
│ Farm ID Filter  │ All         │
│ Status Filter   │ All         │
│ Date From       │ All         │
│ Date To         │ All         │
│ Export Results  │ No          │
└─────────────────┴─────────────┘

🔍 Performing pre-flight checks...
✅ Pre-flight checks passed
📋 Found 25 records to process

████████████████████████████████████████ 25/25 [100%]

📊 Processing Report
┌──────────────┬───────┐
│ Metric       │ Count │
├──────────────┼───────┤
│ Total Processed │ 25   │
│ Fixed        │ 23   │
│ Skipped      │ 2    │
│ Errors       │ 0    │
│ Duration     │ 1.2 seconds │
└──────────────┴───────┘

✅ Sample of Fixed Records
┌──────────────┬──────────────┬────────────┬──────────────┬──────────────┬─────────────────────┐
│ ID           │ Invoice      │ Date       │ Old Number   │ New Number   │ New Full Number     │
├──────────────┼──────────────┼────────────┼──────────────┼──────────────┼─────────────────────┤
│ 12345678...  │ INV-001      │ 2025-01-15 │ NULL         │ 1            │ SPB-00001/15/01/2025 │
│ 87654321...  │ INV-002      │ 2025-01-15 │ NULL         │ 2            │ SPB-00002/15/01/2025 │
└──────────────┴──────────────┴────────────┴──────────────┴──────────────┴─────────────────────┘

✅ Supply Purchase Numbering Fix completed successfully
```

### JSON Export Format

When using `--export`, the command creates a JSON file with:

```json
{
    "command": "supply:fix-numbering",
    "executed_at": "2025-01-15T10:30:00.000000Z",
    "options": {
        "dry_run": false,
        "batch_size": 100,
        "force": false,
        "farm_id": null,
        "status": null,
        "date_from": null,
        "date_to": null
    },
    "summary": {
        "total_processed": 25,
        "total_fixed": 23,
        "total_skipped": 2,
        "total_errors": 0
    },
    "details": [
        {
            "id": "uuid-here",
            "invoice_number": "INV-001",
            "date": "2025-01-15",
            "status": "draft",
            "old_number": null,
            "old_number_full": null,
            "new_number": 1,
            "new_number_full": "SPB-00001/15/01/2025",
            "status": "fixed",
            "message": "Numbering generated successfully"
        }
    ]
}
```

## Error Handling

### Common Errors

1. **Service Not Found**: `SupplyNumberGeneratorService not found`

    - **Solution**: Ensure the service class exists and is properly namespaced

2. **Model Not Found**: `SupplyPurchaseBatch model not found`

    - **Solution**: Ensure the model class exists and is properly namespaced

3. **Database Connection Failed**: `Database connection failed`

    - **Solution**: Check database configuration and connectivity

4. **Table Not Found**: `supply_purchase_batches table not found`
    - **Solution**: Run migrations to create the table

### Error Logging

All errors are logged with detailed information:

-   Error message
-   File and line number
-   Batch ID and invoice number
-   Full stack trace

## Best Practices

### 1. Always Use Dry Run First

```bash
# Always test with dry-run first
php artisan supply:fix-numbering --dry-run
```

### 2. Use Appropriate Batch Sizes

```bash
# For large datasets, use smaller batches
php artisan supply:fix-numbering --batch-size=50

# For small datasets, use larger batches for better performance
php artisan supply:fix-numbering --batch-size=200
```

### 3. Filter When Possible

```bash
# Process specific farms or date ranges to reduce scope
php artisan supply:fix-numbering --farm-id=uuid-here --date-from=2025-01-01
```

### 4. Export Results for Audit

```bash
# Always export results for audit trail
php artisan supply:fix-numbering --export=fix-results-$(date +%Y%m%d).json
```

### 5. Monitor Logs

Check Laravel logs for detailed information:

```bash
tail -f storage/logs/laravel.log | grep "SupplyPurchaseBatch numbering"
```

## Integration with Existing Systems

### Create.php Integration

The command complements the existing numbering logic in `Create.php`:

```php
// In Create.php - existing logic
if (empty($batch->number) || empty($batch->number_full)) {
    $numbering = SupplyNumberGeneratorService::generateNumber('supply_purchase_batches', $batch->date ?? now(), []);
    $batch->number = $numbering['number'];
    $batch->number_full = $numbering['full_number'];
    $batch->save();
}
```

### Scheduled Execution

Add to `routes/console.php` for automated fixes:

```php
// Fix numbering daily at 2 AM
$schedule->command('supply:fix-numbering --dry-run --export=logs/numbering-check-' . date('Y-m-d') . '.json')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Fix numbering weekly on Sundays
$schedule->command('supply:fix-numbering --export=logs/numbering-fix-' . date('Y-m-d') . '.json')
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->withoutOverlapping();
```

## Troubleshooting

### Issue: No Records Found

**Symptoms**: Command reports "No records found matching the criteria"

**Solutions**:

1. Check if records exist: `php artisan tinker` → `App\Models\SupplyPurchaseBatch::count()`
2. Verify filters: Remove filters to see all records
3. Check date format: Ensure dates are in Y-m-d format

### Issue: Numbering Conflicts

**Symptoms**: Duplicate numbers generated

**Solutions**:

1. Check existing numbering: `php artisan tinker` → `App\Models\SupplyPurchaseBatch::whereNotNull('number')->get(['number', 'number_full', 'date'])`
2. Use `--force` to regenerate all numbering
3. Check `SupplyNumberGeneratorService` logic

### Issue: Performance Problems

**Symptoms**: Command runs slowly or times out

**Solutions**:

1. Reduce batch size: `--batch-size=25`
2. Add filters to reduce scope
3. Run during low-traffic periods
4. Monitor server resources

### Issue: Permission Errors

**Symptoms**: Cannot write to export file or database

**Solutions**:

1. Check file permissions for export directory
2. Verify database user permissions
3. Run with appropriate user privileges

## Security Considerations

1. **Database Access**: Command requires database write access
2. **File System**: Export option requires file system write access
3. **Logging**: Sensitive data may be logged (invoice numbers, IDs)
4. **Audit Trail**: All changes are logged for audit purposes

## Performance Considerations

1. **Memory Usage**: Large datasets may require increased PHP memory limit
2. **Database Load**: Command performs multiple database operations
3. **Locking**: Consider running during maintenance windows
4. **Indexing**: Ensure proper indexes on `number`, `number_full`, `date` fields

## Related Commands

-   `supply:fix-metadata` - Fix missing metadata in supply stock records
-   `data:integrity-check` - Comprehensive data integrity checks
-   `supply:validate` - Validate supply data integrity

## Support

For issues or questions:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Run with verbose output: `php artisan supply:fix-numbering -v`
3. Export results for analysis: `--export=debug.json`
4. Contact development team with error details
