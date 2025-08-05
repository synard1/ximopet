# Livestock Purchase DataTable Status Filtering

## Tanggal: 2025-01-27

## Status: ✅ Completed

## Overview

Penyesuaian list status yang muncul di DataTable dengan kondisi config yang digunakan. DataTable sekarang hanya menampilkan status yang sesuai dengan business flow yang aktif, bukan semua status yang tersedia.

## Problem Analysis

### **Masalah Sebelumnya:**

DataTable menampilkan semua status dari `STATUS_LABELS` model, padahal seharusnya hanya menampilkan status yang sesuai dengan business flow yang aktif.

### **Kekurangan:**

1. ❌ Menampilkan status yang tidak relevan dengan flow
2. ❌ User bisa memilih status yang tidak valid untuk transisi
3. ❌ Tidak konsisten dengan business flow config
4. ❌ UX yang membingungkan

### **Contoh Masalah:**

-   **Simple Flow** (5 status): `draft` → `confirmed` → `in_transit` → `arrived` → `completed`
-   **DataTable** menampilkan: `draft`, `pending`, `confirmed`, `in_transit`, `arrived`, `in_coop`, `completed`, `cancelled`
-   **Status `pending` dan `in_coop`** tidak ada di simple flow tapi tetap muncul di dropdown

## Solution Implementation

### **1. Model Helper Methods**

#### **getAvailableStatusesForFlow()**

```php
public function getAvailableStatusesForFlow($flowType = null)
{
    // Get current business flow type if not provided
    if (!$flowType) {
        $flowType = \App\Config\LivestockPurchaseConfig::getWorkflowConfig()['business_flow_type'] ?? 'simple';
    }

    // Get available statuses for current flow
    $flowConfig = \App\Config\LivestockPurchaseConfig::getBusinessFlowConfig($flowType);
    $availableStatuses = $flowConfig['statuses'] ?? [];

    // Get status labels from config
    $statusLabels = \App\Config\LivestockPurchaseConfig::getWorkflowConfig()['status_labels'] ?? [];

    // Filter statuses based on current flow
    $filteredStatuses = array_intersect_key($statusLabels, array_flip($availableStatuses));

    // Always include current status even if not in flow (for backward compatibility)
    $currentStatus = $this->status;
    if (!array_key_exists($currentStatus, $filteredStatuses)) {
        $filteredStatuses[$currentStatus] = $statusLabels[$currentStatus] ?? $currentStatus;
    }

    // Always include cancelled status for safety
    if (!array_key_exists('cancelled', $filteredStatuses)) {
        $filteredStatuses['cancelled'] = $statusLabels['cancelled'] ?? 'Cancelled';
    }

    return $filteredStatuses;
}
```

#### **canTransitionToStatus()**

```php
public function canTransitionToStatus($targetStatus, $flowType = null)
{
    // Get current business flow type if not provided
    if (!$flowType) {
        $flowType = \App\Config\LivestockPurchaseConfig::getWorkflowConfig()['business_flow_type'] ?? 'simple';
    }

    return \App\Config\LivestockPurchaseConfig::canTransitionToWithFlow($this->status, $targetStatus, $flowType);
}
```

#### **getNextAvailableStatuses()**

```php
public function getNextAvailableStatuses($flowType = null)
{
    // Get current business flow type if not provided
    if (!$flowType) {
        $flowType = \App\Config\LivestockPurchaseConfig::getWorkflowConfig()['business_flow_type'] ?? 'simple';
    }

    return \App\Config\LivestockPurchaseConfig::getNextStatusesWithFlow($this->status, $flowType);
}
```

### **2. DataTable Status Column Update**

#### **Sebelumnya:**

```php
->editColumn('status', function (Transaksi $transaksi) {
    $statuses = Transaksi::STATUS_LABELS; // Semua status
    $currentStatus = $transaksi->status;

    // ... render dropdown dengan semua status
    foreach ($statuses as $value => $label) {
        // Semua status ditampilkan
    }
})
```

#### **Sekarang:**

