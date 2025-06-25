# Manual Batch Selection - BatchDepletionService

**Tanggal**: 2024-01-15  
**Versi**: 2.0  
**Status**: Enhanced with Manual Selection Support

## Overview

Dokumentasi ini menjelaskan implementasi dan penggunaan metode manual batch selection dalam `BatchDepletionService`. Fitur ini memungkinkan user untuk memilih batch secara spesifik untuk proses depletion, memberikan kontrol penuh atas batch mana yang akan digunakan.

## Architectural Changes

### 1. Enhanced Service Methods

#### New Methods Added:

-   `processManualBatchDepletion()` - Memproses depletion dengan batch yang dipilih manual
-   `validateManualBatchData()` - Validasi data batch manual
-   `getAvailableBatchesForManualSelection()` - Mendapatkan daftar batch tersedia untuk manual selection
-   `previewManualBatchDepletion()` - Preview depletion manual sebelum eksekusi

#### Updated Methods:

-   `processDepletion()` - Enhanced untuk mendukung routing ke manual batch processing
-   `validateDepletionData()` - Enhanced validation untuk manual batch requirements

### 2. Data Structure Changes

#### Manual Batch Input Format:

```php
[
    'livestock_id' => 'livestock-123',
    'type' => 'mortality|sales|mutation|culling|other',
    'date' => '2024-01-15',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-001',
            'quantity' => 30,
            'note' => 'Optional note'
        ]
    ]
]
```

## Implementation Guide

### 1. Getting Available Batches for Manual Selection

```php
use App\Services\Livestock\BatchDepletionService;

$service = new BatchDepletionService();

// Get all available batches for manual selection
$batches = $service->getAvailableBatchesForManualSelection('livestock-123');

/*
Response format:
{
    "livestock_id": "livestock-123",
    "livestock_name": "Ayam Broiler Kandang A",
    "total_batches": 5,
    "batches": [
        {
            "batch_id": "batch-001",
            "batch_name": "Batch Jan 2024 - Week 1",
            "start_date": "2024-01-01",
            "age_days": 14,
            "initial_quantity": 1000,
            "used_quantity": {
                "depletion": 50,
                "sales": 100,
                "mutated": 25,
                "total": 175
            },
            "available_quantity": 825,
            "utilization_rate": 17.5,
            "status": "active"
        },
        // ... more batches
    ]
}
*/
```

### 2. Preview Manual Batch Depletion

```php
$depletionData = [
    'livestock_id' => 'livestock-123',
    'type' => 'mortality',
    'depletion_method' => 'manual',
    'manual_batches' => [
        ['batch_id' => 'batch-001', 'quantity' => 30],
        ['batch_id' => 'batch-003', 'quantity' => 20]
    ]
];

// Preview before actual processing
$preview = $service->previewManualBatchDepletion($depletionData);

/*
Response format:
{
    "method": "manual",
    "livestock_id": "livestock-123",
    "livestock_name": "Ayam Broiler Kandang A",
    "total_quantity": 50,
    "can_fulfill": true,
    "batches_count": 2,
    "batches_preview": [
        {
            "batch_id": "batch-001",
            "batch_name": "Batch Jan 2024 - Week 1",
            "batch_age_days": 14,
            "available_quantity": 825,
            "requested_quantity": 30,
            "can_fulfill": true,
            "shortfall": 0,
            "note": null
        },
        {
            "batch_id": "batch-003",
            "batch_name": "Batch Jan 2024 - Week 3",
            "batch_age_days": 7,
            "available_quantity": 900,
            "requested_quantity": 20,
            "can_fulfill": true,
            "shortfall": 0,
            "note": null
        }
    ],
    "errors": [],
    "validation_passed": true
}
*/
```

### 3. Process Manual Batch Depletion

