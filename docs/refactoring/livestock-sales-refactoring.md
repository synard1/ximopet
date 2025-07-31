# Refactoring Model LivestockSales

## **LOG REFACTORING - 27 Januari 2025**

### **Analisis Kebutuhan Sebelum Refactoring**

Model `LivestockSales` sebelumnya hanya memiliki field dasar:

-   `company_id`, `livestock_id`, `date`
-   `customer_name`, `customer_id`, `status`, `notes`, `data`

**Kekurangan yang ditemukan:**

1. Tidak ada penomoran invoice dan DO
2. Tidak ada tracking lokasi (farm, coop)
3. Tidak ada informasi batch ternak
4. Tidak ada data pengiriman dan ekspedisi
5. Tidak ada tracking pembayaran
6. Tidak ada workflow approval
7. Tidak ada total quantity, weight, dan amount
8. Tidak ada logging untuk debugging

### **Parameter Baru yang Ditambahkan**

#### **1. Penomoran dan Referensi**

```php
'invoice_number' => 'string',     // Nomor invoice penjualan
'do_number' => 'string',          // Nomor delivery order/surat jalan
```

#### **2. Lokasi dan Batch**

```php
'farm_id' => 'uuid',              // ID farm/kebun
'coop_id' => 'uuid',              // ID kandang
'livestock_batch_id' => 'uuid',   // ID batch ternak
```

#### **3. Pengiriman**

```php
'delivery_date' => 'datetime',    // Tanggal pengiriman
'delivery_address' => 'text',     // Alamat pengiriman
'expedition_id' => 'uuid',        // ID ekspedisi pengiriman
'expedition_fee' => 'decimal',    // Biaya ekspedisi
```

#### **4. Total dan Nilai**

```php
'total_quantity' => 'integer',    // Total quantity terjual
'total_weight' => 'decimal',      // Total berat terjual
'total_amount' => 'decimal',      // Total nilai penjualan
```

#### **5. Pembayaran**

```php
'payment_method' => 'string',     // Metode pembayaran (cash/transfer/credit)
'payment_status' => 'string',     // Status pembayaran (unpaid/partial/paid/overdue)
'due_date' => 'datetime',         // Tanggal jatuh tempo
```

#### **6. Approval Workflow**

```php
'approved_by' => 'uuid',          // ID user yang approve
'approved_at' => 'datetime',      // Tanggal approval
```

### **Fitur Baru yang Ditambahkan**

#### **1. Constants dan Status Management**

```php
// Status transaksi
const STATUS_DRAFT = 'draft';
const STATUS_PENDING = 'pending';
const STATUS_CONFIRMED = 'confirmed';
const STATUS_DELIVERED = 'delivered';
const STATUS_COMPLETED = 'completed';
const STATUS_CANCELLED = 'cancelled';

// Status pembayaran
const PAYMENT_STATUS_UNPAID = 'unpaid';
const PAYMENT_STATUS_PARTIAL = 'partial';
const PAYMENT_STATUS_PAID = 'paid';
const PAYMENT_STATUS_OVERDUE = 'overdue';

// Metode pembayaran
const PAYMENT_METHOD_CASH = 'cash';
const PAYMENT_METHOD_TRANSFER = 'transfer';
const PAYMENT_METHOD_CREDIT = 'credit';
```

#### **2. Accessor Methods**

```php
// Label untuk display
public function getStatusLabelAttribute(): string
public function getPaymentStatusLabelAttribute(): string
public function getPaymentMethodLabelAttribute(): string

// Helper methods
public function isApproved(): bool
public function isPaymentOverdue(): bool
public function getRemainingAmount(): float
```

#### **3. Relationships Baru**

```php
// Lokasi
public function farm(): BelongsTo
public function coop(): BelongsTo

// Batch dan ekspedisi
public function livestockBatch(): BelongsTo
public function expedition(): BelongsTo

// Approval dan audit
public function approvedBy(): BelongsTo
public function createdBy(): BelongsTo
public function updatedBy(): BelongsTo
```

#### **4. Query Scopes**

