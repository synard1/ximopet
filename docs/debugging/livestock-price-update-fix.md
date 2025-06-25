# Perbaikan Update Kolom Price pada Livestock

## Tanggal: 2025-06-21 12:25 WIB

## Masalah

Kolom `price` pada model Livestock belum terupdate dengan benar setelah batch creation, meskipun kolom `initial_quantity` dan `initial_weight` sudah ter-update.

## Root Cause Analysis

### Possible Issues:

1. **Field Mapping Error**: `price_total` di batch mungkin kosong atau tidak ter-set dengan benar
2. **Calculation Logic**: Perhitungan average price mungkin tidak akurat
3. **Model Update Issue**: Ada masalah dengan proses update Eloquent model
4. **Data Type Issue**: Mismatch data type antara yang dicalculate dengan yang disimpan

### Debugging yang Dilakukan:

1. Added detailed logging untuk melihat nilai batch sebelum aggregation
2. Added alternative calculation method jika primary method gagal
3. Added direct database update sebagai fallback
4. Added validation untuk memastikan price ter-update

## Perbaikan Dilakukan

### 1. Enhanced Batch Data Debugging

```php
Log::info('Debug: Batches found for Livestock aggregation:', [
    'livestock_id' => $livestock->id,
    'batch_count' => $allBatchesForLivestock->count(),
    'batches' => $allBatchesForLivestock->map(function($batch) {
        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'initial_quantity' => $batch->initial_quantity,
            'weight_total' => $batch->weight_total,
            'price_total' => $batch->price_total,        // ← Key untuk debug
            'price_per_unit' => $batch->price_per_unit,  // ← Alternative source
        ];
    })->toArray()
]);
```

### 2. Alternative Price Calculation

```php
// Primary calculation
$avgPrice = $totalQuantity > 0 ? $totalPriceValue / $totalQuantity : 0;

// Alternative calculation jika price_total tidak tersedia atau 0
if ($avgPrice <= 0 && $totalQuantity > 0) {
    $totalPriceFromPerUnit = $allBatchesForLivestock->sum(function($batch) {
        return $batch->initial_quantity * ($batch->price_per_unit ?? 0);
    });
    $avgPrice = $totalPriceFromPerUnit / $totalQuantity;

    Log::info('Debug: Using alternative price calculation from price_per_unit:', [
        'total_price_from_per_unit' => $totalPriceFromPerUnit,
        'alternative_avg_price' => $avgPrice
    ]);
}
```

### 3. Direct Database Update Fallback

```php
// Validasi update berhasil, khususnya untuk price
if ($livestock->price <= 0 && $avgPrice > 0) {
    Log::warning('Price update failed, attempting direct update:', [
        'livestock_id' => $livestock->id,
        'expected_price' => $avgPrice,
        'actual_price' => $livestock->price
    ]);

    // Try direct database update
    DB::table('livestocks')
        ->where('id', $livestock->id)
        ->update([
            'initial_quantity' => $totalQuantity,
            'initial_weight' => $avgWeight,
            'price' => $avgPrice,
            'updated_by' => auth()->id(),
            'updated_at' => now()
        ]);

    $livestock->refresh();
}
```

### 4. Enhanced Validation & Logging

```php
Log::info('Updated Livestock with totals from batches:', [
    'livestock_id' => $livestock->id,
    'update_success' => $updateResult,
    'old_values' => [
        'initial_quantity' => $livestock->getOriginal('initial_quantity'),
        'initial_weight' => $livestock->getOriginal('initial_weight'),
        'price' => $livestock->getOriginal('price')
    ],
    'new_values' => [
        'initial_quantity' => $livestock->initial_quantity,
        'initial_weight' => $livestock->initial_weight,
        'price' => $livestock->price
    ],
    'batch_count' => $allBatchesForLivestock->count(),
    'price_update_success' => $livestock->price > 0  // ← Validation flag
]);
```

