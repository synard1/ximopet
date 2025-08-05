# Livestock Tracking Helper Methods Documentation

## Overview

Method helper telah ditambahkan di model `Livestock` dan `LivestockBatch` untuk memudahkan akses data tracking dari pembelian hingga mutasi. Method ini memanfaatkan struktur tracking yang sudah ada di tabel `livestock_batches` dengan field `source_type`, `source_id`, dan `livestock_purchase_item_id`.

## Last Updated

Date: 2025-01-27  
Time: 15:30 WIB

## Model Livestock Helper Methods

### 1. getPurchaseInfo()

Mendapatkan informasi pembelian dari batch yang memiliki `source_type = 'purchase'`.

```php
$livestock = Livestock::find($id);
$purchaseInfo = $livestock->getPurchaseInfo();

if ($purchaseInfo) {
    echo "Invoice: " . $purchaseInfo['invoice_number'];
    echo "Supplier: " . $purchaseInfo['supplier']->name;
    echo "Quantity: " . $purchaseInfo['quantity'];
    echo "Price per unit: " . $purchaseInfo['price_per_unit'];
}
```

**Return Value:**

```php
[
    'purchase' => LivestockPurchase,
    'purchase_item' => LivestockPurchaseItem,
    'batch' => LivestockBatch,
    'invoice_number' => string,
    'purchase_date' => Carbon,
    'supplier' => Partner,
    'quantity' => int,
    'price_per_unit' => float,
    'weight_per_unit' => float
]
```

### 2. getMutationHistory()

Mendapatkan riwayat mutasi dari batch yang memiliki `source_type = 'mutation'`.

```php
$livestock = Livestock::find($id);
$mutations = $livestock->getMutationHistory();

foreach ($mutations as $mutation) {
    echo "Date: " . $mutation['date'];
    echo "Quantity: " . $mutation['quantity'];
    echo "Direction: " . $mutation['direction'];
    echo "Type: " . $mutation['type'];
}
```

**Return Value:**

```php
[
    [
        'mutation' => LivestockMutation,
        'batch' => LivestockBatch,
        'quantity' => int,
        'date' => Carbon,
        'direction' => string,
        'type' => string,
        'source_livestock' => Livestock,
        'destination_livestock' => Livestock
    ]
]
```

### 3. getOriginChain()

Mendapatkan rantai asal lengkap dari pembelian hingga mutasi terakhir.

```php
$livestock = Livestock::find($id);
$chain = $livestock->getOriginChain();

foreach ($chain as $step) {
    echo "Type: " . $step['type'];
    echo "Date: " . $step['date'];
    echo "Quantity: " . $step['quantity'];

    if ($step['type'] === 'purchase') {
        echo "Invoice: " . $step['invoice'];
        echo "Supplier: " . $step['supplier'];
    } elseif ($step['type'] === 'mutation') {
        echo "Direction: " . $step['direction'];
        echo "Source: " . $step['source_livestock'];
        echo "Destination: " . $step['destination_livestock'];
    }
}
```

**Return Value:**

```php
[
    [
        'type' => 'purchase',
        'date' => Carbon,
        'invoice' => string,
        'supplier' => string,
        'quantity' => int,
        'price_per_unit' => float,
        'weight_per_unit' => float,
        'data' => array
    ],
    [
        'type' => 'mutation',
        'date' => Carbon,
        'quantity' => int,
        'direction' => string,
        'mutation_type' => string,
        'source_livestock' => string,
        'destination_livestock' => string,
        'data' => array
    ]
]
```

### 4. getOriginalPurchaseInfo()

Mendapatkan informasi pembelian asli (pembelian pertama dalam rantai).

```php
$livestock = Livestock::find($id);
$originalPurchase = $livestock->getOriginalPurchaseInfo();

if ($originalPurchase) {
    echo "Original Invoice: " . $originalPurchase['invoice_number'];
    echo "Original Supplier: " . $originalPurchase['supplier']->name;
}
```

### 5. isFromPurchase() / isFromMutation()

Mengecek apakah livestock berasal dari pembelian atau mutasi.

```php
$livestock = Livestock::find($id);

if ($livestock->isFromPurchase()) {
    echo "Livestock berasal dari pembelian";
}

if ($livestock->isFromMutation()) {
    echo "Livestock berasal dari mutasi";
}
```

### 6. getSourceType()

Mendapatkan tipe sumber (purchase, mutation, mixed, unknown).

```php
$livestock = Livestock::find($id);
$sourceType = $livestock->getSourceType();

switch ($sourceType) {
    case 'purchase':
        echo "Hanya dari pembelian";
        break;
    case 'mutation':
        echo "Hanya dari mutasi";
        break;
    case 'mixed':
        echo "Campuran pembelian dan mutasi";
        break;
    case 'unknown':
        echo "Sumber tidak diketahui";
        break;
}
```

### 7. getBatchesBySourceType()

Mendapatkan batch dikelompokkan berdasarkan tipe sumber.

```php
$livestock = Livestock::find($id);
$batchesBySource = $livestock->getBatchesBySourceType();

echo "Purchase batches: " . count($batchesBySource['purchase']);
echo "Mutation batches: " . count($batchesBySource['mutation']);
echo "Unknown batches: " . count($batchesBySource['unknown']);
```

