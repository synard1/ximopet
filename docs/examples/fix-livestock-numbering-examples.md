# Fix Livestock Numbering Command Examples

## Quick Start Examples

### 1. Basic Dry Run

```bash
# Check what would be fixed without making changes
php artisan livestock:fix-numbering --dry-run --verbose
```

**Expected Output:**

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

### 2. Fix All Missing Numbering

```bash
# Fix all livestock records with missing numbering (default limit: 100)
php artisan livestock:fix-numbering
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 100 records
   • Company ID: All
   • Farm ID: All
   • Coop ID: All
   • Date: Current date
   • Force Update: No
   • Verbose: No

⚠️  This will update Livestock records in the database
Do you want to continue? (yes/no) [no]:
> yes

🔍 Querying Livestock records...
📊 Found 15 Livestock records to process

[████████████████████] 100%

📊 Results Summary:

   • Total Processed: 15
   • Total Fixed: 15
   • Total Skipped: 0
   • Total Errors: 0

✅ Process completed successfully!
```

### 3. Fix with Custom Limit

```bash
# Fix up to 500 records
php artisan livestock:fix-numbering --limit=500 --verbose
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 500 records
   • Company ID: All
   • Farm ID: All
   • Coop ID: All
   • Date: Current date
   • Force Update: No
   • Verbose: Yes

⚠️  This will update Livestock records in the database
Do you want to continue? (yes/no) [no]:
> yes

🔍 Querying Livestock records...
📊 Found 250 Livestock records to process

✅ Fixed Livestock ID 123:
   • Old: number='', number_full=''
   • New: number='LS-2024-001', number_full='LS-2024-001-FARM01-COOP01'

✅ Fixed Livestock ID 124:
   • Old: number='', number_full=''
   • New: number='LS-2024-002', number_full='LS-2024-002-FARM01-COOP02'

[████████████████████] 100%

📊 Results Summary:

   • Total Processed: 250
   • Total Fixed: 250
   • Total Skipped: 0
   • Total Errors: 0

✅ Process completed successfully!
```

## Filtered Examples

### 4. Fix Specific Company

```bash
# Fix numbering for company ID 1 only
php artisan livestock:fix-numbering --company-id=1 --limit=200 --verbose
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 200 records
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
📊 Found 75 Livestock records to process

✅ Fixed Livestock ID 123:
   • Old: number='', number_full=''
   • New: number='LS-2024-001', number_full='LS-2024-001-FARM01-COOP01'

[████████████████████] 100%

📊 Results Summary:

   • Total Processed: 75
   • Total Fixed: 75
   • Total Skipped: 0
   • Total Errors: 0
   • Companies Processed: 1

✅ Process completed successfully!
```

### 5. Fix Specific Farm

```bash
# Fix numbering for farm ID 5 only
php artisan livestock:fix-numbering --farm-id=5 --verbose
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 100 records
   • Company ID: All
   • Farm ID: 5
   • Coop ID: All
   • Date: Current date
   • Force Update: No
   • Verbose: Yes

⚠️  This will update Livestock records in the database
Do you want to continue? (yes/no) [no]:
> yes

🔍 Querying Livestock records...
📊 Found 12 Livestock records to process

✅ Fixed Livestock ID 456:
   • Old: number='', number_full=''
   • New: number='LS-2024-001', number_full='LS-2024-001-FARM05-COOP01'

[████████████████████] 100%

📊 Results Summary:

   • Total Processed: 12
   • Total Fixed: 12
   • Total Skipped: 0
   • Total Errors: 0
   • Farms Processed: 5

✅ Process completed successfully!
```

### 6. Fix with Specific Date

```bash
# Use specific date for numbering generation
php artisan livestock:fix-numbering --date=2024-01-15 --dry-run --verbose
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: Yes
   • Limit: 100 records
   • Company ID: All
   • Farm ID: All
   • Coop ID: All
   • Date: 2024-01-15
   • Force Update: No
   • Verbose: Yes

⚠️  DRY RUN MODE: No changes will be made to the database

🔍 Querying Livestock records...
📊 Found 8 Livestock records to process

🔍 DRY RUN - Would fix Livestock ID 789:
   • Current: number='', number_full=''
   • Would set: number='LS-2024-015', number_full='LS-2024-015-FARM01-COOP01'

📊 Results Summary:

   • Total Processed: 8
   • Total Fixed: 8
   • Total Skipped: 0
   • Total Errors: 0

⚠️  DRY RUN MODE: No actual changes were made to the database

✅ Process completed successfully!
```

## Advanced Examples

### 7. Force Update All Records

