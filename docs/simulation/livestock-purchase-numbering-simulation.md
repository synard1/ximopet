# Simulasi Penomoran Livestock Purchase

## 📋 Konfigurasi Sistem

Berdasarkan `LivestockNumberingConfig.php`:

```php
'livestock_purchases' => [
    'enabled' => true,
    'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
    'reset_rule' => 'yearly',        // Reset tahunan
    'prefix' => 'LSP',
    'number_padding' => 3,
    'example' => 'LSP-001/14/07/2025',
    'notes' => 'Nomor pembelian ternak, reset tahunan.',
],
```

## 🎯 Simulasi Pembelian 2025

### Urutan Pembelian (berdasarkan tanggal):

1. **01 Januari 2025** - Pembelian pertama tahun 2025
2. **02 Februari 2025** - Pembelian kedua tahun 2025
3. **10 Juni 2025** - Pembelian ketiga tahun 2025
4. **15 Juli 2025** - Pembelian keempat tahun 2025

### Hasil Penomoran:

| Urutan | Tanggal Pembelian | Nomor yang Dihasilkan | Penjelasan                             |
| ------ | ----------------- | --------------------- | -------------------------------------- |
| 1      | 01 Januari 2025   | `LSP-001/01/01/2025`  | Pembelian pertama tahun 2025, urut = 1 |
| 2      | 02 Februari 2025  | `LSP-002/02/02/2025`  | Pembelian kedua tahun 2025, urut = 2   |
| 3      | 10 Juni 2025      | `LSP-003/10/06/2025`  | Pembelian ketiga tahun 2025, urut = 3  |
| 4      | 15 Juli 2025      | `LSP-004/15/07/2025`  | Pembelian keempat tahun 2025, urut = 4 |

## 🔍 Detail Proses Penomoran

### 1. Pembelian 01 Januari 2025

```php
// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-01-01')
// Reset Rule: yearly - cari max number untuk tahun 2025
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 0 (belum ada data 2025)
$number = $lastNumber + 1; // = 1

// Placeholders:
// {prefix} = 'LSP'
// {urut:3} = '001' (padding 3 digit)
// {TGL} = '01'
// {BLN} = '01'
// {THN} = '2025'

// Result: LSP-001/01/01/2025
```

### 2. Pembelian 02 Februari 2025

```php
// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-02-02')
// Reset Rule: yearly - cari max number untuk tahun 2025
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 1 (dari pembelian sebelumnya)
$number = $lastNumber + 1; // = 2

// Placeholders:
// {prefix} = 'LSP'
// {urut:3} = '002' (padding 3 digit)
// {TGL} = '02'
// {BLN} = '02'
// {THN} = '2025'

// Result: LSP-002/02/02/2025
```

### 3. Pembelian 10 Juni 2025

```php
// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-06-10')
// Reset Rule: yearly - cari max number untuk tahun 2025
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 2 (dari pembelian sebelumnya)
$number = $lastNumber + 1; // = 3

// Placeholders:
// {prefix} = 'LSP'
// {urut:3} = '003' (padding 3 digit)
// {TGL} = '10'
// {BLN} = '06'
// {THN} = '2025'

// Result: LSP-003/10/06/2025
```

### 4. Pembelian 15 Juli 2025

```php
// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-07-15')
// Reset Rule: yearly - cari max number untuk tahun 2025
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 3 (dari pembelian sebelumnya)
$number = $lastNumber + 1; // = 4

// Placeholders:
// {prefix} = 'LSP'
// {urut:3} = '004' (padding 3 digit)
// {TGL} = '15'
// {BLN} = '07'
// {THN} = '2025'

// Result: LSP-004/15/07/2025
```

## 📊 Database State Setelah Simulasi

### Tabel: livestock_purchases

| id  | number | number_full        | tanggal    | created_at          | updated_at          |
| --- | ------ | ------------------ | ---------- | ------------------- | ------------------- |
| 1   | 1      | LSP-001/01/01/2025 | 2025-01-01 | 2025-01-01 08:00:00 | 2025-01-01 08:00:00 |
| 2   | 2      | LSP-002/02/02/2025 | 2025-02-02 | 2025-02-02 09:30:00 | 2025-02-02 09:30:00 |
| 3   | 3      | LSP-003/10/06/2025 | 2025-06-10 | 2025-06-10 14:15:00 | 2025-06-10 14:15:00 |
| 4   | 4      | LSP-004/15/07/2025 | 2025-07-15 | 2025-07-15 11:45:00 | 2025-07-15 11:45:00 |

## 🔄 Reset Rule: Yearly

Karena menggunakan **reset rule yearly**, maka:

### Jika ada pembelian di tahun 2026:

```php
// Pembelian 05 Januari 2026
// Reset Rule: yearly - cari max number untuk tahun 2026
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2026)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 0 (belum ada data 2026)
$number = $lastNumber + 1; // = 1

// Result: LSP-001/05/01/2026
```

### Jika ada pembelian di tahun 2024:

```php
// Pembelian 20 Desember 2024
// Reset Rule: yearly - cari max number untuk tahun 2024
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2024)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 0 (belum ada data 2024)
$number = $lastNumber + 1; // = 1

// Result: LSP-001/20/12/2024
```

## 🎯 Karakteristik Sistem

### ✅ **Konsisten**

-   Format seragam: `LSP-{urut:3}/{TGL}/{BLN}/{THN}`
-   Padding 3 digit untuk nomor urut
-   Reset tahunan untuk isolasi per tahun

### ✅ **Unik per Tahun**

-   Setiap tahun dimulai dari nomor 1
-   Tidak ada konflik antar tahun
-   Mudah untuk tracking per tahun

### ✅ **Informatif**

-   Nomor urut menunjukkan urutan pembelian dalam tahun
-   Tanggal terlihat jelas dalam format
-   Prefix LSP menunjukkan jenis transaksi

### ✅ **Scalable**

-   Support hingga 999 pembelian per tahun (3 digit)
-   Bisa diubah ke 4 digit jika diperlukan
-   Reset rule bisa diubah ke monthly/daily jika diperlukan

## 🔧 Testing Command

Untuk menguji simulasi ini, bisa menggunakan:

```bash
# Test numbering generation untuk tanggal tertentu
php artisan tinker

# Test pembelian 01 Januari 2025
>>> $numbering = App\Services\Livestock\LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-01-01');
>>> echo $numbering['full_number']; // Output: LSP-001/01/01/2025

# Test pembelian 15 Juli 2025 (setelah ada 3 pembelian sebelumnya)
>>> $numbering = App\Services\Livestock\LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-07-15');
>>> echo $numbering['full_number']; // Output: LSP-004/15/07/2025
```

## 📈 Kesimpulan

Sistem penomoran livestock purchase dengan konfigurasi existing akan menghasilkan:

1. **Urutan yang konsisten** berdasarkan tanggal pembelian
2. **Reset tahunan** untuk isolasi per tahun
3. **Format yang informatif** dengan tanggal terlihat jelas
4. **Scalability** yang baik untuk volume pembelian yang tinggi

Sistem ini sangat cocok untuk tracking pembelian ternak dengan kebutuhan reset tahunan dan format yang mudah dibaca.