### 8. getTrackingSummary()

Mendapatkan ringkasan lengkap informasi tracking.

```php
$livestock = Livestock::find($id);
$summary = $livestock->getTrackingSummary();

echo "Source type: " . $summary['source_type'];
echo "Total batches: " . $summary['total_batches'];
echo "Mutation count: " . $summary['mutation_count'];
echo "Has purchase info: " . ($summary['is_from_purchase'] ? 'Yes' : 'No');
```

### 9. Helper Methods untuk Informasi Spesifik

```php
$livestock = Livestock::find($id);

// Get purchase ID
$purchaseId = $livestock->getPurchaseId();

// Get invoice number
$invoiceNumber = $livestock->getInvoiceNumber();

// Get supplier info
$supplierInfo = $livestock->getSupplierInfo();
if ($supplierInfo) {
    echo "Supplier: " . $supplierInfo['name'];
    echo "Code: " . $supplierInfo['code'];
}

// Get expedition info
$expeditionInfo = $livestock->getExpeditionInfo();
if ($expeditionInfo) {
    echo "Expedition: " . $expeditionInfo['name'];
    echo "Fee: " . $expeditionInfo['fee'];
}
```

## Model LivestockBatch Helper Methods

### 1. getSourceObject()

Mendapatkan object sumber berdasarkan `source_type`.

```php
$batch = LivestockBatch::find($id);
$sourceObject = $batch->getSourceObject();

if ($sourceObject instanceof LivestockPurchase) {
    echo "Source is purchase: " . $sourceObject->invoice_number;
} elseif ($sourceObject instanceof LivestockMutation) {
    echo "Source is mutation: " . $sourceObject->id;
}
```

### 2. getPurchaseInfo() / getMutationInfo()

Mendapatkan informasi detail berdasarkan tipe sumber.

```php
$batch = LivestockBatch::find($id);

if ($batch->isFromPurchase()) {
    $purchaseInfo = $batch->getPurchaseInfo();
    echo "Invoice: " . $purchaseInfo['invoice_number'];
    echo "Supplier: " . $purchaseInfo['supplier']->name;
}

if ($batch->isFromMutation()) {
    $mutationInfo = $batch->getMutationInfo();
    echo "Direction: " . $mutationInfo['direction'];
    echo "Type: " . $mutationInfo['type'];
}
```

### 3. getSourceInfo()

Mendapatkan ringkasan informasi sumber.

```php
$batch = LivestockBatch::find($id);
$sourceInfo = $batch->getSourceInfo();

echo "Source type: " . $sourceInfo['source_type'];
echo "Source ID: " . $sourceInfo['source_id'];
echo "Is from purchase: " . ($sourceInfo['is_from_purchase'] ? 'Yes' : 'No');
echo "Is from mutation: " . ($sourceInfo['is_from_mutation'] ? 'Yes' : 'No');
```

### 4. Helper Methods untuk Informasi Spesifik

```php
$batch = LivestockBatch::find($id);

// Get purchase ID
$purchaseId = $batch->getPurchaseId();

// Get mutation ID
$mutationId = $batch->getMutationId();

// Get invoice number
$invoiceNumber = $batch->getInvoiceNumber();

// Get supplier info
$supplierInfo = $batch->getSupplierInfo();

// Get expedition info
$expeditionInfo = $batch->getExpeditionInfo();

// Get source livestock info (for mutations)
$sourceLivestockInfo = $batch->getSourceLivestockInfo();

// Get destination livestock info (for mutations)
$destinationLivestockInfo = $batch->getDestinationLivestockInfo();
```

### 5. getTrackingSummary()

Mendapatkan ringkasan lengkap tracking untuk batch.

```php
$batch = LivestockBatch::find($id);
$summary = $batch->getTrackingSummary();

echo "Batch: " . $summary['batch_name'];
echo "Livestock: " . $summary['livestock_name'];
echo "Source type: " . $summary['source_info']['source_type'];
echo "Available quantity: " . $summary['quantity_info']['quantity_available'];
echo "Farm: " . $summary['location_info']['farm_name'];
echo "Coop: " . $summary['location_info']['coop_name'];
```

## Usage Examples

### Example 1: Tracking Livestock dari Pembelian ke Mutasi

```php
$livestock = Livestock::find($id);

// Cek apakah ada informasi pembelian
if ($livestock->isFromPurchase()) {
    $purchaseInfo = $livestock->getPurchaseInfo();
    echo "Livestock ini berasal dari pembelian:";
    echo "- Invoice: " . $purchaseInfo['invoice_number'];
    echo "- Supplier: " . $purchaseInfo['supplier']->name;
    echo "- Quantity: " . $purchaseInfo['quantity'];
}

// Cek riwayat mutasi
$mutations = $livestock->getMutationHistory();
if (!empty($mutations)) {
    echo "Riwayat mutasi:";
    foreach ($mutations as $mutation) {
        echo "- " . $mutation['date']->format('Y-m-d') . ": " .
             $mutation['quantity'] . " ekor " . $mutation['direction'];
    }
}

// Dapatkan rantai asal lengkap
$chain = $livestock->getOriginChain();
echo "Rantai asal:";
foreach ($chain as $step) {
    echo "- " . $step['type'] . " (" . $step['date']->format('Y-m-d') . "): " .
         $step['quantity'] . " ekor";
}
```

