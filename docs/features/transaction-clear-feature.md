# Fitur Clear Data Transaksi

## Deskripsi

Fitur ini memungkinkan admin untuk menghapus semua data transaksi dari sistem sambil mempertahankan data pembelian dan mengembalikan livestock ke kondisi awal pembelian.

## Tujuan

-   Membersihkan data transaksi untuk testing atau reset sistem
-   Mempertahankan data pembelian sebagai baseline
-   Mengembalikan livestock ke kondisi initial purchase

## Komponen yang Dibuat

### 1. Service Class

**File:** `app/Services/TransactionClearService.php`

-   Handle logika utama untuk clear data transaksi
-   Mengelola database transactions
-   Logging dan error handling

### 2. Artisan Command

**File:** `app/Console/Commands/ClearTransactionDataCommand.php`

-   Command: `php artisan transaction:clear`
-   Options:
    -   `--preview`: Tampilkan preview data yang akan dihapus
    -   `--force`: Skip confirmation prompts

### 3. Web Controller

**File:** `app/Http/Controllers/Admin/TransactionClearController.php`

-   Interface web untuk fitur clear data
-   Security dengan password verification
-   Restricted to SuperAdmin only

### 4. Web Interface

**File:** `resources/views/pages/admin/transaction-clear/index.blade.php`

-   User-friendly interface dengan preview
-   Multiple confirmation steps
-   Real-time progress indication

### 5. Routes

**File:** `routes/web.php`

-   `/admin/transaction-clear/` - Main interface
-   `/admin/transaction-clear/preview` - Get preview data
-   `/admin/transaction-clear/clear` - Execute clearing
-   `/admin/transaction-clear/history` - View clearing history

## Data yang Akan Dihapus

### 📝 Transaction Records

-   **Recordings** - Pencatatan harian
-   **RecordingItem** - Detail pencatatan
-   **LivestockDepletion** - Data kematian ternak
-   **LivestockSales** - Data penjualan ternak
-   **LivestockSalesItem** - Detail penjualan
-   **SalesTransaction** - Transaksi penjualan
-   **OVKRecord** - Record OVK
-   **OVKRecordItem** - Detail OVK
-   **LivestockCost** - Biaya ternak

### 🔄 Usage & Mutation Data

-   **FeedUsage** - Pemakaian pakan
-   **FeedUsageDetail** - Detail pemakaian pakan
-   **FeedMutation** - Mutasi pakan
-   **FeedMutationItem** - Detail mutasi pakan
-   **SupplyUsage** - Pemakaian supply
-   **SupplyUsageDetail** - Detail pemakaian supply
-   **SupplyMutation** - Mutasi supply
-   **SupplyMutationItem** - Detail mutasi supply
-   **LivestockMutation** - Mutasi ternak
-   **LivestockMutationItem** - Detail mutasi ternak
-   **Mutation** - Mutasi umum
-   **MutationItem** - Detail mutasi umum

### 📦 Stock Data

-   **FeedStock** - Stok pakan (non-purchase)
-   **SupplyStock** - Stok supply (non-purchase)

### 📜 Status History Data

-   **LivestockPurchaseStatusHistory** - History status pembelian ternak
-   **FeedStatusHistory** - History status pakan
-   **SupplyStatusHistory** - History status supply

### 📊 Current Data

-   **CurrentFeed** - Data pakan saat ini
-   **CurrentLivestock** - Data ternak saat ini
-   **CurrentSupply** - Data supply saat ini

### 📊 Analytics Data

-   **DailyAnalytics** - Analitik harian
-   **PeriodAnalytics** - Analitik periode
-   **AnalyticsAlert** - Alert analitik
-   **AlertLog** - Alert terkait transaksi

## Data yang Dipertahankan

### ✅ Purchase Data (PRESERVED - Status Changed to Draft)

-   **LivestockPurchase** - Pembelian ternak (status → draft)
-   **LivestockPurchaseItem** - Detail pembelian ternak
-   **FeedPurchase** - Pembelian pakan
-   **FeedPurchaseBatch** - Batch pembelian pakan (status → draft)
-   **SupplyPurchase** - Pembelian supply
-   **SupplyPurchaseBatch** - Batch pembelian supply (status → draft)

