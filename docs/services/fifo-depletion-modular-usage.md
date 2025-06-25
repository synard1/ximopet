# FIFO Depletion Modular System - Usage Guide

## Overview

Sistem FIFO depletion telah dibuat modular dan reusable untuk dapat digunakan di controller, service, dan component manapun dengan mudah. Sistem ini menyediakan beberapa cara penggunaan sesuai kebutuhan.

## Architecture

```
FIFODepletionService (Core)
    ↓
FIFODepletionManagerService (Modular Wrapper)
    ↓
HasFifoDepletion (Trait for easy integration)
```

## Usage Methods

### 1. Static Method (Paling Sederhana)

```php
use App\Services\Livestock\FIFODepletionManagerService;

// Basic usage
$result = FIFODepletionManagerService::store('mortality', 10, $recordingId, $livestock);

// With options
$result = FIFODepletionManagerService::store('Mati', 5, $recordingId, $livestock, [
    'date' => '2025-01-22',
    'reason' => 'Disease outbreak',
    'notes' => 'Emergency depletion'
]);
```

### 2. Dependency Injection

```php
use App\Services\Livestock\FIFODepletionManagerService;

class LivestockController extends Controller
{
    protected FIFODepletionManagerService $fifoManager;

    public function __construct(FIFODepletionManagerService $fifoManager)
    {
        $this->fifoManager = $fifoManager;
    }

    public function depleteStock(Request $request)
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        // User's requested signature
        $fifoResult = $this->fifoManager->storeDeplesiWithFifo(
            $request->jenis,      // 'Mati', 'Afkir', 'mortality', 'culling'
            $request->jumlah,     // quantity
            $request->recording_id, // optional
            $livestock
        );

        if ($fifoResult['success']) {
            return response()->json([
                'success' => true,
                'message' => $fifoResult['message'],
                'data' => $fifoResult
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $fifoResult['error']
        ], 400);
    }
}
```

### 3. Using Trait (Recommended)

```php
use App\Traits\HasFifoDepletion;

class RecordsController extends Controller
{
    use HasFifoDepletion;

    public function storeDepletion(Request $request)
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        // Exact signature as requested by user
        $fifoResult = $this->storeDeplesiWithFifo(
            $request->jenis,
            $request->jumlah,
            $request->recording_id,
            $livestock
        );

        return response()->json($fifoResult);
    }

    public function quickDepletion(Request $request)
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        // Auto-creates recording if needed
        $result = $this->quickStoreFifoDepletion(
            $request->jenis,
            $request->jumlah,
            $livestock,
            ['reason' => $request->reason]
        );

        return response()->json($result);
    }

    public function previewDepletion(Request $request)
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $preview = $this->previewFifoDepletion(
            $request->jenis,
            $request->jumlah,
            $livestock
        );

        return response()->json($preview);
    }
}
```

### 4. Service Class Integration

```php
use App\Traits\HasFifoDepletion;

class LivestockDepletionService
{
    use HasFifoDepletion;

    public function processDepletionBatch(array $depletions, Livestock $livestock)
    {
        // Batch processing
        $result = $this->batchStoreFifoDepletion($depletions, $livestock, [
            'reason' => 'Batch processing',
            'date' => now()->format('Y-m-d')
        ]);

        return $result;
    }

    public function smartDepletionWithFallback(string $jenis, int $jumlah, Livestock $livestock)
    {
        // Automatically chooses FIFO or manual based on livestock config
        return $this->smartDepletion(
            $jenis,
            $jumlah,
            null,
            $livestock,
            [],
            function($jenis, $jumlah, $recordingId, $livestock, $options) {
                // Manual fallback method
                return $this->processManualDepletion($jenis, $jumlah, $recordingId, $livestock, $options);
            }
        );
    }
}
```

### 5. Livewire Component Integration

```php
use App\Traits\HasFifoDepletion;
use Livewire\Component;

class DepletionComponent extends Component
{
    use HasFifoDepletion;

    public $livestock;
    public $depletionType = 'mortality';
    public $quantity = 1;

    public function processDepletion()
    {
        // User's requested signature
        $fifoResult = $this->storeDeplesiWithFifo(
            $this->depletionType,
            $this->quantity,
            null, // auto-find recording
            $this->livestock
        );

        if ($fifoResult['success']) {
            session()->flash('success', $fifoResult['message']);
            $this->dispatch('depletion-processed', $fifoResult);
        } else {
            session()->flash('error', $fifoResult['error']);
        }
    }

    public function checkFifoAvailability()
    {
        return $this->canUseFifoDepletion($this->livestock, $this->depletionType);
    }
}
```

## Advanced Usage Examples

### 1. Batch Processing

```php
$depletions = [
    ['jenis' => 'mortality', 'jumlah' => 5],
    ['jenis' => 'culling', 'jumlah' => 3],
    ['jenis' => 'sales', 'jumlah' => 10]
];

$result = $this->batchStoreFifoDepletion($depletions, $livestock, [
    'reason' => 'End of period processing',
    'date' => '2025-01-22'
]);

// Result structure:
// {
//     "success": true,
//     "total_depletions": 3,
//     "success_count": 3,
//     "failure_count": 0,
//     "results": [...],
//     "message": "All 3 depletions processed successfully"
// }
```