### Example 2: Analisis Batch berdasarkan Sumber

```php
$livestock = Livestock::find($id);
$batchesBySource = $livestock->getBatchesBySourceType();

echo "Analisis batch untuk livestock: " . $livestock->name;

foreach ($batchesBySource as $sourceType => $batches) {
    echo "\n" . ucfirst($sourceType) . " batches (" . count($batches) . "):";

    foreach ($batches as $batch) {
        echo "- " . $batch->name . ": " . $batch->getQuantityAvailable() . " ekor tersedia";

        if ($batch->isFromPurchase()) {
            $purchaseInfo = $batch->getPurchaseInfo();
            echo " (Invoice: " . $purchaseInfo['invoice_number'] . ")";
        } elseif ($batch->isFromMutation()) {
            $mutationInfo = $batch->getMutationInfo();
            echo " (Mutation: " . $mutationInfo['direction'] . ")";
        }
    }
}
```

### Example 3: Validasi Data Tracking

```php
$livestock = Livestock::find($id);
$summary = $livestock->getTrackingSummary();

echo "Validasi tracking untuk livestock: " . $livestock->name;

// Cek konsistensi data
if ($summary['is_from_purchase'] && !$summary['purchase_info']) {
    echo "⚠️ Warning: Livestock marked as from purchase but no purchase info found";
}

if ($summary['mutation_count'] > 0 && empty($summary['origin_chain'])) {
    echo "⚠️ Warning: Livestock has mutations but no origin chain found";
}

// Cek kelengkapan data
if ($summary['source_type'] === 'unknown') {
    echo "⚠️ Warning: Livestock source type is unknown";
}

if ($summary['total_batches'] === 0) {
    echo "⚠️ Warning: Livestock has no batches";
}
```

## Performance Considerations

### 1. Eager Loading

Gunakan eager loading untuk menghindari N+1 query problem:

```php
// Good: Eager load relationships
$livestock = Livestock::with([
    'batches.sourcePurchase',
    'batches.sourceMutation',
    'batches.purchaseItem'
])->find($id);

// Bad: Lazy loading (N+1 queries)
$livestock = Livestock::find($id);
$purchaseInfo = $livestock->getPurchaseInfo(); // Will trigger additional queries
```

### 2. Caching

Untuk data yang sering diakses, pertimbangkan caching:

```php
$livestock = Livestock::find($id);

// Cache tracking summary for 1 hour
$summary = Cache::remember("livestock_tracking_{$livestock->id}", 3600, function () use ($livestock) {
    return $livestock->getTrackingSummary();
});
```

### 3. Batch Processing

Untuk multiple livestock, gunakan batch processing:

```php
$livestocks = Livestock::with([
    'batches.sourcePurchase',
    'batches.sourceMutation',
    'batches.purchaseItem'
])->get();

foreach ($livestocks as $livestock) {
    $summary = $livestock->getTrackingSummary();
    // Process summary...
}
```

## Error Handling

### 1. Null Safety

Selalu cek null sebelum mengakses data:

```php
$livestock = Livestock::find($id);
$purchaseInfo = $livestock->getPurchaseInfo();

if ($purchaseInfo && $purchaseInfo['supplier']) {
    echo "Supplier: " . $purchaseInfo['supplier']->name;
} else {
    echo "Supplier information not available";
}
```

### 2. Exception Handling

Tangani exception untuk relasi yang tidak ada:

```php
try {
    $livestock = Livestock::find($id);
    $chain = $livestock->getOriginChain();
} catch (Exception $e) {
    Log::error('Error getting origin chain', [
        'livestock_id' => $id,
        'error' => $e->getMessage()
    ]);
    $chain = [];
}
```

## Future Enhancements

### 1. Additional Source Types

Method helper dapat diperluas untuk source type baru:

```php
// Future: Support for 'transfer', 'adjustment', etc.
public function getSourceType(): string
{
    $sourceTypes = [
        'purchase' => $this->isFromPurchase(),
        'mutation' => $this->isFromMutation(),
        'transfer' => $this->isFromTransfer(), // Future
        'adjustment' => $this->isFromAdjustment() // Future
    ];

    $activeTypes = array_filter($sourceTypes);
    return count($activeTypes) > 1 ? 'mixed' : (key($activeTypes) ?: 'unknown');
}
```

### 2. Advanced Analytics

Tambahkan method untuk analisis lanjutan:

```php
// Future: Cost tracking
public function getCostAnalysis(): array
{
    // Calculate cost from purchase to current state
}

// Future: Performance tracking
public function getPerformanceMetrics(): array
{
    // Calculate growth rate, mortality rate, etc.
}
```

---

**Prepared by:** System Developer  
**Date:** 2025-01-27  
**Status:** Production Ready  
**Next Review:** Q2 2025
