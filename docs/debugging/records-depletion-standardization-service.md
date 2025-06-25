# Depletion Data Standardization Service - Documentation

**Date:** January 23, 2025  
**Service:** `app/Services/Livestock/DepletionDataStandardizationService.php`  
**Status:** ✅ CREATED & INTEGRATED

## 🎯 **OVERVIEW**

Service yang dibuat untuk mengatasi perbedaan struktur data antara metode manual/traditional dan FIFO dalam Records component. Service ini memastikan konsistensi metadata dan data structure untuk semua metode depletion.

## 🐛 **PROBLEM SOLVED**

### **Original Issue:**

```json
// Manual Method
{
  "jenis": "mortality",
  "metadata": { "processed_at": "...", "batch_metadata": {...} },
  "data": { "batch_id": "...", "depletion_method": "manual" }
}

// FIFO Method
{
  "jenis": "Mati",
  "metadata": { "coop_id": "...", "depletion_config": {...} },
  "data": null
}
```

### **After Standardization:**

```json
// Both Methods Now Have Consistent Structure
{
    "jenis": "mortality", // Normalized consistently
    "metadata": {
        "livestock_name": "...",
        "farm_id": "...",
        "depletion_config": {
            "normalized_type": "mortality",
            "legacy_type": "Mati",
            "display_name": "Kematian"
        },
        "processing_method": "standardization_service"
    },
    "data": {
        "batch_id": "...",
        "depletion_method": "fifo|manual",
        "data_structure_version": "3.0",
        "standardized": true
    }
}
```

## 🏗️ **SERVICE ARCHITECTURE**

### **Core Methods**

#### **1. standardizeFifoDepletionRecords()**

```php
public function standardizeFifoDepletionRecords(
    array $fifoRecords,
    Livestock $livestock,
    string $jenis,
    string $recordingId,
    int $age
): array
```

**Purpose:** Main entry point untuk standardisasi multiple FIFO records  
**Features:**

-   Batch processing multiple records
-   Comprehensive error handling
-   Detailed logging untuk debugging
-   Transaction-safe operations

#### **2. standardizeSingleRecord()**

```php
private function standardizeSingleRecord(
    $record,
    Livestock $livestock,
    string $jenis,
    string $recordingId,
    int $age
): ?array
```

**Purpose:** Standardisasi individual record  
**Features:**

-   Model/array flexible input
-   Type normalization menggunakan LivestockDepletionConfig
-   Database update dengan standardized structure
-   Null return untuk failed records

#### **3. buildStandardizedMetadata()**

```php
private function buildStandardizedMetadata(
    Livestock $livestock,
    string $normalizedType,
    string $recordingId,
    int $age
): array
```

**Purpose:** Build consistent metadata structure  
**Includes:**

-   Basic livestock information (farm, coop, kandang)
-   Age and timing information
-   Depletion configuration with normalization
-   Processing method tracking
-   Standardization versioning

#### **4. buildStandardizedData()**

```php
private function buildStandardizedData(
    array $recordData,
    Livestock $livestock,
    string $normalizedType
): array
```

**Purpose:** Build consistent data structure  
**Includes:**

-   Batch information extraction
-   Method identification (fifo/manual)
-   Quantity tracking
-   FIFO-specific data preservation
-   Structure versioning
-   Backward compatibility

### **Utility Methods**

#### **5. validateRecordConsistency()**

```php
public function validateRecordConsistency(
    array $standardizedRecords,
    Livestock $livestock
): array
```

**Purpose:** Validate standardized records quality  
**Returns:**

```php
[
    'valid' => true,
    'total_records' => 5,
    'valid_records' => 5,
    'invalid_records' => 0,
    'issues' => []
]
```

#### **6. getStandardizationStats()**

```php
public function getStandardizationStats(
    Livestock $livestock,
    string $period = '30_days'
): array
```

**Purpose:** Analytics untuk standardization quality  
**Returns:**

```php
[
    'total_records' => 100,
    'standardized_records' => 95,
    'fifo_records' => 60,
    'manual_records' => 40,
    'data_quality_score' => 95.0,
    'methods_breakdown' => [...]
]
```

## 🔧 **INTEGRATION WITH RECORDS.PHP**

### **Service Injection**

```php
// In Records.php constructor
public function __construct()
{
    $this->standardizationService = app(DepletionDataStandardizationService::class);
}
```

### **Usage in storeDeplesiWithDetails()**

```php
// After FIFO depletion processing
if ($fifoResult && $this->standardizationService) {
    $this->standardizationService->standardizeFifoDepletionRecords(
        [$fifoResult],
        $livestock,
        $jenis,
        $recordingId,
        $age
    );
}
```