```bash
# Force update all records (even if numbering exists)
php artisan livestock:fix-numbering --force --limit=50 --verbose
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 50 records
   • Company ID: All
   • Farm ID: All
   • Coop ID: All
   • Date: Current date
   • Force Update: Yes
   • Verbose: Yes

⚠️  This will update Livestock records in the database
Do you want to continue? (yes/no) [no]:
> yes

🔍 Querying Livestock records...
📊 Found 50 Livestock records to process

✅ Fixed Livestock ID 123:
   • Old: number='OLD-001', number_full='OLD-001-FARM01-COOP01'
   • New: number='LS-2024-001', number_full='LS-2024-001-FARM01-COOP01'

✅ Fixed Livestock ID 124:
   • Old: number='OLD-002', number_full='OLD-002-FARM01-COOP02'
   • New: number='LS-2024-002', number_full='LS-2024-002-FARM01-COOP02'

[████████████████████] 100%

📊 Results Summary:

   • Total Processed: 50
   • Total Fixed: 50
   • Total Skipped: 0
   • Total Errors: 0

✅ Process completed successfully!
```

### 8. Comprehensive Fix

```bash
# Fix with all options for comprehensive processing
php artisan livestock:fix-numbering \
    --company-id=1 \
    --farm-id=5 \
    --limit=200 \
    --date=2024-01-15 \
    --verbose
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 200 records
   • Company ID: 1
   • Farm ID: 5
   • Coop ID: All
   • Date: 2024-01-15
   • Force Update: No
   • Verbose: Yes

⚠️  This will update Livestock records in the database
Do you want to continue? (yes/no) [no]:
> yes

🔍 Querying Livestock records...
📊 Found 45 Livestock records to process

✅ Fixed Livestock ID 456:
   • Old: number='', number_full=''
   • New: number='LS-2024-015', number_full='LS-2024-015-FARM05-COOP01'

✅ Fixed Livestock ID 457:
   • Old: number='', number_full=''
   • New: number='LS-2024-016', number_full='LS-2024-016-FARM05-COOP02'

[████████████████████] 100%

📊 Results Summary:

   • Total Processed: 45
   • Total Fixed: 45
   • Total Skipped: 0
   • Total Errors: 0
   • Companies Processed: 1
   • Farms Processed: 5

✅ Process completed successfully!
```

## Error Examples

### 9. Invalid Date Format

```bash
php artisan livestock:fix-numbering --date=2024/01/15
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

❌ Invalid date format: 2024/01/15. Use Y-m-d format (e.g., 2024-01-15)
```

### 10. Invalid Limit

```bash
php artisan livestock:fix-numbering --limit=0
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

❌ Limit must be greater than 0
```

### 11. No Records Found

```bash
php artisan livestock:fix-numbering --company-id=999
```

**Expected Output:**

```
🚀 Starting Livestock Numbering Fix Process...

✅ Options validation passed

📋 Configuration:
   • Dry Run: No
   • Limit: 100 records
   • Company ID: 999
   • Farm ID: All
   • Coop ID: All
   • Date: Current date
   • Force Update: No
   • Verbose: No

⚠️  This will update Livestock records in the database
Do you want to continue? (yes/no) [no]:
> yes

🔍 Querying Livestock records...
📊 Found 0 Livestock records to process

✅ No records need fixing

📊 Results Summary:

   • Total Processed: 0
   • Total Fixed: 0
   • Total Skipped: 0
   • Total Errors: 0
   • Companies Processed: 999

✅ Process completed successfully!
```

## Log Examples

### 12. Individual Fix Log

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

### 13. Final Statistics Log

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

## Testing Scenarios

### 14. Testing with Large Dataset

```bash
# Test with large dataset to check performance
php artisan livestock:fix-numbering --limit=1000 --verbose
```

### 15. Testing Error Handling

```bash
# Test error handling by using invalid service configuration
php artisan livestock:fix-numbering --company-id=1 --limit=10
```

### 16. Testing Dry Run vs Actual

```bash
# Compare dry run vs actual execution
php artisan livestock:fix-numbering --dry-run --verbose
php artisan livestock:fix-numbering --verbose
```

## Best Practices Examples

### 17. Always Use Dry Run First

```bash
# Best practice: Always test with dry-run first
php artisan livestock:fix-numbering --dry-run --verbose --company-id=1
```

### 18. Use Appropriate Limits

```bash
# Best practice: Use smaller limits for large datasets
php artisan livestock:fix-numbering --limit=50 --verbose --company-id=1
```

### 19. Monitor Logs

```bash
# Best practice: Monitor logs during execution
tail -f storage/logs/laravel.log | grep "Fixed Livestock numbering"
```

### 20. Backup Before Execution

```bash
# Best practice: Always backup before running on production
php artisan backup:run
php artisan livestock:fix-numbering --company-id=1 --verbose
```

## Troubleshooting Examples

### 21. Check Command Help

```bash
# Get help for the command
php artisan livestock:fix-numbering --help
```

### 22. Check Available Options

```bash
# List all available options
php artisan list | grep livestock
```

### 23. Debug Service Issues

```bash
# Test numbering service directly
php artisan tinker
>>> App\Services\Livestock\LivestockNumberGeneratorService::generateNumber('livestocks', now(), [])
```

### 24. Check Database State

```bash
# Check current livestock numbering state
php artisan tinker
>>> App\Models\Livestock::whereNull('number')->orWhere('number', '')->count()
```
