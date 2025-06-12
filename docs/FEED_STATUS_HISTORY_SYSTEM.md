# 🔄 **Universal Feed Status History System**

**Date:** 2025-01-02  
**Type:** New Feature - Status Tracking System  
**Components:**

-   `app/Models/FeedStatusHistory.php`
-   `app/Traits/HasFeedStatusHistory.php`
-   Database Migration: `feed_status_histories` table

---

## 📋 **Overview**

Sistem universal untuk tracking perubahan status pada semua model Feed-related menggunakan polymorphic relationship. Sistem ini memungkinkan satu model untuk menangani status history dari berbagai model Feed\* seperti `FeedPurchaseBatch`, `FeedPurchase`, `FeedStock`, dll.

---

## 🎯 **Features**

### ✅ **Polymorphic Relationship**

-   Mendukung tracking status untuk berbagai model Feed secara universal
-   Tidak terbatas pada satu jenis model Feed saja

### ✅ **Audit Trail Lengkap**

-   Menyimpan status from/to dengan timestamp
-   Tracking user yang melakukan perubahan
-   Metadata tambahan (IP address, user agent, dll.)

### ✅ **Trait-Based Implementation**

-   `HasFeedStatusHistory` trait untuk kemudahan implementasi
-   Method helper untuk operasi status umum
-   Auto-creation initial status saat record dibuat

### ✅ **Validation & Business Rules**

-   Notes wajib untuk status tertentu (dapat dikustomisasi per model)
-   Validasi foreign key constraints
-   Error handling yang komprehensif

---

## 🏗️ **Database Structure**

```sql
CREATE TABLE feed_status_histories (
    id CHAR(36) PRIMARY KEY,
    feedable_type VARCHAR(191) NOT NULL,  -- Model class name
    feedable_id CHAR(36) NOT NULL,        -- Model instance ID
    model_name VARCHAR(191) NULL,         -- Simple model name
    status_from VARCHAR(191) NULL,        -- Previous status
    status_to VARCHAR(191) NOT NULL,      -- New status
    notes TEXT NULL,                      -- Additional notes
    metadata JSON NULL,                   -- Extra data (IP, user agent, etc.)
    created_by BIGINT UNSIGNED NULL,      -- User who made the change
    updated_by BIGINT UNSIGNED NULL,      -- User who updated
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,

    INDEX feedable_type_id (feedable_type, feedable_id),
    INDEX status_transition (status_from, status_to),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 🔧 **Implementation Guide**

### **1. Model Setup**

Tambahkan trait ke model Feed yang ingin dilacak:

```php
<?php

namespace App\Models;

use App\Traits\HasFeedStatusHistory;

class FeedPurchaseBatch extends BaseModel
{
    use HasFeedStatusHistory;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    // Override trait methods for custom behavior
    protected function requiresNotesForStatus($status)
    {
        return in_array($status, [self::STATUS_CANCELLED]);
    }

    public function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CANCELLED
        ];
    }
}
```

### **2. Basic Usage**

```php
// Update status dengan history tracking
$feedBatch = FeedPurchaseBatch::find($id);
$feedBatch->updateFeedStatus('confirmed', 'Approved by manager', [
    'approval_method' => 'manual',
    'department' => 'operations'
]);

// Get status history
$timeline = $feedBatch->getStatusTimeline();
$latestHistory = $feedBatch->getLatestStatusHistory();

// Get specific transition history
$confirmations = $feedBatch->getStatusHistoryFor('pending', 'confirmed');
```

### **3. Manual Status History Creation**

```php
// Create status history manually
FeedStatusHistory::createForModel(
    $model,
    'old_status',
    'new_status',
    'Status updated via API',
    ['api_version' => '2.1', 'automated' => true]
);
```

---

## 📊 **Supported Models**

Sistem ini mendukung tracking untuk semua model Feed:

| Model               | Description                | Auto-tracking |
| ------------------- | -------------------------- | ------------- |
| `FeedPurchaseBatch` | ✅ Batch pembelian pakan   | Yes           |
| `FeedPurchase`      | Pembelian pakan individual | No (planned)  |
| `FeedStock`         | Stok pakan                 | No (planned)  |
| `FeedUsage`         | Penggunaan pakan           | No (planned)  |
| `FeedMutation`      | Mutasi pakan               | No (planned)  |
| `CurrentFeed`       | Current feed status        | No (planned)  |

---

## 🔍 **Query Examples**

### **Get All Status Changes for a Model**

```php
$histories = FeedStatusHistory::forModelInstance($feedBatch)
    ->with(['creator', 'updater'])
    ->orderBy('created_at')
    ->get();
