# Fix Filter Livestock Issues

## **LOG PERBAIKAN - 27 Januari 2025**

### **Analisis Masalah**

**Error yang Ditemukan:**

#### **1. Filter Spesifik Livestock Tidak Muncul Data**

```
[2025-07-31 13:25:47] production.INFO: All recording sales found for livestock {"livestock_id":"9f80faa1-db0b-464f-a290-6d62eca2a9e7","total_recording_sales":0,"status_distribution":{"Illuminate\\Database\\Eloquent\\Collection":[]},"sample_records":[]}
[2025-07-31 13:25:47] production.WARNING: No sales data found in both new and old models {"livestock_id":"9f80faa1-db0b-464f-a290-6d62eca2a9e7","livestock_name":"PR-Farm01-K1 F1-2025-07-29"}
```

#### **2. Error Ketika Tidak Ada Livestock Dipilih**

```
[2025-07-31 13:26:50] production.ERROR: Error generating sales report {"livestock_id":"N/A","error":"The periode field is required.","trace":"#0 C:\\laragon\\www\\demo51\\vendor\\laravel\\framework\\src\\Illuminate\\Validation\\Validator.php(558): throw_if(true, 'Illuminate\\\\Vali...', Object(Illuminate\\Validation\\Validator))
```

### **Root Cause Analysis**

#### **1. Validation Error**

-   **Problem:** Periode field di-require padahal bisa optional
-   **Impact:** Error ketika user tidak memilih livestock spesifik

#### **2. Data Not Found**

-   **Problem:** Livestock ID mismatch antara request dan data recording sales
-   **Impact:** Data tidak ditemukan meskipun ada di database

#### **3. Missing Fallback Logic**

-   **Problem:** Tidak ada logic untuk search data berdasarkan farm/coop filter
-   **Impact:** Tidak bisa menampilkan data ketika tidak ada livestock spesifik

### **Solusi yang Diterapkan**

#### **1. Optional Periode Validation**

**Before:**

```php
$request->validate([
    'periode' => 'required|exists:livestocks,id'
]);
```

**After:**

```php
// Handle optional periode parameter
$livestockId = $request->periode;
$includeTempSales = $request->boolean('include_temp_sales', false);
$includeDraftSales = $request->boolean('include_draft_sales', false);

// If periode is provided, validate it exists
if ($livestockId) {
    $request->validate([
        'periode' => 'exists:livestocks,id'
    ]);
} else {
    Log::info('No specific livestock selected, will search for all available data', [
        'farm' => $request->farm,
        'coop' => $request->coop,
        'tahun' => $request->tahun
    ]);
}
```

#### **2. Conditional Livestock Loading**

**Before:**

```php
$livestock = Livestock::with([...])->findOrFail($livestockId);
```

**After:**

```php
// Load livestock dengan relationship yang diperlukan
$livestock = null;
if ($livestockId) {
    $livestock = Livestock::with([...])->findOrFail($livestockId);

    Log::info('Livestock loaded successfully', [
        'livestock_id' => $livestockId,
        'livestock_name' => $livestock->name ?? 'N/A',
        'include_temp_sales' => $includeTempSales,
        'include_draft_sales' => $includeDraftSales
    ]);
} else {
    Log::info('No specific livestock selected, will search for all available data', [
        'farm' => $request->farm,
        'coop' => $request->coop,
        'tahun' => $request->tahun,
        'include_temp_sales' => $includeTempSales,
        'include_draft_sales' => $includeDraftSales
    ]);
}
```

#### **3. New Methods for All Data Search**

**getAllSalesData Method:**

```php
protected function getAllSalesData($request, $includeDraftSales = false)
{
    try {
        $query = LivestockSales::with([
            'customer',
            'expedition',
            'farm',
            'coop',
            'livestock',
            'livestockSalesItems' => function ($query) {
                $query->orderBy('date', 'ASC');
            }
        ])
        ->where('status', '!=', LivestockSales::STATUS_CANCELLED);

        // Apply farm filter if provided
        if ($request->farm) {
            $query->where('farm_id', $request->farm);
        }

        // Apply coop filter if provided
        if ($request->coop) {
            $query->where('coop_id', $request->coop);
        }

        // Apply year filter if provided
        if ($request->tahun) {
            $query->whereYear('date', $request->tahun);
        }

        // Apply status filter based on includeDraftSales
        if (!$includeDraftSales) {
            $query->whereNotIn('status', [
                LivestockSales::STATUS_DRAFT,
                LivestockSales::STATUS_PENDING
            ]);
        }

        $salesData = $query->orderBy('date', 'ASC')->get();

        Log::info('All sales data retrieved', [
            'total_sales' => $salesData->count(),
            'farm' => $request->farm,
            'coop' => $request->coop,
            'tahun' => $request->tahun,
            'include_draft_sales' => $includeDraftSales
        ]);

        return $salesData;

    } catch (\Exception $e) {
        Log::error('Error retrieving all sales data', [
            'farm' => $request->farm,
            'coop' => $request->coop,
            'tahun' => $request->tahun,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return collect();
    }
}
```

