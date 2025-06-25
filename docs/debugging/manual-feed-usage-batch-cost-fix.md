# Manual Feed Usage Batch ID & Total Cost Fix

## Tanggal: 2025-01-23 17:15:00 WIB

## Masalah yang Ditemukan

User melaporkan bahwa saat input manual feed usage:

1. **livestock_batch_id tidak terisi dengan baik** - data batch tidak tersimpan di field utama
2. **total_cost belum terisi dengan baik** - data cost tidak tersimpan di field utama
3. **Saat edit, data yang di-load tidak sesuai dengan batch** - karena data batch tidak tersimpan dengan benar

## Analisis Root Cause

### 1. Field Mapping Error di FeedUsage

-   `livestock_batch_id` disimpan di `metadata` bukan di field utama `livestock_batch_id`
-   `total_cost` disimpan di `metadata` bukan di field utama `total_cost`
-   `purpose` dan `notes` juga disimpan di `metadata` bukan di field utama

### 2. FeedUsageDetail Structure Issue

-   Model tidak memiliki field `cost_per_unit`, `total_cost`, `notes` di database
-   Data cost disimpan di `metadata` tapi tidak di-handle dengan benar
-   Missing `metadata` field di fillable array

### 3. Data Retrieval Error saat Edit

-   Method `getExistingUsageData` mencari data di field utama tapi data tersimpan di metadata
-   Calculation `total_cost` menggunakan field yang tidak ada
-   Field mapping tidak konsisten antara save dan load

## Solusi yang Diterapkan

### 1. Fix FeedUsage Creation - `processManualFeedUsage()`

#### BEFORE (problematic):

```php
$feedUsage = FeedUsage::create([
    'livestock_id' => $livestock->id,
    'usage_date' => $usageData['usage_date'],
    'total_quantity' => 0,
    'metadata' => [
        'livestock_batch_id' => $batch?->id,  // ❌ Wrong location
        'usage_purpose' => $usageData['usage_purpose'],  // ❌ Wrong location
        'notes' => $usageData['notes'],  // ❌ Wrong location
        // ...
    ],
]);

// Update totals
$feedUsage->update([
    'total_quantity' => $totalProcessed,
    'metadata' => array_merge($feedUsage->metadata ?? [], [
        'total_cost' => $totalCost,  // ❌ Wrong location
        // ...
    ]),
]);
```

#### AFTER (fixed):

```php
$feedUsage = FeedUsage::create([
    'livestock_id' => $livestock->id,
    'livestock_batch_id' => $batch?->id, // ✅ Main field
    'usage_date' => $usageData['usage_date'],
    'purpose' => $usageData['usage_purpose'] ?? 'feeding', // ✅ Main field
    'notes' => $usageData['notes'] ?? null, // ✅ Main field
    'total_quantity' => 0,
    'total_cost' => 0, // ✅ Main field
    'metadata' => [
        'livestock_batch_name' => $batch?->name, // ✅ Metadata only
        'is_manual_usage' => true,
        // ...
    ],
]);

// Update totals
$feedUsage->update([
    'total_quantity' => $totalProcessed,
    'total_cost' => $totalCost, // ✅ Main field
    'metadata' => array_merge($feedUsage->metadata ?? [], [
        'average_cost_per_unit' => $totalProcessed > 0 ? $totalCost / $totalProcessed : 0,
        // ...
    ]),
]);
```

### 2. Fix FeedUsageDetail Creation

#### BEFORE (problematic):

```php
FeedUsageDetail::create([
    'feed_usage_id' => $feedUsage->id,
    'feed_stock_id' => $stock->id,
    'feed_id' => $stock->feed_id,
    'quantity_taken' => $requestedQuantity,
    'cost_per_unit' => $costPerUnit,  // ❌ Field doesn't exist
    'total_cost' => $lineCost,  // ❌ Field doesn't exist
    'notes' => $manualStock['note'],  // ❌ Field doesn't exist
    // ...
]);
```

#### AFTER (fixed):

```php
FeedUsageDetail::create([
    'feed_usage_id' => $feedUsage->id,
    'feed_stock_id' => $stock->id,
    'feed_id' => $stock->feed_id,
    'quantity_taken' => $requestedQuantity,
    'metadata' => [
        'cost_calculation' => [
            'quantity' => $requestedQuantity,
            'cost_per_unit' => $costPerUnit, // ✅ In metadata
            'total_cost' => $lineCost // ✅ In metadata
        ],
        'notes' => $manualStock['note'] ?? null, // ✅ In metadata
        // ...
    ],
    // ...
]);
```

