# MySQL Index Name Length Fix

**Date:** 2025-01-24 10:54:12  
**Issue:** Database migration failing due to MySQL index name length limit  
**Status:** Fixed

## Problem Description

The migration `2025_04_19_105412_create_livestock_management_table.php` was failing with the error:

```
SQLSTATE[42000]: Syntax error or access violation: 1059 Identifier name 'livestock_mutations_company_id_source_livestock_id_direction_index' is too long
```

This occurred because MySQL has a limit of 64 characters for index names, and Laravel's auto-generated index names were exceeding this limit.

## Root Cause

Laravel automatically generates index names using the pattern: `{table_name}_{column1}_{column2}_{column3}_index`

For the `livestock_mutations` table with columns like `company_id`, `source_livestock_id`, and `direction`, the auto-generated name became:
`livestock_mutations_company_id_source_livestock_id_direction_index` (67 characters)

This exceeds MySQL's 64-character limit.

## Solution Implemented

### 1. Fixed `2025_04_19_105412_create_livestock_management_table.php`

**Livestocks table:**

-   Changed: `$table->index(['id', 'start_date']);`
-   To: `$table->index(['id', 'start_date'], 'ls_id_start_date_idx');`

**Livestock Purchase Items table:**

-   Changed: `$table->index(['livestock_id']);`
-   To: `$table->index(['livestock_id'], 'lpi_livestock_id_idx');`

**Livestock Mutations table:**

-   Changed all auto-generated indexes to custom names:
    -   `['company_id', 'tanggal']` → `'lm_co_date_idx'`
    -   `['source_livestock_id', 'direction']` → `'lm_src_dir_idx'`
    -   `['destination_livestock_id', 'direction']` → `'lm_dest_dir_idx'`
    -   `['jenis', 'direction']` → `'lm_type_dir_idx'`
    -   `['tanggal', 'company_id']` → `'lm_date_co_idx'`
    -   `['created_at']` → `'lm_created_idx'`
    -   `['deleted_at']` → `'lm_deleted_idx'`

### 2. Fixed `2025_04_24_000000_update_livestock_mutations_structure.php`

**Livestock Mutations table:**

-   Applied same custom index names as above

**Livestock Mutation Items table:**

-   `['livestock_mutation_id']` → `'lmi_mutation_id_idx'`
-   `['batch_id']` → `'lmi_batch_id_idx'`
-   `['created_by']` → `'lmi_created_by_idx'`
-   `['updated_by']` → `'lmi_updated_by_idx'`

## Index Naming Convention

Adopted a consistent naming convention:

-   `ls_` = Livestocks table
-   `lpi_` = Livestock Purchase Items table
-   `lm_` = Livestock Mutations table
-   `lmi_` = Livestock Mutation Items table

Followed by descriptive abbreviations:

-   `co` = company
-   `src` = source
-   `dest` = destination
-   `dir` = direction
-   `date` = tanggal/date
-   `idx` = index

## Testing

The migration should now run successfully without the index name length error.

## Prevention

For future migrations:

1. Always use custom index names for composite indexes
2. Follow the established naming convention
3. Keep index names under 50 characters to provide buffer
4. Test migrations on MySQL environments

## Files Modified

1. `database/migrations/2025_04_19_105412_create_livestock_management_table.php`
2. `database/migrations/2025_04_24_000000_update_livestock_mutations_structure.php`

## Related Issues

This fix prevents similar issues in other migrations that might have long table or column names.
