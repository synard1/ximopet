# Manual Batch Depletion Edit Mode Enhancement

**Date:** {{ now()->format('Y-m-d H:i:s') }}  
**Component:** `app/Livewire/MasterData/Livestock/ManualBatchDepletion.php`  
**View:** `resources/views/livewire/master-data/livestock/manual-batch-depletion.blade.php`  
**Status:** ✅ Completed

## Overview

Enhanced the ManualBatchDepletion component to match the edit mode functionality found in ManualFeedUsage component, providing a consistent user experience across both manual input components.

## Key Enhancements

### 1. Enhanced Edit Mode Detection

-   **Method:** `checkForExistingDepletion()`
-   **Improvement:** Added automatic edit mode activation when existing manual depletion data is found for the selected date
-   **Features:**
    -   Automatic data loading when date is changed
    -   Real-time edit mode switching
    -   Comprehensive error handling

### 2. Edit Mode Data Loading

-   **Method:** `loadExistingDepletionData()`
-   **Purpose:** Load existing depletion data into component state for editing
-   **Features:**
    -   Populates form fields with existing data
    -   Loads selected batches with quantities and notes
    -   Sets edit mode flags properly

### 3. Edit Mode Management

-   **Methods:** `cancelEditMode()`, `resetEditMode()`
-   **Features:**
    -   Cancel edit mode and return to create mode
    -   Reset form to initial state
    -   Clear edit mode flags and data

### 4. Enhanced User Interface

-   **Edit Mode Alert:** Added prominent warning alert when in edit mode
-   **Cancel Button:** Added cancel edit mode functionality
-   **Visual Indicators:** Updated button text and icons for edit mode

### 5. Improved Event Handling

-   **Events Added:**
    -   `depletion-edit-mode-enabled`
    -   `depletion-edit-mode-cancelled`
    -   `depletion-processed`
-   **Notifications:** SweetAlert/Toastr notifications for mode changes

### 6. Enhanced Modal Management

-   **Method:** `closeModalSilent()`
-   **Purpose:** Handle Bootstrap modal close events without infinite loops
-   **Features:**
    -   Proper event dispatching
    -   Bootstrap compatibility
    -   Debug logging

## Technical Implementation

### Component Properties Added

```php
public $isEditing = false;
public $existingDepletionId = null;
```

### Key Methods Enhanced

#### `updatedDepletionDate($value)`

```php
public function updatedDepletionDate($value)
{
    $this->depletionDate = $value;
    $this->loadAvailableBatches();
    $this->selectedBatches = [];
    $this->checkForExistingDepletion(); // Auto-check for existing data
}
```

#### `checkForExistingDepletion()`

-   Searches for existing manual depletion data on selected date
-   Automatically switches to edit mode if data found
-   Dispatches notification events
-   Comprehensive error handling

#### `loadExistingDepletionData($depletion)`

-   Loads existing depletion data into component state
-   Populates form fields and selected batches
-   Sets edit mode flags
-   Logs data loading for debugging

#### `cancelEditMode()`

-   Resets edit mode flags
-   Clears form data
-   Returns to create mode
-   Dispatches cancellation event

### UI Enhancements

#### Edit Mode Alert

```html
@if($isEditing)
<div class="alert alert-warning d-flex align-items-center p-5 mb-6">
    <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
        <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
    </i>
    <div class="d-flex flex-column flex-grow-1">
        <h4 class="mb-1 text-warning">Edit Mode Active</h4>
        <span
            >You are editing existing manual depletion data for {{
            $depletionDate }}. All changes will update the existing
            records.</span
        >
    </div>
    <button
        type="button"
        class="btn btn-sm btn-light-warning"
        wire:click="cancelEditMode"
    >
        <i class="ki-duotone ki-cross fs-6 me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Cancel Edit
    </button>
</div>
@endif
```

#### Dynamic Button Text

```html
{{ $isEditing ? 'Preview Update' : 'Preview Depletion' }} {{ $isEditing ?
'Update Data' : 'Process Depletion' }}
```

### JavaScript Event Handling

#### Edit Mode Events

```javascript
// Handle edit mode enabled event
Livewire.on("depletion-edit-mode-enabled", (data) => {
    Swal.fire({
        title: "Edit Mode Enabled",
        text: `Existing depletion data loaded for ${data.date}. You can now edit the existing records.`,
        icon: "info",
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });
});

// Handle edit mode cancelled event
Livewire.on("depletion-edit-mode-cancelled", () => {
    Swal.fire({
        title: "Edit Mode Cancelled",
        text: "Switched back to create new depletion mode.",
        icon: "success",
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
});
```

