# LivestockMutationService v2 - Analysis dan Refactoring

**Date**: 2025-01-22 23:00:00  
**Issue**: Analisa ulang LivestockMutationService.php untuk menggunakan 2 model (LivestockMutation dan LivestockMutationItem)  
**Status**: ✅ COMPLETED

## Problem Analysis

### Current Structure Issue

Service sebelumnya menggunakan struktur flat dimana setiap batch mutation dibuat sebagai record `LivestockMutation` terpisah. Ini menyebabkan:

1. **Duplikasi data**: Informasi header mutation (tanggal, jenis, direction) disimpan berulang untuk setiap batch
2. **Kesulitan tracking**: Sulit mengetahui batch mana yang terkait dalam satu transaksi mutation
3. **Inkonsistensi struktur**: Tidak sesuai dengan desain database yang sudah ada (header-detail pattern)
4. **Legacy support**: Ada 2 struktur tabel yang berbeda untuk `livestock_mutations`

### Database Structure Analysis

#### Tabel `livestock_mutations` (Header)

```sql
-- Struktur Baru (2025-01-23)
CREATE TABLE livestock_mutations (
    id UUID PRIMARY KEY,
    company_id UUID,
    source_livestock_id UUID,
    destination_livestock_id UUID NULL,
    tanggal DATETIME,
    jumlah INTEGER,              -- Total quantity (calculated from items)
    jenis VARCHAR,               -- mutation type
    direction VARCHAR,           -- in/out
    data JSON NULL,              -- Additional data
    metadata JSON NULL,          -- Processing metadata
    created_by UUID NULL,
    updated_by UUID NULL
);

-- Struktur Lama (2025-04-19) - Legacy Support
CREATE TABLE livestock_mutations (
    id UUID PRIMARY KEY,
    tanggal DATE,
    from_livestock_id UUID,      -- Legacy: equivalent to source_livestock_id
    to_livestock_id UUID,        -- Legacy: equivalent to destination_livestock_id
    keterangan VARCHAR NULL,
    created_by BIGINT NULL,
    updated_by BIGINT NULL
);
```

#### Tabel `livestock_mutation_items` (Detail)

```sql
CREATE TABLE livestock_mutation_items (
    id UUID PRIMARY KEY,
    livestock_mutation_id UUID,  -- FK to livestock_mutations
    batch_id UUID NULL,          -- Optional: specific batch
    quantity INTEGER,            -- Quantity for this item
    weight DECIMAL(10,2) NULL,   -- Optional weight
    keterangan VARCHAR NULL,     -- Item-specific notes
    payload JSON NULL,           -- Item-specific data
    created_by BIGINT,
    updated_by BIGINT NULL
);
```

## Solution Implementation

### 1. Updated Model Relationships

#### LivestockMutation.php

```php
/**
 * Enhanced LivestockMutation Model with legacy support
 */
class LivestockMutation extends Model
{
    // Legacy column support
    protected $fillable = [
        'source_livestock_id', 'from_livestock_id',      // Both supported
        'destination_livestock_id', 'to_livestock_id',   // Both supported
        'tanggal', 'jumlah', 'jenis', 'direction', 'keterangan',
        'data', 'metadata', 'created_by', 'updated_by'
    ];

    // Relationships
    public function items(): HasMany
    {
        return $this->hasMany(LivestockMutationItem::class, 'livestock_mutation_id');
    }

    // Legacy support methods
    private function getSourceLivestockColumn(): string
    {
        return Schema::hasColumn($this->getTable(), 'source_livestock_id')
            ? 'source_livestock_id' : 'from_livestock_id';
    }

    // Auto-calculate total from items
    public function calculateTotalQuantity(): void
    {
        $totalQuantity = $this->items()->sum('quantity');
        $this->updateQuietly(['jumlah' => $totalQuantity]);
    }
}
```

#### LivestockMutationItem.php