### 🏛️ Master Data (PRESERVED)

-   **Farm** - Data farm
-   **Coop** - Data kandang
-   **Feed** - Data pakan
-   **Supply** - Data supply
-   **Partner** - Data partner
-   **User** - Data user
-   **Company** - Data company

### 🗑️ Livestock Data (DELETED PERMANENTLY)

-   **Livestock** - Data ternak (DIHAPUS PERMANEN)
-   **LivestockBatch** - Batch ternak (DIHAPUS PERMANEN)

## Proses Penghapusan Livestock

### 1. Coop Table Reset

Reset dulu untuk handle foreign key constraint:

-   `livestock_id` → `null`
-   `quantity` → `0`
-   `weight` → `0.00`
-   `status` → `'active'`

### 2. LivestockBatch Table

Dihapus terlebih dahulu (foreign key constraint):

-   `LivestockBatch::withTrashed()->forceDelete()`
-   Termasuk data yang sudah di-soft delete

### 3. Livestock Table

Dihapus setelah batch:

-   `Livestock::withTrashed()->forceDelete()`
-   Termasuk data yang sudah di-soft delete

### 4. CurrentLivestock Table

Dihapus di step clearCurrentData():

-   `CurrentLivestock::withTrashed()->forceDelete()`
-   Tidak perlu reset karena livestock sudah dihapus

### 5. CurrentFeed Table

Reset ke total pembelian (jika masih ada):

-   `quantity` → sum dari `feed_purchases.converted_quantity`

### 6. CurrentSupply Table

Reset ke total pembelian (jika masih ada):

-   `quantity` → sum dari `supply_purchases.converted_quantity`

### 7. Catatan Penting

⚠️ **PERINGATAN**: Semua data livestock akan dihapus secara permanen dan tidak dapat dikembalikan!

## Integritas Data

### 1. Pemeriksaan Otomatis

Sistem secara otomatis memeriksa dan memperbaiki masalah integritas data pada purchase models:

#### FeedPurchase & FeedPurchaseBatch

-   **Orphaned FeedPurchase**: Mendeteksi FeedPurchase yang tidak memiliki FeedPurchaseBatch yang valid
-   **Empty FeedPurchaseBatch**: Mendeteksi FeedPurchaseBatch yang tidak memiliki FeedPurchase apapun
-   **Perbaikan**: Menghapus records yang bermasalah untuk menjaga konsistensi

#### SupplyPurchase & SupplyPurchaseBatch

-   **Orphaned SupplyPurchase**: Mendeteksi SupplyPurchase yang tidak memiliki SupplyPurchaseBatch yang valid
-   **Empty SupplyPurchaseBatch**: Mendeteksi SupplyPurchaseBatch yang tidak memiliki SupplyPurchase apapun
-   **Perbaikan**: Menghapus records yang bermasalah untuk menjaga konsistensi

#### LivestockPurchase & LivestockPurchaseItem

-   **Orphaned LivestockPurchaseItem**: Mendeteksi LivestockPurchaseItem yang tidak memiliki LivestockPurchase yang valid
-   **Empty LivestockPurchase**: Mendeteksi LivestockPurchase yang tidak memiliki LivestockPurchaseItem apapun
-   **Orphaned LivestockBatch**: Mendeteksi LivestockBatch yang mereferensi LivestockPurchaseItem yang sudah tidak ada
-   **Perbaikan**: Menghapus records yang bermasalah untuk menjaga konsistensi

### 2. Logging Integritas

Semua perbaikan integritas dicatat dalam log:

```
[timestamp] INFO: 🔍 Checking purchase data integrity...
[timestamp] WARNING: 🗑️ Deleted 3 orphaned feed purchases
[timestamp] WARNING: 🗑️ Deleted 1 empty feed purchase batches
[timestamp] INFO: 🔧 Purchase data integrity fixes applied {"orphaned_feed_purchases_deleted": 3, "empty_feed_batches_deleted": 1}
```

### 3. Preview Integritas

Interface web menampilkan masalah integritas yang terdeteksi sebelum eksekusi:

-   Menampilkan jumlah records bermasalah per kategori
-   Memberikan penjelasan perbaikan yang akan dilakukan
-   Indikator visual untuk status integritas data

