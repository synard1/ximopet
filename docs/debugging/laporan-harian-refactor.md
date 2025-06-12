# Refactoring Laporan Harian - Dokumentasi Debugging

## Masalah yang Diidentifikasi

### 1. Template Crash Ketika Tidak Ada Data Pakan

**Problem**: Template `harian.blade.php` crash ketika `$distinctFeedNames` kosong atau tidak sinkron dengan `$totals['pakan_harian']`

**Root Cause**:

-   Loop `@foreach($distinctFeedNames as $feedName)` dan `@foreach($totals['pakan_harian'] as $totalQuantity)` tidak konsisten
-   Tidak ada handling ketika array kosong
-   Simple mode menggunakan loop yang berbeda dengan detail mode

**Solution**:

-   Menambahkan validasi `@if(count($distinctFeedNames) > 0)`
-   Memastikan semua feed names ada di totals dengan default value 0
-   Menggunakan konsistensi loop yang sama antara mode simple dan detail

### 2. Total Stock Salah Membaca Data Mortalitas

**Problem**: Perhitungan `stock_akhir` tidak akurat karena tidak mengurangi penjualan

**Root Cause**:

```php
$stockAkhir = $stockAwal - $totalDeplesi; // SALAH - tidak mengurangi penjualan
```

**Solution**:

```php
$stockAkhir = $stockAwal - $totalDepletionCumulative - $totalSalesCumulative;
```

### 3. Total Deplesi Menampilkan 0

**Problem**: Data deplesi tidak terhitung dengan benar

**Root Cause**:

-   Mixing antara data harian dan kumulatif
-   Query deplesi menggunakan tanggal yang salah (harian vs kumulatif)

**Solution**:

-   Memisahkan dengan jelas antara data harian dan kumulatif
-   Deplesi harian untuk tampilan per hari
-   Deplesi kumulatif untuk perhitungan total

### 4. Survival Rate Menampilkan 0

**Problem**: Survival rate tidak dihitung

**Root Cause**: Tidak ada perhitungan survival rate di controller

**Solution**:

```php
$totals['survival_rate'] = $totals['stock_awal'] > 0
    ? round(($totals['stock_akhir'] / $totals['stock_awal']) * 100, 2)
    : 0;
```

### 5. Kolom Pakan Tidak Konsisten

**Problem**: Jumlah kolom pakan di header tidak match dengan data rows

**Root Cause**:

-   `distinctFeedNames` dan `totals['pakan_harian']` tidak sinkron
-   Mode simple dan detail menggunakan loop yang berbeda

**Solution**:

-   Ensure all feed names exist in totals array
-   Gunakan loop yang konsisten untuk semua mode

## Perubahan yang Dilakukan

### 1. Controller (ReportsController.php)

#### processLivestockData()

```php
// BEFORE
$totalDeplesi = (int) LivestockDepletion::where('livestock_id', $livestock->id)
    ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
    ->sum('jumlah');
$stockAkhir = $stockAwal - $totalDeplesi;

// AFTER
$totalDepletionCumulative = (int) LivestockDepletion::where('livestock_id', $livestock->id)
    ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
    ->sum('jumlah');
$totalSalesCumulative = (int) LivestockSalesItem::where('livestock_id', $livestock->id)
    ->whereHas('livestockSale', function ($query) use ($tanggal) {
        $query->whereDate('tanggal', '<=', $tanggal);
    })->sum('quantity');
$stockAkhir = $stockAwal - $totalDepletionCumulative - $totalSalesCumulative;
```

#### getHarianReportData()

```php
// AFTER - Ensure feed names consistency
foreach ($distinctFeedNames as $feedName) {
    if (!isset($totals['pakan_harian'][$feedName])) {
        $totals['pakan_harian'][$feedName] = 0;
    }
}

// Calculate survival rate
$totals['survival_rate'] = $totals['stock_awal'] > 0
    ? round(($totals['stock_akhir'] / $totals['stock_awal']) * 100, 2)
    : 0;
```

### 2. Template (harian.blade.php)

#### Header Kolom Pakan

```html
<!-- BEFORE -->
<th colspan="4">PEMAKAIAN PAKAN</th>

<!-- AFTER -->
<th colspan="{{ count($distinctFeedNames) + 1 }}">PEMAKAIAN PAKAN</th>
```

#### Handling Data Kosong

```html
<!-- BEFORE -->
@foreach($distinctFeedNames as $feedName)
<th>{{ $feedName }}</th>
@endforeach

<!-- AFTER -->
@if(count($distinctFeedNames) > 0) @foreach($distinctFeedNames as $feedName)
<th>{{ $feedName }}</th>
@endforeach @else
<th>-</th>
@endif
```

#### Consistent Loop untuk Data Rows

```html
<!-- AFTER - Simple Mode -->
@if(count($distinctFeedNames) > 0 && isset($record['pakan_harian']))
@foreach($distinctFeedNames as $feedName)
<td>{{ formatNumber($record['pakan_harian'][$feedName] ?? 0, 0) }}</td>
@endforeach @else @for($i = 0; $i < max(1, count($distinctFeedNames)); $i++)
<td>0</td>
@endfor @endif
```

## Logging dan Debugging

### Log Points Ditambahkan:

1. **processLivestockData()**: Log detail perhitungan per livestock
2. **processCoopAggregation()**: Log aggregasi per kandang
3. **getHarianReportData()**: Log final totals dan consistency check

### Debug Data Structure:

```php
Log::info("Processed livestock data", [
    'livestock_id' => $livestock->id,
    'stock_awal' => $stockAwal,
    'mortality_daily' => $mortality,
    'total_depletion_cumulative' => $totalDepletionCumulative,
    'total_sales_cumulative' => $totalSalesCumulative,
    'stock_akhir' => $stockAkhir,
    'feed_types_count' => count($pakanHarianPerJenis)
]);
```

## Testing Scenarios

### 1. Normal Data

-   Ada data pakan, deplesi, dan penjualan
-   **Expected**: Semua data tampil dengan benar

### 2. No Feed Data

-   Tidak ada data penggunaan pakan
-   **Expected**: Template tidak crash, kolom pakan menampilkan 0 atau "-"

### 3. No Depletion Data

-   Tidak ada data mortalitas/afkir
-   **Expected**: Deplesi = 0, survival rate = 100%

### 4. No Sales Data

-   Tidak ada data penjualan
-   **Expected**: Stock akhir = stock awal - deplesi

### 5. Mixed Data

-   Beberapa batch ada data, beberapa tidak
-   **Expected**: Kolom tetap konsisten, data kosong tampil sebagai 0

## Performance Considerations

### Optimized Queries:

1. Separate daily vs cumulative queries
2. Single query per livestock untuk feed usage
3. Efficient grouping by coop name

### Memory Management:

1. Process batch data collection untuk avoid double processing
2. Clear separation between processing dan aggregation

## Future Improvements

1. **Caching**: Implement cache untuk data yang frequently accessed
2. **Batch Processing**: Process multiple livestocks dalam single query
3. **Data Validation**: Add validation untuk data integrity
4. **Error Handling**: Better error handling untuk missing relationships
