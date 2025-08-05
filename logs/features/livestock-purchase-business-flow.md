# Livestock Purchase Business Flow Configuration

## Tanggal: 2025-01-27

## Status: ✅ Completed

## Overview

Business flow configuration untuk sistem pembelian ternak yang mendukung 3 level kompleksitas: Simple, Standard, dan Complex. Setiap flow disesuaikan dengan kebutuhan bisnis yang berbeda berdasarkan nilai pembelian, rating supplier, dan kompleksitas proses.

## Business Flow Types

### 1. Simple Flow

**Target:** Pembelian kecil dengan proses sederhana

#### Karakteristik:

-   **Total Status:** 3 status (Draft → Confirmed → Completed)
-   **Approval:** Tidak diperlukan (Auto approval)
-   **Batch Creation:** Tidak diperlukan
-   **Document Requirements:** Minimal
-   **Complexity:** Low

#### Status Flow:

```
Draft → Confirmed → Completed
```

#### Transitions:

-   `draft` → `confirmed`
-   `confirmed` → `completed`
-   `completed` → (end)

#### Use Cases:

-   Pembelian kecil (< 10 juta)
-   Supplier terpercaya (rating >= 4)
-   Tidak memerlukan tracking
-   Proses cepat
-   Dokumentasi minimal

### 2. Standard Flow

**Target:** Pembelian menengah dengan proses standar

#### Karakteristik:

-   **Total Status:** 6 status (Draft → Pending → Confirmed → In Transit → Arrived → Completed)
-   **Approval:** Diperlukan
-   **Batch Creation:** Diperlukan
-   **Document Requirements:** Standard
-   **Complexity:** Medium

#### Status Flow:

```
Draft → Pending → Confirmed → In Transit → Arrived → Completed
  ↓       ↓         ↓           ↓          ↓
Cancelled ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ←
```

#### Transitions:

-   `draft` → `pending`
-   `pending` → `confirmed`, `cancelled`
-   `confirmed` → `in_transit`, `cancelled`
-   `in_transit` → `arrived`, `cancelled`
-   `arrived` → `completed`, `cancelled`
-   `completed` → (end)
-   `cancelled` → (end)

#### Use Cases:

-   Pembelian menengah (10-100 juta)
-   Supplier dengan rating normal
-   Memerlukan approval
-   Batch creation
-   Dokumentasi standar

### 3. Complex Flow

**Target:** Pembelian besar dengan proses kompleks

#### Karakteristik:

-   **Total Status:** 7 status (Draft → Pending → Confirmed → In Transit → Arrived → In Coop → Completed)
-   **Approval:** Diperlukan dengan multiple levels
-   **Batch Creation:** Diperlukan
-   **Document Requirements:** Comprehensive
-   **Complexity:** High
-   **Quality Checks:** Diperlukan
-   **Tracking:** Diperlukan

#### Status Flow:

```
Draft → Pending → Confirmed → In Transit → Arrived → In Coop → Completed
  ↓       ↓         ↓           ↓          ↓         ↓
Cancelled ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ←
```

#### Transitions:

-   `draft` → `pending`
-   `pending` → `confirmed`, `cancelled`
-   `confirmed` → `in_transit`, `cancelled`
-   `in_transit` → `arrived`, `cancelled`
-   `arrived` → `in_coop`, `cancelled`
-   `in_coop` → `completed`
-   `completed` → (end)
-   `cancelled` → (end)

#### Use Cases:

-   Pembelian besar (> 100 juta)
-   Supplier baru/bermasalah
-   Multiple approvals
-   Quality checks
-   Tracking lengkap
-   Dokumentasi komprehensif

## Status Configuration Detail

### 1. Draft Status

```php
'draft' => [
    'enabled' => true,
    'can_edit' => true,
    'can_delete' => true,
    'requires_approval' => false,
    'auto_numbering' => false,
    'next_statuses' => ['pending', 'confirmed'],
    'description' => 'Status awal saat membuat pembelian. Belum ada konfirmasi atau validasi. Masih bisa diedit/dihapus.',
    'color' => 'light-gray',
    'icon' => 'fas fa-edit',
    'validation_rules' => [
        'require_basic_info' => true,
        'require_supplier' => true,
        'require_items' => true,
    ],
    'notifications' => [
        'on_create' => false,
        'on_update' => false,
        'on_delete' => false,
    ],
]
```

### 2. Pending Status

