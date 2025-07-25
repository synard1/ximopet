# Fix Livestock Numbering Command

## Overview

Command `livestock:fix-numbering` digunakan untuk memperbaiki field `number` dan `number_full` yang kosong pada model Livestock. Command ini menggunakan `LivestockNumberGeneratorService` untuk generate numbering yang konsisten dengan sistem yang ada.

## Command Signature

```bash
php artisan livestock:fix-numbering [options]
```

## Options

### Basic Options

| Option      | Description                                     | Default | Example      |
| ----------- | ----------------------------------------------- | ------- | ------------ |
| `--dry-run` | Show what would be fixed without making changes | `false` | `--dry-run`  |
| `--limit`   | Maximum number of records to process            | `100`   | `--limit=50` |
| `--force`   | Force update even if numbering exists           | `false` | `--force`    |
| `--verbose` | Show detailed output (Laravel built-in)         | `false` | `--verbose`  |

### Filter Options

| Option         | Description                                     | Example             |
| -------------- | ----------------------------------------------- | ------------------- |
| `--company-id` | Process only specific company ID                | `--company-id=1`    |
| `--farm-id`    | Process only specific farm ID                   | `--farm-id=5`       |
| `--coop-id`    | Process only specific coop ID                   | `--coop-id=10`      |
| `--date`       | Use specific date for numbering (format: Y-m-d) | `--date=2024-01-15` |

## Usage Examples

### 1. Basic Usage - Dry Run

```bash
# Check what would be fixed without making changes
php artisan livestock:fix-numbering --dry-run --verbose
```

### 2. Fix All Missing Numbering

```bash
# Fix all livestock records with missing numbering
php artisan livestock:fix-numbering --limit=1000
```

### 3. Fix Specific Company

```bash
# Fix numbering for specific company only
php artisan livestock:fix-numbering --company-id=1 --limit=500
```

### 4. Fix Specific Farm

```bash
# Fix numbering for specific farm only
php artisan livestock:fix-numbering --farm-id=5 --verbose
```

### 5. Force Update All Records

```bash
# Force update all records (even if numbering exists)
php artisan livestock:fix-numbering --force --limit=100
```

### 6. Use Specific Date

```bash
# Use specific date for numbering generation
php artisan livestock:fix-numbering --date=2024-01-15 --dry-run
```

### 7. Comprehensive Fix

```bash
# Fix with all options for comprehensive processing
php artisan livestock:fix-numbering \
    --company-id=1 \
    --farm-id=5 \
    --limit=200 \
    --date=2024-01-15 \
    --verbose
```

## Command Flow

### 1. Validation Phase

-   Validates command options
-   Checks date format if provided
-   Ensures limit is greater than 0

### 2. Configuration Display

-   Shows current configuration
-   Displays all active options
-   Confirms execution with user (unless dry-run)

### 3. Processing Phase

-   Queries Livestock records based on filters
-   Applies numbering condition (missing or force)
-   Processes records with progress bar
-   Generates numbering using LivestockNumberGeneratorService

### 4. Results Phase

-   Shows comprehensive statistics
-   Displays error summary if any
-   Logs final execution data

## Output Examples

### Dry Run Output

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: Yes
   • Limit: 100 records
   • Company ID: All
   • Farm ID: All
   • Coop ID: All
   • Date: Current date
   • Force Update: No
   • Verbose: Yes

⚠️  DRY RUN MODE: No changes will be made to the database

🔍 Querying Livestock records...
📊 Found 25 Livestock records to process

🔍 DRY RUN - Would fix Livestock ID 123:
   • Current: number='', number_full=''
   • Would set: number='LS-2024-001', number_full='LS-2024-001-FARM01-COOP01'

📊 Results Summary:

   • Total Processed: 25
   • Total Fixed: 25
   • Total Skipped: 0
   • Total Errors: 0

⚠️  DRY RUN MODE: No actual changes were made to the database

✅ Process completed successfully!
```

### Actual Execution Output

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 100 records
   • Company ID: 1
   • Farm ID: All
   • Coop ID: All
   • Date: Current date
   • Force Update: No
   • Verbose: Yes

⚠️  This will update Livestock records in the database
Do you want to continue? (yes/no) [no]:
> yes

🔍 Querying Livestock records...
📊 Found 15 Livestock records to process

✅ Fixed Livestock ID 123:
   • Old: number='', number_full=''
   • New: number='LS-2024-001', number_full='LS-2024-001-FARM01-COOP01'

📊 Results Summary:

   • Total Processed: 15
   • Total Fixed: 15
   • Total Skipped: 0
   • Total Errors: 0
   • Companies Processed: 1

✅ Process completed successfully!
```

## Logging

### Command Execution Log

