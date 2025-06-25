# Manual Depletion Recording Integration Refactoring

## Tanggal: 2025-06-23

## Status: IMPLEMENTED

### Problem Statement

Manual Depletion component tidak menggunakan `recording_id` yang sudah ada jika pada tanggal yang dipilih sudah ada data Recording. Hal ini menyebabkan:

-   Data depletion tidak terhubung dengan recording harian
-   Duplikasi informasi tanpa relasi yang jelas
-   Kesulitan dalam tracking dan reporting yang terintegrasi

### Solution Implementation

#### 1. **Enhanced Model Imports**

Added necessary imports to `ManualDepletion.php`:

```php
use App\Models\Recording;
use Carbon\Carbon;
```

#### 2. **New Method: findExistingRecording()**

```php
/**
 * Find existing recording for the selected date and livestock
 */
private function findExistingRecording($date = null)
{
    $searchDate = $date ?: $this->depletionDate;

    try {
        $recording = Recording::where('livestock_id', $this->livestockId)
            ->whereDate('tanggal', $searchDate)
            ->first();

        if ($recording) {
            Log::info('Found existing recording for manual depletion', [
                'livestock_id' => $this->livestockId,
                'recording_id' => $recording->id,
                'date' => $searchDate
            ]);
        } else {
            Log::info('No existing recording found for manual depletion', [
                'livestock_id' => $this->livestockId,
                'date' => $searchDate
            ]);
        }

        return $recording;
    } catch (Exception $e) {
        Log::error('Error finding existing recording', [
            'livestock_id' => $this->livestockId,
            'date' => $searchDate,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
```

#### 3. **Enhanced previewDepletion() Method**

**Before:**

```php
$depletionData = [
    'livestock_id' => $this->livestockId,
    'type' => $this->depletionType,
    'depletion_method' => 'manual',
    'manual_batches' => [...]
];
```

**After:**

```php
// Find existing recording for the selected date
$existingRecording = $this->findExistingRecording();

$depletionData = [
    'livestock_id' => $this->livestockId,
    'type' => $this->depletionType,
    'depletion_method' => 'manual',
    'recording_id' => $existingRecording ? $existingRecording->id : null,
    'manual_batches' => [...]
];

// Add recording information to preview data
if ($existingRecording) {
    $this->previewData['recording_info'] = [
        'recording_id' => $existingRecording->id,
        'recording_date' => $existingRecording->tanggal->format('Y-m-d'),
        'current_stock' => $existingRecording->final_stock ?? $existingRecording->stock_akhir,
        'mortality' => $existingRecording->mortality ?? 0,
        'culling' => $existingRecording->culling ?? 0
    ];
}
```

#### 4. **Enhanced processDepletion() Method**

**Before:**

```php
$depletionData = [
    'livestock_id' => $this->livestockId,
    'type' => $this->depletionType,
    'date' => $this->depletionDate,
    'depletion_method' => 'manual',
    'manual_batches' => [...],
    'reason' => $this->reason
];
```

**After:**

```php
// Find existing recording for the selected date
$existingRecording = $this->findExistingRecording($this->depletionDate);

$depletionData = [
    'livestock_id' => $this->livestockId,
    'type' => $this->depletionType,
    'date' => $this->depletionDate,
    'depletion_method' => 'manual',
    'recording_id' => $existingRecording ? $existingRecording->id : null,
    'manual_batches' => [...],
    'reason' => $this->reason
];
```

#### 5. **Enhanced Logging**

Added comprehensive logging for recording integration:

```php
Log::info('Manual depletion processed successfully', [
    'livestock_id' => $this->livestockId,
    'total_depleted' => $result['total_depleted'],
    'processed_batches' => count($result['processed_batches']),
    'recording_id' => $existingRecording ? $existingRecording->id : null,
    'recording_found' => $existingRecording !== null
]);
```

### Data Flow Integration

#### **Scenario 1: Recording Exists**

```
User selects date: 2025-06-23
↓
findExistingRecording() searches Recording table
↓
Recording found: ID = "rec-123", tanggal = "2025-06-23"
↓
Manual depletion uses recording_id = "rec-123"
↓
LivestockDepletion.recording_id = "rec-123"
```

#### **Scenario 2: No Recording Exists**

