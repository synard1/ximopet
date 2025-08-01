# BaseModel Company ID Handling untuk Model Detail

## Overview

BaseModel telah direfactor untuk menangani kondisi dimana beberapa model adalah detail model yang tidak memerlukan `company_id` karena mewarisi dari model parent.

## Fitur Baru

### 1. Property `$requiresCompanyId`

```php
/**
 * Whether this model requires company_id handling
 * Set to false for detail models that inherit company_id from parent
 *
 * @var bool
 */
protected $requiresCompanyId = true;
```

### 2. Dynamic Fillable Fields

BaseModel sekarang menggunakan `getFillable()` method yang secara dinamis menambahkan `company_id` hanya jika model memerlukannya.

### 3. Conditional Company ID Setting

Event handlers (`creating`, `updating`) sekarang hanya mengatur `company_id` jika `$requiresCompanyId = true`.

## Cara Penggunaan

### Untuk Model Utama (Memerlukan Company ID)

```php
class FeedPurchase extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    // Default: requiresCompanyId = true (tidak perlu diubah)

    protected $additionalFillable = [
        'id',
        'invoice_number',
        'supplier_id',
        'purchase_date',
        // ... field lainnya
    ];
}
```

### Untuk Model Detail (Tidak Memerlukan Company ID)

```php
class FeedPurchaseItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * This is a detail model that inherits company_id from FeedPurchase parent
     * No need to handle company_id separately
     */
    protected $requiresCompanyId = false;

    protected $additionalFillable = [
        'id',
        'feed_purchase_id', // Foreign key ke parent
        'feed_id',
        'quantity',
        'price',
        // ... field lainnya
    ];
}
```

## Daftar Model Detail yang Sudah Diupdate

### 1. FeedPurchaseItem

-   **Parent**: FeedPurchase
-   **Alasan**: Item detail dari pembelian pakan, company_id diwarisi dari FeedPurchase

### 2. RecordingItem

-   **Parent**: Recording
-   **Alasan**: Item detail dari pencatatan, company_id diwarisi dari Recording

### 3. TransaksiDetail

-   **Parent**: Transaksi
-   **Alasan**: Detail transaksi, company_id diwarisi dari Transaksi

## Model Detail Lainnya yang Perlu Diupdate

Berdasarkan analisis, model-model berikut juga perlu diupdate:

### Feed Related

-   `FeedMutationItem` (parent: FeedMutation)
-   `FeedRollbackItem` (parent: FeedRollback)
-   `FeedUsageDetail` (parent: FeedUsage)
-   `FeedStatusHistory` (parent: Feed)

### Livestock Related

-   `LivestockPurchaseItem` (parent: LivestockPurchase)
-   `LivestockSalesItem` (parent: LivestockSales)
-   `LivestockPurchaseStatusHistory` (parent: LivestockPurchase)

### Supply Related

-   `SupplyMutationItem` (parent: SupplyMutation)
-   `SupplyUsageDetail` (parent: SupplyUsage)
-   `SupplyStatusHistory` (parent: Supply)
-   `SupplyPurchaseBatch` (parent: SupplyPurchase)

### Transaction Related

-   `TransaksiBeliDetail` (parent: TransaksiBeli)
-   `TransaksiHarianDetail` (parent: TransaksiHarian)
-   `TransaksiJualDetail` (parent: TransaksiJual)

### Recording Related

-   `RecordingItem` (parent: Recording) ✅ **Sudah diupdate**

## Method Helper

### `requiresCompanyId(): bool`

Mengecek apakah model memerlukan company_id handling.

### `setRequiresCompanyId(bool $requires): self`

Mengatur apakah model memerlukan company_id handling.

```php
$model = new FeedPurchaseItem();
$model->setRequiresCompanyId(false);
```

## Logging dan Debugging

Sistem akan mencatat log ketika:

-   Model detail mencoba mengatur company_id (akan diabaikan)
-   Model utama tidak memiliki company_id (akan diatur otomatis)

## Migration Strategy

### Phase 1: Update BaseModel ✅

-   [x] Refactor BaseModel dengan conditional company_id handling
-   [x] Tambah property `$requiresCompanyId`
-   [x] Update event handlers

### Phase 2: Update Detail Models

-   [x] FeedPurchaseItem
-   [x] RecordingItem
-   [x] TransaksiDetail
-   [ ] FeedMutationItem
-   [ ] LivestockPurchaseItem
-   [ ] SupplyMutationItem
-   [ ] Dan lainnya...

### Phase 3: Testing

-   [ ] Test model creation tanpa company_id
-   [ ] Test model update tanpa company_id
-   [ ] Test relationship integrity
-   [ ] Test performance impact

## Benefits

1. **Cleaner Code**: Model detail tidak perlu menangani company_id
2. **Data Integrity**: Company_id diwarisi dari parent, tidak ada duplikasi
3. **Performance**: Mengurangi overhead setting company_id untuk detail models
4. **Maintainability**: Lebih mudah memahami relasi parent-child
5. **Flexibility**: Bisa mengatur per-model apakah memerlukan company_id

## Best Practices

1. **Selalu set `$requiresCompanyId = false`** untuk model detail
2. **Gunakan `$additionalFillable`** untuk field spesifik model
3. **Pastikan foreign key ke parent** ada di `$additionalFillable`
4. **Test relationship** setelah update model detail
5. **Document parent-child relationship** di model detail

## Troubleshooting

### Error: "company_id cannot be null"

-   Pastikan parent model memiliki company_id
-   Cek apakah `$requiresCompanyId = false` sudah diset

### Error: "company_id column not found"

-   Pastikan tabel detail tidak memiliki kolom company_id
-   Atau kolom company_id dibuat nullable

### Performance Issue

-   Monitor query performance setelah update
-   Pastikan tidak ada N+1 query untuk company_id