```

### **Get Status Changes by Model Type**

```php
$batchHistories = FeedStatusHistory::forModel(FeedPurchaseBatch::class)
    ->where('status_to', 'confirmed')
    ->get();
```

### **Get Recent Status Changes**

```php
$recentChanges = FeedStatusHistory::with(['feedable', 'creator'])
    ->where('created_at', '>=', now()->subDays(7))
    ->orderBy('created_at', 'desc')
    ->get();
```

### **Status Transition Report**

```php
$transitionReport = FeedStatusHistory::statusTransition('pending', 'confirmed')
    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->get();
```

---

## 🎨 **Frontend Integration**

### **Status Timeline Display**

```php
// In Livewire component
public function getStatusTimelineProperty()
{
    return $this->model->getStatusTimeline();
}
```

### **Blade Template Example**

```blade
<div class="status-timeline">
    @foreach($statusTimeline as $history)
        <div class="timeline-item">
            <div class="timeline-marker bg-{{ $history->status_to === 'confirmed' ? 'success' : 'warning' }}"></div>
            <div class="timeline-content">
                <h6>{{ $history->status_transition }}</h6>
                <p>{{ $history->notes }}</p>
                <small>{{ $history->creator->name }} - {{ $history->created_at->diffForHumans() }}</small>
            </div>
        </div>
    @endforeach
</div>
```

---

## 🔧 **Fix Implementation in FeedPurchases**

### **Problem Fixed**

Error foreign key constraint pada `FeedStock` creation karena menggunakan `FeedPurchaseBatch` ID sebagai `feed_purchase_id`.

### **Solution Applied**

1. **Proper Model Usage**: Menggunakan actual `FeedPurchase` records dari batch
2. **Validation**: Validasi existence `FeedPurchase` sebelum create `FeedStock`
3. **Logging**: Enhanced logging untuk debugging

```php
// Before (Error)
$this->processFeedStock($purchase, $feed, $livestock, $convertedQuantity);

// After (Fixed)
foreach ($purchase->feedPurchases as $feedPurchase) {
    $this->processFeedStock($feedPurchase, $feed, $livestock, $convertedQuantity);
}
```

---

## 📈 **Performance Considerations**

### **Indexes**

-   `feedable_type + feedable_id` untuk polymorphic queries
-   `status_from + status_to` untuk transition reports
-   `created_at` untuk timeline queries

### **Optimization Tips**

1. Use eager loading untuk related models
2. Consider pagination untuk large datasets
3. Archive old status histories jika diperlukan

---

## 🧪 **Testing**

### **Unit Test Examples**

```php
// Test status update dengan history
$batch = FeedPurchaseBatch::factory()->create(['status' => 'pending']);
$batch->updateFeedStatus('confirmed', 'Test approval');

$this->assertEquals('confirmed', $batch->status);
$this->assertDatabaseHas('feed_status_histories', [
    'feedable_id' => $batch->id,
    'status_from' => 'pending',
    'status_to' => 'confirmed'
]);
```

---

## 🚀 **Next Steps**

1. **Extend to Other Models**: Implement trait di model Feed lainnya
2. **Dashboard Analytics**: Create status transition analytics
3. **API Endpoints**: Expose status history via API
4. **Notifications**: Add status change notifications
5. **Bulk Operations**: Support bulk status updates

---

_Sistem ini memberikan foundation yang solid untuk tracking status changes pada semua model Feed-related dengan flexibility dan maintainability yang tinggi._
