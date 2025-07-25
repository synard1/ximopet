# CurrentFeedService Documentation

## Overview

`CurrentFeedService` is a robust, modular Laravel service for updating, recalculating, and syncing the `CurrentFeed` model's `quantity` field. It is designed to replace all inline logic for updating `CurrentFeed` in controllers, components, commands, and other services, ensuring consistency, maintainability, and future extensibility.

---

## Key Features

-   **Flexible Filtering:** Update or recalculate by `livestock_id`, `feed_id`, `farm_id`, `unit_id` (any combination).
-   **Batch & Single Update:** Supports both batch and single-record operations.
-   **Dry-Run Mode:** Preview changes without saving (safe for audits and previews).
-   **Comprehensive Logging:** All updates, skips, and errors are logged for audit and debugging.
-   **Extensible:** Ready for future enhancements (e.g., audit trail, custom sync logic).
-   **Production-Ready:** Used by CLI commands, can be called from any service/component.

---

## API Reference

### 1. `updateQuantity(array $filter = [], bool $dryRun = false): array`

-   **Description:** Batch update or recalculate `CurrentFeed` quantities matching the filter.
-   **Parameters:**
    -   `filter`: Associative array. Supported keys: `livestock_id`, `feed_id`, `farm_id`, `unit_id` (all optional).
    -   `dryRun`: If `true`, only preview changes (no DB writes).
-   **Returns:**
    -   `array` with keys: `fixed`, `skipped`, `errors`, `details` (per-record info).

### 2. `recalculateAll(array $filter = [], bool $dryRun = false): array`

-   **Description:** Alias for `updateQuantity`. For semantic clarity in batch recalc scenarios.

### 3. `syncWithFeedStock(CurrentFeed $currentFeed, bool $dryRun = false): array`

-   **Description:** Sync a single `CurrentFeed` record with its related `FeedStock` records.
-   **Returns:**
    -   `array` with keys: `fixed`, `skipped`, `errors`, `detail`.

### 4. `getCurrentFeed(array $filter = []): Collection`

-   **Description:** Retrieve `CurrentFeed` records matching the filter.

---

## Usage Examples

### Batch Update (e.g., in a Command or Controller)

```php
$service = app(\App\Services\Feed\CurrentFeedService::class);
$result = $service->updateQuantity(['farm_id' => 1]);
```

### Single Record Sync

```php
$currentFeed = CurrentFeed::find($id);
$service->syncWithFeedStock($currentFeed);
```

### Dry-Run Preview

```php
$result = $service->updateQuantity(['livestock_id' => 123], true); // No DB writes
```

---

## Filter Options

-   `livestock_id`: Filter by livestock/ternak/kandang.
-   `feed_id`: Filter by feed type.
-   `farm_id`: Filter by farm.
-   `unit_id`: Filter by unit (optional).
-   All filters are optional and can be combined.

---

## Logging & Debugging

-   All updates, skips, and errors are logged using Laravel's logging system.
-   Log entries include before/after values, filter context, and error details.
-   Use logs for audit trail and troubleshooting.

---

## Extensibility & Future-Proofing

-   The service is designed for easy extension (e.g., add audit trail, notification, or custom sync logic).
-   All update logic is centralized for maintainability.
-   Can be safely called from jobs, commands, controllers, or other services.

---

## Migrating from Inline Logic

-   **Old Pattern:**
    ```php
    // Inline update
    $total = FeedStock::where('feed_id', $feedId)->where('livestock_id', $livestockId)->sum(...);
    $currentFeed->quantity = $total;
    $currentFeed->save();
    ```
-   **New Pattern:**
    ```php
    $service = app(\App\Services\Feed\CurrentFeedService::class);
    $service->updateQuantity(['livestock_id' => $livestockId, 'feed_id' => $feedId]);
    ```
-   For batch or CLI: use `updateQuantity` or `recalculateAll` with appropriate filters.

---

## Integration Notes

-   Used by `feed:fix-current-feed` artisan command (see `app/Console/Commands/FixCurrentFeedQuantity.php`).
-   Can be injected or resolved via Laravel's service container.
-   Safe for use in production and batch jobs.

---

## Maintainers & Contributors

-   All update logic is centralized here for consistency.
-   Please update this documentation and add changelog notes for any future changes.
-   For questions, see the code docblocks or contact the core backend team.

---

## Changelog

-   **2024-07-13:** Initial version, modularized all CurrentFeed update logic, added batch/dry-run support, and documentation.
