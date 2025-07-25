# Simulasi Penomoran Livestock Purchase - Non-Sequential Input

## 🚨 **KONDISI REALISTIS: User Tidak Input Data Secara Realtime dan Berurutan**

### 📋 **Konfigurasi Sistem:**

```php
'livestock_purchases' => [
    'format' => '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}',
    'reset_rule' => 'yearly',        // Reset tahunan
    'prefix' => 'LSP',
    'number_padding' => 3,
],
```

### 🎯 **Skenario Realistis:**

User input data dengan urutan **tidak berurutan** dan **tidak realtime**:

1. **15 Juli 2025** - Input pertama (pembelian terlambat)
2. **02 Februari 2025** - Input kedua (pembelian terlambat)
3. **01 Januari 2025** - Input ketiga (pembelian terlambat)
4. **10 Juni 2025** - Input keempat (pembelian terlambat)

## 🔍 **Analisis Kode LivestockNumberGeneratorService:**

Berdasarkan kode di `LivestockNumberGeneratorService.php`:

```php
// 1. Tentukan nomor urut terakhir sesuai reset_rule
$query = DB::table($table);
if ($resetRule === 'yearly') {
    $query->whereYear('created_at', $dateObj->year);
}
$lastNumber = $query->max('number') ?? 0;
$number = $lastNumber + 1;
```

**PENTING**: Sistem menggunakan `created_at` untuk reset rule, bukan `tanggal` pembelian!

## 🎯 **Simulasi Realistis:**

### **Asumsi:**

-   User input data pada tanggal yang sama (misal: 15 Juli 2025)
-   Semua record memiliki `created_at` yang sama
-   Tanggal pembelian (`tanggal`) berbeda-beda

### **Hasil Penomoran Berdasarkan Urutan Input:**

| Urutan Input | Tanggal Input | Tanggal Pembelian    | Nomor yang Dihasilkan | Penjelasan              |
| ------------ | ------------- | -------------------- | --------------------- | ----------------------- |
| 1            | 15 Juli 2025  | **15 Juli 2025**     | `LSP-001/15/07/2025`  | Input pertama, urut = 1 |
| 2            | 15 Juli 2025  | **02 Februari 2025** | `LSP-002/02/02/2025`  | Input kedua, urut = 2   |
| 3            | 15 Juli 2025  | **01 Januari 2025**  | `LSP-003/01/01/2025`  | Input ketiga, urut = 3  |
| 4            | 15 Juli 2025  | **10 Juni 2025**     | `LSP-004/10/06/2025`  | Input keempat, urut = 4 |

## 🔍 **Detail Proses Penomoran:**

### **1. Input Pertama (15 Juli 2025)**

```php
// User input: tanggal pembelian = 15 Juli 2025
// created_at = 15 Juli 2025 (saat input)

// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-07-15')
// Reset Rule: yearly - cari max number untuk tahun 2025 berdasarkan created_at
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 0 (belum ada data 2025)
$number = 0 + 1; // = 1

// Placeholders berdasarkan tanggal pembelian:
// {prefix} = 'LSP'
// {urut:3} = '001' (padding 3 digit)
// {TGL} = '15' (dari tanggal pembelian)
// {BLN} = '07' (dari tanggal pembelian)
// {THN} = '2025' (dari tanggal pembelian)

// Result: LSP-001/15/07/2025
```

### **2. Input Kedua (02 Februari 2025)**

```php
// User input: tanggal pembelian = 02 Februari 2025
// created_at = 15 Juli 2025 (saat input)

// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-02-02')
// Reset Rule: yearly - cari max number untuk tahun 2025 berdasarkan created_at
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 1 (dari input sebelumnya)
$number = 1 + 1; // = 2

// Placeholders berdasarkan tanggal pembelian:
// {TGL} = '02' (dari tanggal pembelian)
// {BLN} = '02' (dari tanggal pembelian)
// {THN} = '2025' (dari tanggal pembelian)

// Result: LSP-002/02/02/2025
```

### **3. Input Ketiga (01 Januari 2025)**

```php
// User input: tanggal pembelian = 01 Januari 2025
// created_at = 15 Juli 2025 (saat input)

// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-01-01')
// Reset Rule: yearly - cari max number untuk tahun 2025 berdasarkan created_at
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 2 (dari input sebelumnya)
$number = 2 + 1; // = 3

// Placeholders berdasarkan tanggal pembelian:
// {TGL} = '01' (dari tanggal pembelian)
// {BLN} = '01' (dari tanggal pembelian)
// {THN} = '2025' (dari tanggal pembelian)

// Result: LSP-003/01/01/2025
```

