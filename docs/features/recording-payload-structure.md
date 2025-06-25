# Recording Payload Structure Documentation

## Overview

Dokumentasi ini menjelaskan struktur payload untuk sistem recording ternak yang telah direfactor untuk meningkatkan organisasi data, future-proofing, dan maintainability.

## Schema Version: 3.0

**Tanggal Release:** 23 Januari 2025  
**Backward Compatibility:** Mendukung versi 2.0 dan 3.0  
**Struktur:** Hierarchical Organized

## Struktur Payload

### 1. Metadata Section

```json
{
    "schema": {
        "version": "3.0",
        "schema_date": "2025-01-23",
        "compatibility": ["2.0", "3.0"],
        "structure": "hierarchical_organized"
    },
    "recording": {
        "timestamp": "2025-01-23T08:30:00+07:00",
        "date": "2025-01-23",
        "age_days": 45,
        "user": {
            "id": 6,
            "name": "Bo Bradtke",
            "role": "Operator",
            "company_id": "9f2098ea-1064-4ee2-94b3-9a308e21fa59"
        },
        "source": {
            "application": "livewire_records",
            "component": "Records",
            "method": "save",
            "version": "3.0"
        }
    }
}
```

### 2. Business Data Section

#### 2.1 Livestock Information

```json
{
    "livestock": {
        "basic_info": {
            "id": "9f34a470-0484-422a-8fca-5177c347951c",
            "name": "PR-DF01-K01-DF01-01062025",
            "strain": "Arbor Acres",
            "start_date": "2025-05-31T17:00:00.000000Z",
            "age_days": 45
        },
        "location": {
            "farm_id": "9f2098ea-1064-4ee2-94b3-9a308e21fa59",
            "farm_name": "Demo Farm",
            "coop_id": "9f2098ea-13d5-46c0-a845-616d2bad79e0",
            "coop_name": "Kandang 1 - Demo Farm"
        },
        "population": {
            "initial": 1000,
            "stock_start": 985,
            "stock_end": 980,
            "change": -5
        }
    }
}
```

#### 2.2 Production Data

```json
{
    "production": {
        "weight": {
            "yesterday": 2250.5,
            "today": 2280.0,
            "gain": 29.5,
            "unit": "grams"
        },
        "depletion": {
            "mortality": 3,
            "culling": 2,
            "total": 5
        },
        "sales": {
            "quantity": 0,
            "weight": 0.0,
            "price_per_unit": 0.0,
            "total_value": 0.0,
            "average_weight": 0.0
        }
    }
}
```

#### 2.3 Consumption Data

```json
{
    "consumption": {
        "feed": {
            "total_quantity": 150.5,
            "total_cost": 225750.0,
            "items": [
                {
                    "feed_id": "feed-001",
                    "quantity": 150.5,
                    "feed_name": "Starter Feed",
                    "feed_code": "SF-001",
                    "unit_id": "kg",
                    "unit_name": "Kilogram",
                    "price_per_unit": 1500.0,
                    "total_cost": 225750.0
                }
            ],
            "types_count": 1,
            "cost_per_kg": 1500.0
        },
        "supply": {
            "total_quantity": 5.0,
            "total_cost": 75000.0,
            "items": [
                {
                    "supply_id": "supply-001",
                    "quantity": 5.0,
                    "supply_name": "Vitamin Complex",
                    "supply_code": "VC-001",
                    "unit_name": "Liter",
                    "price_per_unit": 15000.0
                }
            ],
            "types_count": 1,
            "cost_per_unit": 15000.0
        }
    }
}
```

### 3. Performance Section

```json
{
    "performance": {
        "liveability": 98.0,
        "mortality_rate": 2.0,
        "fcr": 1.85,
        "feed_intake": 153.6,
        "adg": 50.67,
        "ip": 285.5,
        "weight_per_age": 50.67,
        "feed_per_day": 3.41,
        "depletion_per_day": 0.11,
        "calculated_at": "2025-01-23T08:30:00+07:00",
        "calculation_method": "standard_poultry_metrics"
    }
}
```

### 4. Historical Data Section

```json
{
    "history": {
        "weight": {
            "initial_weight": 45.0,
            "latest_weight": 2280.0,
            "total_gain": 2235.0,
            "average_daily_gain": 49.67,
            "weights": [
                {
                    "date": "2025-01-20",
                    "weight": 2180.0,
                    "age": 42
                }
            ],
            "gains": [
                {
                    "date": "2025-01-21",
                    "gain": 45.0,
                    "age": 43
                }
            ]
        },
        "feed": {
            "cumulative_feed_consumption": 6780.5,
            "feed_by_day": [
                {
                    "date": "2025-01-22",
                    "amount": 148.5
                }
            ],
            "feed_by_type": [
                {
                    "type": "Starter Feed",
                    "amount": 3400.5
                },
                {
                    "type": "Grower Feed",
                    "amount": 3380.0
                }
            ],
            "average_daily_consumption": 150.68
        },
        "population": {
            "initial_population": 1000,
            "current_population": 980,
            "total_mortality": 15,
            "total_culling": 5,
            "total_sales": 0,
            "mortality_rate": 1.5,
            "culling_rate": 0.5,
            "sales_rate": 0.0,
            "survival_rate": 98.0,
            "daily_changes": [
                {
                    "date": "2025-01-22",
                    "population": 985,
                    "mortality": 2,
                    "culling": 1,
                    "sales": 0,
                    "age": 44
                }
            ],
            "age_days": 45
        },
        "outflow": {
            "mortality": 15,
            "culling": 5,
            "sales": 0,
            "total": 20,
            "by_date": [
                {
                    "date": "2025-01-22",
                    "mortality": 2,
                    "culling": 1,
                    "sales": 0
                }
            ]
        }
    }
}
```