```json
{
    "level": "info",
    "message": "📈 Livestock numbering fix command completed",
    "context": {
        "command": "livestock:fix-numbering",
        "execution_time": "2024-01-15 10:30:00",
        "options": {
            "dry_run": false,
            "limit": 100,
            "company_id": "1",
            "farm_id": null,
            "coop_id": null,
            "date": null,
            "force": false,
            "verbose": true
        },
        "statistics": {
            "total_processed": 15,
            "total_fixed": 15,
            "total_skipped": 0,
            "total_errors": 0,
            "companies_processed": ["1"],
            "farms_processed": [],
            "errors": []
        }
    }
}
```

### Individual Fix Log

```json
{
    "level": "info",
    "message": "🔧 Fixed Livestock numbering",
    "context": {
        "livestock_id": "uuid-123",
        "livestock_name": "PR-FARM01-COOP01-150124",
        "old_number": "",
        "old_number_full": "",
        "new_number": "LS-2024-001",
        "new_number_full": "LS-2024-001-FARM01-COOP01",
        "farm_id": 1,
        "coop_id": 1,
        "company_id": 1,
        "updated_at": "2024-01-15 10:30:00"
    }
}
```

## Error Handling

### Common Errors

#### 1. Invalid Date Format

```
❌ Invalid date format: 2024/01/15. Use Y-m-d format (e.g., 2024-01-15)
```

#### 2. Invalid Limit

```
❌ Limit must be greater than 0
```

#### 3. Numbering Generation Error

```
❌ Error processing Livestock ID 123: Failed to generate numbering: Service unavailable
```

### Error Recovery

-   **Individual Record Errors**: Command continues processing other records
-   **Database Errors**: Automatic rollback for failed transactions
-   **Service Errors**: Detailed error logging for debugging
-   **Validation Errors**: Command exits with error code 1

## Safety Features

### 1. Dry Run Mode

-   Preview changes without making actual updates
-   Safe for testing and validation
-   Shows exactly what would be changed

### 2. User Confirmation

-   Requires explicit confirmation for actual changes
-   Prevents accidental execution
-   Clear warning about database modifications

### 3. Transaction Safety

-   Each update wrapped in database transaction
-   Automatic rollback on errors
-   Data integrity protection

### 4. Comprehensive Logging

-   Detailed execution logs
-   Individual record update logs
-   Error tracking and reporting

## Performance Considerations

### 1. Batch Processing

-   Processes records in batches (default: 100)
-   Configurable limit for memory management
-   Progress bar for long-running operations

### 2. Database Optimization

-   Uses eager loading for relationships
-   Efficient query building with filters
-   Index-friendly ordering

### 3. Memory Management

-   Processes records one by one
-   No bulk loading of large datasets
-   Garbage collection friendly

## Best Practices

### 1. Always Use Dry Run First

```bash
# Always test with dry-run first
php artisan livestock:fix-numbering --dry-run --verbose
```

### 2. Use Appropriate Limits

```bash
# For large datasets, use smaller limits
php artisan livestock:fix-numbering --limit=50 --verbose
```

### 3. Filter by Company/Farm

```bash
# Process specific entities to reduce scope
php artisan livestock:fix-numbering --company-id=1 --farm-id=5
```

### 4. Monitor Logs

```bash
# Check logs after execution
tail -f storage/logs/laravel.log | grep "Fixed Livestock numbering"
```

### 5. Backup Before Execution

```bash
# Always backup before running on production
php artisan backup:run
```

## Troubleshooting

### Common Issues

#### 1. No Records Found

-   Check if filters are too restrictive
-   Verify livestock records exist
-   Check if numbering condition is correct

#### 2. Service Errors

-   Verify LivestockNumberGeneratorService is working
-   Check service configuration
-   Review service logs

#### 3. Database Errors

-   Check database connectivity
-   Verify table structure
-   Review database logs

#### 4. Permission Issues

-   Ensure proper file permissions
-   Check database user permissions
-   Verify command execution rights

## Laravel Built-in Options

Command ini menggunakan Laravel's built-in options yang tersedia secara default:

| Option                       | Description                               | Example            |
| ---------------------------- | ----------------------------------------- | ------------------ |
| `--verbose` atau `-v`        | Increase verbosity (show detailed output) | `--verbose`        |
| `--quiet` atau `-q`          | Suppress all output                       | `--quiet`          |
| `--help` atau `-h`           | Show command help                         | `--help`           |
| `--no-interaction` atau `-n` | Do not ask any interactive question       | `--no-interaction` |

### Best Practices for Built-in Options

-   **Verbose Mode**: Gunakan `--verbose` untuk debugging dan detailed output
-   **Quiet Mode**: Gunakan `--quiet` untuk automated scripts
-   **No Interaction**: Gunakan `--no-interaction` untuk non-interactive execution
-   **Help**: Gunakan `--help` untuk melihat semua available options

## Related Commands

-   `php artisan livestock:list` - List livestock records
-   `php artisan backup:run` - Create database backup
-   `php artisan migrate:status` - Check migration status

## Related Documentation

-   [Livestock Model Documentation](../models/livestock-model.md)
-   [LivestockNumberGeneratorService Documentation](../services/livestock-numbering-service.md)
-   [Database Backup Documentation](../maintenance/backup.md)
