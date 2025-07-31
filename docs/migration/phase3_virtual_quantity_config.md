# 🔧 Virtual Quantity Calculation Configuration Documentation

## 📋 Overview

**Virtual Quantity Calculation** adalah fitur yang memungkinkan operator menyimpan data penjualan tanpa mempengaruhi stock real di database. Data virtual disimpan di kolom `data` pada tabel `livestock_batches` dan tidak mengubah nilai `quantity_available` yang real.

## 🎯 Business Requirements

### **Primary Goals:**

1. ✅ **Operator dapat input data penjualan** tanpa mengurangi stock real
2. ✅ **Data penjualan tersimpan** untuk keperluan recording dan reporting
3. ✅ **Stock real tetap aman** dan tidak terpengaruh oleh recording
4. ✅ **Fleksibilitas dalam perhitungan** berdasarkan berbagai metode

### **Business Flow:**

```
Operator Input Sales Data → Virtual Calculation → Store in Data Column → No Real Stock Impact
```

## ⚙️ Configuration Structure

### **1. Quantity Calculation Mode:**

```php
'quantity_calculation' => [
    'mode' => 'virtual', // 'virtual', 'real', 'hybrid'
    'virtual_settings' => [
        'enabled' => true,
        'description' => 'Virtual quantity calculation for recording without affecting real stock',
        'store_in_data_column' => true,
        'data_column_structure' => [
            'virtual_sales' => [
                'quantity' => 'int',
                'weight' => 'decimal',
                'date' => 'date',
                'status' => 'string',
                'metadata' => 'json'
            ],
            'virtual_depletion' => [
                'mortality' => 'int',
                'culling' => 'int',
                'date' => 'date',
                'status' => 'string',
                'metadata' => 'json'
            ]
        ],
    ]
]
```

### **2. Calculation Methods:**

#### **A. Projection Method:**

```php
'calculation_method' => 'projection',
'projection_settings' => [
    'enabled' => true,
    'base_on_historical_data' => true,
    'historical_days' => 7,
    'growth_rate_percentage' => 0,
    'seasonal_adjustment' => false,
]
```

#### **B. Estimate Method:**

```php
'calculation_method' => 'estimate',
'estimate_settings' => [
    'enabled' => false,
    'accuracy_threshold' => 95, // percentage
    'confidence_level' => 90, // percentage
]
```

#### **C. Forecast Method:**

```php
'calculation_method' => 'forecast',
'forecast_settings' => [
    'enabled' => false,
    'forecast_period_days' => 30,
    'trend_analysis' => true,
]
```

## 🏗️ Implementation Details

### **1. Service Architecture:**

```
VirtualQuantityCalculationService
├── calculateVirtualSalesQuantity()
├── getRealStockData()
├── calculateVirtualQuantity()
│   ├── calculateProjectionQuantity()
│   ├── calculateEstimateQuantity()
│   └── calculateForecastQuantity()
├── storeVirtualData()
├── getVirtualQuantityData()
└── clearVirtualData()
```

### **2. Data Storage Structure:**

#### **A. Livestock Batches Data Column:**

```json
{
    "virtual_sales": {
        "2025-07-22": {
            "quantity": 300,
            "weight": 9000.0,
            "date": "2025-07-22",
            "status": "draft",
            "metadata": {
                "calculation_method": "projection",
                "calculation_timestamp": "2025-07-27T14:30:00+07:00",
                "real_stock_reference": {
                    "total_available": 6960,
                    "total_initial_quantity": 7000,
                    "total_depletion": 40,
                    "total_sales": 0,
                    "batches_count": 1
                },
                "sales_data": {
                    "quantity": 300,
                    "weight": 9000
                }
            }
        }
    },
    "virtual_depletion": {
        "2025-07-22": {
            "mortality": 0,
            "culling": 0,
            "date": "2025-07-22",
            "status": "draft",
            "metadata": {
                "calculation_method": "manual",
                "calculation_timestamp": "2025-07-27T14:30:00+07:00"
            }
        }
    }
}
```

### **3. Real Stock Protection:**

#### **A. Before Virtual Calculation:**

```sql
-- Real stock remains unchanged
SELECT quantity_available FROM livestock_batches
WHERE livestock_id = '9f6f82e5-5a91-4843-9164-5f547bbf111a';
-- Result: 6960 (unchanged)
```

#### **B. After Virtual Calculation:**

```sql
-- Real stock still unchanged
SELECT quantity_available FROM livestock_batches
WHERE livestock_id = '9f6f82e5-5a91-4843-9164-5f547bbf111a';
-- Result: 6960 (still unchanged)

-- Virtual data stored in data column
SELECT data->'$.virtual_sales.2025-07-22.quantity' as virtual_quantity
FROM livestock_batches
WHERE livestock_id = '9f6f82e5-5a91-4843-9164-5f547bbf111a';
-- Result: 300 (virtual quantity)
```

## 🔄 Calculation Methods Explained

### **1. Projection Method:**

#### **Logic:**

```php
// Get historical sales data (last 7 days)
$historicalSales = getHistoricalSalesData($livestockId, $date, 7);

// Calculate average daily sales
$averageDailySales = calculateAverageDailySales($historicalSales);

// Apply growth rate
$projectedSales = $averageDailySales * (1 + ($growthRate / 100));

// Use provided sales data if available, otherwise use projection
$virtualQuantity = $salesData['quantity'] ?? $projectedSales;
```

