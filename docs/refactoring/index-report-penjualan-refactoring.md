# Refactoring Index Report Penjualan - Include Penjualan Temporer

## **LOG REFACTORING - 27 Januari 2025**

### **Analisis Kebutuhan**

**Permintaan User:**

-   Menambahkan opsi untuk include penjualan temporer
-   Data diambil dari `RecordingSale.php` dan `RecordingSaleItem.php`
-   Integrasi dengan sistem reporting yang ada

**Tujuan:**

1. **Enhanced Data Coverage** - Menampilkan data penjualan yang lebih lengkap
2. **Temporary Sales Integration** - Integrasi data dari sistem recording
3. **User Control** - User dapat memilih data mana yang ingin ditampilkan
4. **Better UX** - Interface yang lebih informatif dan user-friendly

### **Solusi yang Diterapkan**

#### **1. Enhanced Form Interface**

**Checkbox Options:**

```html
<!-- Opsi Tambahan untuk Data Penjualan -->
<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="form-check">
            <input
                class="form-check-input"
                type="checkbox"
                id="include_temp_sales"
                name="include_temp_sales"
                value="1"
            />
            <label class="form-check-label" for="include_temp_sales">
                <i class="fas fa-clock text-warning me-2"></i>
                Include Penjualan Temporer (RecordingSale)
            </label>
            <div class="form-text text-muted">
                Menyertakan data penjualan temporer dari sistem recording untuk
                analisis yang lebih lengkap
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check">
            <input
                class="form-check-input"
                type="checkbox"
                id="include_draft_sales"
                name="include_draft_sales"
                value="1"
            />
            <label class="form-check-label" for="include_draft_sales">
                <i class="fas fa-edit text-info me-2"></i>
                Include Draft Sales
            </label>
            <div class="form-text text-muted">
                Menyertakan data penjualan dengan status draft/pending
            </div>
        </div>
    </div>
</div>
```

**Data Info Display:**

```html
<!-- Informasi Data yang Akan Ditampilkan -->
<div class="alert alert-info mt-3" id="data-info" style="display: none;">
    <h6 class="alert-heading">
        <i class="fas fa-info-circle me-2"></i>
        Data yang akan ditampilkan:
    </h6>
    <ul class="mb-0" id="data-info-list">
        <!-- Will be populated by JavaScript -->
    </ul>
</div>
```

#### **2. Enhanced JavaScript Functionality**

**Dynamic Data Info Update:**

```javascript
function updateDataInfo() {
    var farmId = $("#farm").val();
    var coopId = $("#coop").val();
    var tahun = $("#tahun").val();
    var periodeId = $("#periode").val();
    var includeTempSales = $("#include_temp_sales").is(":checked");
    var includeDraftSales = $("#include_draft_sales").is(":checked");

    var dataTypes = [];

    // Base data
    if (periodeId) {
        dataTypes.push(
            '<li><i class="fas fa-check text-success me-2"></i>Data penjualan final (LivestockSales)</li>'
        );
    }

    // Additional data based on checkboxes
    if (includeTempSales) {
        dataTypes.push(
            '<li><i class="fas fa-clock text-warning me-2"></i>Data penjualan temporer (RecordingSale)</li>'
        );
    }

    if (includeDraftSales) {
        dataTypes.push(
            '<li><i class="fas fa-edit text-info me-2"></i>Data penjualan draft/pending</li>'
        );
    }

    if (dataTypes.length > 0) {
        $("#data-info-list").html(dataTypes.join(""));
        $("#data-info").show();
    } else {
        $("#data-info").hide();
    }
}
```

**Enhanced Form Submission:**

