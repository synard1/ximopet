# RecordingDataAggregatorService

## 📋 Overview

`RecordingDataAggregatorService` adalah service yang robust, modular, dan future-proof untuk menghandle data recording dari berbagai sumber dengan konsistensi data yang akurat. Service ini dapat mengumpulkan dan menggabungkan data dari Feed Usage, Supply Usage, Depletion, dan Recording dengan validasi dan monitoring yang komprehensif.

## 🎯 Fitur Utama

### 1. **Multi-Source Data Aggregation**

-   **Feed Usage** (manual dan automated)
-   **Supply Usage** (dengan status filtering)
-   **Depletion** (mortality, culling, sales)
-   **Recording** (weight, population, performance metrics)

### 2. **Status-Based Filtering**

-   Hanya supply usage dengan status valid yang diproses:
    -   `pending`
    -   `in_process`
    -   `completed`
    -   `partially_used`
-   Status yang diabaikan: `draft`, `cancelled`, `rejected`, `expired`, `damaged`

### 3. **Priority-Based Data Merging**

-   **Recording** (priority: 100) - Highest authority
-   **Manual Feed/Supply Usage** (priority: 90)
-   **Automated Feed/Supply Usage** (priority: 80)
-   **Calculated Data** (priority: 50)
-   **Estimated Data** (priority: 30)
-   **Default Values** (priority: 10)

### 4. **Data Validation & Consistency**

-   Validasi input parameters
-   Validasi data quality dan completeness
-   Consistency checks antar data sources
-   Business rule validation

### 5. **Performance Monitoring**

-   Execution time tracking
-   Memory usage monitoring
-   Data source statistics
-   Performance bottleneck detection

## 🏗️ Architecture

### Interface

```php
interface RecordingDataAggregatorInterface
{
    public function aggregateData(string $livestockId, string $date, array $options = []): ServiceResult;
    public function getConfig(): array;
    public function updateConfig(array $newConfig): void;
    public function resetConfig(): void;
}
```

### Service Implementation

```php
class RecordingDataAggregatorService implements RecordingDataAggregatorInterface
{
    // Configuration constants
    private const VALID_SUPPLY_STATUSES = ['pending', 'in_process', 'completed', 'partially_used'];
    private const DATA_SOURCE_PRIORITIES = [
        'recording' => 100,
        'manual_feed_usage' => 90,
        'manual_supply_usage' => 90,
        // ... more priorities
    ];
}
```

## 📊 Data Flow

### 1. **Input Validation**

```php
// Validate livestock ID and date
$this->validateInputs($livestockId, $date);
$livestock = Livestock::findOrFail($livestockId);
```

### 2. **Data Collection**

```php
// Collect from all available sources
$dataSources = $this->collectDataSources($livestockId, $date, $livestock);
```

### 3. **Data Aggregation**

```php
// Aggregate and merge based on priority
$aggregatedData = $this->aggregateAndMergeData($dataSources, $livestock, $date);
```

### 4. **Validation**

```php
// Validate aggregated data
if ($this->config['enable_data_validation']) {
    $validationResult = $this->validateAggregatedData($aggregatedData, $livestock);
}
```

### 5. **Payload Building**

```php
// Build final payload with schema v3.0
$payload = $this->buildPayload($aggregatedData, $livestock, $date, $options);
```

## 🔧 Configuration

### Default Configuration

```php
$config = [
    'enable_status_filtering' => true,
    'enable_data_validation' => true,
    'enable_consistency_checks' => true,
    'enable_performance_monitoring' => true,
    'default_values' => [
        'weight_gain' => 0,
        'mortality' => 0,
        'culling' => 0,
        'sales_quantity' => 0,
        'feed_consumption' => 0,
        'supply_consumption' => 0
    ],
    'validation_rules' => [
        'max_weight_gain_per_day' => 100, // grams
        'max_mortality_rate' => 0.1, // 10%
        'max_feed_consumption_per_chicken' => 0.2, // kg
        'max_supply_consumption_per_chicken' => 0.01 // L
    ]
];
```

### Custom Configuration

```php
$service = new RecordingDataAggregatorService([
    'enable_status_filtering' => false,
    'validation_rules' => [
        'max_weight_gain_per_day' => 150,
        'max_mortality_rate' => 0.15
    ]
]);
```

## 📈 Output Payload Structure

### Schema v3.0 Format