### 3. Fix Data Retrieval - `getExistingUsageData()`

#### BEFORE (problematic):

```php
// Wrong field access
$totalQuantity += $detail->quantity; // ❌ Field doesn't exist
$totalCost += $detail->cost; // ❌ Field doesn't exist

// Wrong batch data source
if (isset($usage->livestock_batch_id) && $usage->livestock_batch_id) {
    // This would work now, but was looking in wrong place before
}

// Wrong cost calculation
$totalCost = floatval($usage->metadata['total_cost'] ?? 0); // ❌ Should check main field first
```

#### AFTER (fixed):

```php
// Correct field access
$totalQuantity += $detail->quantity_taken; // ✅ Correct field
$detailCost = $detail->metadata['cost_calculation']['total_cost'] ?? 0; // ✅ From metadata
$totalCost += floatval($detailCost);

// Correct batch data source
if ($usage->livestock_batch_id) { // ✅ From main field
    $livestockBatchId = $usage->livestock_batch_id;
    // ...
}

// Correct cost calculation with fallback
$totalCost = floatval($usage->total_cost ?? $usage->metadata['total_cost'] ?? 0); // ✅ Main field first
```

### 4. Fix Model Configuration

#### FeedUsageDetail Model:

```php
protected $fillable = [
    'id',
    'feed_usage_id',
    'feed_id',
    'feed_stock_id',
    'quantity_taken',
    'metadata', // ✅ Added
    'created_by',
    'updated_by',
];

protected $casts = [
    'metadata' => 'array', // ✅ Added
];
```

### 5. Fix Update Process - `updateExistingFeedUsage()`

#### Restore Logic:

```php
// BEFORE
$quantityToRestore = floatval($detail->quantity); // ❌ Wrong field

// AFTER
$quantityToRestore = floatval($detail->quantity_taken); // ✅ Correct field

// Cost retrieval with proper fallback
$totalCost = floatval($usage->total_cost ?? $usage->metadata['total_cost'] ?? 0);
```

## Key Improvements

### 1. Consistent Field Mapping

-   `livestock_batch_id` → main field di FeedUsage table
-   `total_cost` → main field di FeedUsage table
-   `purpose` → main field di FeedUsage table
-   `notes` → main field di FeedUsage table

### 2. Proper Metadata Usage

-   Cost calculations → metadata di FeedUsageDetail
-   Batch names → metadata (since not needed for queries)
-   Additional tracking info → metadata

### 3. Robust Data Retrieval

-   Check main fields first, fallback to metadata
-   Proper field name usage (`quantity_taken` not `quantity`)
-   Safe numeric conversions with fallbacks

### 4. Edit Mode Consistency

-   Load data from correct fields
-   Proper cost calculation during edit
-   Batch information correctly retrieved and displayed

## Testing Scenarios

### 1. New Usage Creation

-   Create manual feed usage with batch selection
-   Verify `livestock_batch_id` saved in main field
-   Verify `total_cost` calculated and saved in main field
-   Check metadata contains supplementary information

### 2. Edit Existing Usage

-   Open existing usage for edit
-   Verify batch information loads correctly
-   Verify cost information displays correctly
-   Update quantities and verify calculations

### 3. Database Verification

```sql
-- Check main fields are populated
SELECT id, livestock_batch_id, total_cost, purpose, notes
FROM feed_usages
WHERE created_at >= '2025-01-23';

-- Check detail metadata
SELECT id, quantity_taken, metadata
FROM feed_usage_details
WHERE created_at >= '2025-01-23';
```

## Files Modified

1. **`app/Services/Feed/ManualFeedUsageService.php`**

    - Fixed `processManualFeedUsage()` - proper field mapping
    - Fixed `getExistingUsageData()` - correct data retrieval
    - Fixed `updateExistingFeedUsage()` - proper restore logic

2. **`app/Models/FeedUsageDetail.php`**
    - Added `metadata` to fillable array
    - Added `metadata` casting to array

## Database Schema Verification

### FeedUsage Table Fields:

-   ✅ `livestock_batch_id` - exists, now used properly
-   ✅ `total_cost` - exists, now used properly
-   ✅ `purpose` - exists, now used properly
-   ✅ `notes` - exists, now used properly

### FeedUsageDetail Table Fields:

-   ✅ `quantity_taken` - exists, used correctly
-   ✅ `metadata` - exists, now configured properly

## Status: ✅ RESOLVED

Manual feed usage sekarang menyimpan `livestock_batch_id` dan `total_cost` dengan benar di field utama database, dan data dapat di-load dengan benar saat edit mode.