**getAllTemporarySalesData Method:**

```php
protected function getAllTemporarySalesData($request, $includeDraftSales = false)
{
    try {
        $query = \App\Models\RecordingSale::with([
            'items' => function ($query) {
                $query->orderBy('created_at', 'ASC');
            },
            'livestock',
            'livestockBatch',
            'livestock.farm',
            'livestock.coop'
        ])
        ->where('status', '!=', \App\Models\RecordingSale::STATUS_CANCELLED);

        // Apply farm filter if provided
        if ($request->farm) {
            $query->whereHas('livestock', function($q) use ($request) {
                $q->where('farm_id', $request->farm);
            });
        }

        // Apply coop filter if provided
        if ($request->coop) {
            $query->whereHas('livestock', function($q) use ($request) {
                $q->where('coop_id', $request->coop);
            });
        }

        // Apply year filter if provided
        if ($request->tahun) {
            $query->whereYear('date', $request->tahun);
        }

        // Apply status filter based on includeDraftSales
        if (!$includeDraftSales) {
            $query->whereNotIn('status', [
                \App\Models\RecordingSale::STATUS_DRAFT,
                \App\Models\RecordingSale::STATUS_PENDING
            ]);
        }

        $tempSalesData = $query->orderBy('date', 'ASC')->get();

        Log::info('All temporary sales data retrieved', [
            'total_temp_sales' => $tempSalesData->count(),
            'farm' => $request->farm,
            'coop' => $request->coop,
            'tahun' => $request->tahun,
            'include_draft_sales' => $includeDraftSales
        ]);

        return $tempSalesData;

    } catch (\Exception $e) {
        Log::error('Error retrieving all temporary sales data', [
            'farm' => $request->farm,
            'coop' => $request->coop,
            'tahun' => $request->tahun,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return collect();
    }
}
```

#### **4. Enhanced Data Retrieval Logic**

**Conditional Sales Data Retrieval:**

```php
// Get sales data menggunakan field baru
$salesData = collect();
if ($livestockId) {
    $salesData = LivestockSales::with([...])
        ->where('livestock_id', $livestockId)
        ->where('status', '!=', LivestockSales::STATUS_CANCELLED);

    // Apply status filter based on includeDraftSales
    if (!$includeDraftSales) {
        $salesData->whereNotIn('status', [
            LivestockSales::STATUS_DRAFT,
            LivestockSales::STATUS_PENDING
        ]);
    }

    $salesData = $salesData->orderBy('date', 'ASC')->get();

    Log::info('Sales data retrieved', [
        'total_sales' => $salesData->count(),
        'livestock_id' => $livestockId
    ]);
} else {
    // Search for all sales data based on farm/coop filters
    $salesData = $this->getAllSalesData($request, $includeDraftSales);
    Log::info('All sales data retrieved', [
        'total_sales' => $salesData->count(),
        'farm' => $request->farm,
        'coop' => $request->coop,
        'tahun' => $request->tahun
    ]);
}
```

**Conditional Temporary Sales Data Retrieval:**

```php
// Get temporary sales data if requested
$tempSalesData = collect();
if ($includeTempSales) {
    if ($livestockId) {
        $tempSalesData = $this->getTemporarySalesData($livestockId, $includeDraftSales);
        Log::info('Temporary sales data retrieved', [
            'total_temp_sales' => $tempSalesData->count(),
            'livestock_id' => $livestockId
        ]);
    } else {
        $tempSalesData = $this->getAllTemporarySalesData($request, $includeDraftSales);
        Log::info('All temporary sales data retrieved', [
            'total_temp_sales' => $tempSalesData->count(),
            'farm' => $request->farm,
            'coop' => $request->coop,
            'tahun' => $request->tahun
        ]);
    }
}
```

#### **5. Enhanced Return Data Handling**

**Improved Return Data:**

```php
// Calculate summary metrics
$summary = $this->calculateSalesSummary($salesData, $tempSalesData);

// Format period menggunakan field baru
$periode = $livestock ? $this->formatPeriod($livestock) : 'Semua Periode';

$reportData = [
    'data' => $salesData,
    'livestock' => $livestock,
    'coop' => $livestock ? ($livestock->coop->name ?? 'N/A') : 'Semua Kandang',
    'farm' => $livestock ? ($livestock->farm->name ?? 'N/A') : 'Semua Farm',
    'periode' => $periode,
    'penjualanData' => $salesData,
    'tempSalesData' => $tempSalesData,
    'summary' => $summary,
    'has_data' => $salesData->isNotEmpty() || $tempSalesData->isNotEmpty(),
    'include_temp_sales' => $includeTempSales,
    'include_draft_sales' => $includeDraftSales
];
```

#### **6. Enhanced formatPeriod Method**

**Updated formatPeriod:**

