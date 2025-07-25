# Feed Numbering Fix Command

## Overview

The `feed:fix-numbering` command is designed to fix missing or empty `number` and `number_full` fields for Feed-related records in the database. This command uses the `FeedNumberingService` to generate proper sequential numbering according to the configuration defined in `FeedNumberingConfig`.

## Command Signature

```bash
php artisan feed:fix-numbering [options]
```

## Options

| Option           | Description                                          | Default    | Example                  |
| ---------------- | ---------------------------------------------------- | ---------- | ------------------------ |
| `--type`         | Type of data to fix (purchase, usage, mutation, all) | `purchase` | `--type=usage`           |
| `--dry-run`      | Show what would be fixed without making changes      | `false`    | `--dry-run`              |
| `--batch-size`   | Number of records to process per batch               | `100`      | `--batch-size=50`        |
| `--force`        | Force update even if numbering exists                | `false`    | `--force`                |
| `--livestock-id` | Filter by specific livestock ID                      | `All`      | `--livestock-id=uuid`    |
| `--status`       | Filter by specific status (for purchases)            | `All`      | `--status=arrived`       |
| `--date-from`    | Filter by date from (Y-m-d)                          | `All`      | `--date-from=2025-01-01` |
| `--date-to`      | Filter by date to (Y-m-d)                            | `All`      | `--date-to=2025-01-31`   |
| `--export`       | Export results to JSON file                          | `No`       | `--export=results.json`  |
| `--reset-cache`  | Reset cache before processing                        | `No`       | `--reset-cache`          |

## Supported Types

### 1. Purchase (`--type=purchase`)

-   **Model**: `FeedPurchase`
-   **Table**: `feed_purchases`
-   **Date Field**: `date`
-   **Extra Fields**: `invoice_number`
-   **Filters**: `livestock_id`, `status`, `date`
-   **Numbering Format**: `FDP-001/14/07/2025` (monthly reset)

### 2. Usage (`--type=usage`)

-   **Model**: `FeedUsage`
-   **Table**: `feed_usages`
-   **Date Field**: `usage_date`
-   **Extra Fields**: None
-   **Filters**: `livestock_id`, `usage_date`
-   **Numbering Format**: `FDU-001/14/07/2025` (daily reset)

### 3. Mutation (`--type=mutation`)

-   **Model**: `FeedMutation`
-   **Table**: `feed_mutations`
-   **Date Field**: `date`
-   **Extra Fields**: None
-   **Filters**: `from_livestock_id`, `to_livestock_id`, `date`
-   **Numbering Format**: `FDM-001/14/07/2025` (yearly reset)

## Usage Examples

### Basic Usage

```bash
# Fix purchase numbering
php artisan feed:fix-numbering --type=purchase

# Fix usage numbering
php artisan feed:fix-numbering --type=usage

# Fix mutation numbering
php artisan feed:fix-numbering --type=mutation

# Fix all types
php artisan feed:fix-numbering --type=all
```

### Dry Run (Preview Changes)

```bash
# Preview what would be changed
php artisan feed:fix-numbering --dry-run --type=purchase

# Preview with detailed output
php artisan feed:fix-numbering --dry-run --type=usage --batch-size=10
```

### Force Update (Renumber All Records)

```bash
# Force update all records, even if they already have numbers
php artisan feed:fix-numbering --type=purchase --force

# Force update with dry-run to see changes
php artisan feed:fix-numbering --dry-run --type=purchase --force
```

### Filtered Processing

```bash
# Fix only specific livestock
php artisan feed:fix-numbering --type=purchase --livestock-id=uuid-here

# Fix only specific date range
php artisan feed:fix-numbering --type=usage --date-from=2025-01-01 --date-to=2025-01-31

# Fix only specific status
php artisan feed:fix-numbering --type=purchase --status=arrived
```

### Export Results

```bash
# Export results to JSON file
php artisan feed:fix-numbering --type=purchase --export=feed-purchase-results.json

# Export with dry-run
php artisan feed:fix-numbering --dry-run --type=all --export=preview-results.json
```

## Dry Run Output

The dry-run mode provides detailed before/after information:

```
📋 Record 9f64ca8d-7847-464d-a3fc-3f0fe38d8a62:
   Date: 2025-05-01
   Before: number='10', number_full='FDP-010/01/05/2025'
   After:  number='1', number_full='FDP-001/01/05/2025'
   Status: Would be fixed
```

## Sequence Numbering Logic

### Problem Solved

The command was designed to solve the issue where sequence numbers were not starting from 1 for each reset period. For example:

-   **Before**: 1 record got number `10` instead of `1`
-   **After**: 1 record correctly gets number `1`

### How It Works

1. **Ignore Existing Numbers**: When fixing numbering, the command ignores existing `number` values in the database
2. **Start from 1**: Each reset period (daily/monthly/yearly) starts numbering from 1
3. **Chronological Order**: Records are processed in chronological order within each period
4. **Cache Management**: Uses intelligent cache management to prevent conflicts

### Reset Rules

-   **Daily**: Numbers reset every day (e.g., `FDU-001/01/05/2025`, `FDU-001/02/05/2025`)
-   **Monthly**: Numbers reset every month (e.g., `FDP-001/01/05/2025`, `FDP-001/01/06/2025`)
-   **Yearly**: Numbers reset every year (e.g., `FDM-001/01/05/2025`, `FDM-001/01/05/2026`)

## Database Schema Requirements