## User Experience Flow

### Create Mode (Default)

1. User opens modal
2. Selects date (current date by default)
3. No existing data found
4. User proceeds with normal creation flow

### Edit Mode (Automatic)

1. User opens modal
2. Selects date with existing manual depletion data
3. System automatically detects existing data
4. Switches to edit mode with notification
5. Loads existing data into form
6. User can edit and update existing data

### Edit Mode Cancellation

1. User clicks "Cancel Edit" button
2. System resets to create mode
3. Form cleared and ready for new entry
4. Success notification shown

## Benefits

### 1. Consistency

-   Matches ManualFeedUsage functionality exactly
-   Uniform user experience across components
-   Consistent event handling and notifications

### 2. User-Friendly

-   Automatic edit mode detection
-   Clear visual indicators
-   Easy mode switching
-   Comprehensive notifications

### 3. Data Integrity

-   Prevents accidental data loss
-   Clear edit vs create distinction
-   Proper validation in both modes

### 4. Developer Experience

-   Comprehensive logging
-   Clear method separation
-   Reusable patterns
-   Extensive error handling

## Testing Checklist

-   [ ] Edit mode activates when existing data found
-   [ ] Form populates correctly with existing data
-   [ ] Cancel edit mode works properly
-   [ ] Notifications display correctly
-   [ ] Modal close events handled properly
-   [ ] Data updates correctly in edit mode
-   [ ] Create mode works normally when no existing data
-   [ ] Date changes trigger proper mode detection
-   [ ] Error handling works in all scenarios
-   [ ] JavaScript events fire correctly

## Future Enhancements

### Potential Improvements

1. **Batch History:** Show edit history for batches
2. **Conflict Detection:** Handle concurrent edits
3. **Partial Updates:** Allow editing specific batches only
4. **Audit Trail:** Track all edit operations
5. **Validation Rules:** Different rules for edit vs create mode

### Configuration Options

1. **Auto-Edit Mode:** Toggle automatic edit mode detection
2. **Edit Permissions:** Role-based edit restrictions
3. **Edit Time Limits:** Restrict editing after certain time
4. **Backup Creation:** Auto-backup before edits

## Related Files

### Core Files

-   `app/Livewire/MasterData/Livestock/ManualBatchDepletion.php`
-   `resources/views/livewire/master-data/livestock/manual-batch-depletion.blade.php`

### Partial Views

-   `resources/views/livewire/master-data/livestock/partials/depletion-selection-step.blade.php`
-   `resources/views/livewire/master-data/livestock/partials/depletion-preview-step.blade.php`
-   `resources/views/livewire/master-data/livestock/partials/depletion-result-step.blade.php`

### Services

-   `app/Services/Livestock/BatchDepletionService.php`

### Models

-   `app/Models/LivestockDepletion.php`
-   `app/Models/LivestockBatch.php`

## Conclusion

The ManualBatchDepletion component now provides the same level of edit mode functionality as ManualFeedUsage, ensuring a consistent and user-friendly experience across all manual input components. The implementation includes automatic edit mode detection, comprehensive notifications, and proper data handling for both create and edit scenarios.

The enhancement maintains backward compatibility while adding powerful new functionality that improves data management and user workflow efficiency.

## Troubleshooting & Data Structure Detection

### Issue: Existing Data Not Detected for Edit Mode

**Problem:** Data exists in database but edit mode is not activated.

**Solution:** Enhanced detection logic with multiple fallback methods:

#### Detection Methods (in order of priority):

1. **Method 1:** Check for `data.method === 'manual'`
2. **Method 2:** Check for `data.depletion_method === 'manual'`
3. **Method 3:** Check for `data.manual_batches` array
4. **Method 4:** Check for `metadata.method === 'manual'`
5. **Method 5:** Check for `data.batches` array
6. **Method 6:** Fallback - Simple depletion with basic data (non-FIFO)

#### Data Structure Handling:

The system now handles multiple data structures:

