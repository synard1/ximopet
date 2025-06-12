# Livestock Cost Report Refactor Documentation v2.0.0

## Gambaran Umum

Dokumentasi ini menjelaskan refactor fitur report LivestockCost yang dilakukan untuk meningkatkan akurasi perhitungan biaya deplesi dan menampilkan informasi harga awal pembelian DOC/livestock.

## Perubahan yang Dilakukan

### 1. ReportsController.php - exportCostHarian Method

#### Penambahan Fitur Baru:

-   **Harga Awal DOC**: Menampilkan informasi harga pembelian awal DOC/livestock
-   **Kalkulasi Deplesi yang Diperbaiki**: Menggunakan harga awal + beban akumulatif untuk biaya deplesi
-   **Informasi Tanggal**: Menambahkan kolom tanggal untuk detail report

#### Detail Perubahan:

```php
// Ambil data pembelian awal livestock
$initialPurchaseItem = LivestockPurchaseItem::where('livestock_id', $livestock->id)
    ->orderBy('created_at', 'asc')
    ->first();

$initialPurchasePrice = $initialPurchaseItem->price_per_unit ?? 0;
$initialPurchaseQuantity = $initialPurchaseItem->quantity ?? $livestock->initial_quantity ?? 0;
$initialPurchaseDate = $initialPurchaseItem->created_at ?? $livestock->start_date ?? null;
```

#### Perbaikan Kalkulasi Deplesi:

-   **Sebelum**: Menggunakan hanya `price_per_unit` dari LivestockPurchaseItem
-   **Sesudah**: Menggunakan `cumulative_cost_per_ayam` yang mencakup harga awal + beban akumulatif

```php
// Kalkulasi deplesi dengan biaya kumulatif
$prevCumulativeCostPerAyam = $costData?->cost_breakdown['prev_cost']['cumulative_cost_per_ayam'] ??
                           $initialPurchasePrice;

$deplesiHargaSatuan = $prevCumulativeCostPerAyam;
```

### 2. Template Blade Updates (livestock-cost.blade.php)

#### Penambahan Kolom Baru:

-   **Kolom Tanggal**: Menampilkan tanggal untuk setiap item biaya
-   **Highlighting**: Memberikan warna khusus untuk harga awal DOC
-   **Informasi Tambahan**: Menampilkan ringkasan harga awal di bagian bawah

#### Styling Khusus:

```html
<!-- Highlight untuk harga awal DOC -->
<tr style="background-color: #e8f4fd;">
    <td class="text-left">
        Harga Awal DOC
        <small style="color: #0066cc;">(Harga Pembelian Awal)</small>
    </td>
</tr>
```

### 3. Data Structure Enhancements

#### Struktur Data Breakdown yang Diperbaiki:

```php
$detailedBreakdown[] = [
    'kategori' => 'Harga Awal DOC',
    'jumlah' => $initialPurchaseQuantity,
    'satuan' => 'Ekor',
    'harga_satuan' => $initialPurchasePrice,
    'subtotal' => $initialPurchasePrice * $initialPurchaseQuantity,
    'tanggal' => $initialPurchaseDate ? $initialPurchaseDate->format('d/m/Y') : '-',
    'is_initial_purchase' => true,
];
```

#### Data Deplesi yang Diperbaiki:

```php
$detailedBreakdown[] = [
    'kategori' => 'Deplesi (Biaya Awal + Beban)',
    'jumlah' => $deplesiQty,
    'satuan' => 'Ekor',
    'harga_satuan' => $deplesiHargaSatuan,
    'subtotal' => $deplesiCost,
    'is_initial_purchase' => false,
    'calculation_detail' => [
        'initial_price' => $initialPurchasePrice,
        'cumulative_cost_per_chicken' => $prevCumulativeCostPerAyam,
        'method' => 'cumulative_cost_calculation'
    ]
];
```

## Benefit dan Peningkatan

### 1. Akurasi Perhitungan

-   **Deplesi Cost**: Sekarang menggunakan total biaya akumulatif (harga awal + pakan + OVK + dll)
-   **Transparansi**: User dapat melihat harga awal pembelian DOC
-   **Konsistensi**: Perhitungan deplesi konsisten dengan LivestockCostService

### 2. User Experience