```php
'pending' => [
    'enabled' => true,
    'can_edit' => false,
    'can_delete' => false,
    'requires_approval' => true,
    'auto_numbering' => true,
    'next_statuses' => ['confirmed', 'cancelled'],
    'description' => 'Sudah dibuat tapi menunggu konfirmasi. Menunggu persetujuan dari pihak terkait. Belum bisa diproses lebih lanjut.',
    'color' => 'yellow',
    'icon' => 'fas fa-clock',
    'validation_rules' => [
        'require_complete_data' => true,
        'require_approval_workflow' => true,
        'validate_business_rules' => true,
    ],
    'notifications' => [
        'on_create' => true,
        'on_update' => false,
        'on_delete' => false,
        'reminder_enabled' => true,
        'reminder_interval_hours' => 6,
    ],
]
```

### 3. Confirmed Status

```php
'confirmed' => [
    'enabled' => true,
    'can_edit' => false,
    'can_delete' => false,
    'requires_approval' => false,
    'auto_numbering' => true,
    'next_statuses' => ['in_transit', 'cancelled'],
    'description' => 'Sudah dikonfirmasi/disetujui. Siap untuk diproses pengiriman. Belum ada pengiriman ternak.',
    'color' => 'blue',
    'icon' => 'fas fa-check-circle',
    'validation_rules' => [
        'require_expedition_info' => true,
        'require_delivery_schedule' => true,
        'validate_supplier_contract' => true,
    ],
    'notifications' => [
        'on_create' => true,
        'on_update' => false,
        'on_delete' => false,
    ],
]
```

### 4. In Transit Status

```php
'in_transit' => [
    'enabled' => true,
    'can_edit' => false,
    'can_delete' => false,
    'requires_approval' => false,
    'auto_numbering' => true,
    'next_statuses' => ['arrived', 'cancelled'],
    'description' => 'Ternak sedang dalam perjalanan. Sudah ada nomor DO/Surat Jalan. Belum sampai di lokasi tujuan.',
    'color' => 'orange',
    'icon' => 'fas fa-truck',
    'validation_rules' => [
        'require_do_number' => true,
        'require_expedition_tracking' => true,
        'validate_delivery_schedule' => true,
    ],
    'notifications' => [
        'on_create' => true,
        'on_update' => true,
        'on_delete' => false,
        'tracking_updates' => true,
    ],
]
```

### 5. Arrived Status

```php
'arrived' => [
    'enabled' => true,
    'can_edit' => false,
    'can_delete' => false,
    'requires_approval' => false,
    'auto_numbering' => true,
    'next_statuses' => ['in_coop', 'cancelled'],
    'description' => 'Ternak sudah sampai di lokasi tujuan. Sudah dilakukan pemeriksaan awal. Siap untuk dipindahkan ke kandang.',
    'color' => 'green',
    'icon' => 'fas fa-map-marker-alt',
    'validation_rules' => [
        'require_arrival_confirmation' => true,
        'require_initial_inspection' => true,
        'require_health_check' => true,
    ],
    'notifications' => [
        'on_create' => true,
        'on_update' => false,
        'on_delete' => false,
    ],
]
```

### 6. In Coop Status

```php
'in_coop' => [
    'enabled' => true,
    'can_edit' => false,
    'can_delete' => false,
    'requires_approval' => false,
    'auto_numbering' => true,
    'next_statuses' => ['completed'],
    'description' => 'Ternak sudah dipindahkan ke kandang. Sudah dilakukan pencatatan di sistem. Proses pembelian selesai.',
    'color' => 'green',
    'icon' => 'fas fa-home',
    'validation_rules' => [
        'require_coop_assignment' => true,
        'require_batch_creation' => true,
        'require_final_inspection' => true,
    ],
    'notifications' => [
        'on_create' => true,
        'on_update' => false,
        'on_delete' => false,
    ],
]
```

### 7. Completed Status

```php
'completed' => [
    'enabled' => true,
    'can_edit' => false,
    'can_delete' => false,
    'requires_approval' => false,
    'auto_numbering' => true,
    'next_statuses' => [],
    'description' => 'Seluruh proses selesai. Semua dokumen lengkap. Pembayaran sudah selesai.',
    'color' => 'dark-gray',
    'icon' => 'fas fa-flag-checkered',
    'validation_rules' => [
        'require_complete_documentation' => true,
        'require_payment_confirmation' => true,
        'require_final_report' => true,
    ],
    'notifications' => [
        'on_create' => true,
        'on_update' => false,
        'on_delete' => false,
    ],
]
```