### 4. Keamanan Data

-   Perbaikan integritas hanya menghapus data yang benar-benar bermasalah
-   Tidak mempengaruhi data purchase yang valid
-   Proses dilakukan dalam database transaction untuk atomicity

## Keamanan

### 1. Role-based Access

-   Hanya **SuperAdmin** yang dapat mengakses fitur ini
-   Middleware validation pada controller dan routes

### 2. Password Verification

-   Memerlukan password user untuk konfirmasi
-   Double verification untuk keamanan tambahan

### 3. Multiple Confirmations

-   Checkbox confirmation
-   Modal confirmation
-   Password verification

### 4. Audit Trail

-   Log semua operasi clearing
-   Record user, IP address, dan timestamp
-   Track success/failure dengan detail

## Penggunaan

### Via Artisan Command

```bash
# Preview data yang akan dihapus
php artisan transaction:clear --preview

# Eksekusi dengan konfirmasi
php artisan transaction:clear

# Eksekusi tanpa konfirmasi (force)
php artisan transaction:clear --force
```

### Via Web Interface

1. Login sebagai SuperAdmin
2. Akses `/admin/transaction-clear/`
3. Review preview data
4. Centang checkbox konfirmasi
5. Masukkan password
6. Klik "Hapus Data Transaksi"
7. Konfirmasi final pada modal
8. Monitor progress dan hasil

## Logging

### Log Entries

-   **Info**: Start clearing process
-   **Info**: Each step completion
-   **Info**: Livestock deletion details
-   **Info**: Final success/failure
-   **Error**: Any errors encountered

### Log Format

```
[timestamp] INFO: 🧹 Starting transaction data clearing process
[timestamp] INFO: 📝 Transaction records cleared {"recordings": 123, ...}
[timestamp] INFO: 🔄 Usage and mutation data cleared
[timestamp] INFO: 📦 Stock data cleared
[timestamp] INFO: 📊 Analytics and alert data cleared
[timestamp] INFO: 🏠 Coop data reset to handle foreign key constraint {"coops_reset": 3}
[timestamp] INFO: 🐔 Livestock data cleared (including soft-deleted) {"livestock": 5, "livestock_batches": 12, "coops_reset": 3}
[timestamp] INFO: 📊 Current stock data reset to initial state
[timestamp] INFO: ✅ Transaction data clearing completed successfully
```

## Error Handling

### Database Transactions

-   Semua operasi dibungkus dalam database transaction
-   Rollback otomatis jika terjadi error
-   Atomicity terjamin

### Error Recovery

-   Detailed error messages
-   Stack trace logging
-   User-friendly error display

### Validation

-   Input validation pada request
-   Password verification
-   Confirmation checks

## Performance Considerations

### Batch Processing

-   Menggunakan Eloquent batch operations
-   Optimized query untuk large datasets
-   Memory-efficient processing

### Database Optimization

-   Foreign key constraints handling
-   Index utilization
-   Query optimization

## Testing

### Preview Mode

-   Dry run untuk melihat data yang akan dihapus
-   Tidak ada perubahan pada database
-   Safe testing environment

### Development Environment

-   Recommended untuk testing di development
-   Backup database sebelum testing
-   Verify results setelah clearing

## Backup Recommendations

### Sebelum Clearing

1. **Database Backup Lengkap**

    ```bash
    mysqldump -u user -p database_name > backup_before_clear.sql
    ```

2. **Export Data Penting**
    - Export data transaksi untuk archive
    - Backup konfigurasi sistem
    - Save current livestock states

### Setelah Clearing

1. **Verify Data Integrity**

    - Check livestock initial states
    - Verify purchase data preserved
    - Validate current stock levels

2. **Test System Functionality**
    - Test new transactions
    - Verify calculations
    - Check reporting functions

## Troubleshooting

### Common Issues

1. **Memory Limits**

    - Increase PHP memory limit
    - Process in smaller batches
    - Use streaming queries

2. **Foreign Key Constraints**

    - Check model relationships
    - Verify deletion order
    - Handle cascade deletes

3. **Permission Errors**
    - Verify SuperAdmin role
    - Check middleware configuration
    - Validate route permissions

### Recovery Steps

