# Performance Report Testing Guide

## Overview

Dokumen ini menjelaskan cara melakukan testing pada Performance Report Service dengan fokus pada fitur supply usage yang baru ditambahkan.

## Testing Routes

### 1. Test Performance Report

**URL**: `/test/performance-report/{livestockId?}`
**Method**: GET
**Parameters**:

-   `livestockId` (optional): ID livestock yang akan ditest (default: `9f577ca2-20b9-4038-ae75-19014c120d80`)
-   `date` (query parameter): Tanggal test (default: `2025-07-05`)

**Example**:

```
GET /test/performance-report/9f577ca2-20b9-4038-ae75-19014c120d80?date=2025-07-05
```

### 2. Debug Supply Usage

**URL**: `/test/debug-supply-usage/{livestockId?}`
**Method**: GET
**Parameters**:

-   `livestockId` (optional): ID livestock yang akan di-debug (default: `9f577ca2-20b9-4038-ae75-19014c120d80`)
-   `start_date` (query parameter): Tanggal mulai (default: `2025-01-01`)
-   `end_date` (query parameter): Tanggal akhir (default: `2025-07-05`)

**Example**:

```
GET /test/debug-supply-usage/9f577ca2-20b9-4038-ae75-19014c120d80?start_date=2025-01-01&end_date=2025-07-05
```

## Testing Steps

### Step 1: Debug Supply Usage Data

1. Buka browser dan akses URL debug supply usage
2. Periksa response untuk melihat:
    - Jumlah supply usage records
    - Status setiap record
    - Detail supply yang digunakan
    - Perhitungan cost

### Step 2: Test Performance Report

1. Akses URL test performance report
2. Periksa response untuk melihat:
    - Data livestock
    - Daily records dengan supply usage
    - Overall totals dengan supply cost
    - Supply by type breakdown

### Step 3: Analyze Results

1. Bandingkan data supply usage dengan yang ada di database
2. Periksa apakah cost calculation sudah benar
3. Verifikasi bahwa supply usage muncul di daily records
4. Pastikan overall totals sudah termasuk supply cost

## Expected Results

### Supply Usage Data

-   Supply usage records dengan status `pending`, `in_process`, atau `completed`
-   Detail supply dengan quantity, unit, dan cost calculation
-   Proper relationship antara SupplyUsage dan SupplyUsageDetail

### Performance Report

-   Daily records dengan field `supply_usage_by_type` dan `supply_total_cost`
-   Overall totals dengan `total_supply_cost` dan `supply_cost_per_head`
-   Supply breakdown dalam `supply_by_type`

## Troubleshooting

### Issue: No Supply Usage Data

**Possible Causes**:

1. Supply usage records tidak ada untuk livestock tersebut
2. Status supply usage tidak termasuk dalam filter (`pending`, `in_process`, `completed`)
3. Date range tidak mencakup supply usage records

**Solutions**:

1. Periksa database untuk supply usage records
2. Verifikasi status supply usage
3. Sesuaikan date range

### Issue: Incorrect Cost Calculation

**Possible Causes**:

1. `price_per_unit` tidak terisi
2. Supply price tidak ada
3. Unit conversion error

**Solutions**:

1. Periksa `price_per_unit` di SupplyUsageDetail
2. Verifikasi supply price di Supply model
3. Periksa unit relationship

### Issue: Supply Usage Not Appearing in Report

**Possible Causes**:

1. Method `getSupplyUsageData` tidak dipanggil
2. Data tidak diaggregasi dengan benar
3. Error dalam processing

**Solutions**:

1. Periksa log untuk error
2. Debug method `getSupplyUsageData`
3. Verifikasi data aggregation

## Debug Information

### Log Files

-   Check `storage/logs/laravel.log` untuk error messages
-   Look for "Testing Performance Report" dan "Debug Supply Usage" entries

### Database Queries

-   SupplyUsage: `SELECT * FROM supply_usages WHERE livestock_id = ? AND usage_date BETWEEN ? AND ?`
-   SupplyUsageDetail: `SELECT * FROM supply_usage_details WHERE supply_usage_id IN (...)`

### Key Methods

-   `getSupplyUsageData()`: Mengambil dan menghitung supply usage
-   `summarizeDailyRecords()`: Mengaggregasi daily records
-   `updateOverallTotals()`: Menambahkan supply cost ke overall totals

## Example Test Data

### Livestock Information

```json
{
    "id": "9f577ca2-20b9-4038-ae75-19014c120d80",
    "name": "Test Livestock",
    "coop": "Test Coop",
    "farm": "Test Farm",
    "start_date": "2025-01-01",
    "initial_quantity": 1000
}
```

### Expected Supply Usage

```json
{
    "supply_usages": {
        "count": 1,
        "records": [
            {
                "id": "uuid",
                "date": "2025-07-05",
                "status": "completed",
                "details": [
                    {
                        "supply_name": "Biocid",
                        "quantity": 10.5,
                        "unit": "ml",
                        "cost": 52500
                    }
                ]
            }
        ]
    }
}
```

## Next Steps

1. **Run Tests**: Akses test routes untuk memverifikasi functionality
2. **Check Data**: Periksa apakah supply usage data ada dan benar
3. **Debug Issues**: Gunakan debug routes untuk troubleshooting
4. **Fix Problems**: Perbaiki issues yang ditemukan
5. **Verify UI**: Pastikan data muncul di UI performance report
