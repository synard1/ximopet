# Refactor Export Pembelian Pakan - Struktur Database Baru

## Ringkasan Perubahan

Tanggal: 2025-07-30  
Author: AI Assistant  
Status: Completed

## Latar Belakang

Struktur database untuk feed purchase telah berubah dari pola **Batch-Item** menjadi pola **Header-Detail**:

### Struktur Lama (Batch Pattern)

```
FeedPurchaseBatch (header)
├── FeedPurchase (detail)
```

### Struktur Baru (Header-Detail Pattern)

```
FeedPurchase (header)
├── FeedPurchaseItem (detail)
```

## Perubahan yang Dilakukan

### 1. **Update Import Models**

```php
// Sebelum
use App\Models\FeedPurchaseBatch;

// Sesudah
use App\Models\FeedPurchase;
use App\Models\FeedPurchaseItem;
```

### 2. **Refactor Query Structure**

#### Sebelum (Batch Pattern)

```php
$batchesQuery = FeedPurchaseBatch::with([
    'supplier',
    'expedition',
    'feedPurchases.livestock.farm',
    'feedPurchases.livestock.coop',
    'feedPurchases.feed',
    'feedPurchases.unit'
])
->whereHas('feedPurchases', function ($q) use ($request) {
    if ($request->farm_id) {
        $q->whereHas('livestock', function ($subQ) use ($request) {
            $subQ->where('farm_id', $request->farm_id);
        });
    }
    // ... more nested queries
});
```

#### Sesudah (Header-Detail Pattern)

```php
$purchasesQuery = FeedPurchase::with([
    'supplier',
    'expedition',
    'livestock.farm',
    'livestock.coop',
    'feedPurchaseItems.feed',
    'feedPurchaseItems.unit'
])
->when($request->farm_id, function ($query) use ($request) {
    return $query->where('farm_id', $request->farm_id);
})
->when($request->livestock_id, function ($query) use ($request) {
    return $query->where('livestock_id', $request->livestock_id);
})
->when($request->feed_id, function ($query) use ($request) {
    return $query->whereHas('feedPurchaseItems', function ($q) use ($request) {
        $q->where('feed_id', $request->feed_id);
    });
});
```

### 3. **Update Summary Calculations**

#### Sebelum

```php
$summary = [
    'total_batches' => $batches->count(),
    'total_purchases' => $batches->sum(function ($batch) {
        return $batch->feedPurchases->count();
    }),
    'total_value' => $batches->sum(function ($batch) {
        return $batch->feedPurchases->sum(function ($purchase) {
            return $purchase->quantity * $purchase->price_per_unit;
        }) + $batch->expedition_fee;
    }),
    // ...
];
```

#### Sesudah

```php
$summary = [
    'total_purchases' => $purchases->count(),
    'total_items' => $purchases->sum(function ($purchase) {
        return $purchase->feedPurchaseItems->count();
    }),
    'total_value' => $purchases->sum(function ($purchase) {
        return $purchase->feedPurchaseItems->sum(function ($item) {
            return $item->quantity * $item->price_per_unit;
        }) + $purchase->expedition_fee;
    }),
    // ...
];
```

### 4. **Update Status Validation**

```php
// Sebelum
'status' => 'nullable|in:draft,confirmed,arrived,completed'

// Sesudah
'status' => 'nullable|in:draft,pending,confirmed,in_transit,arrived,cancelled,completed'
```

### 5. **Data Structure Transformation**

```php
private function exportFeedPurchaseToHtml($data)
{
    // Transform data structure to match view expectations
    $transformedData = [
        'batches' => $data['purchases'], // Keep 'batches' key for view compatibility
        'summary' => $data['summary'],
        'filters' => $data['filters']
    ];

    return view('pages.reports.pembelian-pakan', $transformedData);
}
```

## Keuntungan Perubahan

### 1. **Simplified Query Structure**

-   Menghilangkan nested queries yang kompleks
-   Query lebih straightforward dan mudah dipahami
-   Performance improvement untuk large datasets

### 2. **Better Data Organization**

-   Header data (invoice, supplier, expedition) terpusat di FeedPurchase
-   Detail data (feed items) terorganisir di FeedPurchaseItem
-   Konsisten dengan pola header-detail yang sudah ada

### 3. **Enhanced Filtering**

-   Filter farm_id dan livestock_id langsung di level header
-   Filter feed_id melalui relationship ke detail items
-   Lebih efisien dan logical

### 4. **Improved Maintainability**

-   Kode lebih clean dan readable
-   Mudah untuk extend dengan field baru
-   Konsisten dengan business logic

## Testing Checklist

