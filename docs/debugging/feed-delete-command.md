# [FEATURE] Artisan Command: feed:delete-all

**Tanggal:** 2024-06-09

## Fungsi

Command ini digunakan untuk menghapus seluruh data Feed dan UnitConversion yang terkait (type=Feed) dari database. Proses penghapusan menggunakan soft delete (jika model mendukung).

## Cara Pakai

```bash
php artisan feed:delete-all
```

-   Akan muncul konfirmasi dua kali sebelum eksekusi.
-   Untuk menghapus tanpa konfirmasi:

```bash
php artisan feed:delete-all --force
```

## Output

-   Jumlah Feed yang dihapus
-   Jumlah UnitConversion (type=Feed) yang dihapus
-   Status sukses

## Catatan

-   **Tidak dapat di-undo!**
-   Pastikan backup data sebelum menjalankan command ini di production.

## Status

-   [x] Implementasi selesai
-   [x] Dokumentasi dibuat
-   [x] Siap digunakan