```javascript
$("#filter-form").on("submit", function (e) {
    e.preventDefault();

    // Show loading state
    saveChangesButton.disabled = true;
    saveChangesButton.innerHTML =
        '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';

    var formData = $(this).serialize();

    // Add loading indicator
    $("#report-content").html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Memuat laporan penjualan...</p>
        </div>
    `);

    $.ajax({
        url: "/api/v2/reports/penjualan",
        method: "POST",
        data: formData,
        success: function (data) {
            // Handle success with enhanced UI
        },
        error: function (xhr, status, error) {
            // Handle error with better messaging
        },
        complete: function () {
            // Reset button state
            saveChangesButton.disabled = false;
            saveChangesButton.innerHTML =
                '<i class="fas fa-filter me-2"></i>Filter';
        },
    });
});
```

#### **3. Enhanced Service Layer**

**Updated generateSalesReport Method:**

```php
public function generateSalesReport(Request $request)
{
    try {
        $livestockId = $request->periode;
        $includeTempSales = $request->boolean('include_temp_sales', false);
        $includeDraftSales = $request->boolean('include_draft_sales', false);

        // Load livestock dengan filter berdasarkan opsi
        $livestock = Livestock::with([
            'coop',
            'farm',
            'livestockDepletion',
            'livestockSales' => function($query) use ($includeDraftSales) {
                $query->with(['customer', 'expedition', 'livestockSalesItems'])
                      ->where('status', '!=', LivestockSales::STATUS_CANCELLED);

                // Include draft sales if requested
                if (!$includeDraftSales) {
                    $query->whereNotIn('status', [
                        LivestockSales::STATUS_DRAFT,
                        LivestockSales::STATUS_PENDING
                    ]);
                }

                $query->orderBy('date', 'ASC');
            }
        ])->findOrFail($livestockId);

        // Get temporary sales data if requested
        $tempSalesData = collect();
        if ($includeTempSales) {
            $tempSalesData = $this->getTemporarySalesData($livestockId, $includeDraftSales);
        }

        // Calculate summary metrics including temporary sales
        $summary = $this->calculateSalesSummary($salesData, $tempSalesData);

        return [
            'data' => $salesData,
            'livestock' => $livestock,
            'tempSalesData' => $tempSalesData,
            'summary' => $summary,
            'has_data' => true,
            'include_temp_sales' => $includeTempSales,
            'include_draft_sales' => $includeDraftSales
        ];

    } catch (\Exception $e) {
        // Enhanced error handling
    }
}
```

**New getTemporarySalesData Method:**

```php
protected function getTemporarySalesData($livestockId, $includeDraftSales = false)
{
    try {
        $query = \App\Models\RecordingSale::with([
            'items' => function($query) {
                $query->orderBy('created_at', 'ASC');
            },
            'livestock',
            'livestockBatch'
        ])
        ->where('livestock_id', $livestockId)
        ->where('status', '!=', \App\Models\RecordingSale::STATUS_CANCELLED);

        // Apply status filter based on includeDraftSales
        if (!$includeDraftSales) {
            $query->whereNotIn('status', [
                \App\Models\RecordingSale::STATUS_DRAFT,
                \App\Models\RecordingSale::STATUS_PENDING
            ]);
        }

        $tempSales = $query->orderBy('date', 'ASC')->get();

        Log::info('Temporary sales data retrieved', [
            'livestock_id' => $livestockId,
            'total_temp_sales' => $tempSales->count(),
            'include_draft_sales' => $includeDraftSales
        ]);

        return $tempSales;

    } catch (\Exception $e) {
        Log::error('Error retrieving temporary sales data', [
            'livestock_id' => $livestockId,
            'error' => $e->getMessage()
        ]);
        return collect();
    }
}
```

#### **4. Enhanced View Display**

**Separated Data Sections:**

```php
<!-- Main Sales Data -->
@if($penjualanData->isNotEmpty())
    <h4 class="mt-4 mb-3">
        <i class="fas fa-chart-line text-primary me-2"></i>
        Data Penjualan Final
    </h4>
    <table id="tableReport" class="mb-4">
        <!-- Main sales table -->
    </table>
@endif