### 5. Environment Section (Extensible)

```json
{
    "environment": {
        "climate": {
            "temperature": 28.5,
            "humidity": 65.0,
            "pressure": 1013.25
        },
        "housing": {
            "lighting": "LED_16h",
            "ventilation": "auto",
            "density": 0.98
        },
        "water": {
            "consumption": 245.5,
            "quality": "good",
            "temperature": 25.0
        }
    }
}
```

### 6. Configuration Section

```json
{
    "config": {
        "manual_depletion_enabled": true,
        "manual_feed_usage_enabled": true,
        "recording_method": "total",
        "livestock_config": {
            "recording_method": "total",
            "depletion_method": "fifo",
            "mutation_method": "fifo",
            "feed_usage_method": "total"
        }
    }
}
```

### 7. Validation Section

```json
{
    "validation": {
        "data_quality": {
            "weight_logical": true,
            "population_logical": true,
            "feed_consumption_logical": true,
            "depletion_logical": true
        },
        "completeness": {
            "has_weight_data": true,
            "has_feed_data": true,
            "has_depletion_data": true,
            "has_supply_data": true
        }
    }
}
```

## Keunggulan Struktur Baru

### 1. **Hierarchical Organization**

-   Data diorganisir dalam section yang jelas dan logis
-   Memudahkan pencarian dan parsing data
-   Mengurangi konflik nama field

### 2. **Future-Proof Design**

-   Schema versioning yang jelas
-   Backward compatibility tracking
-   Extensible environment section
-   Validation built-in

### 3. **Better Data Integrity**

-   Validation section untuk quality control
-   Completeness checking
-   Data relationship tracking

### 4. **Enhanced Metadata**

-   Detailed source tracking
-   User information lengkap
-   Timestamp dan version tracking

### 5. **Business Logic Separation**

-   Production data terpisah dari metadata
-   Historical data terintegrasi
-   Performance metrics terkalibrasi

## Migration Guide

### Dari Version 2.0 ke 3.0

#### Field Mapping:

```php
// Version 2.0 -> Version 3.0
$old['mortality'] -> $new['production']['depletion']['mortality']
$old['feed_usage'] -> $new['consumption']['feed']['items']
$old['performance'] -> $new['performance'] (enhanced)
$old['recorded_by'] -> $new['recording']['user']['id']
$old['farm_id'] -> $new['livestock']['location']['farm_id']
```

#### Backward Compatibility Helper:

```php
public function getCompatibleData($version = '2.0') {
    if ($version === '2.0') {
        return $this->convertToV2Format();
    }
    return $this->payload;
}
```

## Sample Payload Lengkap