```php
$depletionData = [
    'livestock_id' => 'livestock-123',
    'type' => 'culling',
    'date' => '2024-01-15',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-001',
            'quantity' => 25,
            'note' => 'Selected for health issues'
        ],
        [
            'batch_id' => 'batch-004',
            'quantity' => 15,
            'note' => 'Low performance batch'
        ]
    ],
    'reason' => 'Selective culling based on health assessment'
];

// Process the manual depletion
$result = $service->processDepletion($depletionData);

/*
Response format:
{
    "success": true,
    "livestock_id": "livestock-123",
    "total_depleted": 40,
    "processed_batches": [
        {
            "batch_id": "batch-001",
            "batch_name": "Batch Jan 2024 - Week 1",
            "depleted_quantity": 25,
            "remaining_quantity": 800,
            "depletion_record_id": "depletion-456",
            "age_days": 14,
            "user_selected": true,
            "manual_note": "Selected for health issues"
        },
        {
            "batch_id": "batch-004",
            "batch_name": "Batch Jan 2024 - Week 4",
            "depleted_quantity": 15,
            "remaining_quantity": 885,
            "depletion_record_id": "depletion-457",
            "age_days": 3,
            "user_selected": true,
            "manual_note": "Low performance batch"
        }
    ],
    "depletion_method": "manual",
    "manual_selection": true,
    "message": "Manual batch depletion completed successfully"
}
*/
```

## Comparison of Depletion Methods

### 1. FIFO (First In First Out)

```php
$fifoData = [
    'livestock_id' => 'livestock-123',
    'quantity' => 100,
    'type' => 'sales',
    'depletion_method' => 'fifo' // Optional, default method
];

$result = $service->processDepletion($fifoData);
```

**Characteristics:**

-   ✅ Automatic batch selection
-   ✅ Uses oldest batches first
-   ✅ Good for inventory rotation
-   ❌ No user control over batch selection

### 2. LIFO (Last In First Out)

```php
$lifoData = [
    'livestock_id' => 'livestock-123',
    'quantity' => 100,
    'type' => 'sales',
    'depletion_method' => 'lifo'
];

$result = $service->processDepletion($lifoData);
```

**Characteristics:**

-   ✅ Automatic batch selection
-   ✅ Uses newest batches first
-   ✅ Good for specific business needs
-   ❌ No user control over batch selection

### 3. Manual Selection

```php
$manualData = [
    'livestock_id' => 'livestock-123',
    'type' => 'culling',
    'depletion_method' => 'manual',
    'manual_batches' => [
        ['batch_id' => 'batch-002', 'quantity' => 50, 'note' => 'Health issues'],
        ['batch_id' => 'batch-005', 'quantity' => 30, 'note' => 'Performance issues']
    ]
];

$result = $service->processDepletion($manualData);
```

**Characteristics:**

-   ✅ Full user control over batch selection
-   ✅ Can target specific batches
-   ✅ Supports notes for each batch
-   ✅ Flexible quantity distribution
-   ❌ Requires more user input
-   ❌ More complex validation

## Frontend Integration Examples

### 1. Vue.js Component for Manual Batch Selection