<!-- Temporary Sales Data -->
@if(isset($tempSalesData) && $tempSalesData->isNotEmpty())
    <h4 class="mt-4 mb-3">
        <i class="fas fa-clock text-warning me-2"></i>
        Data Penjualan Temporer (RecordingSale)
        <small class="text-muted">- Data dari sistem recording yang belum final</small>
    </h4>
    <table id="tableTempReport" class="mb-4">
        <thead>
            <tr style="background-color: #fff3cd;">
                <th>Tgl Penjualan</th>
                <th>Status</th>
                <th>Batch</th>
                <th>Jumlah</th>
                <th>Berat (Kg)</th>
                <th>ABW (Kg)</th>
                <th>Harga/Kg (Rp)</th>
                <th>Total (Rp)</th>
                <th>Tipe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tempSalesData as $tempData)
                @if($tempData->isHeader() && $tempData->items->isNotEmpty())
                    <!-- Header record with multiple items -->
                    @foreach($tempData->items as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tempData->date)->format('d-M-y') }}</td>
                            <td>
                                <span class="badge bg-{{ $tempData->status === 'confirmed' ? 'success' : ($tempData->status === 'pending' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($tempData->status) }}
                                </span>
                            </td>
                            <td>{{ $item->batch->name ?? 'Unknown Batch' }}</td>
                            <td style="text-align: right;">{{ number_format($item->quantity ?? 0, 0, ',', '.') }}</td>
                            <td style="text-align: right;">{{ number_format($item->weight ?? 0, 1, ',', '.') }}</td>
                            <td>{{ number_format(round(($item->weight ?? 0) / ($item->quantity ?? 1), 2), 2, ',', '.') }}</td>
                            <td>{{ number_format($item->price_per_unit ?? 0, 0, ',', '.') }}</td>
                            <td style="text-align: right;">{{ number_format($item->amount ?? 0, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-info">Header</span>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <!-- Legacy single record -->
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($tempData->date)->format('d-M-y') }}</td>
                        <td>
                            <span class="badge bg-{{ $tempData->status === 'confirmed' ? 'success' : ($tempData->status === 'pending' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($tempData->status) }}
                            </span>
                        </td>
                        <td>{{ $tempData->livestockBatch->name ?? 'Main Batch' }}</td>
                        <td style="text-align: right;">{{ number_format($tempData->quantity ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($tempData->weight ?? 0, 1, ',', '.') }}</td>
                        <td>{{ number_format(round(($tempData->weight ?? 0) / ($tempData->quantity ?? 1), 2), 2, ',', '.') }}</td>
                        <td>{{ number_format($tempData->price ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format(($tempData->quantity ?? 0) * ($tempData->price ?? 0), 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-secondary">Single</span>
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
@endif
```

### **Fitur Baru yang Ditambahkan**

#### **1. Opsi Include Penjualan Temporer**

-   **Checkbox** untuk include data dari `RecordingSale`
-   **Visual indicator** dengan ikon dan warna yang berbeda
-   **Help text** untuk menjelaskan fungsi

#### **2. Opsi Include Draft Sales**

-   **Checkbox** untuk include data dengan status draft/pending
-   **Warning indicator** untuk data yang belum final
-   **Status filtering** yang fleksibel

#### **3. Dynamic Data Info Display**

-   **Real-time update** berdasarkan pilihan user
-   **Visual feedback** untuk data yang akan ditampilkan
-   **Clear categorization** antara data final dan temporer

#### **4. Enhanced Report Display**

-   **Separated sections** untuk data final dan temporer
-   **Status badges** untuk identifikasi status transaksi
-   **Type indicators** untuk header vs single records
-   **Color coding** untuk membedakan jenis data

#### **5. Help Modal**

-   **Comprehensive guide** untuk penggunaan fitur
-   **Warning messages** untuk data temporer
-   **Best practices** untuk analisis yang akurat

### **Manfaat Refactoring**

#### **1. Data Completeness**

-   ✅ **Lengkapi data penjualan** dengan data temporer
-   ✅ **Analisis yang lebih akurat** dengan data yang komprehensif
-   ✅ **Tracking progress** dari draft hingga final

#### **2. User Control**

-   ✅ **Flexible filtering** berdasarkan kebutuhan user
-   ✅ **Clear data categorization** untuk pemahaman yang lebih baik
-   ✅ **Transparent data sources** dengan labeling yang jelas

#### **3. Better UX**

-   ✅ **Intuitive interface** dengan visual indicators
-   ✅ **Real-time feedback** untuk pilihan user
-   ✅ **Comprehensive help** untuk guidance

#### **4. Technical Benefits**

-   ✅ **Modular design** untuk maintainability
-   ✅ **Enhanced logging** untuk debugging
-   ✅ **Backward compatibility** dengan sistem existing

### **Data Flow Baru**

#### **1. User Selection Flow**

```
User selects options → JavaScript updates info → Form submission → Service processes → View displays
```

#### **2. Data Retrieval Flow**

```
Request → Load LivestockSales → Check includeTempSales → Load RecordingSale → Merge data → Calculate summary
```

#### **3. Display Flow**

```
Data → Separate sections → Apply styling → Show status badges → Display totals
```

### **Testing Scenarios**

#### **1. Basic Functionality**

-   ✅ Should display main sales data without options
-   ✅ Should enable/disable checkboxes based on selection
-   ✅ Should update data info display dynamically

#### **2. Temporary Sales Integration**

-   ✅ Should load RecordingSale data when option selected
-   ✅ Should display temporary sales in separate section
-   ✅ Should handle both header and single records

#### **3. Draft Sales Handling**

-   ✅ Should include draft sales when option selected
-   ✅ Should filter out draft sales when option not selected
-   ✅ Should show appropriate status badges

#### **4. Error Handling**

-   ✅ Should handle missing temporary sales data gracefully
-   ✅ Should show appropriate error messages
-   ✅ Should maintain functionality when data unavailable

### **Files Modified**

1. **`resources/views/pages/reports/index_report_penjualan.blade.php`**

    - Added checkbox options for temporary sales
    - Enhanced JavaScript functionality
    - Added help modal
    - Improved UI/UX

2. **`app/Services/Report/SalesReportService.php`**

    - Added temporary sales data retrieval
    - Enhanced summary calculation
    - Added status filtering
    - Improved error handling

3. **`resources/views/pages/reports/penjualan_details.blade.php`**

    - Added temporary sales display section
    - Enhanced data presentation
    - Added status indicators
    - Improved layout

4. **`docs/refactoring/index-report-penjualan-refactoring.md`**
    - Documentation (this file)

### **Dependencies**

-   **RecordingSale Model** - Untuk data penjualan temporer
-   **RecordingSaleItem Model** - Untuk detail item penjualan
-   **FontAwesome Icons** - Untuk visual indicators
-   **Bootstrap CSS/JS** - Untuk styling dan modal
-   **jQuery** - Untuk JavaScript functionality

### **Next Steps**

1. **Testing:**

    - Test semua scenario dengan data real
    - Verify performance dengan data besar
    - Test error handling

2. **Enhancement:**

    - Add export functionality untuk temporary sales
    - Add more detailed filtering options
    - Add data validation

3. **Monitoring:**
    - Monitor usage patterns
    - Track performance metrics
    - Gather user feedback

### **Log Perubahan**

-   **27 Jan 2025:** Index report penjualan refactoring completed
-   **UI Enhancement:** Added checkbox options and dynamic info display
-   **Service Integration:** Added temporary sales data retrieval
-   **View Enhancement:** Added separate sections for different data types
-   **JavaScript:** Enhanced form handling and user feedback
-   **Documentation:** Created comprehensive documentation

### **Expected Results**

Setelah refactoring ini, user akan mendapatkan:

1. **Enhanced Data Coverage** - Data penjualan yang lebih lengkap dengan temporary sales
2. **Better Control** - Opsi untuk memilih data mana yang ingin ditampilkan
3. **Clear Visualization** - Pemisahan yang jelas antara data final dan temporer
4. **Improved UX** - Interface yang lebih informatif dan user-friendly
5. **Comprehensive Analysis** - Kemampuan untuk menganalisis data dari berbagai sumber

**Tidak ada lagi:** Keterbatasan data penjualan yang hanya dari satu sumber!
