# Analisis Struktur Tabel Feed Purchase Management

## Ringkasan Eksekutif

Setelah menganalisis struktur tabel feed management yang ada, **kami merekomendasikan untuk tetap menggunakan pola Header-Detail** dengan `feed_purchases` sebagai header dan `feed_purchase_items` sebagai detail. Struktur ini lebih sesuai dengan business logic dan konsisten dengan pattern yang sudah ada di sistem.

## Analisis Struktur Saat Ini

### Struktur Sebelumnya (Batch Pattern)

```
feed_purchase_batches (header)
├── feed_purchases (detail)
```

### Struktur yang Direkomendasikan (Header-Detail Pattern)

```
feed_purchases (header)
├── feed_purchase_items (detail)
```

## Keunggulan Pola Header-Detail

### 1. **Business Logic Alignment**

-   **Realitas Bisnis**: 1 DO/Invoice bisa berisi multiple jenis pakan
-   **Supplier Management**: Info supplier dan expedition terpusat di header
-   **Financial Tracking**: Expedition fee dan total invoice di level header
-   **Status Management**: Status transaksi (draft, confirmed, completed) di header

### 2. **Data Integrity & Consistency**

-   **Referential Integrity**: Foreign key constraints yang jelas
-   **Transaction Safety**: Cascade delete yang proper
-   **Audit Trail**: Tracking perubahan di level header dan detail
-   **Data Normalization**: Menghindari data redundancy

### 3. **Performance & Scalability**

-   **Query Efficiency**: Header queries lebih cepat untuk summary data
-   **Lazy Loading**: Detail data hanya di-load saat diperlukan
-   **Indexing Strategy**: Index yang optimal untuk header dan detail
-   **Memory Usage**: Reduced memory footprint untuk large datasets

### 4. **Consistency dengan Pattern Lain**

Sistem sudah menggunakan header-detail pattern di berbagai tempat:

-   `feed_mutations` → `feed_mutation_items`
-   `feed_usages` → `feed_usage_details`
-   `feed_rollbacks` → `feed_rollback_items`

## Struktur Tabel yang Direkomendasikan

### Feed Purchases (Header)

```sql
feed_purchases
├── id (UUID, Primary Key)
├── invoice_number (String)
├── do_number (String, nullable)
├── supplier_id (UUID, Foreign Key)
├── expedition_id (UUID, Foreign Key, nullable)
├── date (Date)
├── expedition_fee (Decimal)
├── data (JSON, nullable)
├── notes (Text, nullable)
├── status (String, indexed)
├── created_by (UUID, Foreign Key)
├── updated_by (UUID, Foreign Key)
├── timestamps
└── soft_deletes
```

### Feed Purchase Items (Detail)

```sql
feed_purchase_items
├── id (UUID, Primary Key)
├── livestock_id (UUID, Foreign Key)
├── feed_purchase_id (UUID, Foreign Key)
├── feed_id (UUID, Foreign Key)
├── unit_id (UUID, nullable)
├── quantity (Decimal)
├── converted_unit (UUID, nullable)
├── converted_quantity (Decimal)
├── price_per_unit (Decimal)
├── price_per_converted_unit (Decimal)
├── created_by (UUID, Foreign Key)
├── updated_by (UUID, Foreign Key)
├── timestamps
└── soft_deletes
```

## Use Cases & Business Scenarios

### 1. **Purchase Process**

```
1. User membuat feed_purchase (header)
   - Input: invoice_number, supplier_id, expedition_id, date

2. User menambahkan feed_purchase_items (detail)
   - Input: feed_id, quantity, price_per_unit untuk setiap jenis pakan

3. System menghitung total dan update header
   - Total quantity, total price, expedition_fee
```

### 2. **Reporting & Analytics**

```
- Laporan per invoice (query header)
- Laporan per feed type (query detail)
- Laporan supplier performance (join header + detail)
- Laporan cost analysis (aggregate detail data)
```

### 3. **Stock Management**

```
- Stock masuk dari feed_purchase_items
- Tracking per item untuk FIFO/LIFO
- Stock adjustment per item
```

## Perbandingan dengan Alternatif

### Pola Flat (Single Table)

❌ **Tidak Direkomendasikan**

-   Data redundancy tinggi
-   Invoice info diulang untuk setiap item
-   Sulit untuk reporting per invoice
-   Performance issues untuk large datasets

### Pola Item-Only

❌ **Tidak Direkomendasikan**

-   Kehilangan context invoice
-   Sulit untuk supplier management
-   Expedition fee tidak bisa di-track
-   Status management kompleks

### Pola Header-Detail

✅ **Direkomendasikan**

