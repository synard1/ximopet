# FIFO Depletion Component - Documentation

**Date:** January 23, 2025  
**Component:** `app/Livewire/MasterData/Livestock/FifoDepletion.php`  
**View:** `resources/views/livewire/master-data/livestock/fifo-depletion.blade.php`  
**Status:** ✅ CREATED

## 🎯 **OVERVIEW**

Component Livewire untuk input FIFO Depletion yang dibuat berdasarkan referensi dari `ManualDepletion.php`. Component ini menyediakan interface yang user-friendly untuk melakukan depletion menggunakan metode FIFO (First In, First Out).

## 🏗️ **ARCHITECTURE**

### **Component Structure**

```
FifoDepletion.php
├── Properties
│   ├── Form Data (depletionType, depletionDate, totalQuantity, reason, notes)
│   ├── UI State (step, isLoading, errors, successMessage)
│   └── FIFO Data (previewData, fifoDistribution, canProcess)
├── Validation
│   ├── Real-time validation
│   ├── Configuration validation
│   └── FIFO support validation
├── Business Logic
│   ├── FIFO preview generation
│   ├── Distribution calculation
│   └── Depletion processing
└── Integration
    ├── FIFODepletionService
    ├── LivestockDepletionConfig
    └── Recording system
```

### **Key Features**

#### **1. Multi-Step Process**

-   **Step 1**: Input form dengan validasi real-time
-   **Step 2**: Preview distribusi FIFO dengan detail batch
-   **Step 3**: Result dan konfirmasi sukses

#### **2. FIFO Validation**

-   Validasi konfigurasi livestock mendukung FIFO
-   Pengecekan jumlah batch aktif (minimal 2 batch)
-   Validasi ketersediaan FIFODepletionService

#### **3. Smart Distribution Preview**

-   Menampilkan distribusi quantity ke batch-batch
-   Urutan berdasarkan FIFO (oldest first)
-   Detail informasi setiap batch (age, available, will take, remaining)
-   Summary cards dengan metrics penting

## 🔧 **TECHNICAL IMPLEMENTATION**

### **Core Methods**

#### **validateFifoSupport()**

```php
private function validateFifoSupport()
{
    // Validasi konfigurasi livestock
    $config = $this->livestock->getConfiguration();
    $depletionMethod = $config['depletion_method'] ?? 'manual';

    if ($depletionMethod !== 'fifo') {
        return false; // Error: Tidak menggunakan FIFO
    }

    // Validasi jumlah batch aktif
    $activeBatchesCount = $this->livestock->getActiveBatchesCount();
    if ($activeBatchesCount <= 1) {
        return false; // Error: Batch tidak cukup untuk FIFO
    }

    return true;
}
```

#### **previewDepletion()**

```php
public function previewDepletion()
{
    // 1. Validasi form input
    $this->validate();

    // 2. Normalisasi depletion type
    $normalizedType = LivestockDepletionConfig::normalize($this->depletionType);

    // 3. Mapping ke FIFO service types
    $fifoDepletionType = $depletionTypeMap[$normalizedType] ?? 'mortality';

    // 4. Generate preview menggunakan FIFODepletionService
    $service = app(FIFODepletionService::class);
    $this->previewData = $service->previewFifoDepletion($depletionData);

    // 5. Set UI state untuk step 2
    $this->step = 2;
    $this->fifoDistribution = $this->previewData['distribution'] ?? [];
}
```

#### **processDepletion()**

```php
public function processDepletion()
{
    // 1. Validasi dapat diproses
    if (!$this->canProcess) return;

    // 2. Proses depletion menggunakan FIFO service
    $service = app(FIFODepletionService::class);
    $result = $service->processDepletion($depletionData);

    // 3. Handle result dan emit events
    if ($result['success']) {
        $this->step = 3;
        $this->dispatch('depletion-processed', [...]);
    }
}
```

### **Data Structure**

#### **Form Data**

```php
public $depletionType = 'mortality';     // Tipe depletion
public $depletionDate;                   // Tanggal depletion
public $totalQuantity = 1;               // Total quantity untuk deplesi
public $reason = '';                     // Alasan (opsional)
public $notes = '';                      // Catatan tambahan
```

#### **FIFO Preview Data**

```php
public $previewData = [
    'can_fulfill' => true,               // Apakah dapat dipenuhi
    'total_available' => 1000,           // Total quantity tersedia
    'distribution' => [...],             // Detail distribusi per batch
    'recording_info' => [...]            // Informasi recording terkait
];

public $fifoDistribution = [
    [
        'batch_name' => 'PR-DF01-K01-...',
        'start_date' => '2025-05-31',
        'age_days' => 25,
        'available_quantity' => 500,
        'quantity_to_take' => 100,
        'remaining_after' => 400
    ],
    // ... batch lainnya
];
```

## 🎨 **UI/UX DESIGN**

### **Step Indicator**