```json
{
    "schema": {
        "version": "3.0",
        "schema_date": "2025-01-23",
        "compatibility": ["2.0", "3.0"],
        "structure": "hierarchical_organized"
    },
    "recording": {
        "timestamp": "2025-01-23T08:30:00+07:00",
        "date": "2025-01-23",
        "age_days": 45,
        "user": {
            "id": 6,
            "name": "Bo Bradtke",
            "role": "Operator",
            "company_id": "9f2098ea-1064-4ee2-94b3-9a308e21fa59"
        },
        "source": {
            "application": "livewire_records",
            "component": "Records",
            "method": "save",
            "version": "3.0"
        }
    },
    "livestock": {
        "basic_info": {
            "id": "9f34a470-0484-422a-8fca-5177c347951c",
            "name": "PR-DF01-K01-DF01-01062025",
            "strain": "Arbor Acres",
            "start_date": "2025-05-31T17:00:00.000000Z",
            "age_days": 45
        },
        "location": {
            "farm_id": "9f2098ea-1064-4ee2-94b3-9a308e21fa59",
            "farm_name": "Demo Farm",
            "coop_id": "9f2098ea-13d5-46c0-a845-616d2bad79e0",
            "coop_name": "Kandang 1 - Demo Farm"
        },
        "population": {
            "initial": 1000,
            "stock_start": 985,
            "stock_end": 980,
            "change": -5
        }
    },
    "production": {
        "weight": {
            "yesterday": 2250.5,
            "today": 2280.0,
            "gain": 29.5,
            "unit": "grams"
        },
        "depletion": {
            "mortality": 3,
            "culling": 2,
            "total": 5
        },
        "sales": {
            "quantity": 0,
            "weight": 0.0,
            "price_per_unit": 0.0,
            "total_value": 0.0,
            "average_weight": 0.0
        }
    },
    "consumption": {
        "feed": {
            "total_quantity": 150.5,
            "total_cost": 225750.0,
            "items": [
                {
                    "feed_id": "feed-001",
                    "quantity": 150.5,
                    "feed_name": "Starter Feed",
                    "feed_code": "SF-001",
                    "unit_id": "kg",
                    "unit_name": "Kilogram",
                    "conversion_factor": 1,
                    "converted_quantity": 150.5,
                    "stock_prices": {
                        "min_price": 1400.0,
                        "max_price": 1600.0,
                        "average_price": 1500.0
                    }
                }
            ],
            "types_count": 1,
            "cost_per_kg": 1500.0
        },
        "supply": {
            "total_quantity": 5.0,
            "total_cost": 75000.0,
            "items": [
                {
                    "supply_id": "supply-001",
                    "quantity": 5.0,
                    "supply_name": "Vitamin Complex",
                    "supply_code": "VC-001"
                }
            ],
            "types_count": 1,
            "cost_per_unit": 15000.0
        }
    },
    "performance": {
        "liveability": 98.0,
        "mortality_rate": 2.0,
        "fcr": 1.85,
        "feed_intake": 153.6,
        "adg": 50.67,
        "ip": 285.5,
        "weight_per_age": 50.67,
        "feed_per_day": 3.41,
        "depletion_per_day": 0.11,
        "calculated_at": "2025-01-23T08:30:00+07:00",
        "calculation_method": "standard_poultry_metrics"
    },
    "history": {
        "weight": {
            "initial_weight": 45.0,
            "latest_weight": 2280.0,
            "total_gain": 2235.0,
            "average_daily_gain": 49.67,
            "weights": [],
            "gains": []
        },
        "feed": {
            "cumulative_feed_consumption": 6780.5,
            "feed_by_day": [],
            "feed_by_type": [],
            "average_daily_consumption": 150.68
        },
        "population": {
            "initial_population": 1000,
            "current_population": 980,
            "total_mortality": 15,
            "total_culling": 5,
            "total_sales": 0,
            "mortality_rate": 1.5,
            "culling_rate": 0.5,
            "sales_rate": 0.0,
            "survival_rate": 98.0,
            "daily_changes": [],
            "age_days": 45
        },
        "outflow": {
            "mortality": 15,
            "culling": 5,
            "sales": 0,
            "total": 20,
            "by_date": []
        }
    },
    "environment": {
        "climate": {
            "temperature": null,
            "humidity": null,
            "pressure": null
        },
        "housing": {
            "lighting": null,
            "ventilation": null,
            "density": null
        },
        "water": {
            "consumption": null,
            "quality": null,
            "temperature": null
        }
    },
    "config": {
        "manual_depletion_enabled": true,
        "manual_feed_usage_enabled": true,
        "recording_method": "total",
        "livestock_config": {
            "recording_method": "total",
            "depletion_method": "fifo",
            "mutation_method": "fifo",
            "feed_usage_method": "total"
        }
    },
    "validation": {
        "data_quality": {
            "weight_logical": true,
            "population_logical": true,
            "feed_consumption_logical": true,
            "depletion_logical": true
        },
        "completeness": {
            "has_weight_data": true,
            "has_feed_data": true,
            "has_depletion_data": true,
            "has_supply_data": false
        }
    }
}
```

## Best Practices

### 1. **Reading Payload Data**

```php
// Mengakses data dengan null safety
$mortality = $payload['production']['depletion']['mortality'] ?? 0;
$feedCost = $payload['consumption']['feed']['total_cost'] ?? 0;
$userRole = $payload['recording']['user']['role'] ?? 'Unknown';
```

### 2. **Validating Data Quality**

```php
$isValid = $payload['validation']['data_quality']['weight_logical'] ?? false;
$hasCompleteData = $payload['validation']['completeness']['has_weight_data'] ?? false;
```

### 3. **Version Checking**

```php
$version = $payload['schema']['version'] ?? '2.0';
if (in_array($version, ['2.0', '3.0'])) {
    // Process normally
} else {
    // Handle unknown version
}
```

### 4. **Extending Environment Data**

```php
// Future extension example
$payload['environment']['sensors'] = [
    'temperature_sensor_id' => 'TEMP_001',
    'humidity_sensor_id' => 'HUM_001',
    'readings_interval' => 300 // seconds
];
```

## Changelog

### Version 3.0 (2025-01-23)

-   **Added:** Hierarchical structure organization
-   **Added:** Schema versioning system
-   **Added:** Validation section
-   **Added:** Enhanced environment section
-   **Added:** Configuration tracking
-   **Improved:** Performance metrics with calculation metadata
-   **Improved:** User information with role and company tracking
-   **Improved:** Historical data organization
-   **Changed:** Flat structure to hierarchical sections
-   **Deprecated:** Direct field access (use section-based access)

### Version 2.0 (Previous)

-   Basic flat structure
-   Limited metadata
-   Basic performance tracking