### 8. Cancelled Status

```php
'cancelled' => [
    'enabled' => true,
    'can_edit' => false,
    'can_delete' => false,
    'requires_approval' => true,
    'auto_numbering' => false,
    'next_statuses' => [],
    'description' => 'Pembelian dibatalkan. Bisa karena berbagai alasan. Tidak bisa diproses lebih lanjut.',
    'color' => 'red',
    'icon' => 'fas fa-times-circle',
    'validation_rules' => [
        'require_cancellation_reason' => true,
        'require_approval_for_cancellation' => true,
        'validate_cancellation_impact' => true,
    ],
    'notifications' => [
        'on_create' => true,
        'on_update' => false,
        'on_delete' => false,
    ],
]
```

## Status Requirements

### Draft Status Requirements

```php
'draft' => [
    'required_fields' => ['tanggal', 'supplier_id', 'items'],
    'optional_fields' => ['notes', 'expected_delivery_date'],
    'documents' => [],
]
```

### Pending Status Requirements

```php
'pending' => [
    'required_fields' => ['tanggal', 'supplier_id', 'farm_id', 'coop_id', 'items', 'total_amount'],
    'optional_fields' => ['notes', 'expected_delivery_date', 'special_requirements'],
    'documents' => ['purchase_order'],
]
```

### Confirmed Status Requirements

```php
'confirmed' => [
    'required_fields' => ['expedition_id', 'expected_delivery_date', 'do_number'],
    'optional_fields' => ['tracking_number', 'special_instructions'],
    'documents' => ['purchase_order', 'confirmation_letter'],
]
```

### In Transit Status Requirements

```php
'in_transit' => [
    'required_fields' => ['do_number', 'expedition_tracking'],
    'optional_fields' => ['estimated_arrival_time', 'route_information'],
    'documents' => ['delivery_order', 'waybill'],
]
```

### Arrived Status Requirements

```php
'arrived' => [
    'required_fields' => ['arrival_time', 'initial_inspection_result'],
    'optional_fields' => ['weather_conditions', 'transport_notes'],
    'documents' => ['arrival_confirmation', 'initial_inspection_report'],
]
```

### In Coop Status Requirements

```php
'in_coop' => [
    'required_fields' => ['coop_id', 'batch_number', 'final_inspection_result'],
    'optional_fields' => ['coop_notes', 'health_status'],
    'documents' => ['coop_assignment', 'batch_creation', 'final_inspection_report'],
]
```

### Completed Status Requirements

```php
'completed' => [
    'required_fields' => ['completion_date', 'payment_confirmation'],
    'optional_fields' => ['final_notes', 'performance_rating'],
    'documents' => ['completion_certificate', 'payment_receipt', 'final_report'],
]
```

### Cancelled Status Requirements

```php
'cancelled' => [
    'required_fields' => ['cancellation_reason', 'cancellation_date'],
    'optional_fields' => ['refund_information', 'lessons_learned'],
    'documents' => ['cancellation_letter', 'refund_documentation'],
]
```

## API Methods

### 1. Get Business Flow Configuration

```php
// Get current business flow config
$flowConfig = LivestockPurchaseConfig::getBusinessFlowConfig();

// Get specific business flow config
$simpleFlow = LivestockPurchaseConfig::getBusinessFlowConfig('simple');
$standardFlow = LivestockPurchaseConfig::getBusinessFlowConfig('standard');
$complexFlow = LivestockPurchaseConfig::getBusinessFlowConfig('complex');
```

### 2. Get Available Business Flows

```php
$availableFlows = LivestockPurchaseConfig::getAvailableBusinessFlows();
// Returns: ['simple' => [...], 'standard' => [...], 'complex' => [...]]
```

### 3. Get Business Flow Summary

```php
$summary = LivestockPurchaseConfig::getBusinessFlowSummary('complex');
// Returns: [
//     'name' => 'Complex Flow',
//     'description' => 'Flow kompleks untuk pembelian besar dengan multiple approvals',
//     'total_statuses' => 7,
//     'approval_required' => true,
//     'auto_approval' => false,
//     'batch_creation' => true,
//     'document_requirements' => 'comprehensive',
//     'multiple_approvals' => true,
//     'quality_checks' => true,
//     'tracking_required' => true,
// ]
```

### 4. Get Status Requirements