```php
// Structure 1: Manual batch depletion
$data = [
    'method' => 'manual',
    'depletion_type' => 'mortality',
    'reason' => 'Disease outbreak',
    'batches' => [
        [
            'batch_id' => 'batch-001',
            'batch_name' => 'Batch A',
            'quantity' => 10,
            'note' => 'Sick birds'
        ]
    ]
];

// Structure 2: FIFO-style with manual_batches
$data = [
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-001',
            'quantity' => 15
        ]
    ]
];

// Structure 3: Simple depletion (fallback)
$data = [
    'note' => 'Manual entry'
];
// Uses main depletion fields: jenis, jumlah
```

#### Debugging Steps:

1. **Check Logs:** Look for these log entries:

    ```
    🔍 Searching for existing depletions
    🔍 Checking depletion for manual type
    🔄 Existing manual depletion loaded automatically
    ```

2. **Verify Data Structure:** Check the logged data structure:

    ```
    'data_keys' => [...],
    'metadata_keys' => [...],
    'is_manual' => true/false
    ```

3. **Test Date Changes:** Change dates to trigger detection:
    ```
    🔄 Depletion date updated
    ```

#### Common Issues:

1. **Date Format Mismatch:** Ensure date format is 'Y-m-d'
2. **No Batch Data:** System creates fallback single batch entry
3. **Complex FIFO Data:** System excludes FIFO depletions from manual editing
4. **Missing Batch IDs:** System attempts to match by batch name

### Testing Scenarios:

1. **Create New Depletion:**

    - Open modal → Select date without existing data → Should show create mode

2. **Edit Existing Manual Depletion:**

    - Open modal → Select date with manual depletion data → Should auto-switch to edit mode

3. **Edit Simple Depletion (Fallback):**

    - Open modal → Select date with basic depletion data → Should allow manual editing

4. **Cancel Edit Mode:**

    - In edit mode → Click "Cancel Edit" → Should return to create mode

5. **Date Change Detection:**
    - In modal → Change date → Should detect existing data and switch modes

### Log Analysis:

Enable debug logging and look for these patterns:

```log
[INFO] 🔍 Searching for existing depletions: livestock_id=..., found_depletions=2
[INFO] 🔍 Checking depletion for manual type: is_manual=true, batches_source=data.batches
[INFO] 🔄 Existing manual depletion loaded automatically: batches_count=2
[INFO] 🔄 Loaded existing depletion data for editing: selected_batches=[...]
```

If edit mode is not working:

1. Check if `found_depletions > 0`
2. Verify `is_manual=true` for at least one depletion
3. Confirm `batches_count > 0` after loading
4. Check for any error messages in logs

### Multiple Depletions Handling Fix

**Date:** 2025-01-22  
**Issue:** Quantity data not loaded correctly when multiple depletion records exist for the same date.

#### Problem Description:

-   Database contained 2 depletion records (quantities: 10 and 15)
-   Edit mode only loaded 1 record with incorrect quantity (6)
-   System was breaking after finding first manual depletion
-   Quantities were not being combined properly

#### Solution Implemented:

1. **Collect All Manual Depletions:**

    ```php
    $allManualDepletions = [];
    foreach ($depletions as $depletion) {
        if ($isManualDepletion) {
            $allManualDepletions[] = $depletion;
        }
    }
    ```

2. **Process Multiple Depletions:**

    ```php
    private function loadExistingDepletionData($primaryDepletion, $allManualDepletions)
    {
        $batchQuantities = []; // Track quantities per batch

        foreach ($allManualDepletions as $depletion) {
            // Process each depletion and accumulate quantities
        }
    }
    ```

3. **Quantity Accumulation Logic:**

    ```php
    // Accumulate quantities for the same batch
    if (isset($batchQuantities[$batchKey])) {
        $batchQuantities[$batchKey]['quantity'] += $quantity;
        $batchQuantities[$batchKey]['note'] .= '; ' . $note;
    } else {
        $batchQuantities[$batchKey] = [
            'batch_id' => $batchId,
            'quantity' => $quantity,
            // ... other fields
        ];
    }
    ```

4. **Enhanced UI Feedback:**
    - Shows number of records being combined
    - Stores all depletion IDs for proper update handling
    - Improved logging for debugging

#### Key Improvements:

1. **Multi-Record Processing:** System now processes all manual depletions on the same date
2. **Quantity Combination:** Properly combines quantities from multiple records into single batches
3. **Batch Auto-Assignment:** Automatically assigns suitable batches for records without batch data
4. **Enhanced Logging:** Comprehensive logging for debugging quantity loading issues
5. **UI Indicators:** Shows when multiple records are being combined

#### Expected Behavior After Fix:

