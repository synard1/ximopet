# Supply Usage Feature - Implementation Documentation

## Overview

Fitur Supply Usage adalah sistem untuk mencatat penggunaan supply (obat-obatan, vitamin, alat kesehatan, dll) untuk livestock dengan business flow yang terkontrol berdasarkan stock per kandang.

## 📋 Business Requirements

### Core Requirements

1. ✅ **Stock Validation**: Hanya supply dengan quantity > 0 yang bisa dipilih
2. ✅ **Location-Based**: User harus pilih Farm dan Kandang (Coop) yang akan dipakai
3. ✅ **Multi-Source Stock Check**: Mengecek data dari CurrentSupply, SupplyStock, atau SupplyPurchase
4. ✅ **Stock Locking**: Supply dikunci berdasarkan stock per kandang
5. ✅ **Unit Conversion**: Support konversi unit sesuai konfigurasi supply
6. ✅ **FIFO Stock Update**: Update stock menggunakan metode FIFO (First In First Out)

### Additional Features

1. ✅ **Edit Mode**: Support untuk mengedit usage yang sudah ada
2. ✅ **Multi-Item**: Bisa menggunakan multiple supplies dalam satu transaksi
3. ✅ **Real-time Validation**: Validasi stock availability secara real-time
4. ✅ **Audit Trail**: Complete logging untuk semua transaksi
5. ✅ **Data Integrity**: Restore stock saat edit/delete

## 🏗️ Architecture

### Files Structure

```
app/
├── Livewire/MasterData/Supply/
│   ├── Create.php                    # Existing supply creation
│   └── Usage.php                     # NEW: Supply usage component
├── Models/
│   ├── SupplyUsage.php              # NEW: Main usage model
│   └── SupplyUsageDetail.php        # NEW: Usage detail model
└── database/migrations/
    ├── 2025_01_28_100000_create_supply_usages_table.php
    └── 2025_01_28_100001_create_supply_usage_details_table.php

resources/views/livewire/master-data/supply/
└── usage.blade.php                   # NEW: Usage form UI
```

### Database Schema

#### supply_usages Table

| Field        | Type    | Description                           |
| ------------ | ------- | ------------------------------------- |
| id           | bigint  | Primary key                           |
| farm_id      | bigint  | Farm reference                        |
| coop_id      | bigint  | Kandang reference                     |
| livestock_id | bigint  | Livestock reference                   |
| usage_date   | date    | Tanggal penggunaan                    |
| notes        | text    | Catatan tambahan                      |
| total_items  | integer | Jumlah item yang digunakan            |
| status       | enum    | Status: pending, completed, cancelled |
| created_by   | bigint  | User creator                          |
| updated_by   | bigint  | User updater                          |

#### supply_usage_details Table

| Field              | Type          | Description                |
| ------------------ | ------------- | -------------------------- |
| id                 | bigint        | Primary key                |
| supply_usage_id    | bigint        | Reference to supply_usages |
| supply_id          | bigint        | Supply reference           |
| quantity           | decimal(10,4) | Quantity used              |
| unit_id            | bigint        | Unit reference             |
| converted_quantity | decimal(10,4) | Quantity in smallest unit  |
| price_per_unit     | decimal(12,2) | Price per unit (optional)  |
| total_price        | decimal(12,2) | Total price (optional)     |
| notes              | text          | Item notes                 |
| batch_number       | varchar(100)  | Batch number               |
| expiry_date        | date          | Expiry date                |

## 🔄 Business Flow

### 1. Stock Check Hierarchy

```php
1. CurrentSupply::where('farm_id', $farmId)
   ->where('quantity', '>', 0)

2. SupplyStock::where('farm_id', $farmId)
   ->where('date', '<=', $usageDate)
   ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
```

### 2. User Flow

1. **Select Location**: User pilih Farm → Kandang → Livestock
2. **Load Available Supplies**: System load supplies dengan stock > 0
3. **Add Items**: User tambah supply items dengan quantity & unit
4. **Real-time Validation**: System validasi stock availability
5. **Save**: Update stock menggunakan FIFO method
6. **Audit**: Log semua perubahan untuk traceability

### 3. Stock Update Flow (FIFO)

```php
// Update SupplyStock records (oldest first)
$stockRecords = SupplyStock::where('farm_id', $farmId)
    ->where('supply_id', $supplyId)
    ->where('date', '<=', $usageDate)
    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    ->orderBy('date')
    ->orderBy('created_at')
    ->get();

// Update CurrentSupply
$currentSupply->decrement('quantity', $convertedQuantity);
```

## 🎯 Key Features

### 1. Real-time Stock Validation

-   Stock availability dicheck setiap kali user ubah quantity
-   Error message jika quantity melebihi stock
-   Visual indicator untuk available stock

### 2. Unit Conversion System

```php
// Convert to smallest unit based on supply configuration
private function convertToSmallestUnit($item)
{
    $supply = Supply::find($item['supply_id']);
    $units = collect($supply->payload['conversion_units']);
    $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
    $smallestUnit = $units->firstWhere('is_smallest', true);

    return ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];
}
```

### 3. Edit Mode with Stock Restoration