The command requires the following columns in each table:

-   `number` (bigint) - Stores the numeric sequence
-   `number_full` (varchar) - Stores the formatted number string

### Verified Tables

✅ `feed_purchases` - Has required columns  
✅ `feed_usages` - Has required columns  
✅ `feed_mutations` - Has required columns

## Error Handling

When errors occur, the command will:

-   Display error details in the console
-   Log errors to Laravel logs
-   Continue processing other records
-   Show error summary in the final report

## Logging

The command logs all activities to Laravel logs:

```php
Log::info("Fixed numbering for FeedPurchase", [
    'record_id' => $rec->id,
    'old_number' => $rec->getOriginal('number'),
    'new_number' => $number,
    'old_number_full' => $rec->getOriginal('number_full'),
    'new_number_full' => $numberFull,
    'date' => $rec->$dateField?->format('Y-m-d')
]);
```

## Performance Considerations

-   Uses batch processing to handle large datasets
-   Implements caching to avoid race conditions
-   Processes records in chronological order
-   Provides progress indicators for long-running operations

## Troubleshooting

### Common Issues

1. **"Column number does not exist"**

    - Ensure the table has `number` and `number_full` columns
    - Run database migrations if needed

2. **"Incorrect integer value"**

    - The `number` column expects integer values
    - The service automatically handles this conversion

3. **"No records found"**

    - Check if there are records matching the filter criteria
    - Verify the date range and status filters

4. **"Sequence numbers not starting from 1"**
    - Use `--force` flag to renumber all records
    - The command automatically starts from 1 for each reset period

### Debug Mode

Enable debug logging by setting `APP_DEBUG=true` in your `.env` file.

## Related Commands

-   `supply:fix-numbering` - Fix numbering for Supply-related records
-   `livestock:generate-numbering` - Generate numbering for Livestock-related records

## Related Files

-   `app/Console/Commands/FixFeedNumbering.php` - Command implementation
-   `app/Services/Feed/FeedNumberingService.php` - Numbering service
-   `app/Config/FeedNumberingConfig.php` - Numbering configuration
-   `app/Models/FeedPurchase.php` - FeedPurchase model
-   `app/Models/FeedUsage.php` - FeedUsage model
-   `app/Models/FeedMutation.php` - FeedMutation model

## Implementation Status

✅ **Completed** - Command is fully functional and tested  
✅ **Dry-run mode** - Shows detailed before/after information  
✅ **Error handling** - Comprehensive error handling and logging  
✅ **Database compatibility** - Works with existing schema  
✅ **Performance optimized** - Batch processing and caching  
✅ **Sequence numbering fixed** - Starts from 1 for each reset period  
✅ **Documentation** - Complete usage guide and examples

## Recent Fixes

### v1.2 - Complete Implementation Alignment

**Problem**: Feed numbering implementation was incomplete compared to Supply and Livestock implementations

**Solution**:

-   Added static `generateNumber()` method for easy integration
-   Implemented flexible date field mapping (`getDateFieldForTable()`)
-   Added auto-generation in `Create.php` components
-   Enhanced error handling with graceful fallbacks
-   Added comprehensive logging and validation methods

**New Features**:

-   **Static Method**: `FeedNumberingService::generateNumber()` for easy integration
-   **Date Field Mapping**: Automatic field detection for different tables
-   **Auto-Generation**: Automatic numbering in Create components
-   **Validation**: `validateTableDateField()` method
-   **Statistics**: `getNumberingStats()` method
-   **Context Support**: Custom placeholder support

**Integration Examples**:

```php
// Auto-generation in Create.php
$numbering = FeedNumberingService::generateNumber('feed_purchases', $date);
$purchase->number = $numbering['number'];
$purchase->number_full = $numbering['full_number'];

// With custom context
$numbering = FeedNumberingService::generateNumber('feed_purchases', $date, [
    'custom_field' => 'value'
]);
```

### v1.1 - Sequence Numbering Fix

**Problem**: Records were getting incorrect sequence numbers (e.g., 1 record got number 10 instead of 1)

**Solution**:

-   Added `setIgnoreExisting()` method to ignore existing numbers during fix operations
-   Implemented proper reset logic for each period (daily/monthly/yearly)
-   Enhanced cache management to prevent sequence conflicts
-   Added `--force` flag for complete renumbering

**Result**: Sequence numbers now correctly start from 1 for each reset period

## Comparison with Supply & Livestock

| Feature            | Supply                 | Livestock                   | Feed (v1.2)                 |
| ------------------ | ---------------------- | --------------------------- | --------------------------- |
| Static Method      | ✅ `generateNumber()`  | ✅ `generateNumber()`       | ✅ `generateNumber()`       |
| Date Field Mapping | ✅ Flexible            | ✅ `getDateFieldForTable()` | ✅ `getDateFieldForTable()` |
| Auto-Generation    | ✅ Create.php          | ✅ Create.php               | ✅ Create.php               |
| Error Handling     | ✅ Graceful            | ✅ Graceful                 | ✅ Graceful                 |
| Validation         | ✅ Table validation    | ✅ Field validation         | ✅ Field validation         |
| Statistics         | ❌                     | ✅ `getNumberingStats()`    | ✅ `getNumberingStats()`    |
| Context Support    | ✅ Custom placeholders | ✅ Custom placeholders      | ✅ Custom placeholders      |

**Status**: Feed numbering implementation is now **fully aligned** with Supply and Livestock implementations.