-   **Multiple Records:** All manual depletions on date are processed
-   **Correct Quantities:** Quantities are properly combined (10 + 15 = 25)
-   **Batch Assignment:** Records without batch info get assigned to suitable batches
-   **Edit Mode:** Proper edit mode with all data loaded correctly
-   **Update Handling:** All original depletion IDs stored for proper updates

#### Testing Verification:

1. **Check Logs:** Look for:

    ```
    🔍 Processing multiple depletions for edit mode: total_depletions=2
    🔍 Processing depletion: jumlah=10
    🔍 Processing depletion: jumlah=15
    🔄 Loaded existing depletion data: batches_count=1, total_depletions_processed=2
    ```

2. **UI Verification:**

    - Edit mode alert shows "Menggabungkan 2 record deplesi"
    - Selected batch shows combined quantity (25)
    - Form pre-populated with correct data

3. **Data Integrity:**
    - All original depletion IDs stored in `existingDepletionIds`
    - Primary depletion ID in `existingDepletionId`
    - Quantities properly accumulated per batch

### Edit Mode Save Functionality Fix

**Date:** 2025-01-22  
**Issue:** Edit mode was creating new records instead of updating existing ones.

#### Problem Description:

-   Edit mode detected correctly and data loaded properly
-   But when saving, system created new depletion records instead of updating existing ones
-   Log showed `LivestockDepletion created` instead of update operation
-   Result: Duplicate records and incorrect quantities

#### Root Cause:

`BatchDepletionService.processManualBatchDepletion()` method had no logic to handle edit mode - it always created new records regardless of `is_editing` flag.

#### Solution Implemented:

1. **Enhanced Service Method:**

    ```php
    // Check for edit mode
    $isEditMode = isset($depletionData['is_editing']) && $depletionData['is_editing'] === true;
    $existingDepletionIds = $depletionData['existing_depletion_ids'] ?? [];
    ```

2. **Reverse Existing Depletions:**

    ```php
    if ($isEditMode && !empty($existingDepletionIds)) {
        foreach ($existingDepletionIds as $depletionId) {
            $existingDepletion = LivestockDepletion::find($depletionId);
            if ($existingDepletion) {
                $this->reverseDepletionQuantities($existingDepletion);
                $existingDepletion->delete();
            }
        }
    }
    ```

3. **Added Reverse Quantities Method:**

    ```php
    private function reverseDepletionQuantities(LivestockDepletion $depletion): void
    {
        // Reverse batch quantities
        $this->reverseBatchQuantities($batch, $depletion->jenis, $depletion->jumlah);

        // Reverse livestock totals
        $livestock->quantity_depletion = max(0, $livestock->quantity_depletion - $quantity);
    }
    ```

4. **Enhanced Data Tracking:**
    ```php
    'data' => [
        'is_edit_replacement' => $isEditMode,
        'manual_batch_note' => $depletionData['manual_batch_note'] ?? null,
        'reason' => $depletionData['reason'] ?? null
    ],
    'metadata' => [
        'edit_mode' => $isEditMode,
        'replaced_depletions' => count($existingDepletionIds)
    ]
    ```

#### Key Improvements:

1. **Proper Edit Handling:** System now detects edit mode and processes accordingly
2. **Quantity Reversal:** Existing records are properly reversed before creating new ones
3. **Data Integrity:** All quantities are recalculated correctly
4. **Audit Trail:** Clear tracking of edit operations in metadata
5. **Error Handling:** Robust error handling for reversal operations

#### Expected Behavior After Fix:

-   **Edit Mode Detection:** ✅ Service recognizes `is_editing=true`
-   **Existing Data Reversal:** ✅ Old records deleted and quantities reversed
-   **New Records Creation:** ✅ New records created with updated data
-   **Quantity Accuracy:** ✅ Final quantities reflect only new values
-   **Audit Trail:** ✅ Metadata shows edit operation details

#### Testing Verification:

1. **Log Messages to Look For:**

    ```
    🔄 Edit mode: Reversing existing depletions: existing_ids=[id1,id2]
    🔄 Reversed and deleted existing depletion: depletion_id=id1, quantity=10
    🔄 Reversed and deleted existing depletion: depletion_id=id2, quantity=15
    ✅ Manual batch depleted: depleted_quantity=1 (new values)
    ✅ Manual batch depleted: depleted_quantity=2 (new values)
    🎉 Manual batch depletion process completed: edit_mode=true, replaced_depletions=2
    ```