```json
{
    "schema": {
        "version": "3.0",
        "structure": "hierarchical_organized",
        "schema_date": "2025-01-25",
        "compatibility": ["2.0", "3.0"]
    },
    "livestock": {
        "location": {
            "coop_id": "uuid",
            "farm_id": "uuid",
            "coop_name": "string",
            "farm_name": "string"
        },
        "basic_info": {
            "id": "uuid",
            "name": "string",
            "strain": "string",
            "age_days": 31,
            "start_date": "2025-05-31T17:00:00.000000Z"
        },
        "population": {
            "stock_start": 12970,
            "stock_end": 12945,
            "change": -25
        }
    },
    "recording": {
        "date": "2025-07-02",
        "user": {
            "id": "uuid",
            "name": "string",
            "role": "string",
            "company_id": "uuid"
        },
        "source": {
            "method": "aggregated",
            "version": "3.0",
            "component": "RecordingDataAggregatorService",
            "application": "service_aggregator"
        },
        "age_days": 31,
        "timestamp": "2025-07-19T11:08:15+07:00"
    },
    "production": {
        "sales": {
            "weight": 0,
            "quantity": 0,
            "total_value": 0,
            "average_weight": 0,
            "price_per_unit": 0
        },
        "weight": {
            "today": 50,
            "yesterday": 40,
            "gain": 10
        },
        "depletion": {
            "total": 25,
            "culling": 0,
            "mortality": 25
        }
    },
    "consumption": {
        "feed": {
            "items": [...],
            "total_cost": 6500,
            "cost_per_kg": 0.5,
            "types_count": 1,
            "total_quantity": 1
        },
        "supply": {
            "items": [...],
            "total_cost": 24000,
            "types_count": 1,
            "cost_per_unit": 1.85,
            "total_quantity": 15
        }
    },
    "performance": {
        "ip": 647.25,
        "fcr": 0.015,
        "liveability": 99.58,
        "calculated_at": "2025-07-19T11:08:15+07:00",
        "calculation_method": "standard_poultry_metrics"
    },
    "validation": {
        "completeness": {
            "has_feed_data": true,
            "has_supply_data": true,
            "has_weight_data": true,
            "has_depletion_data": true
        },
        "data_quality": {
            "weight_logical": true,
            "depletion_logical": true,
            "population_logical": true,
            "feed_consumption_logical": true
        }
    },
    "metadata": {
        "aggregated_at": "2025-07-19T11:08:15+07:00",
        "data_sources": ["recording", "feed_usage", "supply_usage", "depletion"],
        "service_version": "1.0",
        "aggregation_method": "priority_based_merge"
    }
}
```

## 🚀 Usage Examples

### Basic Usage

```php
use App\Services\Recording\RecordingDataAggregatorService;

$service = new RecordingDataAggregatorService();
$result = $service->aggregateData('livestock-id', '2025-07-02');

if ($result->isSuccess()) {
    $payload = $result->getData();
    // Process payload
} else {
    $error = $result->getMessage();
    // Handle error
}
```

### With Custom Configuration

```php
$service = new RecordingDataAggregatorService([
    'enable_status_filtering' => true,
    'enable_data_validation' => true,
    'validation_rules' => [
        'max_weight_gain_per_day' => 120,
        'max_mortality_rate' => 0.08
    ]
]);

$result = $service->aggregateData('livestock-id', '2025-07-02', [
    'include_calculated_metrics' => true,
    'force_recalculation' => false
]);
```

### Configuration Management

```php
// Get current configuration
$config = $service->getConfig();

// Update configuration
$service->updateConfig([
    'enable_performance_monitoring' => false
]);

// Reset to defaults
$service->resetConfig();
```

## 🔍 Data Source Details

### 1. **Recording Data**

-   **Source**: `Recording` model
-   **Priority**: 100 (highest)
-   **Fields**: weight, population, payload data
-   **Validation**: Schema compatibility check

### 2. **Feed Usage Data**

-   **Source**: `FeedUsage` + `FeedUsageDetail` models
-   **Priority**: 90 (manual) / 80 (automated)
-   **Fields**: consumption items, costs, quantities
-   **Status Filtering**: N/A (always included)

### 3. **Supply Usage Data**

-   **Source**: `SupplyUsage` + `SupplyUsageDetail` models
-   **Priority**: 90 (manual) / 80 (automated)
-   **Fields**: consumption items, costs, quantities
-   **Status Filtering**: Only valid statuses included