## Diagram Alur Price Update

```mermaid
graph TD
    A[Start: Get All Active Batches] --> B[Sum price_total from batches]
    B --> C{price_total > 0?}
    C -->|Yes| D[Calculate avgPrice = totalPrice / totalQuantity]
    C -->|No| E[Use Alternative: Sum(quantity × price_per_unit)]
    E --> F[Calculate avgPrice from alternative]
    D --> G[Update Livestock Model]
    F --> G
    G --> H[Refresh Model]
    H --> I{Price Updated Successfully?}
    I -->|Yes| J[Log Success]
    I -->|No| K[Direct Database Update]
    K --> L[Refresh Model Again]
    L --> M[Final Validation]
    J --> M
    M --> N[Complete]

    style A fill:#e1f5fe
    style K fill:#fff3e0
    style M fill:#e8f5e8
```

## Expected Behavior Setelah Fix

### Scenario 1: Normal Price Update

```
[timestamp] Debug: Batches found for Livestock aggregation: {
    "batches": [
        {
            "id": "...",
            "initial_quantity": 10000,
            "price_total": 65000000,
            "price_per_unit": 6500
        }
    ]
}
[timestamp] Debug: Calculated values before Livestock update: {
    "total_price_value": 65000000,
    "avg_price": 6500,
    "calculation_method": "weighted_average"
}
[timestamp] Updated Livestock: {
    "new_values": {"price": 6500},
    "price_update_success": true
}
```

### Scenario 2: Fallback to Alternative Calculation

```
[timestamp] Debug: Calculated values: {
    "total_price_value": 0,  ← price_total kosong
    "avg_price": 0
}
[timestamp] Debug: Using alternative price calculation: {
    "total_price_from_per_unit": 65000000,
    "alternative_avg_price": 6500
}
[timestamp] Updated Livestock: {
    "new_values": {"price": 6500},
    "price_update_success": true
}
```

### Scenario 3: Direct Database Update

```
[timestamp] Price update failed, attempting direct update: {
    "expected_price": 6500,
    "actual_price": 0
}
[timestamp] Direct database update completed: {
    "final_price": 6500
}
```

## Testing Commands

### Manual Validation Query:

```sql
-- Check price consistency
SELECT
    l.id,
    l.name,
    l.initial_quantity,
    l.initial_weight,
    l.price as livestock_price,
    COUNT(lb.id) as batch_count,
    SUM(lb.initial_quantity) as total_batch_quantity,
    SUM(lb.price_total) as total_batch_price,
    AVG(lb.price_per_unit) as avg_batch_price_per_unit,
    SUM(lb.price_total) / SUM(lb.initial_quantity) as calculated_avg_price
FROM livestocks l
LEFT JOIN livestock_batches lb ON l.id = lb.livestock_id AND lb.status = 'active'
WHERE l.updated_at > NOW() - INTERVAL 1 HOUR
GROUP BY l.id
ORDER BY l.updated_at DESC;
```

### Expected Results:

-   `livestock_price` should equal `calculated_avg_price`
-   `livestock_price` should be > 0 if there are active batches
-   All fields should have consistent values

## Production Ready Checklist

-   [x] Enhanced debugging untuk trace price calculation
-   [x] Alternative calculation method untuk edge cases
-   [x] Direct database update sebagai fallback
-   [x] Comprehensive validation dan logging
-   [x] Fixed linter errors
-   [x] Dokumentasi lengkap
-   [ ] Unit tests untuk price calculation scenarios
-   [ ] Integration tests untuk full price update flow
-   [ ] Performance testing untuk large batch datasets

## Future Improvements

1. **Price Calculation Service**: Extract logic ke dedicated service
2. **Real-time Price Sync**: Auto-update saat ada perubahan batch
3. **Price History Tracking**: Track perubahan price over time
4. **Batch Price Validation**: Validate price consistency across batches