2. **Database Verification:**

    - Old depletion records with IDs from `existingDepletionIds` should be deleted
    - New depletion records created with current timestamp
    - Livestock quantities should reflect only new values (not cumulative)

3. **UI Verification:**
    - Success message shows "Data deplesi berhasil diperbarui"
    - No duplicate records in database
    - Quantities match user input exactly

### History-Based Update Strategy Configuration

**Date:** 2025-01-22  
**Feature:** Configurable update strategy based on `history_enabled` setting.

#### Overview:

The system now supports two different update strategies for edit mode based on the `history_enabled` configuration in `CompanyConfig`:

1. **History Disabled** (`history_enabled: false`): Update existing records in place
2. **History Enabled** (`history_enabled: true`): Delete old records and create new ones

#### Configuration:

```php
// app/Config/CompanyConfig.php
'manual' => [
    'enabled' => true,
    'status' => 'ready',
    'history_enabled' => false, // Controls update strategy
    'track_age' => true,
    'auto_select' => false,
    'show_batch_details' => true,
    'require_selection' => true,
],
```

#### Implementation Details:

1. **Configuration Access:**

    ```php
    $manualConfig = CompanyConfig::getManualDepletionHistorySettings();
    $historyEnabled = $manualConfig['history_enabled'] ?? false;
    ```

2. **Strategy Selection:**
    ```php
    if ($historyEnabled) {
        // DELETE_AND_CREATE strategy
        // - Delete existing records
        // - Create new records with new data
    } else {
        // UPDATE_EXISTING strategy
        // - Update existing records in place
        // - Preserve record IDs and timestamps
    }
    ```

#### Update Strategies:

##### 1. UPDATE_EXISTING (history_enabled: false)

-   **Process:** Existing records are updated in place
-   **Benefits:** Preserves record IDs, creation timestamps, and relationships
-   **Use Case:** When historical data integrity is important
-   **Database Impact:** Minimal - only updates existing rows
-   **Message:** "Data deplesi berhasil diperbarui (record existing di-update)"

**Process Flow:**

1. Reverse existing quantities to reset state
2. Update existing depletion records with new data
3. Update batch quantities with new values
4. Update livestock totals
5. Return success with UPDATE_EXISTING strategy

##### 2. DELETE_AND_CREATE (history_enabled: true)

-   **Process:** Delete old records and create new ones
-   **Benefits:** Clean audit trail, new timestamps for tracking
-   **Use Case:** When fresh records are preferred for each edit
-   **Database Impact:** Higher - deletes and creates new rows
-   **Message:** "Data deplesi berhasil diperbarui (record lama dihapus, record baru dibuat)"

**Process Flow:**

1. Reverse existing quantities
2. Delete existing depletion records
3. Create new depletion records with new data
4. Update batch quantities with new values
5. Update livestock totals
6. Return success with DELETE_AND_CREATE strategy

#### Key Methods Added:

1. **CompanyConfig::getManualDepletionConfig():** Get manual depletion configuration
2. **CompanyConfig::getManualDepletionHistorySettings():** Get history-specific settings
3. **BatchDepletionService::updateExistingDepletions():** Update records in place
4. **BatchDepletionService::buildProcessedBatchesFromUpdate():** Build result data for updates

#### Enhanced Logging:

```
🔄 Edit mode: Processing existing depletions: strategy=UPDATE_EXISTING
🔄 History disabled: Updating existing depletions in place
✅ Updated existing depletion record: depletion_id=123, new_quantity=5
🎉 Manual batch depletion process completed (update mode): update_strategy=UPDATE_EXISTING
```

#### Configuration Benefits:

-   **Flexibility:** Choose update strategy based on business needs
-   **Performance:** UPDATE_EXISTING is more efficient for large datasets
-   **Audit Trail:** DELETE_AND_CREATE provides cleaner audit trail
-   **Backward Compatibility:** Default to history_enabled=false for existing behavior
-   **Future Proof:** Easy to switch strategies via configuration

#### Testing Verification:

1. **Set history_enabled = false:**

    - Edit existing depletion data
    - Verify existing record IDs are preserved
    - Check success message mentions "record existing di-update"
    - Confirm quantities are updated correctly

2. **Set history_enabled = true:**
    - Edit existing depletion data
    - Verify old record IDs are deleted
    - Check new record IDs are created
    - Confirm success message mentions "record lama dihapus, record baru dibuat"
    - Verify quantities are correct in new records
