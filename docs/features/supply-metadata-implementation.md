# Supply Metadata Implementation

## Overview

Implementasi sistem metadata untuk supply management yang reusable untuk purchasing dan mutation operations. Sistem ini memberikan foundation yang solid untuk tracking, audit trail, dan data integrity.

## Architecture

### 1. Core Services

#### SupplyMetadataService

-   **Purpose**: Service utama untuk membangun dan mengelola metadata
-   **Features**:
    -   Build metadata untuk purchase, inbound mutation, outbound mutation
    -   Add history entries dengan automatic tracking
    -   Update approval dan quality check information
    -   Validate metadata structure
    -   Generate batch codes

#### SupplyStockManagementService

-   **Purpose**: Service untuk mengelola supply stock dengan metadata integration
-   **Features**:
    -   Create stock entries dengan metadata
    -   Update stock quantities dengan history tracking
    -   Get stock summaries dengan metadata breakdown
    -   FIFO stock allocation
    -   Automatic CurrentSupply updates

### 2. Model Enhancements

#### SupplyStock Model

-   **Metadata Support**: Cast metadata sebagai array
-   **Helper Methods**: Accessor untuk metadata fields
-   **Scopes**: Purchase, mutation, available, FIFO
-   **Automatic Updates**: quantity_available calculation

## Usage Examples

### 1. Creating Purchase Stock with Metadata

```php
// Di Livewire component atau controller
$purchaseData = [
    'farm_id' => 'farm-1',
    'supply_id' => 'biodes-supply-id',
    'quantity' => 10.0,
    'purchase_id' => 'purchase-001',
    'invoice_number' => 'INV-2024-001',
    'supplier_id' => 'supplier-1',
    'supplier_name' => 'Supplier A',
    'purchase_date' => '2024-07-01',
    'delivery_date' => '2024-07-02',
    'payment_terms' => '30 days',
    'notes' => 'Regular monthly purchase',
    'quality_passed' => true,
    'quality_checked_by' => auth()->id(),
    'tags' => ['purchase', 'monthly'],
    'custom_fields' => [
        'delivery_method' => 'direct',
        'payment_status' => 'pending'
    ]
];

$result = $stockManagementService->createPurchaseStock($purchaseData);

if ($result['success']) {
    // Stock created with metadata
    $stock = $result['stock_entry'];
    $metadataSummary = $metadataService->getMetadataSummary($stock->metadata);

    // Access metadata fields
    echo $stock->batch_code; // PUR-ABC123-20240701
    echo $stock->source_type; // purchase
    echo $stock->approval_status; // pending_approval
    echo $stock->quality_status; // passed
}
```

### 2. Creating Inbound Mutation Stock

```php
$mutationData = [
    'from_farm_id' => 'farm-1',
    'to_farm_id' => 'farm-2',
    'supply_id' => 'biodes-supply-id',
    'quantity' => 5.0,
    'mutation_id' => 'mutation-001',
    'mutation_type' => 'transfer',
    'from_coop_id' => 'coop-1',
    'to_coop_id' => 'coop-2',
    'mutation_date' => '2024-07-05',
    'reason' => 'Transfer rutin bulanan',
    'notes' => 'Transfer dari Farm 1 ke Farm 2',
    'original_batch' => 'PUR-ABC123-20240701',
    'original_purchase_id' => 'purchase-001',
    'verification_required' => true,
    'tags' => ['mutation', 'transfer'],
    'custom_fields' => [
        'transfer_method' => 'manual',
        'approval_level' => 'manager'
    ]
];

$result = $stockManagementService->createInboundMutationStock($mutationData);
```

### 3. Updating Stock with History

```php
// Update stock quantity (usage, mutation, reservation)
$additionalData = [
    'usage_id' => 'usage-001',
    'livestock_id' => 'livestock-1',
    'notes' => 'Penggunaan untuk kandang A'
];

$result = $stockManagementService->updateStockQuantity(
    'stock-uuid-1',
    'usage',
    2.5,
    $additionalData
);

// Metadata akan otomatis diupdate dengan history entry
```

### 4. Getting Stock Summary with Metadata

```php
$summary = $stockManagementService->getStockSummary('farm-2', 'biodes-supply-id');

if ($summary['success']) {
    $data = $summary['summary'];

    echo "Total Records: " . $data['total_records'];
    echo "Total Available: " . $data['total_quantity_available'];
    echo "Purchase Records: " . $data['breakdown']['purchase_records'];
    echo "Mutation Records: " . $data['breakdown']['mutation_records'];

    // Metadata summaries untuk setiap stock
    foreach ($data['metadata_summaries'] as $metadataSummary) {
        echo "Batch: " . $metadataSummary['batch_code'];
        echo "Source: " . $metadataSummary['source_type'];
        echo "Available: " . $metadataSummary['quantity_available'];
        echo "Last Activity: " . $metadataSummary['last_activity'];
    }
}
```

### 5. FIFO Stock Allocation

```php
$fifoResult = $stockManagementService->getFifoStock('farm-2', 'biodes-supply-id', 3.0);

if ($fifoResult['success']) {
    $allocatedStocks = $fifoResult['allocated_stocks'];

    foreach ($allocatedStocks as $allocation) {
        echo "Stock ID: " . $allocation['stock_id'];
        echo "Batch: " . $allocation['batch_code'];
        echo "Allocated: " . $allocation['allocated_quantity'];
        echo "Source: " . $allocation['source_type'];
    }
}
```

## Metadata Structure

### Purchase Metadata