### 2. With Automatic Fallback

```php
$result = $this->storeDeplesiWithFifoFallback(
    'mortality',
    10,
    $recordingId,
    $livestock,
    ['reason' => 'Disease'],
    function($jenis, $jumlah, $recordingId, $livestock, $options) {
        // This will be called if FIFO fails
        return $this->processManualDepletion($jenis, $jumlah, $recordingId, $livestock, $options);
    }
);
```

### 3. Smart Method Selection

```php
// Automatically chooses FIFO or manual based on livestock configuration
$result = $this->smartDepletion(
    'Afkir',
    5,
    $recordingId,
    $livestock,
    ['reason' => 'Poor performance'],
    [$this, 'processManualDepletion'] // Fallback method
);
```

### 4. Statistics and Monitoring

```php
// Check if FIFO is available
if ($this->canUseFifoDepletion($livestock, 'mortality')) {
    // Get statistics
    $stats = $this->getFifoDepletionStats($livestock, '30_days');

    // Preview before processing
    $preview = $this->previewFifoDepletion('mortality', 10, $livestock);

    if ($preview['can_process']) {
        $result = $this->storeDeplesiWithFifo('mortality', 10, null, $livestock);
    }
}
```

## Supported Depletion Types

The system supports both Indonesian legacy and English standard types:

```php
const TYPE_MAPPING = [
    // Indonesian legacy types
    'Mati' => 'mortality',
    'Afkir' => 'culling',
    'Jual' => 'sales',
    'Mutasi' => 'mutation',

    // English standard types
    'mortality' => 'mortality',
    'culling' => 'culling',
    'sales' => 'sales',
    'mutation' => 'mutation',

    // Alternative names
    'kematian' => 'mortality',
    'death' => 'mortality',
    'cull' => 'culling',
    'sell' => 'sales',
    'transfer' => 'mutation',
    'move' => 'mutation'
];
```

## Result Structure

### Success Result

```php
[
    'success' => true,
    'method' => 'fifo',
    'livestock_id' => 123,
    'livestock_name' => 'Kandang A',
    'depletion_type' => 'mortality',
    'quantity' => 10,
    'batches_affected' => 2,
    'depletion_records' => [1, 2], // IDs of created records
    'updated_batches' => [...],
    'processed_at' => '2025-01-22 10:30:00',
    'message' => 'FIFO depletion successful: 10 units depleted across 2 batches',
    'details' => [...] // Full FIFO service result
]
```

### Error Result

```php
[
    'success' => false,
    'method' => 'fifo',
    'error' => 'FIFO depletion not enabled for this livestock',
    'details' => [
        'reason' => 'fifo_not_enabled',
        'livestock_id' => 123,
        'depletion_type' => 'mortality'
    ],
    'processed_at' => '2025-01-22 10:30:00'
]
```

## Integration with Existing Code

### Update Records.php Component

```php
use App\Traits\HasFifoDepletion;

class Records extends Component
{
    use HasFifoDepletion;

    private function storeDeplesiWithDetails($jenis, $jumlah, $recordingId)
    {
        $livestock = Livestock::find($this->livestockId);

        // Check if FIFO should be used
        if ($this->shouldUseFifoDepletion($livestock, $jenis)) {
            $fifoResult = $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock);

            if ($fifoResult['success']) {
                return $this->convertFifoResultToLegacyFormat($fifoResult);
            }

            // Log FIFO failure and fall back to manual
            Log::warning('FIFO depletion failed, falling back to manual', [
                'error' => $fifoResult['error']
            ]);
        }

        // Continue with existing manual logic...
        return $this->processManualDepletion($jenis, $jumlah, $recordingId);
    }
}
```

## Performance Considerations

1. **Caching**: The service includes built-in caching for batch queries
2. **Batch Processing**: Use `batchStore()` for multiple depletions
3. **Preview First**: Use `previewDepletion()` for validation before processing
4. **Smart Selection**: Use `smartDepletion()` to automatically choose the best method

## Error Handling

The system provides comprehensive error handling:

1. **Input Validation**: Validates all inputs before processing
2. **Configuration Checks**: Verifies FIFO is enabled and livestock has batches
3. **Graceful Fallback**: Supports automatic fallback to manual methods
4. **Detailed Logging**: All operations are logged for debugging

## Migration Path

To migrate existing code:

1. Add the trait: `use HasFifoDepletion;`
2. Replace direct FIFO service calls with trait methods
3. Use the exact signature: `$this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock)`
4. Handle the standardized result format

This modular approach ensures the FIFO depletion system is:

-   ✅ **Reusable** across all components
-   ✅ **Consistent** API and result format
-   ✅ **Maintainable** centralized logic
-   ✅ **Extensible** easy to add new features
-   ✅ **Production-ready** comprehensive error handling and logging