```php
/**
 * New LivestockMutationItem Model for detailed items
 */
class LivestockMutationItem extends Model
{
    protected $fillable = [
        'livestock_mutation_id', 'batch_id', 'quantity',
        'weight', 'keterangan', 'payload', 'created_by', 'updated_by'
    ];

    // Relationships
    public function livestockMutation(): BelongsTo
    {
        return $this->belongsTo(LivestockMutation::class, 'livestock_mutation_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LivestockBatch::class, 'batch_id');
    }

    // Auto-update parent total when saved/deleted
    protected static function boot()
    {
        static::saved(function ($model) {
            $model->livestockMutation?->calculateTotalQuantity();
        });
    }
}
```

### 2. Refactored Service Architecture

#### Header-Detail Pattern Implementation

```php
public function processManualBatchMutation(Livestock $sourceLivestock, array $mutationData): array
{
    return DB::transaction(function () use ($sourceLivestock, $mutationData) {
        // 1. Create mutation header (single record)
        $mutation = $this->createMutationHeader($sourceLivestock, $mutationData, $isEditMode);

        // 2. Create mutation items for each batch (multiple records)
        foreach ($mutationData['manual_batches'] as $manualBatch) {
            $mutationItem = $this->createMutationItem($mutation, $batch, $manualBatch);
            $this->updateBatchQuantities($batch, $mutationData['direction'], $manualBatch['quantity']);
        }

        // 3. Update header total (auto-calculated from items)
        $mutation->update(['jumlah' => $totalProcessed]);

        return $results;
    });
}
```

#### Legacy Support Implementation

```php
private function createMutationHeader(Livestock $sourceLivestock, array $mutationData, bool $isEditMode): LivestockMutation
{
    $headerData = [
        'source_livestock_id' => $sourceLivestock->id,
        'destination_livestock_id' => $mutationData['destination_livestock_id'] ?? null,
        // ... other fields
    ];

    // Handle legacy column names
    if (!Schema::hasColumn('livestock_mutations', 'source_livestock_id')) {
        $headerData['from_livestock_id'] = $headerData['source_livestock_id'];
        unset($headerData['source_livestock_id']);
    }

    return LivestockMutation::create($headerData);
}
```

### 3. Enhanced Data Structure

#### Mutation Header Data

```php
'data' => [
    'mutation_method' => 'manual',
    'reason' => $mutationData['reason'] ?? null,
    'notes' => $mutationData['notes'] ?? null,
    'is_edit_replacement' => $isEditMode,
    'destination_info' => $this->buildDestinationInfo($mutationData),
    'batch_count' => count($mutationData['manual_batches']),
],
'metadata' => [
    'processed_at' => now()->toISOString(),
    'processed_by' => auth()->id(),
    'processing_method' => 'livestock_mutation_service_v2',
    'service_version' => '2.0',
    'uses_items' => true,
]
```

#### Mutation Item Payload

```php
'payload' => [
    'batch_info' => [
        'batch_id' => $batch->id,
        'batch_name' => $batch->name,
        'batch_start_date' => $batch->start_date,
        'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
        'initial_quantity' => $batch->initial_quantity,
        'available_before_mutation' => $this->calculateBatchAvailableQuantity($batch),
    ],
    'mutation_info' => [
        'requested_quantity' => $manualBatch['quantity'],
        'user_note' => $manualBatch['note'] ?? null,
        'processed_at' => now()->toISOString(),
        'processed_by' => auth()->id(),
    ]
]
```

## Technical Benefits

### 1. Improved Data Structure

-   **Header-Detail Pattern**: Proper normalization dengan header untuk informasi umum dan detail untuk item-specific data
-   **Reduced Redundancy**: Informasi header tidak duplikasi untuk setiap batch
-   **Better Relationships**: Clear parent-child relationship antara mutation dan items

### 2. Enhanced Tracking

-   **Transaction Integrity**: Semua items dalam satu mutation tergrouping dengan jelas
-   **Audit Trail**: Comprehensive metadata di level header dan detail
-   **Batch Tracking**: Detailed batch information di setiap item

