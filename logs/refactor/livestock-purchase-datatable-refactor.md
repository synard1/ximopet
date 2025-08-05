# LivestockPurchaseDataTable Refactoring Log

## Tanggal: 2025-01-27

## File: `app/DataTables/LivestockPurchaseDataTable.php`

## Masalah yang Diperbaiki

### 1. Farm dan Kandang Tidak Ditampilkan Saat Status Draft

**Masalah:**

-   Farm dan kandang menampilkan "-" dan "N/A" saat status transaksi masih draft
-   Informasi ini misleading karena sebenarnya farm dan kandang sudah dipilih saat pembuatan transaksi

**Penyebab:**

-   Kode mengambil farm dan kandang dari `$transaksi->details->first()->livestock->farm/coop`
-   Saat status draft, detail livestock mungkin belum dibuat atau belum terhubung

**Solusi:**

```php
// SEBELUM (mengambil dari detail livestock)
$detail = $transaksi->details->first();
return $detail?->livestock?->farm?->name ?? '-';

// SESUDAH (mengambil langsung dari transaksi)
$farmName = $transaksi->farm?->name ?? '-';
return $farmName;
```

### 2. Error Linter Auth Facade

**Masalah:**

-   `auth()->user()` dan `auth()->check()` menyebabkan error linter
-   Method `user()` dan `check()` tidak dikenali

**Solusi:**

```php
// SEBELUM
$canUpdate = auth()->user()->can('update livestock purchase') ||
    auth()->user()->hasRole('Supervisor');

// SESUDAH
$user = Auth::user();
$canUpdate = $user && ($user->can('update livestock purchase') || $user->hasRole('Supervisor'));
```

### 3. Import Facades yang Hilang

**Masalah:**

-   `Auth` dan `Log` facades tidak diimport
-   Menyebabkan error linter

**Solusi:**

```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
```

## Perubahan yang Dilakukan

### 1. Refactoring Farm Display

```php
->editColumn('farm_id', function (Transaksi $transaksi) {
    // REFACTORED: Tampilkan farm dari transaksi langsung, bukan dari detail
    // Ini memastikan farm selalu ditampilkan meskipun status masih draft
    $farmName = $transaksi->farm?->name ?? '-';

    // Log untuk debugging
    Log::info("[LivestockPurchaseDataTable] Farm display for transaction {$transaksi->id}: {$farmName} (status: {$transaksi->status})");

    return $farmName;
})
```

### 2. Refactoring Coop Display

```php
->editColumn('coop_id', function (Transaksi $transaksi) {
    // REFACTORED: Tampilkan kandang dari transaksi langsung, bukan dari detail
    // Ini memastikan kandang selalu ditampilkan meskipun status masih draft
    $coopName = $transaksi->coop?->name ?? 'N/A';

    // Log untuk debugging
    Log::info("[LivestockPurchaseDataTable] Coop display for transaction {$transaksi->id}: {$coopName} (status: {$transaksi->status})");

    return $coopName;
})
```

### 3. Perbaikan Filter Columns

```php
// SEBELUM
->filterColumn('farm_id', function ($query, $keyword) {
    $query->whereHas('details.livestock.farm', function ($q) use ($keyword) {
        $q->where('name', 'like', "%{$keyword}%");
    });
})

// SESUDAH
->filterColumn('farm_id', function ($query, $keyword) {
    $query->whereHas('farm', function ($q) use ($keyword) {
        $q->where('name', 'like', "%{$keyword}%");
    });
})
```

### 4. Perbaikan Query Eager Loading

```php
// SEBELUM
->with(['details.livestock.farm', 'details.livestock.coop', 'supplier', 'expedition'])

// SESUDAH
->with(['farm', 'coop', 'supplier', 'expedition', 'details.livestock.farm', 'details.livestock.coop'])
```

### 5. Perbaikan Auth Usage

```php
// SEBELUM
if (auth()->user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
    $query->where('company_id', auth()->user()->company_id);
}

// SESUDAH
$user = Auth::user();
if ($user && $user->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
    $query->where('company_id', $user->company_id);
}
```

### 6. Perbaikan JavaScript Auth

```php
// SEBELUM
window.Laravel.user = { id: ' . json_encode(auth()->check() ? auth()->id() : null) . ' };

// SESUDAH
window.Laravel.user = { id: ' . json_encode(Auth::check() ? Auth::id() : null) . ' };
```

## Hasil Refactoring

### 1. Farm dan Kandang Selalu Ditampilkan

-   ✅ Farm dan kandang sekarang ditampilkan meskipun status masih draft
-   ✅ Informasi tidak lagi misleading
-   ✅ Data diambil langsung dari relasi transaksi, bukan dari detail

### 2. Error Linter Teratasi

-   ✅ Semua error linter auth facade teratasi
-   ✅ Import facades yang diperlukan sudah ditambahkan
-   ✅ Kode lebih robust dengan null checking

### 3. Logging untuk Debugging

-   ✅ Ditambahkan logging untuk memantau display farm dan kandang
-   ✅ Memudahkan debugging jika ada masalah di masa depan

### 4. Kode Lebih Bersih

-   ✅ Menghapus kode yang dikomentari
-   ✅ Menghapus filter yang tidak digunakan
-   ✅ Kode lebih mudah dibaca dan dipahami

## Testing

### 1. Test Case: Status Draft

-   **Input:** Transaksi dengan status draft
-   **Expected:** Farm dan kandang ditampilkan sesuai data transaksi
-   **Result:** ✅ Berhasil

### 2. Test Case: Status Completed

-   **Input:** Transaksi dengan status completed
-   **Expected:** Farm dan kandang ditampilkan sesuai data transaksi
-   **Result:** ✅ Berhasil

### 3. Test Case: Auth Permissions

-   **Input:** User dengan berbagai permission
-   **Expected:** Status dropdown sesuai permission user
-   **Result:** ✅ Berhasil

## Kesimpulan

Refactoring ini berhasil mengatasi masalah utama:

1. **Misleading information** - Farm dan kandang sekarang selalu ditampilkan
2. **Linter errors** - Semua error auth facade teratasi
3. **Code quality** - Kode lebih bersih dan maintainable

Perubahan ini memastikan bahwa informasi yang ditampilkan di DataTable selalu akurat dan tidak menyesatkan pengguna, terlepas dari status transaksi.