```php
->editColumn('status', function (Transaksi $transaksi) {
    // Get available statuses for current flow using model helper
    $filteredStatuses = $transaksi->getAvailableStatusesForFlow();
    $currentStatus = $transaksi->status;
    $currentFlowType = \App\Config\LivestockPurchaseConfig::getWorkflowConfig()['business_flow_type'] ?? 'simple';

    // Log for debugging
    Log::info("[LivestockPurchaseDataTable] Status dropdown for transaction {$transaksi->id}", [
        'current_status' => $currentStatus,
        'flow_type' => $currentFlowType,
        'available_statuses' => array_keys($filteredStatuses),
        'transaction_id' => $transaksi->id
    ]);

    // ... render dropdown dengan status yang difilter
    foreach ($filteredStatuses as $value => $label) {
        // Check if transition is allowed using model helper
        $canTransition = $transaksi->canTransitionToStatus($value, $currentFlowType);

        // Disable options that are not allowed transitions
        if (!$canTransition && $value !== $currentStatus && $value !== 'cancelled') {
            $optionDisabled = 'disabled';
            $optionStyle = 'style="background-color: #f5f5f5; color: #999;"';
        }
    }
})
```

## Business Flow Status Mapping

### **Simple Flow (5 statuses)**

```
Draft → Confirmed → In Transit → Arrived → Completed
```

**Status yang ditampilkan di dropdown:**

-   ✅ `draft` - Status awal
-   ✅ `confirmed` - Sudah disetujui
-   ✅ `in_transit` - Dalam perjalanan
-   ✅ `arrived` - Sudah sampai
-   ✅ `completed` - Selesai
-   ✅ `cancelled` - Dibatalkan (safety)

**Status yang TIDAK ditampilkan:**

-   ❌ `pending` - Tidak ada di simple flow
-   ❌ `in_coop` - Tidak ada di simple flow

### **Standard Flow (6 statuses)**

```
Draft → Pending → Confirmed → In Transit → Arrived → Completed
```

**Status yang ditampilkan di dropdown:**

-   ✅ `draft` - Status awal
-   ✅ `pending` - Menunggu approval
-   ✅ `confirmed` - Sudah disetujui
-   ✅ `in_transit` - Dalam perjalanan
-   ✅ `arrived` - Sudah sampai
-   ✅ `completed` - Selesai
-   ✅ `cancelled` - Dibatalkan (safety)

**Status yang TIDAK ditampilkan:**

-   ❌ `in_coop` - Tidak ada di standard flow

### **Complex Flow (7 statuses)**

```
Draft → Pending → Confirmed → In Transit → Arrived → In Coop → Completed
```

**Status yang ditampilkan di dropdown:**

-   ✅ `draft` - Status awal
-   ✅ `pending` - Menunggu approval
-   ✅ `confirmed` - Sudah disetujui
-   ✅ `in_transit` - Dalam perjalanan
-   ✅ `arrived` - Sudah sampai
-   ✅ `in_coop` - Sudah di kandang
-   ✅ `completed` - Selesai
-   ✅ `cancelled` - Dibatalkan (safety)

## Transition Validation

### **Smart Transition Checking**

```php
// Check if transition is allowed using model helper
$canTransition = $transaksi->canTransitionToStatus($value, $currentFlowType);

// Disable options that are not allowed transitions
if (!$canTransition && $value !== $currentStatus && $value !== 'cancelled') {
    $optionDisabled = 'disabled';
    $optionStyle = 'style="background-color: #f5f5f5; color: #999;"';
}
```

### **Contoh Validasi Transisi:**

#### **Simple Flow Transitions:**

-   `draft` → `confirmed` ✅
-   `confirmed` → `in_transit` ✅
-   `in_transit` → `arrived` ✅
-   `arrived` → `completed` ✅
-   `draft` → `in_transit` ❌ (disabled)
-   `confirmed` → `completed` ❌ (disabled)

#### **Standard Flow Transitions:**

-   `draft` → `pending` ✅
-   `pending` → `confirmed` ✅
-   `confirmed` → `in_transit` ✅
-   `in_transit` → `arrived` ✅
-   `arrived` → `completed` ✅
-   `draft` → `confirmed` ❌ (disabled)

#### **Complex Flow Transitions:**

-   `draft` → `pending` ✅
-   `pending` → `confirmed` ✅
-   `confirmed` → `in_transit` ✅
-   `in_transit` → `arrived` ✅
-   `arrived` → `in_coop` ✅
-   `in_coop` → `completed` ✅
-   `arrived` → `completed` ❌ (disabled)