```vue
<template>
    <div class="manual-batch-selection">
        <h3>Manual Batch Selection</h3>

        <!-- Available Batches -->
        <div class="available-batches">
            <h4>Available Batches</h4>
            <div
                v-for="batch in availableBatches"
                :key="batch.batch_id"
                class="batch-card"
                @click="selectBatch(batch)"
            >
                <div class="batch-info">
                    <strong>{{ batch.batch_name }}</strong>
                    <span>Age: {{ batch.age_days }} days</span>
                    <span>Available: {{ batch.available_quantity }}</span>
                </div>
            </div>
        </div>

        <!-- Selected Batches -->
        <div class="selected-batches">
            <h4>Selected Batches</h4>
            <div
                v-for="(selection, index) in selectedBatches"
                :key="index"
                class="selected-batch"
            >
                <input
                    v-model="selection.quantity"
                    type="number"
                    :max="selection.available_quantity"
                />
                <input
                    v-model="selection.note"
                    type="text"
                    placeholder="Note (optional)"
                />
                <button @click="removeSelection(index)">Remove</button>
            </div>
        </div>

        <!-- Preview & Submit -->
        <div class="actions">
            <button @click="previewDepletion" :disabled="!canPreview">
                Preview
            </button>
            <button @click="processDepletion" :disabled="!canProcess">
                Process
            </button>
        </div>

        <!-- Preview Results -->
        <div v-if="previewResult" class="preview-result">
            <h4>Preview Result</h4>
            <pre>{{ JSON.stringify(previewResult, null, 2) }}</pre>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            livestockId: "",
            availableBatches: [],
            selectedBatches: [],
            depletionType: "mortality",
            previewResult: null,
        };
    },

    computed: {
        canPreview() {
            return (
                this.selectedBatches.length > 0 &&
                this.selectedBatches.every((s) => s.quantity > 0)
            );
        },

        canProcess() {
            return this.previewResult && this.previewResult.can_fulfill;
        },
    },

    methods: {
        async loadAvailableBatches() {
            try {
                const response = await axios.get(
                    `/api/livestock/${this.livestockId}/available-batches`
                );
                this.availableBatches = response.data.batches;
            } catch (error) {
                console.error("Error loading batches:", error);
            }
        },

        selectBatch(batch) {
            if (
                !this.selectedBatches.find((s) => s.batch_id === batch.batch_id)
            ) {
                this.selectedBatches.push({
                    batch_id: batch.batch_id,
                    batch_name: batch.batch_name,
                    available_quantity: batch.available_quantity,
                    quantity: 1,
                    note: "",
                });
            }
        },

        removeSelection(index) {
            this.selectedBatches.splice(index, 1);
            this.previewResult = null;
        },

        async previewDepletion() {
            try {
                const depletionData = {
                    livestock_id: this.livestockId,
                    type: this.depletionType,
                    depletion_method: "manual",
                    manual_batches: this.selectedBatches.map((s) => ({
                        batch_id: s.batch_id,
                        quantity: parseInt(s.quantity),
                        note: s.note || null,
                    })),
                };

                const response = await axios.post(
                    "/api/depletion/preview-manual",
                    depletionData
                );
                this.previewResult = response.data;
            } catch (error) {
                console.error("Error previewing depletion:", error);
            }
        },

        async processDepletion() {
            try {
                const depletionData = {
                    livestock_id: this.livestockId,
                    type: this.depletionType,
                    date: new Date().toISOString().split("T")[0],
                    depletion_method: "manual",
                    manual_batches: this.selectedBatches.map((s) => ({
                        batch_id: s.batch_id,
                        quantity: parseInt(s.quantity),
                        note: s.note || null,
                    })),
                };

                const response = await axios.post(
                    "/api/depletion/process",
                    depletionData
                );

                if (response.data.success) {
                    alert("Depletion processed successfully!");
                    this.resetForm();
                }
            } catch (error) {
                console.error("Error processing depletion:", error);
                alert(
                    "Error processing depletion: " + error.response.data.message
                );
            }
        },

        resetForm() {
            this.selectedBatches = [];
            this.previewResult = null;
            this.loadAvailableBatches();
        },
    },

    mounted() {
        this.loadAvailableBatches();
    },
};
</script>
```

### 2. Laravel Controller for API Endpoints

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Livestock\BatchDepletionService;
use Illuminate\Http\Request;

class BatchDepletionController extends Controller
{
    protected $batchDepletionService;

    public function __construct(BatchDepletionService $batchDepletionService)
    {
        $this->batchDepletionService = $batchDepletionService;
    }