```php
$requirements = LivestockPurchaseConfig::getStatusRequirements('pending');
// Returns: [
//     'required_fields' => ['tanggal', 'supplier_id', 'farm_id', 'coop_id', 'items', 'total_amount'],
//     'optional_fields' => ['notes', 'expected_delivery_date', 'special_requirements'],
//     'documents' => ['purchase_order'],
// ]
```

### 5. Get Status Display Information

```php
$display = LivestockPurchaseConfig::getStatusDisplay('pending');
// Returns: [
//     'color' => 'yellow',
//     'icon' => 'fas fa-clock',
//     'label' => 'Sudah dibuat tapi menunggu konfirmasi...',
// ]
```

### 6. Check Status Transitions

```php
// Check if transition is allowed
$canTransition = LivestockPurchaseConfig::canTransitionToWithFlow('draft', 'pending', 'standard');
// Returns: true

// Get next available statuses
$nextStatuses = LivestockPurchaseConfig::getNextStatusesWithFlow('pending', 'standard');
// Returns: ['confirmed', 'cancelled']
```

### 7. Get Recommended Flow

```php
$criteria = [
    'amount' => 50000000, // 50 juta
    'quantity' => 5000,
    'supplier_rating' => 4.5,
    'is_urgent' => false,
    'requires_tracking' => true,
];

$recommendedFlow = LivestockPurchaseConfig::getRecommendedFlow($criteria);
// Returns: 'standard'
```

### 8. Get Business Flow Comparison

```php
$comparison = LivestockPurchaseConfig::getBusinessFlowComparison();
// Returns comparison table for all flows
```

## Usage Examples

### 1. Dynamic Flow Selection

```php
class LivestockPurchaseService
{
    public function createPurchase(array $data)
    {
        // Determine business flow based on criteria
        $criteria = [
            'amount' => $data['total_amount'],
            'quantity' => $data['total_quantity'],
            'supplier_rating' => $this->getSupplierRating($data['supplier_id']),
            'is_urgent' => $data['is_urgent'] ?? false,
            'requires_tracking' => $data['requires_tracking'] ?? false,
        ];

        $flowType = LivestockPurchaseConfig::getRecommendedFlow($criteria);
        $flowConfig = LivestockPurchaseConfig::getBusinessFlowConfig($flowType);

        // Create purchase with selected flow
        $purchase = LivestockPurchase::create([
            'business_flow_type' => $flowType,
            'status' => 'draft',
            ...$data
        ]);

        return $purchase;
    }
}
```

### 2. Status Transition Validation

```php
class LivestockPurchaseController
{
    public function updateStatus(Request $request, LivestockPurchase $purchase)
    {
        $newStatus = $request->input('status');
        $flowType = $purchase->business_flow_type;

        // Check if transition is allowed
        if (!LivestockPurchaseConfig::canTransitionToWithFlow($purchase->status, $newStatus, $flowType)) {
            return response()->json(['error' => 'Invalid status transition'], 400);
        }

        // Get status requirements
        $requirements = LivestockPurchaseConfig::getStatusRequirements($newStatus);

        // Validate required fields
        $this->validateStatusRequirements($request, $requirements);

        // Update status
        $purchase->update(['status' => $newStatus]);

        return response()->json(['success' => true]);
    }
}
```

### 3. Dynamic UI Based on Flow

```php
class LivestockPurchaseComponent extends Component
{
    public function getStatusOptions()
    {
        $flowType = $this->purchase->business_flow_type;
        $currentStatus = $this->purchase->status;

        $nextStatuses = LivestockPurchaseConfig::getNextStatusesWithFlow($currentStatus, $flowType);

        $options = [];
        foreach ($nextStatuses as $status) {
            $display = LivestockPurchaseConfig::getStatusDisplay($status);
            $options[] = [
                'value' => $status,
                'label' => $display['label'],
                'color' => $display['color'],
                'icon' => $display['icon'],
            ];
        }

        return $options;
    }
}
```

### 4. Business Flow Comparison Display

```php
class BusinessFlowComparisonComponent extends Component
{
    public function render()
    {
        $comparison = LivestockPurchaseConfig::getBusinessFlowComparison();

        return view('components.business-flow-comparison', [
            'comparison' => $comparison
        ]);
    }
}
```

## Business Rules Integration

### 1. Approval Rules