-   Optimal balance antara normalization dan usability
-   Mendukung semua business requirements
-   Konsisten dengan existing patterns
-   Future-proof untuk enhancements

## Implementation Guidelines

### 1. **Model Relationships**

```php
// FeedPurchase (Header)
class FeedPurchase extends Model
{
    public function items()
    {
        return $this->hasMany(FeedPurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Partner::class, 'supplier_id');
    }
}

// FeedPurchaseItem (Detail)
class FeedPurchaseItem extends Model
{
    public function purchase()
    {
        return $this->belongsTo(FeedPurchase::class, 'feed_purchase_id');
    }

    public function feed()
    {
        return $this->belongsTo(Feed::class);
    }
}
```

### 2. **Service Layer**

```php
class FeedPurchaseService
{
    public function createPurchase(array $headerData, array $itemsData)
    {
        DB::transaction(function () use ($headerData, $itemsData) {
            $purchase = FeedPurchase::create($headerData);

            foreach ($itemsData as $itemData) {
                $purchase->items()->create($itemData);
            }

            $this->updatePurchaseTotals($purchase);
        });
    }

    public function updatePurchaseTotals(FeedPurchase $purchase)
    {
        $totals = $purchase->items()
            ->selectRaw('SUM(quantity * price_per_unit) as total_amount')
            ->first();

        $purchase->update([
            'total_amount' => $totals->total_amount
        ]);
    }
}
```

### 3. **Validation Rules**

```php
// Header validation
$headerRules = [
    'invoice_number' => 'required|unique:feed_purchases',
    'supplier_id' => 'required|exists:partners,id',
    'date' => 'required|date',
    'expedition_fee' => 'nullable|numeric|min:0'
];

// Item validation
$itemRules = [
    'feed_id' => 'required|exists:feeds,id',
    'quantity' => 'required|numeric|min:0.01',
    'price_per_unit' => 'required|numeric|min:0'
];
```

## Migration Strategy

### 1. **Backward Compatibility**

-   Maintain existing foreign key relationships
-   Update references in feed_stocks table
-   Ensure data integrity during migration

### 2. **Data Migration**

```php
// Migration script untuk update existing data
public function migrateExistingData()
{
    // Update feed_stocks references
    DB::table('feed_stocks')
        ->whereNotNull('feed_purchase_id')
        ->update([
            'feed_purchase_item_id' => DB::raw('feed_purchase_id')
        ]);
}
```

### 3. **Rollback Plan**

-   Backup existing data sebelum migration
-   Test migration di staging environment
-   Monitor performance impact
-   Prepare rollback scripts

## Performance Considerations

### 1. **Indexing Strategy**

```sql
-- Header indexes
CREATE INDEX idx_feed_purchases_supplier_date ON feed_purchases(supplier_id, date);
CREATE INDEX idx_feed_purchases_status ON feed_purchases(status);
CREATE INDEX idx_feed_purchases_invoice ON feed_purchases(invoice_number);

-- Detail indexes
CREATE INDEX idx_feed_purchase_items_purchase ON feed_purchase_items(feed_purchase_id);
CREATE INDEX idx_feed_purchase_items_feed ON feed_purchase_items(feed_id);
CREATE INDEX idx_feed_purchase_items_livestock ON feed_purchase_items(livestock_id);
```

### 2. **Query Optimization**

```php
// Eager loading untuk avoid N+1 queries
$purchases = FeedPurchase::with(['items.feed', 'supplier'])
    ->where('status', 'confirmed')
    ->get();

// Pagination untuk large datasets
$purchases = FeedPurchase::with('items')
    ->paginate(50);
```

## Conclusion

Pola Header-Detail dengan `feed_purchases` sebagai header dan `feed_purchase_items` sebagai detail adalah **pilihan terbaik** untuk struktur tabel feed purchase management karena:

1. **Business Logic Alignment**: Sesuai dengan realitas bisnis (1 invoice = multiple feed types)
2. **Data Integrity**: Proper normalization dan referential integrity
3. **Performance**: Optimal untuk query dan reporting
4. **Consistency**: Konsisten dengan existing patterns di sistem
5. **Scalability**: Mendukung growth dan future enhancements
6. **Maintainability**: Mudah untuk maintenance dan debugging

Implementasi ini akan memberikan foundation yang solid untuk feed purchase management system yang robust, scalable, dan maintainable.

## Log Implementasi

-   **2025-01-24**: Analisis struktur tabel feed management
-   **2025-01-24**: Rekomendasi pola header-detail
-   **2025-01-24**: Update migration file dengan naming convention yang konsisten
-   **2025-01-24**: Update foreign key references di feed_stocks table
-   **2025-01-24**: Dokumentasi lengkap dengan guidelines dan best practices