### 4. **Depletion Data**

-   **Source**: `LivestockDepletion` model
-   **Priority**: 90 (manual) / 80 (automated)
-   **Fields**: mortality, culling, sales
-   **Validation**: Logical consistency checks

### 5. **Calculated Data**

-   **Source**: Derived from other sources
-   **Priority**: 50
-   **Fields**: age, performance metrics, estimates
-   **Validation**: Business rule compliance

## 🛡️ Error Handling

### Exception Types

1. **Input Validation Errors**

    - Invalid livestock ID
    - Invalid date format
    - Missing required parameters

2. **Data Validation Errors**

    - Weight gain exceeds limits
    - Mortality rate too high
    - Consumption per chicken excessive

3. **Relationship Errors**
    - Missing model relationships
    - Database connection issues
    - Query execution failures

### Error Response Format

```php
ServiceResult::error('Error message', [
    'validation_errors' => [...],
    'data_source_errors' => [...],
    'performance_metrics' => [...]
]);
```

## 📊 Performance Monitoring

### Metrics Tracked

-   **Execution Time**: Total processing time
-   **Memory Usage**: Peak and current memory
-   **Data Source Count**: Number of sources processed
-   **Payload Size**: Final payload size in bytes
-   **Validation Results**: Success/failure rates

### Performance Logging

```php
Log::info("Performance: aggregateData", [
    'execution_time' => 0.125,
    'memory_usage' => 1048576,
    'peak_memory' => 2097152,
    'data_sources_count' => 4,
    'payload_size' => 2048
]);
```

## 🔮 Future Enhancements

### Planned Features

1. **Caching Layer**

    - Redis-based caching for aggregated data
    - Cache invalidation strategies
    - Performance optimization

2. **Advanced Validation**

    - Machine learning-based anomaly detection
    - Historical data comparison
    - Predictive validation rules

3. **Data Source Extensions**

    - Weather data integration
    - Equipment sensor data
    - External API integrations

4. **Real-time Processing**
    - Event-driven aggregation
    - WebSocket notifications
    - Real-time dashboard updates

### Extensibility Points

1. **Custom Data Sources**

    - Interface for new data sources
    - Plugin architecture
    - Custom validation rules

2. **Output Formats**

    - Multiple schema versions
    - Custom payload structures
    - Export formats (JSON, XML, CSV)

3. **Integration Hooks**
    - Pre-aggregation hooks
    - Post-aggregation hooks
    - Custom business logic injection

## 📝 Best Practices

### 1. **Configuration Management**

-   Use environment-specific configurations
-   Validate configuration on service instantiation
-   Provide sensible defaults

### 2. **Error Handling**

-   Always check ServiceResult success status
-   Log detailed error information
-   Provide user-friendly error messages

### 3. **Performance Optimization**

-   Monitor execution times in production
-   Use caching for frequently accessed data
-   Optimize database queries with eager loading

### 4. **Data Validation**

-   Enable validation in production
-   Customize validation rules per business needs
-   Monitor validation failure rates

### 5. **Logging and Monitoring**

-   Enable performance monitoring
-   Log data source statistics
-   Monitor service health metrics

## 🔧 Troubleshooting

### Common Issues

1. **Service Fails to Start**

    - Check model relationships exist
    - Verify database connections
    - Validate configuration parameters

2. **Data Aggregation Fails**

    - Check data source availability
    - Verify status filtering rules
    - Review validation error messages

3. **Performance Issues**

    - Monitor execution times
    - Check memory usage
    - Optimize database queries

4. **Validation Errors**
    - Review business rules
    - Check data quality
    - Adjust validation thresholds

### Debug Mode

```php
$service = new RecordingDataAggregatorService([
    'enable_performance_monitoring' => true,
    'enable_data_validation' => false, // Disable for debugging
    'debug_mode' => true
]);
```

## 📚 Related Documentation

-   [Supply Usage Status Implementation](../features/supply-usage-status-implementation.md)
-   [Recording Data Service](../services/recording-data-service.md)
-   [Livestock Cost Service](../services/livestock-cost-service.md)
-   [Service Result DTOs](../dto/service-result.md)

---

**Version**: 1.0  
**Last Updated**: 2025-01-25  
**Maintainer**: Development Team