    /**
     * Get available batches for manual selection
     */
    public function getAvailableBatches($livestockId)
    {
        try {
            $batches = $this->batchDepletionService->getAvailableBatchesForManualSelection($livestockId);
            return response()->json($batches);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Preview manual batch depletion
     */
    public function previewManualDepletion(Request $request)
    {
        try {
            $validated = $request->validate([
                'livestock_id' => 'required|string',
                'type' => 'required|in:mortality,sales,mutation,culling,other',
                'depletion_method' => 'required|in:manual',
                'manual_batches' => 'required|array|min:1',
                'manual_batches.*.batch_id' => 'required|string',
                'manual_batches.*.quantity' => 'required|integer|min:1',
                'manual_batches.*.note' => 'nullable|string'
            ]);

            $preview = $this->batchDepletionService->previewManualBatchDepletion($validated);
            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Process depletion (supports all methods including manual)
     */
    public function processDepletion(Request $request)
    {
        try {
            $validated = $request->validate([
                'livestock_id' => 'required|string',
                'quantity' => 'required_unless:depletion_method,manual|integer|min:1',
                'type' => 'required|in:mortality,sales,mutation,culling,other',
                'date' => 'nullable|date',
                'depletion_method' => 'nullable|in:fifo,lifo,manual',
                'manual_batches' => 'required_if:depletion_method,manual|array',
                'manual_batches.*.batch_id' => 'required_with:manual_batches|string',
                'manual_batches.*.quantity' => 'required_with:manual_batches|integer|min:1',
                'manual_batches.*.note' => 'nullable|string',
                'reason' => 'nullable|string'
            ]);

            $result = $this->batchDepletionService->processDepletion($validated);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

## Best Practices

### 1. Validation & Error Handling

-   Always validate batch ownership before processing
-   Check available quantities before allowing selection
-   Provide clear error messages for validation failures
-   Use preview functionality before actual processing

### 2. User Experience

-   Show batch information clearly (age, available quantity, etc.)
-   Allow easy selection/deselection of batches
-   Provide real-time validation feedback
-   Show preview results before final processing

### 3. Performance Considerations

-   Cache available batches data when possible
-   Use efficient queries for batch availability checks
-   Implement proper pagination for large batch lists
-   Consider background processing for bulk operations

### 4. Audit & Logging

-   Log all manual selections with user information
-   Store selection notes for future reference
-   Track manual vs automatic processing metrics
-   Maintain complete audit trail

## Database Tracking

### LivestockDepletion Record for Manual Selection

```json
{
    "id": "depletion-789",
    "livestock_id": "livestock-123",
    "tanggal": "2024-01-15",
    "jumlah": 25,
    "jenis": "culling",
    "data": {
        "batch_id": "batch-001",
        "batch_name": "Batch Jan 2024 - Week 1",
        "batch_start_date": "2024-01-01",
        "depletion_method": "manual",
        "user_selected": true,
        "manual_batch_note": "Selected for health issues",
        "available_in_batch": 825
    },
    "metadata": {
        "processed_at": "2024-01-15T10:30:00Z",
        "processed_by": 123,
        "processing_method": "batch_depletion_service",
        "selection_type": "manual",
        "batch_metadata": {
            "age_days": 14,
            "initial_quantity": 1000,
            "previous_depletion": 50,
            "previous_sales": 100,
            "previous_mutated": 25
        }
    }
}
```

## Testing Manual Selection

### Unit Test Example

```php
class ManualBatchDepletionTest extends TestCase
{
    public function test_manual_batch_depletion_success()
    {
        // Setup test data
        $livestock = Livestock::factory()->create();
        $batch1 = LivestockBatch::factory()->create([
            'livestock_id' => $livestock->id,
            'initial_quantity' => 100,
            'quantity_depletion' => 0
        ]);
        $batch2 = LivestockBatch::factory()->create([
            'livestock_id' => $livestock->id,
            'initial_quantity' => 200,
            'quantity_depletion' => 0
        ]);

        $service = new BatchDepletionService();

        $depletionData = [
            'livestock_id' => $livestock->id,
            'type' => 'mortality',
            'depletion_method' => 'manual',
            'manual_batches' => [
                ['batch_id' => $batch1->id, 'quantity' => 30],
                ['batch_id' => $batch2->id, 'quantity' => 50]
            ]
        ];

        $result = $service->processDepletion($depletionData);

        $this->assertTrue($result['success']);
        $this->assertEquals(80, $result['total_depleted']);
        $this->assertCount(2, $result['processed_batches']);
        $this->assertEquals('manual', $result['depletion_method']);
        $this->assertTrue($result['manual_selection']);
    }

    public function test_manual_batch_validation_fails_for_insufficient_quantity()
    {
        $livestock = Livestock::factory()->create();
        $batch = LivestockBatch::factory()->create([
            'livestock_id' => $livestock->id,
            'initial_quantity' => 50,
            'quantity_depletion' => 0
        ]);

        $service = new BatchDepletionService();

        $depletionData = [
            'livestock_id' => $livestock->id,
            'type' => 'mortality',
            'depletion_method' => 'manual',
            'manual_batches' => [
                ['batch_id' => $batch->id, 'quantity' => 100] // More than available
            ]
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient quantity in batch');

        $service->processDepletion($depletionData);
    }
}
```

## Conclusion

Manual batch selection memberikan fleksibilitas penuh kepada user untuk mengontrol batch mana yang akan digunakan dalam proses depletion. Implementasi ini mempertahankan konsistensi dengan metode lain sambil memberikan kontrol granular yang dibutuhkan untuk use case khusus seperti culling selektif atau manajemen batch berdasarkan kondisi kesehatan.

Fitur ini siap untuk production dengan validasi komprehensif, error handling yang robust, dan audit trail yang lengkap.