```
User selects date: 2025-06-24
↓
findExistingRecording() searches Recording table
↓
No recording found for date
↓
Manual depletion uses recording_id = null
↓
LivestockDepletion.recording_id = null
```

### Preview Data Enhancement

When existing recording is found, preview includes additional information:

```php
$previewData['recording_info'] = [
    'recording_id' => 'rec-123',
    'recording_date' => '2025-06-23',
    'current_stock' => 1000,
    'mortality' => 5,
    'culling' => 2
];
```

This allows users to see:

-   Current stock levels from recording
-   Existing mortality/culling data
-   Context for their manual depletion input

### Benefits of Integration

#### 1. **Data Consistency**

-   ✅ Manual depletion linked to daily recording
-   ✅ Single source of truth for date-based livestock data
-   ✅ Proper relational integrity between tables

#### 2. **Improved Tracking**

-   ✅ Easy to trace depletion back to daily recording
-   ✅ Comprehensive view of all activities for a specific date
-   ✅ Better audit trail and reporting capabilities

#### 3. **Enhanced User Experience**

-   ✅ Users see existing recording context in preview
-   ✅ Clear indication when depletion will be linked to recording
-   ✅ Better understanding of data relationships

#### 4. **Reporting Integration**

-   ✅ Reports can easily join depletion and recording data
-   ✅ Consolidated daily summaries possible
-   ✅ Better analytics and trend analysis

### Database Schema Integration

#### **LivestockDepletion Table**

```php
// Existing columns used
'livestock_id' => 'uuid',           // Links to Livestock
'recording_id' => 'uuid',           // Links to Recording (NOW POPULATED)
'tanggal' => 'date',                // Depletion date
'jumlah' => 'integer',              // Total quantity
'jenis' => 'string',                // Type (mortality, sales, etc.)
'data' => 'json',                   // Batch details
'metadata' => 'json'                // Additional info
```

#### **Recording Table**

```php
// Related columns
'tanggal' => 'date',                // Recording date
'livestock_id' => 'uuid',           // Links to Livestock
'mortality' => 'integer',           // Daily mortality count
'culling' => 'integer',             // Daily culling count
'final_stock' => 'integer'          // End of day stock
```

### Error Handling

#### **Recording Lookup Failures**

```php
try {
    $recording = Recording::where(...)->first();
    return $recording;
} catch (Exception $e) {
    Log::error('Error finding existing recording', [
        'livestock_id' => $this->livestockId,
        'date' => $searchDate,
        'error' => $e->getMessage()
    ]);
    return null; // Graceful fallback
}
```

#### **Null Recording Handling**

-   System continues to work when no recording exists
-   `recording_id` set to `null` in depletion data
-   No disruption to existing functionality

### Testing Scenarios

#### 1. **With Existing Recording**

```
Date: 2025-06-23
Recording exists: ID = "rec-123"
Expected: recording_id = "rec-123" in depletion data
```

#### 2. **Without Existing Recording**

```
Date: 2025-06-24
No recording exists
Expected: recording_id = null in depletion data
```

#### 3. **Database Error**

```
Database connection fails
Expected: recording_id = null, error logged, process continues
```

### Files Modified

1. **app/Livewire/MasterData/Livestock/ManualDepletion.php**
    - Added Recording and Carbon imports
    - Added `findExistingRecording()` method
    - Enhanced `previewDepletion()` with recording lookup
    - Enhanced `processDepletion()` with recording integration
    - Added recording info to preview data
    - Enhanced logging with recording information

### Production Ready Features

-   ✅ **Backward Compatibility**: Works with or without existing recordings
-   ✅ **Error Handling**: Graceful fallback when recording lookup fails
-   ✅ **Logging**: Comprehensive logging for debugging and monitoring
-   ✅ **Data Integrity**: Proper relational links between tables
-   ✅ **User Experience**: Enhanced preview with recording context
-   ✅ **Performance**: Efficient single query for recording lookup

### Future Enhancements

-   [ ] Add recording creation if none exists for the date
-   [ ] Validate stock levels against recording data
-   [ ] Add recording summary in manual depletion UI
-   [ ] Implement recording-based batch availability checks

This refactoring establishes proper integration between manual depletion and daily recording systems, ensuring data consistency and improved traceability while maintaining backward compatibility.