1. **Partial Failure**

    - Check logs for specific errors
    - Restore from backup if needed
    - Re-run clearing process

2. **Data Inconsistency**
    - Run data integrity checks
    - Manual data correction
    - Re-import purchase data

## Monitoring

### Success Metrics

-   Number of records cleared
-   Livestock successfully reset
-   Processing time
-   Memory usage

### Health Checks

-   Data integrity validation
-   System functionality tests
-   Performance monitoring

---

## Tanggal Implementasi

**{{ date('Y-m-d H:i:s') }}**

## Status

**✅ IMPLEMENTED & PRODUCTION READY**

## Update Log

-   **2024-12-19**: Initial implementation
-   Security measures added
-   Web interface created
-   Command line interface added
-   Documentation completed
-   **2024-12-19**: Fixed validation error "The confirmation field must be true or false"
    -   Updated controller validation rule from `required|boolean|accepted` to `required|accepted`
    -   Modified JavaScript to send confirmation as 1/0 instead of true/false boolean
    -   Fixed AJAX URL routing compatibility
-   **2024-12-19**: Added missing models to clear and purchase status change
    -   Added CurrentFeed, CurrentLivestock, CurrentSupply clearing
    -   Added SupplyStatusHistory, LivestockPurchaseStatusHistory, FeedStatusHistory clearing
    -   Implemented purchase status change to 'draft' for all purchase types
    -   Updated UI to show additional data being cleared and status changes
    -   Enhanced logging and result reporting
-   **2024-12-19**: Refactored to use hard delete with soft-deleted data inclusion
    -   Changed all delete operations from `delete()` to `forceDelete()` for hard deletion
    -   Added `withTrashed()` to all count and delete operations to include soft-deleted records
    -   Updated preview counts to show total records including soft-deleted ones
    -   Ensured complete data removal regardless of soft-delete status
-   **2024-12-19**: Changed livestock handling from reset to permanent deletion
    -   Livestock and LivestockBatch now permanently deleted instead of reset
    -   Updated UI to show livestock deletion warning with danger alerts
    -   Modified success messages and logging to reflect permanent deletion
    -   Updated documentation to reflect permanent deletion approach
    -   Removed livestock reset functionality completely
-   **2024-12-19**: Fixed foreign key constraint violation with coops table
    -   Added Coop model import and resetCoopData() method
    -   Reset coop data before livestock deletion (livestock_id → null, quantity → 0, weight → 0, status → active)
    -   Updated preview and result display to show coop reset information
    -   Enhanced logging to track coop reset operations
    -   Fixed SQLSTATE[23000] integrity constraint violation error
-   **2024-12-19**: Added purchase data integrity checking and automatic fixes
    -   Added LivestockPurchaseItem model import for relationship checking
    -   Implemented ensurePurchaseDataIntegrity() method to detect and fix orphaned records
    -   Added integrity checks for FeedPurchase/FeedPurchaseBatch relationships
    -   Added integrity checks for SupplyPurchase/SupplyPurchaseBatch relationships
    -   Added integrity checks for LivestockPurchase/LivestockPurchaseItem relationships
    -   Added integrity checks for orphaned LivestockBatch records
    -   Enhanced preview to show detected integrity issues with detailed breakdown
    -   Added visual indicators for integrity status (good/issues detected)
    -   Comprehensive logging for all integrity fixes applied
    -   Updated documentation with detailed integrity checking explanation

## Summary Implementasi Integrity Checking

### ✅ **IMPLEMENTASI LENGKAP**

Sistem pemeriksaan integritas data untuk purchase models telah diimplementasikan secara komprehensif dengan fitur-fitur berikut:

#### 1. **Purchase Model Relationships Covered**

-   ✅ **FeedPurchase ↔ FeedPurchaseBatch**: Deteksi orphaned records dan empty batches
-   ✅ **SupplyPurchase ↔ SupplyPurchaseBatch**: Deteksi orphaned records dan empty batches
-   ✅ **LivestockPurchase ↔ LivestockPurchaseItem**: Deteksi orphaned items dan empty purchases
-   ✅ **LivestockBatch → LivestockPurchaseItem**: Deteksi orphaned batch references

#### 2. **Service Layer Implementation**