-   Visual progress indicator dengan 3 steps
-   Active state styling dengan warna primary
-   Smooth transitions antar steps

### **Input Form (Step 1)**

-   **Left Panel**: Form input dengan validasi real-time
-   **Right Panel**: Informasi livestock dan FIFO explanation
-   Responsive layout dengan Bootstrap grid

### **Preview (Step 2)**

-   **Summary Cards**: Total quantity, batches affected, can fulfill, total available
-   **Distribution Table**: Detail distribusi per batch dengan informasi lengkap
-   **Recording Info**: Informasi recording yang ada (jika ada)

### **Result (Step 3)**

-   Success message dengan detail hasil
-   Action buttons untuk proses lagi atau close

### **Error Handling**

-   Alert boxes untuk berbagai jenis error
-   Inline validation messages
-   User-friendly error descriptions

## 🔄 **INTEGRATION POINTS**

### **Service Dependencies**

1. **FIFODepletionService**: Core business logic untuk FIFO processing
2. **LivestockDepletionConfig**: Normalisasi dan konfigurasi depletion types
3. **Recording**: Integration dengan sistem recording

### **Event System**

```php
// Event yang di-emit setelah sukses
$this->dispatch('depletion-processed', [
    'livestock_id' => $this->livestockId,
    'type' => $this->depletionType,
    'total_depleted' => $result['total_quantity'],
    'method' => 'fifo'
]);
```

### **Model Integration**

-   **Livestock**: Validasi konfigurasi dan batch count
-   **Recording**: Link dengan recording yang ada
-   **LivestockDepletion**: Create depletion records

## 📊 **PERFORMANCE CONSIDERATIONS**

### **Optimizations**

1. **Lazy Loading**: Service hanya di-load saat diperlukan
2. **Caching**: Leverage existing FIFODepletionService caching
3. **Batch Query Optimization**: Menggunakan optimized queries dari service
4. **Real-time Validation**: Minimal server requests

### **Memory Management**

-   Reset properties saat modal close
-   Clear preview data antar steps
-   Efficient array handling untuk distribution

## 🧪 **TESTING SCENARIOS**

### **Happy Path**

1. ✅ Livestock dengan konfigurasi FIFO
2. ✅ Multiple active batches tersedia
3. ✅ Quantity dapat dipenuhi
4. ✅ Preview menampilkan distribusi yang benar
5. ✅ Processing berhasil dan emit events

### **Error Cases**

1. ❌ Livestock tidak menggunakan FIFO method
2. ❌ Batch aktif kurang dari 2
3. ❌ Total quantity melebihi available
4. ❌ FIFODepletionService tidak tersedia
5. ❌ Network/database errors

### **Edge Cases**

-   Quantity tepat habis di satu batch
-   Distribusi ke banyak batch kecil
-   Recording sudah ada vs belum ada
-   Concurrent access scenarios

## 🚀 **USAGE EXAMPLES**

### **Integration dalam Livestock Table**

```html
<!-- Trigger FIFO Depletion Modal -->
<button
    wire:click="$dispatch('show-fifo-depletion', {{ $livestock->id }})"
    class="btn btn-sm btn-primary"
>
    <i class="fas fa-sort-amount-down"></i> FIFO Depletion
</button>

<!-- Include Component -->
<livewire:master-data.livestock.fifo-depletion />
```

### **Event Handling**

```javascript
// Listen untuk depletion-processed event
document.addEventListener("depletion-processed", function (event) {
    // Refresh livestock table
    Livewire.dispatch("refresh-livestock-data");

    // Show notification
    toastr.success("FIFO Depletion berhasil diproses!");
});
```

## 📝 **FUTURE ENHANCEMENTS**

### **Planned Features**

1. **Bulk FIFO Depletion**: Multiple livestock sekaligus
2. **Scheduled FIFO**: Automatic depletion berdasarkan schedule
3. **Advanced Filters**: Filter batch berdasarkan criteria tambahan
4. **Export Preview**: Export distribusi ke Excel/PDF
5. **Audit Trail**: Detailed logging dan history

### **Performance Improvements**

1. **Background Processing**: Untuk quantity besar
2. **Real-time Updates**: WebSocket untuk live updates
3. **Predictive Analytics**: Suggest optimal quantities
4. **Mobile Optimization**: Touch-friendly interface

## 🔧 **MAINTENANCE**

### **Configuration**

-   Component mengikuti konfigurasi livestock
-   Menggunakan LivestockDepletionConfig untuk consistency
-   Service configuration melalui FIFODepletionService

### **Logging**

-   Comprehensive logging di setiap step
-   Error tracking dengan context
-   Performance monitoring

### **Updates**

-   Component akan otomatis mengikuti updates di FIFODepletionService
-   UI improvements dapat dilakukan independent
-   Backward compatibility dengan existing data

---

**Component ini menyediakan interface yang powerful dan user-friendly untuk FIFO depletion, dengan architecture yang scalable dan maintainable.**
