# [REFACTOR] FeedSeeder & DemoSeeder: Centralized Feed Creation Helper

**Tanggal:** 2024-06-09

## Masalah

-   Terdapat duplikasi logika pembuatan Feed (pakan) dan UnitConversion di FeedSeeder dan DemoSeeder.
-   Field yang digunakan tidak konsisten (kadang 'data', kadang 'payload').
-   Sulit maintenance dan rawan error jika ada perubahan struktur Feed.

## Solusi

-   Dibuat helper reusable `FeedHelper::createFeedWithConversions` di `database/seeders/helpers/FeedHelper.php`.
-   Semua pembuatan Feed dan konversi satuan di FeedSeeder dan DemoSeeder dipanggil melalui helper ini.
-   Field yang digunakan sekarang konsisten: **data** (bukan payload), sesuai model Feed.
-   Kode lebih robust, future-proof, dan mudah di-maintain.
-   Fungsi `createPakan` di DemoSeeder **dihapus**. Semua pembuatan Feed hanya lewat FeedSeeder.

## Contoh Penggunaan

```php
FeedHelper::createFeedWithConversions(
    $code, $name, $unitKg, $unitSak, $createdBy, $companyId
);
```

## File yang Diubah

-   database/seeders/helpers/FeedHelper.php (pakai kolom data)
-   database/seeders/FeedSeeder.php
-   database/seeders/DemoSeeder.php (hapus createPakan, panggil FeedSeeder)

## Status

-   [x] Duplikasi dihilangkan
-   [x] Field konsisten (data)
-   [x] Siap production
-   [x] Dokumentasi dibuat/diupdate
