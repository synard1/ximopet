# Supply Purchase Metadata Fix

**Date:** 2025-07-14  
**Time:** 11:30:00  
**Author:** System  
**Issue:** Metadata tidak tersimpan saat ada proses pembelian

## Problem Description

Saat proses pembelian supply, metadata tidak tersimpan di tabel `supply_stocks`. Data yang tersimpan hanya field-field dasar tanpa informasi metadata yang diperlukan untuk tracking dan audit trail.

### Current Data Structure

```sql
id	company_id	livestock_id	farm_id	coop_id	supply_id	supply_purchase_id	date	source_type	source_id	quantity_in	quantity_used	quantity_mutated	quantity_reserved	quantity_available	metadata	created_by	updated_by	created_at	updated_at	deleted_at
9f62cf6f-d6fa-4f1c-a2f3-0c53f6204a65	9f45ae4f-0630-437e-b064-ad5a1b008e35		9f46ed24-ed84-4746-ada7-ad0747328e92		9f44dc9e-4f96-405c-80c5-f7df7ec8c010	9f62cf62-3137-4e1b-99e9-8ce91da9bc92	2025-07-01	purchase	9f62cf62-3137-4e1b-99e9-8ce91da9bc92	100.00	0.00	0.00				9f48b44b-2142-4b8b-ade3-97e44ea6599c	9f48b44b-2142-4b8b-ade3-97e44ea6599c	2025-07-14 11:29:18	2025-07-14 11:29:18
```

**Issue:** Field `metadata` kosong (NULL)

## Root Cause Analysis

### 1. Missing Metadata Integration

-   Method `processStockArrival()` di `Create.php` tidak menggunakan `SupplyMetadataService`
-   Metadata tidak dibangun saat membuat SupplyStock record
-   Hanya field-field dasar yang disimpan

### 2. Incomplete Data Flow

-   Purchase transaction dan stock processing terpisah
-   Metadata harus dibangun saat stock arrival, bukan saat purchase transaction
-   Service `SupplyMetadataService` sudah tersedia tapi tidak digunakan

## Solution Implementation

### 1. Enhanced processStockArrival Method

**File:** `app/Livewire/SupplyPurchases/Create.php`

**Changes:**

-   Integrasi `SupplyMetadataService` untuk membangun metadata
-   Penggunaan data dari `SupplyPurchaseBatch` dan `SupplyPurchase`
-   Penambahan field `quantity_reserved` dan `quantity_available`
-   Enhanced logging dengan metadata summary

### 2. Metadata Structure

Metadata yang dibangun mencakup:

```php
[
    'source_type' => 'purchase',
    'purchase_id' => $purchase->id,
    'invoice_number' => $batch->invoice_number,
    'supplier_id' => $batch->supplier_id,
    'supplier_name' => $supplierName,
    'batch_code' => 'PUR-20250714-001', // Auto-generated
    'purchase_date' => $batch->date,
    'delivery_date' => $batch->date,
    'quality_check' => [
        'passed' => true,
        'checked_by' => auth()->id(),
        'checked_at' => now()->toISOString(),
        'notes' => null
    ],
    'history' => [
        [
            'date' => now()->toISOString(),
            'action' => 'purchase_created',
            'user_id' => auth()->id(),
            'quantity' => $purchase->converted_quantity,
            'notes' => 'Initial purchase record'
        ]
    ],
    'tags' => ['purchase', 'arrived'],
    'custom_fields' => [
        'batch_id' => $batch->id,
        'do_number' => $batch->do_number,
        'expedition_id' => $batch->expedition_id,
        'expedition_fee' => $batch->expedition_fee,
        'unit_id' => $purchase->unit_id,
        'converted_unit' => $purchase->converted_unit,
        'price_per_unit' => $purchase->price_per_unit,
        'price_per_converted_unit' => $purchase->price_per_converted_unit,
    ]
]
```

### 3. Enhanced SupplyStock Creation