## Backward Compatibility

### **Current Status Inclusion**

```php
// Always include current status even if not in flow (for backward compatibility)
$currentStatus = $this->status;
if (!array_key_exists($currentStatus, $filteredStatuses)) {
    $filteredStatuses[$currentStatus] = $statusLabels[$currentStatus] ?? $currentStatus;
}
```

**Manfaat:**

-   ✅ Data lama tetap bisa ditampilkan
-   ✅ Status yang tidak sesuai flow tetap bisa dilihat
-   ✅ Tidak ada data yang hilang

### **Cancelled Status Safety**

```php
// Always include cancelled status for safety
if (!array_key_exists('cancelled', $filteredStatuses)) {
    $filteredStatuses['cancelled'] = $statusLabels['cancelled'] ?? 'Cancelled';
}
```

**Manfaat:**

-   ✅ Selalu bisa membatalkan transaksi
-   ✅ Safety net untuk emergency cancellation
-   ✅ Konsisten dengan business rules

## Debugging & Logging

### **Enhanced Logging**

```php
Log::info("[LivestockPurchaseDataTable] Status dropdown for transaction {$transaksi->id}", [
    'current_status' => $currentStatus,
    'flow_type' => $currentFlowType,
    'available_statuses' => array_keys($filteredStatuses),
    'transaction_id' => $transaksi->id
]);
```

### **Log Output Example:**

```json
{
    "current_status": "draft",
    "flow_type": "simple",
    "available_statuses": [
        "draft",
        "confirmed",
        "in_transit",
        "arrived",
        "completed",
        "cancelled"
    ],
    "transaction_id": "123e4567-e89b-12d3-a456-426614174000"
}
```

## Use Case Scenarios

### **Scenario 1: Simple Flow Purchase**

```
User membuat pembelian baru
↓
Status: draft
↓
Dropdown menampilkan: draft, confirmed, in_transit, arrived, completed, cancelled
↓
User hanya bisa pilih: confirmed (valid transition)
↓
Status berubah ke: confirmed
↓
Dropdown menampilkan: draft, confirmed, in_transit, arrived, completed, cancelled
↓
User hanya bisa pilih: in_transit (valid transition)
```

### **Scenario 2: Standard Flow Purchase**

```
User membuat pembelian baru
↓
Status: draft
↓
Dropdown menampilkan: draft, pending, confirmed, in_transit, arrived, completed, cancelled
↓
User hanya bisa pilih: pending (valid transition)
↓
Status berubah ke: pending
↓
Dropdown menampilkan: draft, pending, confirmed, in_transit, arrived, completed, cancelled
↓
User hanya bisa pilih: confirmed, cancelled (valid transitions)
```

### **Scenario 3: Complex Flow Purchase**

```
User membuat pembelian baru
↓
Status: draft
↓
Dropdown menampilkan: draft, pending, confirmed, in_transit, arrived, in_coop, completed, cancelled
↓
User hanya bisa pilih: pending (valid transition)
↓
Status berubah ke: pending
↓
Dropdown menampilkan: draft, pending, confirmed, in_transit, arrived, in_coop, completed, cancelled
↓
User hanya bisa pilih: confirmed, cancelled (valid transitions)
```

## Benefits

### **1. Improved User Experience**

-   ✅ **Clear Options:** User hanya melihat status yang relevan
-   ✅ **Valid Transitions:** Tidak ada pilihan yang tidak valid
-   ✅ **Consistent Flow:** Sesuai dengan business flow yang aktif
-   ✅ **Reduced Confusion:** Tidak ada status yang membingungkan

### **2. Business Process Alignment**

-   ✅ **Flow Compliance:** Status dropdown mengikuti business flow
-   ✅ **Process Validation:** Transisi status divalidasi sesuai flow
-   ✅ **Configuration Driven:** Perubahan flow otomatis mengubah dropdown
-   ✅ **Audit Trail:** Logging untuk tracking perubahan

### **3. Technical Benefits**

-   ✅ **Maintainability:** Logic terpusat di model helper
-   ✅ **Reusability:** Method helper bisa digunakan di tempat lain
-   ✅ **Flexibility:** Mudah untuk menambah flow baru
-   ✅ **Backward Compatibility:** Data lama tetap didukung