```json
{
    "source_type": "purchase",
    "purchase_id": "purchase-001",
    "invoice_number": "INV-2024-001",
    "supplier_id": "supplier-1",
    "supplier_name": "Supplier A",
    "batch_code": "PUR-ABC123-20240701",
    "purchase_date": "2024-07-01",
    "delivery_date": "2024-07-02",
    "payment_terms": "30 days",
    "notes": "Regular monthly purchase",
    "quality_check": {
        "passed": true,
        "checked_by": "user-1",
        "checked_at": "2024-07-01T10:00:00Z",
        "notes": null
    },
    "history": [
        {
            "date": "2024-07-01T10:00:00Z",
            "action": "purchase_created",
            "user_id": "user-1",
            "quantity": 10.0,
            "notes": "Initial purchase record"
        }
    ],
    "tags": ["purchase", "monthly"],
    "custom_fields": {
        "delivery_method": "direct",
        "payment_status": "pending"
    }
}
```

### Mutation Metadata

```json
{
    "source_type": "mutation",
    "mutation_id": "mutation-001",
    "mutation_type": "transfer",
    "from_farm_id": "farm-1",
    "from_coop_id": "coop-1",
    "batch_code": "MUT-DEF456-20240705",
    "original_batch": "PUR-ABC123-20240701",
    "original_purchase_id": "purchase-001",
    "mutation_date": "2024-07-05",
    "reason": "Transfer rutin bulanan",
    "notes": "Transfer dari Farm 1 ke Farm 2",
    "approval": {
        "approved_by": "user-2",
        "approved_at": "2024-07-05T09:00:00Z",
        "verification_required": true,
        "verified_by": "user-3",
        "verified_at": "2024-07-05T10:00:00Z"
    },
    "history": [
        {
            "date": "2024-07-05T08:00:00Z",
            "action": "inbound_mutation_created",
            "user_id": "user-1",
            "quantity": 5.0,
            "from_farm": "farm-1",
            "notes": "Inbound mutation record"
        }
    ],
    "tags": ["mutation", "transfer"],
    "custom_fields": {
        "transfer_method": "manual",
        "approval_level": "manager"
    }
}
```

## Benefits

### 1. Data Integrity

-   **Complete Audit Trail**: Setiap perubahan tercatat di history
-   **Source Tracking**: Asal supply selalu jelas (purchase/mutation)
-   **Batch Management**: Tracking per batch untuk FIFO/LIFO

### 2. Flexibility

-   **Custom Fields**: Bisa tambah field sesuai kebutuhan
-   **Tags System**: Kategorisasi dan filtering
-   **Extensible**: Mudah ditambah field baru

### 3. Performance

-   **Pre-calculated Values**: quantity_available tidak perlu kalkulasi ulang
-   **Efficient Queries**: Scope dan accessor untuk query optimal
-   **Cached Metadata**: Metadata summary untuk display cepat

### 4. Maintainability

-   **Modular Services**: Reusable untuk berbagai operasi
-   **Consistent Structure**: Metadata format konsisten
-   **Validation**: Built-in validation untuk metadata structure

## Integration Points

### 1. Existing Systems

-   **SupplyPurchase**: Integrasi dengan purchase workflow
-   **SupplyMutation**: Integrasi dengan mutation workflow
-   **SupplyUsage**: Integrasi dengan usage tracking
-   **CurrentSupply**: Automatic updates untuk summary

### 2. Future Extensions

-   **Quality Management**: Quality check workflow
-   **Approval System**: Approval dan verification workflow
-   **Reporting**: Metadata-based reporting
-   **Analytics**: Metadata untuk analytics dan insights

## Migration Strategy

### Phase 1: Foundation (Current)

-   ✅ Metadata service implementation
-   ✅ Stock management service
-   ✅ Model enhancements
-   ✅ Basic integration examples

### Phase 2: Integration

-   🔄 Update existing Livewire components
-   🔄 Update existing controllers
-   🔄 Database migration untuk kolom baru
-   🔄 Data migration untuk existing records

### Phase 3: Enhancement

-   ⏳ Advanced metadata features
-   ⏳ Performance optimization
-   ⏳ Advanced reporting
-   ⏳ Analytics integration

## Testing

### Unit Tests

```php
// Test metadata service
public function test_build_purchase_metadata()
{
    $service = new SupplyMetadataService();
    $data = [
        'purchase_id' => 'test-001',
        'quantity' => 10.0
    ];

    $metadata = $service->buildPurchaseMetadata($data);

    $this->assertEquals('purchase', $metadata['source_type']);
    $this->assertEquals('test-001', $metadata['purchase_id']);
    $this->assertNotEmpty($metadata['history']);
}

// Test stock management service
public function test_create_purchase_stock()
{
    $service = new SupplyStockManagementService(new SupplyMetadataService());
    $data = [
        'farm_id' => 'farm-1',
        'supply_id' => 'supply-1',
        'quantity' => 10.0,
        'purchase_id' => 'purchase-001'
    ];

    $result = $service->createPurchaseStock($data);

    $this->assertTrue($result['success']);
    $this->assertNotNull($result['stock_entry']);
}
```

## Conclusion

Implementasi metadata system memberikan foundation yang solid untuk supply management dengan:

-   **Complete tracking** dari purchase hingga usage
-   **Flexible structure** untuk berbagai kebutuhan
-   **Performance optimization** dengan pre-calculated values
-   **Maintainable code** dengan modular services
-   **Future-proof design** untuk extensibility

Sistem ini siap untuk integrasi dengan existing components dan future enhancements.