-   **Visual Distinction**: Harga awal DOC dibedakan dengan warna
-   **Detail Information**: Menampilkan tanggal untuk setiap item
-   **Comprehensive View**: Report detail dan simple sama-sama menggunakan kalkulasi yang benar

### 3. Data Integrity

-   **Fallback Values**: Menggunakan fallback jika data tidak tersedia
-   **Error Handling**: Menangani kasus dimana LivestockPurchaseItem tidak ditemukan
-   **Backward Compatibility**: Tetap kompatibel dengan data lama

## Formula Perhitungan

### Harga Awal DOC

```
Total Harga Awal = price_per_unit × quantity
```

### Biaya Deplesi (Diperbaiki)

```
Biaya Deplesi = deplesi_ekor × cumulative_cost_per_ayam

Dimana:
- cumulative_cost_per_ayam = harga_awal + akumulasi_pakan + akumulasi_ovk + akumulasi_deplesi_sebelumnya
```

### Biaya Deplesi (Sebelumnya - Tidak Akurat)

```
Biaya Deplesi = deplesi_ekor × price_per_unit_only
```

## Contoh Output Report

### Report Detail:

```
+---------------------------+--------+--------+-------------+-----------+----------+
| KATEGORI                  | JUMLAH | SATUAN | HARGA SATUAN| TANGGAL   | SUBTOTAL |
+---------------------------+--------+--------+-------------+-----------+----------+
| Harga Awal DOC            | 1000   | Ekor   | 5,500       | 01/01/24  | 5,500,000|
| Pakan BR1                 | 25     | Sak    | 245,000     | 15/01/24  | 6,125,000|
| OVK Vitamin               | 2      | Botol  | 50,000      | 15/01/24  | 100,000  |
| Deplesi (Biaya Awal+Beban)| 10     | Ekor   | 6,250       | -         | 62,500   |
+---------------------------+--------+--------+-------------+-----------+----------+
```

## Testing dan Quality Assurance

### Test Cases:

1. **Test dengan data lengkap**: Livestock dengan purchase item, cost data, dll
2. **Test dengan data parsial**: Livestock tanpa purchase item atau cost data
3. **Test report simple vs detail**: Memastikan kedua tipe menggunakan kalkulasi yang sama
4. **Test backward compatibility**: Data lama tetap bisa ditampilkan

### Performance Impact:

-   **Query Addition**: +1 query untuk mengambil LivestockPurchaseItem
-   **Memory Usage**: +minimal (hanya menyimpan beberapa field tambahan)
-   **Rendering Time**: +negligible (hanya penambahan beberapa baris di template)

## Migration Notes

### Data Migration:

-   **Tidak diperlukan**: Semua perubahan adalah di level aplikasi
-   **Backward Compatible**: Data lama tetap dapat digunakan

### Deployment Notes:

1. Deploy file controller dan template bersamaan
2. Test dengan data sample sebelum production
3. Monitor performance setelah deployment

## Troubleshooting

### Masalah Umum:

1. **Harga awal tidak muncul**: Pastikan LivestockPurchaseItem ada dan terhubung dengan livestock
2. **Deplesi cost tidak akurat**: Periksa data cost_breakdown di LivestockCost
3. **Error formatting**: Pastikan formatNumber helper tersedia

### Debug Commands:

```php
// Check initial purchase data
$livestock = Livestock::find($id);
$purchaseItem = LivestockPurchaseItem::where('livestock_id', $livestock->id)->first();
dd($purchaseItem);

// Check cost breakdown structure
$costData = LivestockCost::where('livestock_id', $id)->first();
dd($costData->cost_breakdown);
```

## Future Enhancements

### Planned Improvements:

1. **Export to Excel**: Include new columns in Excel export
2. **Historical Comparison**: Compare initial vs current costs
3. **Cost Analysis**: Add cost analysis dashboard
4. **API Endpoints**: Expose new data via API

### Performance Optimizations:

1. **Query Optimization**: Use eager loading untuk related data
2. **Caching**: Cache initial purchase data
3. **Indexing**: Add database indexes jika diperlukan

---

**Versi Dokumentasi**: v2.0.0  
**Tanggal**: {{ date('Y-m-d H:i:s') }}  
**Author**: System  
**Status**: Completed