### **4. Data Integrity**

-   ✅ **Valid Transitions:** Hanya transisi yang valid yang diizinkan
-   ✅ **Flow Consistency:** Status selalu konsisten dengan flow
-   ✅ **Error Prevention:** Mencegah user memilih status yang salah
-   ✅ **Business Rules:** Mengikuti aturan bisnis yang ditetapkan

## Testing Scenarios

### **Test Case 1: Simple Flow Status Filtering**

1. Set business flow type ke 'simple'
2. Buka DataTable livestock purchase
3. Edit status dropdown pada transaksi draft
4. Verify hanya menampilkan: draft, confirmed, in_transit, arrived, completed, cancelled
5. Verify status 'pending' dan 'in_coop' tidak muncul

### **Test Case 2: Standard Flow Status Filtering**

1. Set business flow type ke 'standard'
2. Buka DataTable livestock purchase
3. Edit status dropdown pada transaksi draft
4. Verify menampilkan: draft, pending, confirmed, in_transit, arrived, completed, cancelled
5. Verify status 'in_coop' tidak muncul

### **Test Case 3: Complex Flow Status Filtering**

1. Set business flow type ke 'complex'
2. Buka DataTable livestock purchase
3. Edit status dropdown pada transaksi draft
4. Verify menampilkan semua status: draft, pending, confirmed, in_transit, arrived, in_coop, completed, cancelled

### **Test Case 4: Transition Validation**

1. Pilih transaksi dengan status 'confirmed' di simple flow
2. Edit status dropdown
3. Verify hanya 'in_transit' dan 'cancelled' yang enabled
4. Verify status lain disabled dengan style abu-abu

### **Test Case 5: Backward Compatibility**

1. Buat transaksi dengan status 'pending' di simple flow
2. Verify status 'pending' tetap muncul di dropdown (backward compatibility)
3. Verify transaksi bisa diubah ke status yang valid

## Future Enhancements

### **1. Dynamic Flow Switching**

-   **Real-time Flow Change:** Dropdown berubah saat flow berubah
-   **Flow-specific Validation:** Validasi sesuai flow yang aktif
-   **User Notification:** Notifikasi saat flow berubah

### **2. Advanced Transition Rules**

-   **Conditional Transitions:** Transisi berdasarkan kondisi tertentu
-   **Role-based Transitions:** Transisi berdasarkan role user
-   **Time-based Transitions:** Transisi berdasarkan waktu

### **3. Enhanced UI/UX**

-   **Visual Flow Indicator:** Indikator visual untuk flow yang aktif
-   **Status Progress Bar:** Progress bar untuk status flow
-   **Tooltip Information:** Tooltip untuk menjelaskan setiap status

### **4. Integration Features**

-   **API Endpoint:** API untuk mendapatkan status yang valid
-   **Webhook Integration:** Webhook untuk status change events
-   **Third-party Integration:** Integrasi dengan sistem eksternal

## Conclusion

Penyesuaian list status di DataTable telah berhasil diimplementasikan dengan fitur-fitur berikut:

### ✅ **Completed Improvements:**

1. **Status Filtering** - Dropdown hanya menampilkan status yang sesuai flow
2. **Transition Validation** - Validasi transisi berdasarkan business flow
3. **Model Helper Methods** - Method helper untuk status management
4. **Backward Compatibility** - Dukungan untuk data lama
5. **Enhanced Logging** - Logging untuk debugging dan audit

### 🎯 **Business Benefits:**

1. **Better UX** - User experience yang lebih jelas dan konsisten
2. **Process Compliance** - Mengikuti business flow yang ditetapkan
3. **Error Prevention** - Mencegah pemilihan status yang tidak valid
4. **Configuration Driven** - Mudah untuk mengubah flow

### 📊 **Technical Benefits:**

1. **Maintainability** - Code yang lebih terstruktur dan mudah dipelihara
2. **Reusability** - Method helper yang bisa digunakan di tempat lain
3. **Flexibility** - Mudah untuk menambah flow baru
4. **Reliability** - Validasi yang robust dan konsisten

DataTable sekarang menampilkan status yang sesuai dengan business flow yang aktif, memberikan user experience yang lebih baik dan konsisten dengan proses bisnis yang telah ditetapkan.