```php
// Check if approval is required for current flow
$flowType = $purchase->business_flow_type;
$requiresApproval = LivestockPurchaseConfig::isApprovalRequired($flowType);

if ($requiresApproval) {
    $this->requestApproval($purchase);
}
```

### 2. Batch Creation Rules

```php
// Check if batch creation is required
$requiresBatch = LivestockPurchaseConfig::requiresBatchCreation($flowType);

if ($requiresBatch && $purchase->status === 'in_coop') {
    $this->createBatch($purchase);
}
```

### 3. Document Requirements

```php
// Get document requirements level
$docLevel = LivestockPurchaseConfig::getDocumentRequirementsLevel($flowType);

switch ($docLevel) {
    case 'minimal':
        $requiredDocs = ['invoice'];
        break;
    case 'standard':
        $requiredDocs = ['invoice', 'delivery_order', 'purchase_order'];
        break;
    case 'comprehensive':
        $requiredDocs = ['invoice', 'delivery_order', 'purchase_order', 'health_certificate', 'quality_report'];
        break;
}
```

## Testing

### 1. Unit Tests

```php
class LivestockPurchaseBusinessFlowTest extends TestCase
{
    public function test_simple_flow_transitions()
    {
        $this->assertTrue(LivestockPurchaseConfig::canTransitionToWithFlow('draft', 'confirmed', 'simple'));
        $this->assertTrue(LivestockPurchaseConfig::canTransitionToWithFlow('confirmed', 'completed', 'simple'));
        $this->assertFalse(LivestockPurchaseConfig::canTransitionToWithFlow('draft', 'pending', 'simple'));
    }

    public function test_standard_flow_transitions()
    {
        $this->assertTrue(LivestockPurchaseConfig::canTransitionToWithFlow('draft', 'pending', 'standard'));
        $this->assertTrue(LivestockPurchaseConfig::canTransitionToWithFlow('pending', 'confirmed', 'standard'));
        $this->assertTrue(LivestockPurchaseConfig::canTransitionToWithFlow('pending', 'cancelled', 'standard'));
    }

    public function test_complex_flow_transitions()
    {
        $this->assertTrue(LivestockPurchaseConfig::canTransitionToWithFlow('arrived', 'in_coop', 'complex'));
        $this->assertTrue(LivestockPurchaseConfig::canTransitionToWithFlow('in_coop', 'completed', 'complex'));
        $this->assertFalse(LivestockPurchaseConfig::canTransitionToWithFlow('arrived', 'completed', 'complex'));
    }

    public function test_flow_recommendation()
    {
        $criteria = [
            'amount' => 5000000, // 5 juta
            'quantity' => 500,
            'supplier_rating' => 4.5,
            'is_urgent' => false,
            'requires_tracking' => false,
        ];

        $this->assertEquals('simple', LivestockPurchaseConfig::getRecommendedFlow($criteria));
    }
}
```

## Deployment Notes

### 1. Database Considerations

-   Ensure `livestock_purchases` table has `business_flow_type` column
-   Add indexes for `business_flow_type` and `status` columns
-   Consider adding `flow_version` for future flow updates

### 2. Migration Strategy

-   Default existing purchases to 'standard' flow
-   Provide migration script to update existing records
-   Add validation to ensure flow type is valid

### 3. Configuration Management

-   Store flow configuration in database for easy updates
-   Provide admin interface to modify flow settings
-   Version control for flow configurations

## Future Enhancements

### 1. Dynamic Flow Builder

-   Visual flow builder interface
-   Drag-and-drop status configuration
-   Real-time flow validation

### 2. Conditional Flows

-   Flow selection based on business rules
-   Dynamic status requirements
-   Conditional approvals

### 3. Flow Analytics

-   Flow performance metrics
-   Bottleneck identification
-   Process optimization suggestions

### 4. Multi-Company Flows

-   Company-specific flow configurations
-   Flow inheritance from parent company
-   Flow templates for different industries

## Conclusion

Business flow configuration memberikan fleksibilitas maksimal untuk menyesuaikan proses pembelian ternak dengan kebutuhan bisnis yang berbeda. Dengan 3 level kompleksitas (Simple, Standard, Complex), sistem dapat mengakomodasi berbagai skenario pembelian dari yang sederhana hingga yang kompleks.

Fitur ini memastikan bahwa setiap pembelian mengikuti proses yang sesuai dengan nilai, kompleksitas, dan risiko yang terlibat, sambil memberikan kemudahan dalam maintenance dan scaling sistem di masa depan.