```php
// Filtering
public function scopeByStatus($query, $status)
public function scopeByPaymentStatus($query, $paymentStatus)
public function scopeByDateRange($query, $startDate, $endDate)
public function scopeByCustomer($query, $customerId)
public function scopeOverdue($query)
```

#### **5. Logging dan Debugging**

```php
// Auto-logging saat create/update
protected static function boot()
{
    static::created(function ($model) {
        Log::info('LivestockSales created', [...]);
    });

    static::updated(function ($model) {
        Log::info('LivestockSales updated', [...]);
    });
}
```

### **Migration yang Dibuat**

File: `database/migrations/2025_01_27_000000_add_comprehensive_fields_to_livestock_sales_table.php`

**Kolom yang ditambahkan:**

-   17 kolom baru untuk melengkapi kebutuhan penjualan
-   5 foreign key constraints
-   6 composite indexes untuk performa
-   Default values untuk field numerik

### **Manfaat Refactoring**

#### **1. Data Completeness**

-   Semua informasi penjualan tersimpan lengkap
-   Tracking dari draft hingga completed
-   Audit trail yang jelas

#### **2. Business Logic**

-   Workflow approval yang terstruktur
-   Payment tracking yang detail
-   Delivery management yang komprehensif

#### **3. Performance**

-   Indexes untuk query yang sering digunakan
-   Composite indexes untuk filtering kompleks
-   Optimized relationships

#### **4. Debugging & Monitoring**

-   Auto-logging untuk setiap perubahan
-   Status tracking yang detail
-   Payment overdue detection

#### **5. Scalability**

-   Extensible data structure dengan JSON field
-   Flexible payment methods
-   Batch-level tracking

### **Cara Penggunaan**

#### **1. Membuat Penjualan Baru**

```php
$sale = LivestockSales::create([
    'company_id' => $companyId,
    'invoice_number' => 'INV-20250127-001',
    'farm_id' => $farmId,
    'coop_id' => $coopId,
    'livestock_id' => $livestockId,
    'livestock_batch_id' => $batchId,
    'customer_id' => $customerId,
    'total_quantity' => 100,
    'total_weight' => 500.50,
    'total_amount' => 5000000,
    'payment_method' => LivestockSales::PAYMENT_METHOD_CREDIT,
    'payment_status' => LivestockSales::PAYMENT_STATUS_UNPAID,
    'status' => LivestockSales::STATUS_DRAFT,
]);
```

#### **2. Query dengan Scopes**

```php
// Penjualan yang overdue
$overdueSales = LivestockSales::overdue()->get();

// Penjualan per customer
$customerSales = LivestockSales::byCustomer($customerId)
    ->byDateRange($startDate, $endDate)
    ->get();

// Penjualan yang belum dibayar
$unpaidSales = LivestockSales::byPaymentStatus(LivestockSales::PAYMENT_STATUS_UNPAID)
    ->byStatus(LivestockSales::STATUS_CONFIRMED)
    ->get();
```

#### **3. Status Management**

```php
// Check status
if ($sale->isApproved()) {
    // Process delivery
}

if ($sale->isPaymentOverdue()) {
    // Send reminder
}

// Get labels
echo $sale->status_label; // "Confirmed"
echo $sale->payment_status_label; // "Unpaid"
```

### **Next Steps**

1. **Run Migration:**

    ```bash
    php artisan migrate
    ```

2. **Update Related Models:**

    - Update `LivestockSalesItem` jika diperlukan
    - Update controllers dan services
    - Update views dan forms

3. **Testing:**

    - Unit tests untuk model methods
    - Feature tests untuk workflow
    - Performance testing untuk queries

4. **Documentation:**
    - API documentation
    - User manual
    - Database schema documentation

### **Log Perubahan**

-   **27 Jan 2025:** Initial refactoring completed
-   **Model:** Added 17 new fields, 8 new relationships, 5 scopes
-   **Migration:** Created comprehensive migration with indexes
-   **Documentation:** Created detailed documentation
-   **Logging:** Implemented auto-logging for debugging
