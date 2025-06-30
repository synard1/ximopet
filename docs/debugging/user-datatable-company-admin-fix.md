# User DataTable Blank for Company Admin Fix

**Date:** 2025-07-05

## Issue

When logged in as a **Company Administrator**, the _Users_ page showed **"No data available in table"** even though the company already had active users. Super-admins were unaffected.

## Root Causes

1. `UsersDataTable::query()` only returned users that had an **active** entry in the `company_users` pivot table.
2. For legacy / newly–seeded data some users only stored the company reference directly in `users.company_id` and did **not** yet have an active pivot mapping. These records were therefore excluded from the result set, producing an empty DataTable.

## Solution

Extended the query logic so that _Company Admins_ now fetch:

-   Users linked **via `company_users`** (status = `active`), **OR**
-   Users whose `users.company_id` matches the admin's company (legacy fallback).

Super-admin & non-admin behaviour remains unchanged. We also eager-load the `roles` relationship to reduce N+1 queries.

## Files Changed

-   **`app/DataTables/UsersDataTable.php`**
    -   Added eager loading with `$query->with('roles')`.
    -   Replaced previous company filter with a `where(function(){ ... orWhere('company_id', $companyId) })` wrapper.

## Verification Steps

1. Login as _Company Admin_ → Navigate to _Users_ page.
2. All active users (including those without pivot mapping) are listed.
3. Search, sort and pagination operate normally.
4. Login as _Non-admin_ → only "self" is visible.
5. Login as _SuperAdmin_ → all users visible.

## Future Work

-   Back-fill missing `company_users` mappings with an Artisan command to ensure consistent data moving forward.
-   Add database constraint / model event to always create `company_users` mapping upon user creation within a company.