## 📊 **DATA STRUCTURE STANDARDIZATION**

### **Metadata Standardization**

-   **Consistent Fields**: livestock_name, farm_id, coop_id, age_days
-   **Depletion Config**: Normalized types, legacy mapping, display names
-   **Processing Info**: Method tracking, standardization versioning
-   **Timing Info**: Updated timestamps, user tracking

### **Data Standardization**

-   **Batch Info**: Extracted from FIFO distribution or livestock batches
-   **Method Tracking**: Clear identification of processing method
-   **Quantity Info**: Original request vs processed quantity
-   **FIFO Preservation**: Original FIFO data maintained for audit
-   **Versioning**: Structure version untuk future compatibility

### **Type Normalization**

```php
// Using LivestockDepletionConfig
$normalizedType = LivestockDepletionConfig::normalize($jenis);
$legacyType = LivestockDepletionConfig::getLegacyType($normalizedType);
$displayName = LivestockDepletionConfig::getDisplayName($normalizedType);
```

## 🎯 **BENEFITS ACHIEVED**

### **1. Data Consistency**

-   ✅ Uniform metadata structure across all methods
-   ✅ Consistent field naming and types
-   ✅ Standardized depletion type handling

### **2. Improved Debugging**

-   ✅ Consistent logging format
-   ✅ Clear method identification
-   ✅ Comprehensive error tracking

### **3. Better Reporting**

-   ✅ Uniform data structure untuk reports
-   ✅ Consistent aggregation across methods
-   ✅ Quality metrics dan analytics

### **4. Future-Proof Design**

-   ✅ Versioned data structures
-   ✅ Backward compatibility preservation
-   ✅ Extensible untuk new methods

## 🧪 **TESTING & VALIDATION**

### **Test Scenarios**

1. **FIFO Records Standardization**

    - Multiple records dengan berbagai types
    - Records dengan missing fields
    - Records dengan invalid data

2. **Consistency Validation**

    - Required fields checking
    - Metadata structure validation
    - Data structure validation

3. **Statistics Generation**
    - Different time periods
    - Mixed method datasets
    - Quality score calculation

### **Quality Metrics**

-   **Data Quality Score**: Percentage of consistent records
-   **Method Distribution**: Breakdown by processing method
-   **Standardization Coverage**: Percentage of standardized records
-   **Error Tracking**: Issues identification dan resolution

## 🚀 **PERFORMANCE CONSIDERATIONS**

### **Optimizations**

-   **Batch Processing**: Multiple records dalam single operation
-   **Lazy Loading**: Service injection hanya saat diperlukan
-   **Efficient Queries**: Minimal database calls
-   **Memory Management**: Proper cleanup setelah processing

### **Monitoring**

-   **Execution Time**: Tracking processing duration
-   **Memory Usage**: Monitoring resource consumption
-   **Error Rates**: Tracking standardization failures
-   **Quality Trends**: Monitoring improvement over time

## 📝 **USAGE EXAMPLES**

### **Manual Integration**

```php
$service = app(DepletionDataStandardizationService::class);

// Standardize FIFO records
$standardizedRecords = $service->standardizeFifoDepletionRecords(
    $fifoRecords,
    $livestock,
    'mortality',
    $recordingId,
    25
);

// Validate consistency
$validation = $service->validateRecordConsistency($standardizedRecords, $livestock);

// Get statistics
$stats = $service->getStandardizationStats($livestock, '30_days');
```

### **Automated Integration**

```php
// In Records.php save process
if ($this->shouldUseFifoDepletion($livestock, $jenis)) {
    $fifoResult = $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock);

    // Auto-standardize FIFO results
    if ($fifoResult && $this->standardizationService) {
        $this->standardizationService->standardizeFifoDepletionRecords(
            [$fifoResult], $livestock, $jenis, $recordingId, $age
        );
    }
}
```

## 🔧 **MAINTENANCE & MONITORING**

### **Regular Tasks**

1. **Quality Monitoring**: Weekly review of data quality scores
2. **Error Analysis**: Monthly analysis of standardization failures
3. **Performance Review**: Quarterly performance optimization
4. **Structure Updates**: As-needed updates untuk new requirements

### **Alerting**

-   **Low Quality Score**: Alert when quality drops below 90%
-   **High Error Rate**: Alert when failures exceed 5%
-   **Performance Issues**: Alert when processing time exceeds thresholds

---

**Service ini memberikan foundation yang solid untuk data consistency dan quality dalam sistem depletion, dengan monitoring dan analytics yang comprehensive.**