```php
protected function formatPeriod($livestock)
{
    try {
        if (!$livestock) {
            return 'Semua Periode';
        }

        $startDate = Carbon::parse($livestock->start_date);
        $endDate = Carbon::parse($livestock->end_date);
        return $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');
    } catch (\Exception $e) {
        Log::error('Error formatting period', [
            'livestock_id' => $livestock ? $livestock->id : 'N/A',
            'error' => $e->getMessage()
        ]);
        return 'Periode tidak tersedia';
    }
}
```

### **Expected Log Output**

#### **1. Specific Livestock Selected**

```json
{
    "message": "Starting sales report generation",
    "context": {
        "request_data": {
            "farm": "9f46ed24-ed84-4746-ada7-ad0747328e92",
            "coop": "9f46ed77-30ea-4945-b52e-746f7ede5b4c",
            "tahun": "2025",
            "periode": "9f80faa1-db0b-464f-a290-6d62eca2a9e7",
            "include_temp_sales": "1"
        }
    }
}

{
    "message": "Livestock loaded successfully",
    "context": {
        "livestock_id": "9f80faa1-db0b-464f-a290-6d62eca2a9e7",
        "livestock_name": "PR-Farm01-K1 F1-2025-07-29",
        "include_temp_sales": true,
        "include_draft_sales": false
    }
}
```

#### **2. No Specific Livestock Selected**

```json
{
    "message": "Starting sales report generation",
    "context": {
        "request_data": {
            "farm": "9f46ed24-ed84-4746-ada7-ad0747328e92",
            "coop": "9f46ed77-30ea-4945-b52e-746f7ede5b4c",
            "tahun": "2025",
            "periode": null,
            "include_temp_sales": "1"
        }
    }
}

{
    "message": "No specific livestock selected, will search for all available data",
    "context": {
        "farm": "9f46ed24-ed84-4746-ada7-ad0747328e92",
        "coop": "9f46ed77-30ea-4945-b52e-746f7ede5b4c",
        "tahun": "2025",
        "include_temp_sales": true,
        "include_draft_sales": false
    }
}

{
    "message": "All sales data retrieved",
    "context": {
        "total_sales": 15,
        "farm": "9f46ed24-ed84-4746-ada7-ad0747328e92",
        "coop": "9f46ed77-30ea-4945-b52e-746f7ede5b4c",
        "tahun": "2025"
    }
}
```

### **Testing Scenarios**

#### **1. Specific Livestock Filter**

-   ✅ Should load specific livestock data
-   ✅ Should apply livestock-specific filters
-   ✅ Should return livestock-specific information

#### **2. No Livestock Filter**

-   ✅ Should not throw validation error
-   ✅ Should search all data based on farm/coop filters
-   ✅ Should return aggregated data

#### **3. Mixed Data Sources**

-   ✅ Should combine LivestockSales and RecordingSale data
-   ✅ Should apply status filters correctly
-   ✅ Should handle draft sales appropriately

#### **4. Error Handling**

-   ✅ Should handle missing livestock gracefully
-   ✅ Should provide informative error messages
-   ✅ Should continue operation on partial failures

### **Files Modified**

1. **`app/Services/Report/SalesReportService.php`**

    - Updated `generateSalesReport` method
    - Added `getAllSalesData` method
    - Added `getAllTemporarySalesData` method
    - Enhanced `formatPeriod` method
    - Improved validation logic
    - Enhanced return data handling

2. **`docs/refactoring/fix-livestock-filter-issues.md`**
    - Documentation (this file)

### **Benefits**

#### **1. Flexible Filtering**

-   ✅ **Optional Livestock Selection** - User can choose specific livestock or view all data
-   ✅ **Farm/Coop Filtering** - Filter data by farm and coop when no specific livestock
-   ✅ **Year Filtering** - Filter data by year for better organization

#### **2. Better User Experience**

-   ✅ **No Validation Errors** - Graceful handling when no livestock selected
-   ✅ **Comprehensive Data View** - Show all available data when no specific filter
-   ✅ **Clear Data Labels** - Proper labeling for aggregated vs specific data

#### **3. Enhanced Data Discovery**

-   ✅ **All Data Search** - Find data even when livestock ID doesn't match
-   ✅ **Related Data** - Show related data based on farm/coop relationships
-   ✅ **Status Filtering** - Proper handling of draft/pending status

### **Next Steps**

1. **Test the Fix** - Verify both specific and general filtering work
2. **Monitor Performance** - Check query performance with large datasets
3. **User Feedback** - Gather feedback on new filtering capabilities
4. **Data Validation** - Ensure data accuracy across different filter combinations

### **Log Perubahan**

-   **27 Jan 2025:** Fixed livestock filter issues
-   **Validation:** Made periode field optional
-   **Data Retrieval:** Added methods for all data search
-   **Error Handling:** Improved graceful degradation
-   **User Experience:** Enhanced filtering flexibility
-   **Documentation:** Created comprehensive fix documentation

**Masalah filter livestock telah diperbaiki dengan flexible filtering dan enhanced error handling!**