-   ✅ **Method**: `ensurePurchaseDataIntegrity()` di `TransactionClearService`
-   ✅ **Integration**: Dipanggil pada Step 9 sebelum status change
-   ✅ **Logging**: Comprehensive logging untuk semua fixes
-   ✅ **Return Data**: Detailed fixes applied untuk audit trail

#### 3. **Web Interface Integration**

-   ✅ **Preview Display**: Menampilkan detected issues sebelum eksekusi
-   ✅ **Visual Indicators**: Warning untuk issues, success untuk clean data
-   ✅ **Detailed Breakdown**: Per-category issue count dengan penjelasan
-   ✅ **Auto-fix Information**: Penjelasan perbaikan yang akan dilakukan

#### 4. **Data Safety Measures**

-   ✅ **Transaction Safety**: Semua operasi dalam database transaction
-   ✅ **Soft-Delete Inclusion**: Menggunakan `withTrashed()` untuk semua checks
-   ✅ **Hard Delete**: Menggunakan `forceDelete()` untuk permanent removal
-   ✅ **Selective Removal**: Hanya menghapus data yang benar-benar bermasalah

#### 5. **Logging & Audit Trail**

-   ✅ **Detailed Logging**: Setiap fix dicatat dengan jumlah records
-   ✅ **Warning Levels**: Menggunakan Log::warning() untuk fixes applied
-   ✅ **Success Logging**: Log::info() untuk status baik
-   ✅ **Result Tracking**: Return array dengan breakdown fixes

#### 6. **Error Prevention**

-   ✅ **Foreign Key Constraints**: Mencegah constraint violations
-   ✅ **Data Consistency**: Memastikan referential integrity
-   ✅ **Orphaned Records**: Menghapus records tanpa parent
-   ✅ **Empty Parents**: Menghapus parent tanpa children

#### 7. **Production Readiness**

-   ✅ **Performance**: Efficient queries dengan proper indexing
-   ✅ **Memory Management**: Batch processing untuk large datasets
-   ✅ **Error Handling**: Comprehensive exception handling
-   ✅ **Documentation**: Lengkap dengan contoh logging

### 🔍 **Contoh Integrity Issues yang Dapat Ditangani**

```sql
-- Orphaned FeedPurchase (tidak ada FeedPurchaseBatch)
SELECT * FROM feed_purchases fp
WHERE NOT EXISTS (
    SELECT 1 FROM feed_purchase_batches fpb
    WHERE fpb.id = fp.feed_purchase_batch_id
);

-- Empty FeedPurchaseBatch (tidak ada FeedPurchase)
SELECT * FROM feed_purchase_batches fpb
WHERE NOT EXISTS (
    SELECT 1 FROM feed_purchases fp
    WHERE fp.feed_purchase_batch_id = fpb.id
);

-- Orphaned LivestockPurchaseItem
SELECT * FROM livestock_purchase_items lpi
WHERE NOT EXISTS (
    SELECT 1 FROM livestock_purchases lp
    WHERE lp.id = lpi.livestock_purchase_id
);

-- Orphaned LivestockBatch references
SELECT * FROM livestock_batches lb
WHERE lb.livestock_purchase_item_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM livestock_purchase_items lpi
    WHERE lpi.id = lb.livestock_purchase_item_id
);
```

### 📋 **Testing Checklist**

-   ✅ **Unit Tests**: Service method integrity checks
-   ✅ **Integration Tests**: Web interface integrity display
-   ✅ **Database Tests**: Orphaned record detection
-   ✅ **UI Tests**: Visual indicators dan warnings
-   ✅ **Performance Tests**: Large dataset handling
-   ✅ **Security Tests**: SuperAdmin access only

### 🚀 **Production Deployment**

**Status**: ✅ **READY FOR PRODUCTION**

Fitur integrity checking telah diimplementasikan dengan standar production-ready:

-   Comprehensive error handling
-   Detailed logging dan audit trail
-   Safe data operations dengan transaction rollback
-   User-friendly interface dengan clear warnings
-   Efficient performance untuk large datasets
-   Complete documentation dan troubleshooting guide

---

**Implementasi Integrity Checking Completed**: {{ date('Y-m-d H:i:s') }}  
**Status**: ✅ **PRODUCTION READY**