### **4. Input Keempat (10 Juni 2025)**

```php
// User input: tanggal pembelian = 10 Juni 2025
// created_at = 15 Juli 2025 (saat input)

// LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-06-10')
// Reset Rule: yearly - cari max number untuk tahun 2025 berdasarkan created_at
$query = DB::table('livestock_purchases')
    ->whereYear('created_at', 2025)
    ->whereNotNull('number');
$lastNumber = $query->max('number') ?? 0; // = 3 (dari input sebelumnya)
$number = 3 + 1; // = 4

// Placeholders berdasarkan tanggal pembelian:
// {TGL} = '10' (dari tanggal pembelian)
// {BLN} = '06' (dari tanggal pembelian)
// {THN} = '2025' (dari tanggal pembelian)

// Result: LSP-004/10/06/2025
```

## 📊 **Database State Setelah Simulasi:**

### Tabel: livestock_purchases

| id  | number | number_full        | tanggal    | created_at          | updated_at          |
| --- | ------ | ------------------ | ---------- | ------------------- | ------------------- |
| 1   | 1      | LSP-001/15/07/2025 | 2025-07-15 | 2025-07-15 10:00:00 | 2025-07-15 10:00:00 |
| 2   | 2      | LSP-002/02/02/2025 | 2025-02-02 | 2025-07-15 10:15:00 | 2025-07-15 10:15:00 |
| 3   | 3      | LSP-003/01/01/2025 | 2025-01-01 | 2025-07-15 10:30:00 | 2025-07-15 10:30:00 |
| 4   | 4      | LSP-004/10/06/2025 | 2025-06-10 | 2025-07-15 10:45:00 | 2025-07-15 10:45:00 |

## ⚠️ **MASALAH YANG TERJADI:**

### **1. Nomor Urut Tidak Sesuai Tanggal Pembelian**

-   Pembelian 01 Januari 2025 mendapat nomor `LSP-003` (bukan `LSP-001`)
-   Pembelian 02 Februari 2025 mendapat nomor `LSP-002` (bukan `LSP-002`)
-   Pembelian 10 Juni 2025 mendapat nomor `LSP-004` (bukan `LSP-003`)
-   Pembelian 15 Juli 2025 mendapat nomor `LSP-001` (bukan `LSP-004`)

### **2. Reset Rule Berdasarkan `created_at`, Bukan `tanggal`**

-   Sistem menggunakan `created_at` untuk reset rule
-   Bukan menggunakan `tanggal` pembelian
-   Ini menyebabkan inkonsistensi

## 🔧 **SOLUSI YANG DIPERLUKAN:**

### **1. Modifikasi LivestockNumberGeneratorService**

```php
// Ubah dari:
$query->whereYear('created_at', $dateObj->year);

// Menjadi:
$query->whereYear('tanggal', $dateObj->year); // atau field tanggal pembelian
```

### **2. Atau Gunakan Context untuk Override**

```php
// Di Create.php, gunakan tanggal pembelian sebagai context
$numbering = LivestockNumberGeneratorService::generateNumber(
    'livestock_purchases',
    $purchase->tanggal ?? now(), // Gunakan tanggal pembelian
    ['use_purchase_date' => true]
);
```

### **3. Modifikasi Query Logic**

```php
// Di LivestockNumberGeneratorService
if ($resetRule === 'yearly') {
    // Cek apakah ada context untuk menggunakan tanggal pembelian
    if (!empty($context['use_purchase_date'])) {
        $query->whereYear('tanggal', $dateObj->year);
    } else {
        $query->whereYear('created_at', $dateObj->year);
    }
}
```

## 🎯 **KESIMPULAN:**

### **❌ Masalah Saat Ini:**

1. **Nomor urut tidak sesuai urutan tanggal pembelian**
2. **Reset rule berdasarkan `created_at`, bukan `tanggal` pembelian**
3. **Inkonsistensi antara tanggal pembelian dan nomor urut**

### **✅ Solusi yang Diperlukan:**

1. **Modifikasi LivestockNumberGeneratorService** untuk menggunakan `tanggal` pembelian
2. **Atau tambahkan context parameter** untuk override behavior
3. **Atau buat custom handler** untuk kasus khusus

### **🔧 Rekomendasi:**

**Modifikasi LivestockNumberGeneratorService** untuk menggunakan field tanggal pembelian (`tanggal`) sebagai basis reset rule, bukan `created_at`.

Ini akan memastikan nomor urut sesuai dengan urutan tanggal pembelian yang sebenarnya, bukan urutan input data.
