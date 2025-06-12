# Testing Laporan Harian

Direktori ini berisi script testing untuk memvalidasi perbaikan laporan harian.

## Files

-   `laporan-harian-test.php` - Script utama untuk testing
-   `logs/` - Direktori untuk menyimpan hasil test logs

## Cara Menjalankan Test

### 1. Via Command Line

```bash
cd testing
php laporan-harian-test.php
```

### 2. Via Laravel Artisan (Recommended)

```bash
php artisan tinker
require_once 'testing/laporan-harian-test.php';
$tester = new LaporanHarianTest();
$tester->runAllTests();
$tester->testCalculations();
$tester->logTestResults();
```

## Test Scenarios

### 1. Normal Data Test

-   Menguji dengan data lengkap (pakan, deplesi, penjualan)
-   **Expected**: Semua data tampil dengan benar

### 2. No Feed Data Test

-   Menguji ketika tidak ada data penggunaan pakan
-   **Expected**: Template tidak crash, kolom pakan menampilkan 0 atau "-"

### 3. No Depletion Data Test

-   Menguji ketika tidak ada data mortalitas/afkir
-   **Expected**: Deplesi = 0, survival rate = 100%

### 4. No Sales Data Test

-   Menguji ketika tidak ada data penjualan
-   **Expected**: Stock akhir = stock awal - deplesi

### 5. Mixed Data Test

-   Menguji kombinasi data - beberapa batch ada data, beberapa tidak
-   **Expected**: Kolom tetap konsisten, data kosong tampil sebagai 0

### 6. Empty Data Test

-   Menguji dengan farm yang tidak ada atau tidak ada data sama sekali
-   **Expected**: Error message yang proper atau handling graceful

## Calculation Tests

### Stock Calculation

```
Stock Akhir = Stock Awal - Total Deplesi - Total Penjualan
```

### Survival Rate Calculation

```
Survival Rate = (Stock Akhir / Stock Awal) * 100
```

### Depletion Percentage Calculation

```
Depletion Percentage = (Total Deplesi / Stock Awal) * 100
```

## Test Results

Test results akan disimpan dalam format JSON di direktori `logs/` dengan nama:

```
laporan-harian-test-YYYY-MM-DD-HH-mm-ss.json
```

## Troubleshooting

### Error: "No such file or directory"

-   Pastikan script dijalankan dari root directory Laravel
-   Pastikan path ke autoload.php sudah benar

### Error: "Class not found"

-   Pastikan semua dependencies sudah ter-install
-   Jalankan `composer dump-autoload`

### Error: "Database connection"

-   Pastikan database sudah running dan konfigurasi sudah benar
-   Test akan mencoba mengakses data real dari database

## Log Analysis

Hasil test log berisi:

-   Timestamp execution
-   Individual test results (PASS/FAIL)
-   Error messages jika ada
-   Summary statistics

Gunakan log ini untuk:

-   Tracking regression
-   Performance monitoring
-   Debugging issues
-   Historical comparison