```php
$supplyStock = SupplyStock::updateOrCreate(
    [
        'livestock_id' => $this->livestock_id ?? null,
        'farm_id' => $purchase->farm_id,
        'supply_id' => $purchase->supply_id,
        'supply_purchase_id' => $purchase->id,
    ],
    [
        'date' => $batch->date,
        'source_type' => 'purchase',
        'source_id' => $purchase->id,
        'quantity_in' => $purchase->converted_quantity,
        'quantity_mutated' => 0,
        'quantity_used' => 0,
        'quantity_reserved' => 0,
        'quantity_available' => $purchase->converted_quantity,
        'metadata' => $metadata, // ← METADATA DISIMPAN
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]
);
```

## Benefits

### 1. Complete Audit Trail

-   Semua informasi pembelian tersimpan dalam metadata
-   History tracking untuk setiap perubahan
-   Quality check information
-   Supplier dan expedition details

### 2. Enhanced Tracking

-   Batch codes untuk identifikasi unik
-   Custom fields untuk data tambahan
-   Tags untuk kategorisasi
-   Price information untuk cost tracking

### 3. Data Integrity

-   Metadata validation melalui service
-   Consistent structure across all records
-   Backward compatibility maintained
-   Error handling dan logging

### 4. Future Extensibility

-   Metadata structure dapat diperluas
-   Custom fields untuk business-specific data
-   History tracking untuk compliance
-   Integration dengan reporting system

## Testing

### 1. Test Scenario

1. Buat supply purchase dengan status DRAFT
2. Ubah status menjadi ARRIVED
3. Verifikasi metadata tersimpan di SupplyStock

### 2. Expected Result

```sql
metadata = {
    "source_type": "purchase",
    "purchase_id": "9f62cf62-3137-4e1b-99e9-8ce91da9bc92",
    "invoice_number": "INV-2025-001",
    "supplier_id": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
    "supplier_name": "Supplier Name",
    "batch_code": "PUR-20250714-001",
    "purchase_date": "2025-07-01",
    "delivery_date": "2025-07-01",
    "quality_check": {
        "passed": true,
        "checked_by": "9f48b44b-2142-4b8b-ade3-97e44ea6599c",
        "checked_at": "2025-07-14T11:30:00.000000Z"
    },
    "history": [
        {
            "date": "2025-07-14T11:30:00.000000Z",
            "action": "purchase_created",
            "user_id": "9f48b44b-2142-4b8b-ade3-97e44ea6599c",
            "quantity": 100.00,
            "notes": "Initial purchase record"
        }
    ],
    "tags": ["purchase", "arrived"],
    "custom_fields": {
        "batch_id": "9f62cf62-3137-4e1b-99e9-8ce91da9bc92",
        "do_number": "DO-2025-001",
        "expedition_id": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
        "expedition_fee": 50000,
        "unit_id": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
        "converted_unit": "9f44dc9e-4f96-405c-80c5-f7df7ec8c010",
        "price_per_unit": 100000,
        "price_per_converted_unit": 100000
    }
}
```

## Files Modified

1. **app/Livewire/SupplyPurchases/Create.php**
    - Enhanced `processStockArrival()` method
    - Added metadata integration
    - Improved logging

## Dependencies

1. **app/Services/SupplyMetadataService.php** - Already exists
2. **app/Models/SupplyStock.php** - Already supports metadata
3. **app/Models/SupplyPurchaseBatch.php** - Provides batch data
4. **app/Models/SupplyPurchase.php** - Provides purchase data

## Logging

Enhanced logging untuk debugging:

```php
Log::info('Created/Updated SupplyStock with metadata for Purchase ID: ' . $purchase->id, [
    'stock_id' => $supplyStock->id,
    'metadata_summary' => $supplyStock->metadata_summary
]);
```

## Conclusion

Fix ini memastikan bahwa metadata tersimpan dengan lengkap saat proses pembelian supply. Semua informasi penting seperti supplier, invoice, quality check, dan history tracking sekarang tersimpan dalam metadata untuk audit trail yang komprehensif.

**Status:** ✅ Implemented  
**Testing:** Required  
**Deployment:** Ready