-   [x] Export HTML berfungsi dengan struktur data baru
-   [x] Filter farm_id berfungsi
-   [x] Filter livestock_id berfungsi
-   [x] Filter supplier_id berfungsi
-   [x] Filter feed_id berfungsi
-   [x] Filter status berfungsi
-   [x] Summary calculations akurat
-   [x] Data relationships ter-load dengan benar
-   [x] View compatibility error fixed
-   [x] Data mapping between old and new structure working

## Migration Notes

### View Compatibility

View `pages.reports.pembelian-pakan` masih menggunakan key `batches` untuk kompatibilitas. Data structure di-transform untuk mempertahankan compatibility.

**Data Transformation Applied:**

```php
'summary' => [
    'total_batches' => $data['summary']['total_purchases'], // Map total_purchases to total_batches
    'total_purchases' => $data['summary']['total_items'], // Map total_items to total_purchases
    'total_quantity' => $data['summary']['total_quantity'],
    'total_value' => $data['summary']['total_value'],
    // ... other fields
]
```

**View Data Access Updated:**

```php
// Before (old structure)
{{ $batch->feedPurchases->count() }}
{{ $batch->feedPurchases->sum(function($purchase) { ... }) }}

// After (new structure)
{{ $batch->feedPurchaseItems->count() }}
{{ $batch->feedPurchaseItems->sum(function($item) { ... }) }}
```

### Status Mapping

Status baru yang ditambahkan:

-   `pending`: Status menunggu konfirmasi
-   `in_transit`: Status dalam perjalanan
-   `cancelled`: Status dibatalkan

### Data Access Pattern

```php
// Sebelum
$batch->feedPurchases->each(function ($purchase) {
    echo $purchase->feed->name;
    echo $purchase->quantity;
});

// Sesudah
$purchase->feedPurchaseItems->each(function ($item) {
    echo $item->feed->name;
    echo $item->quantity;
});
```

## Bug Fixes Applied

### Error: "Undefined array key 'total_batches'"

**Issue:** View expected old data structure with 'total_batches' key, but new structure uses 'total_purchases'

**Solution:**

1. Added data transformation in controller to map new structure to view expectations
2. Updated view data access patterns to use new relationships
3. Maintained backward compatibility for existing views

**Files Modified:**

-   `app/Http/Controllers/PurchaseReportsController.php` (exportFeedPurchaseToHtml method)
-   `resources/views/pages/reports/pembelian-pakan.blade.php` (data access patterns)

### Error: "Undefined array key 'period'"

**Issue:** Missing 'period' key in data transformation

**Solution:**
Added 'period' key to data transformation in exportFeedPurchaseToHtml():

```php
'summary' => [
    'period' => $data['summary']['period'], // Add period key
    'total_batches' => $data['summary']['total_purchases'],
    // ... other fields
]
```

### Linter Error: Undefined type 'App\Models\FeedPurchaseBatch'

**Issue:** Model still had reference to old FeedPurchaseBatch model

**Solution:**

-   Removed invalid `batch()` method that referenced non-existent FeedPurchaseBatch
-   Removed invalid `livestock()` method (livestock_id not in feed_purchase_items table)
-   Fixed `boot()` method to use livestock_id from parent FeedPurchase

## Model Structure Analysis

### Database Schema Validation

Based on migration analysis:

-   **`feed_purchases` table** (header): has `livestock_id`, `farm_id`, `coop_id`
-   **`feed_purchase_items` table** (detail): NO `livestock_id` field

### Corrected Relationships

```php
// FeedPurchase (Header)
class FeedPurchase extends BaseModel
{
    public function livestock()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id', 'id');
    }

    public function feedPurchaseItems()
    {
        return $this->hasMany(FeedPurchaseItem::class);
    }
}

// FeedPurchaseItem (Detail)
class FeedPurchaseItem extends BaseModel
{
    public function feedPurchase()
    {
        return $this->belongsTo(FeedPurchase::class, 'feed_purchase_id');
    }

    public function feed()
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

    // NO livestock() relationship - livestock_id not in this table
}
```

### Data Access Patterns

```php
// Correct way to access livestock from FeedPurchaseItem
$livestock = $feedPurchaseItem->feedPurchase->livestock;

// Correct way to access livestock from FeedPurchase
$livestock = $feedPurchase->livestock;
```

## Future Considerations

1. **View Refactoring**: Consider updating view to use new data structure directly
2. **Excel/PDF Export**: Update other export methods when implemented
3. **Performance Optimization**: Add database indexes for frequently filtered fields
4. **Caching Strategy**: Implement caching for summary calculations

## Related Files

-   `app/Http/Controllers/PurchaseReportsController.php`
-   `app/Models/FeedPurchase.php`
-   `app/Models/FeedPurchaseItem.php`
-   `resources/views/pages/reports/pembelian-pakan.blade.php`