### 3. Legacy Support

-   **Backward Compatibility**: Support untuk kedua struktur tabel (lama dan baru)
-   **Graceful Migration**: Sistem dapat bekerja dengan struktur tabel manapun
-   **Column Detection**: Automatic detection untuk column names yang berbeda

### 4. Auto-Calculation

-   **Real-time Totals**: Header quantity auto-calculated dari items
-   **Event-Driven Updates**: Model events untuk maintain data consistency
-   **Validation Integration**: Built-in validation untuk data integrity

## Migration Strategy

### Phase 1: Service Update (Current)

-   ✅ Update LivestockMutation model dengan relationship ke items
-   ✅ Create LivestockMutationItem model dengan proper relationships
-   ✅ Refactor LivestockMutationService untuk gunakan header-detail pattern
-   ✅ Add legacy support untuk backward compatibility

### Phase 2: Database Migration (Next)

-   Create migration untuk ensure tabel livestock_mutation_items ada
-   Update existing data untuk conform ke struktur baru
-   Add foreign key constraints jika belum ada

### Phase 3: UI Update (Future)

-   Update mutation display untuk show header-detail structure
-   Enhance mutation reports untuk leverage new data structure
-   Add batch-level detail views

## Testing Scenarios

### Test Case 1: New Structure

1. Create mutation dengan multiple batches
2. Verify: 1 header record, multiple item records
3. Verify: Header quantity = sum of item quantities
4. Verify: Batch quantities updated correctly

### Test Case 2: Legacy Support

1. Test dengan tabel struktur lama (from_livestock_id/to_livestock_id)
2. Verify: Service detect column names correctly
3. Verify: Data created dengan column names yang sesuai

### Test Case 3: Auto-Calculation

1. Create mutation items
2. Verify: Header quantity auto-updated
3. Update/delete items
4. Verify: Header quantity recalculated

### Test Case 4: Edit Mode

1. Edit existing mutation
2. Verify: Items replaced/updated correctly
3. Verify: Header totals recalculated
4. Verify: Batch quantities adjusted properly

## API Response Changes

### Before (v1)

```php
'processed_batches' => [
    ['batch_id' => 'uuid1', 'mutated_quantity' => 100, 'mutation_record_id' => 'uuid_a'],
    ['batch_id' => 'uuid2', 'mutated_quantity' => 150, 'mutation_record_id' => 'uuid_b'],
]
```

### After (v2)

```php
'mutation_id' => 'header_uuid',
'processed_batches' => [
    ['batch_id' => 'uuid1', 'mutated_quantity' => 100, 'mutation_item_id' => 'item_uuid1'],
    ['batch_id' => 'uuid2', 'mutated_quantity' => 150, 'mutation_item_id' => 'item_uuid2'],
],
'items_count' => 2
```

## Performance Impact

### Positive Impact

-   **Reduced Records**: 1 header + N items instead of N separate mutations
-   **Better Queries**: Can query by header untuk get all related items
-   **Efficient Joins**: Proper relationships untuk complex queries

### Considerations

-   **Auto-Calculation Overhead**: Model events untuk maintain totals
-   **Legacy Detection**: Schema checks pada setiap operation
-   **Transaction Complexity**: Lebih complex transaction handling

## Future Enhancements

### 1. Batch Processing Optimization

-   Bulk item creation untuk large mutations
-   Batch quantity validation optimization
-   Parallel processing untuk multiple batches

### 2. Advanced Reporting

-   Header-detail reports dengan drill-down capability
-   Batch-level analytics dan trends
-   Cross-mutation batch tracking

### 3. API Versioning

-   Maintain v1 API compatibility
-   Introduce v2 API dengan new structure
-   Gradual migration path untuk clients

---

**Resolution**: LivestockMutationService v2 berhasil direfactor untuk menggunakan proper header-detail pattern dengan LivestockMutation sebagai header dan LivestockMutationItem sebagai detail. System mendukung legacy table structure dan provides comprehensive tracking untuk mutation transactions.