#### **Example:**

```
Historical Sales (7 days): [100, 150, 200, 180, 220, 250, 300]
Average Daily Sales: 200
Growth Rate: 5%
Projected Sales: 200 * 1.05 = 210
Operator Input: 300
Final Virtual Quantity: 300 (uses operator input)
```

### **2. Estimate Method:**

#### **Logic:**

```php
// Simple estimation based on available stock
$availableStock = $realStockData['total_available'];
$estimatedQuantity = min($salesData['quantity'] ?? $availableStock * 0.1, $availableStock);
```

#### **Example:**

```
Available Stock: 6960
Estimation Percentage: 10%
Estimated Quantity: 6960 * 0.1 = 696
Operator Input: 300
Final Virtual Quantity: 300 (uses operator input)
```

### **3. Forecast Method:**

#### **Logic:**

```php
// Get extended historical data for trend analysis
$historicalSales = getHistoricalSalesData($livestockId, $date, 30);

// Calculate trend using linear regression
$trend = calculateSalesTrend($historicalSales);

// Forecast based on trend
$forecastedQuantity = applyTrendForecast($historicalSales, $trend);
```

#### **Example:**

```
Historical Sales (30 days): [trending upward]
Linear Regression: y = 5x + 100
Forecasted Quantity: 5 * 31 + 100 = 255
Operator Input: 300
Final Virtual Quantity: 300 (uses operator input)
```

## 📊 Data Flow Diagram

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Operator      │    │ VirtualQuantity  │    │ LivestockBatch  │
│   Input Sales   │───▶│ Calculation      │───▶│ Data Column     │
│   Data          │    │ Service          │    │ (JSON)          │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │ Real Stock       │
                       │ (Unchanged)      │
                       │ quantity_available│
                       └──────────────────┘
```

## 🔧 Configuration Examples

### **1. Basic Virtual Configuration:**

```php
'quantity_calculation' => [
    'mode' => 'virtual',
    'virtual_settings' => [
        'enabled' => true,
        'calculation_method' => 'projection',
        'projection_settings' => [
            'historical_days' => 7,
            'growth_rate_percentage' => 0,
        ]
    ]
]
```

### **2. Advanced Virtual Configuration:**

```php
'quantity_calculation' => [
    'mode' => 'virtual',
    'virtual_settings' => [
        'enabled' => true,
        'calculation_method' => 'forecast',
        'forecast_settings' => [
            'forecast_period_days' => 30,
            'trend_analysis' => true,
        ],
        'data_column_structure' => [
            'virtual_sales' => [
                'quantity' => 'int',
                'weight' => 'decimal',
                'date' => 'date',
                'status' => 'string',
                'metadata' => 'json'
            ]
        ]
    ]
]
```

### **3. Hybrid Configuration:**

```php
'quantity_calculation' => [
    'mode' => 'hybrid',
    'virtual_settings' => [
        'enabled' => true,
        'calculation_method' => 'projection',
    ],
    'real_settings' => [
        'enabled' => false,
    ],
    'hybrid_settings' => [
        'enabled' => true,
        'virtual_for_recording' => true,
        'real_for_finalization' => true,
        'transition_threshold' => 'finalization',
    ]
]
```

## ✅ Benefits

### **1. Data Safety:**

-   ✅ **Real stock protected** - Tidak ada perubahan pada `quantity_available`
-   ✅ **Data integrity maintained** - Stock real tetap akurat
-   ✅ **Rollback capability** - Virtual data dapat dihapus tanpa impact

### **2. Business Flexibility:**

-   ✅ **Operator freedom** - Bisa input data tanpa khawatir stock
-   ✅ **Recording accuracy** - Data penjualan tetap tercatat
-   ✅ **Multiple calculation methods** - Fleksibilitas dalam perhitungan

### **3. System Performance:**

-   ✅ **No database locks** - Tidak ada update pada stock real
-   ✅ **Fast operations** - Hanya update JSON data column
-   ✅ **Scalable solution** - Mudah di-extend untuk kebutuhan future

## 🚀 Usage Examples

### **1. Basic Usage:**

```php
$virtualService = app(VirtualQuantityCalculationService::class);

$result = $virtualService->calculateVirtualSalesQuantity(
    livestockId: '9f6f82e5-5a91-4843-9164-5f547bbf111a',
    date: '2025-07-22',
    salesData: [
        'quantity' => 300,
        'weight' => 9000
    ]
);
```

### **2. Get Virtual Data:**

```php
$virtualData = $virtualService->getVirtualQuantityData(
    livestockId: '9f6f82e5-5a91-4843-9164-5f547bbf111a',
    date: '2025-07-22'
);
```

### **3. Clear Virtual Data:**

```php
$virtualService->clearVirtualData(
    livestockId: '9f6f82e5-5a91-4843-9164-5f547bbf111a',
    date: '2025-07-22'
);
```

## 📝 Summary

**Status**: ✅ **VIRTUAL QUANTITY CALCULATION CONFIGURED**  
**Mode**: ✅ **Virtual calculation enabled**  
**Storage**: ✅ **Data column storage**  
**Protection**: ✅ **Real stock protected**  
**Flexibility**: ✅ **Multiple calculation methods**

**Sekarang operator dapat menyimpan data penjualan tanpa mempengaruhi stock real!** 🎯

---

_Virtual quantity calculation configuration completed: 2025-01-26_  
_Ready for Phase 4: Livewire Component Integration_