```php
// Restore previous stock usage before updating
private function restorePreviousStockUsage($usage)
{
    foreach ($usage->details as $detail) {
        // Restore SupplyStock (reverse FIFO order)
        // Restore CurrentSupply
    }
}
```

### 4. Multi-layer Validation

-   **Frontend**: Real-time stock check
-   **Backend**: Database validation before save
-   **Stock**: FIFO validation during update
-   **Business Rule**: Date, permission, status checks

## 🔧 Usage Examples

### 1. Basic Usage Creation

```php
// Livewire component usage
$usage = new \App\Livewire\MasterData\Supply\Usage();
$usage->farm_id = 1;
$usage->coop_id = 1;
$usage->livestock_id = 1;
$usage->usage_date = '2025-01-28';
$usage->items = [
    [
        'supply_id' => 1,
        'quantity' => 5,
        'unit_id' => 1,
        'notes' => 'Vitamin untuk ayam'
    ]
];
$usage->save();
```

### 2. Stock Check

```php
// Check available stock for supply
$availableStock = $usage->getAvailableStock($supplyId);

// Load supplies with stock
$suppliesWithStock = $usage->getSuppliesWithStock();
```

### 3. Unit Conversion

```php
// Get available units for supply
$availableUnits = $usage->getSupplyUnits($supplyId);

// Convert quantity to smallest unit
$convertedQuantity = $usage->convertToSmallestUnit($item);
```

## 🔐 Security & Permissions

### Permission Requirements

-   **Create**: `create supply usage` permission
-   **Edit**: `update supply usage` permission
-   **Delete**: `delete supply usage` permission
-   **View**: `view supply usage` permission

### Data Security

-   All operations wrapped in database transactions
-   Soft deletes for audit trail
-   User tracking (created_by, updated_by)
-   Input validation and sanitization

## 📊 Performance Optimizations

### Database Optimizations

1. **Indexes**: Strategic indexes pada foreign keys dan search fields
2. **Eager Loading**: Load relationships untuk mengurangi N+1 queries
3. **Bulk Operations**: Batch operations untuk multiple items
4. **Query Optimization**: Efficient queries untuk stock checking

### Frontend Optimizations

1. **Livewire**: Real-time updates tanpa page refresh
2. **Lazy Loading**: Load data hanya saat dibutuhkan
3. **Debouncing**: Prevent excessive API calls
4. **Client Validation**: Immediate feedback

## 🧪 Testing Scenarios

### 1. Basic Flow Testing

-   ✅ Select farm, coop, livestock
-   ✅ Load available supplies
-   ✅ Add items with valid quantities
-   ✅ Save successfully

### 2. Validation Testing

-   ✅ Quantity exceeds stock → Error
-   ✅ No farm selected → Error
-   ✅ Invalid date → Error
-   ✅ Empty items → Error

### 3. Stock Update Testing

-   ✅ FIFO order maintained
-   ✅ CurrentSupply updated correctly
-   ✅ SupplyStock records updated

### 4. Edit Mode Testing

-   ✅ Previous stock restored
-   ✅ New stock applied
-   ✅ Data integrity maintained

## 🚀 Deployment Checklist

### Database Migration

```bash
php artisan migrate
```

### Required Data

-   ✅ Farms with active status
-   ✅ Coops linked to farms
-   ✅ Livestocks linked to coops
-   ✅ Supplies with conversion units
-   ✅ Units for conversion
-   ✅ Stock data (CurrentSupply/SupplyStock)

### Configuration

-   ✅ Permissions setup
-   ✅ Route configuration
-   ✅ Menu integration

## 📈 Future Enhancements

### Phase 2 Features

1. **Batch Management**: Track batch numbers and expiry dates
2. **Cost Tracking**: Price per unit and total cost calculation
3. **Reporting**: Usage reports by farm, supply, date range
4. **Alerts**: Low stock alerts, expiry warnings
5. **Mobile App**: Mobile interface for field usage
6. **Barcode Scanner**: QR/Barcode scanning for supplies
7. **Integration**: API for external systems

### Performance Enhancements

1. **Caching**: Redis cache for frequently accessed data
2. **Queue Jobs**: Background processing for heavy operations
3. **Database Optimization**: Partitioning for large datasets
4. **CDN**: Asset optimization for faster loading

## 🛠️ Troubleshooting

### Common Issues

1. **Stock Not Available**: Check CurrentSupply and SupplyStock data
2. **Unit Conversion Error**: Verify supply conversion_units configuration
3. **Permission Denied**: Check user permissions
4. **FIFO Error**: Verify stock records chronological order

### Debug Commands

```php
// Check stock availability
$stock = CurrentSupply::where('farm_id', 1)->where('item_id', 1)->first();

// Check supply configuration
$supply = Supply::find(1);
dd($supply->payload['conversion_units']);

// Check stock records
$stocks = SupplyStock::where('farm_id', 1)->where('supply_id', 1)->get();
```

## 📞 Support

Untuk pertanyaan teknis atau bug report, silakan hubungi development team dengan menyertakan:

1. Error message lengkap
2. Steps to reproduce
3. Environment details
4. Sample data yang bermasalah

---

**Last Updated**: January 28, 2025  
**Version**: 1.0.0  
**Author**: AI Assistant  
**Status**: Production Ready ✅
