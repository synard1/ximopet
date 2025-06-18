# Fitur: Icon Picker & Preview pada Create Menu

**Tanggal update:** 2024-06-09

## Deskripsi

Fitur ini menambahkan preview icon dan icon picker pada form pembuatan menu baru. User dapat memilih icon dari daftar (berdasarkan `public/icons.json`) melalui modal, dan icon yang dipilih otomatis mengisi field input serta menampilkan preview.

## Cara Kerja

-   Input icon kini memiliki tombol "Pilih Icon" dan preview di sampingnya.
-   Klik tombol akan membuka modal berisi daftar icon (kategori: general, it-network, settings).
-   Klik salah satu icon akan mengisi input dan mengupdate preview.
-   User juga bisa mengetik manual kode icon (misal: `fa-gear`).

## Cara Pakai

1. Klik tombol "Pilih Icon" di samping input icon.
2. Pilih icon dari modal, input akan otomatis terisi.
3. Preview icon akan langsung tampil di samping input.
4. Submit form seperti biasa.

## Dependensi

-   **FontAwesome** harus sudah ter-load di halaman agar preview tampil.
-   **public/icons.json** berisi daftar icon yang bisa dipilih.
-   **Bootstrap Modal** untuk tampilan popup.

## Catatan

-   Jika ingin menambah kategori icon, edit script di bawah form dan/atau update `icons.json`.
-   Fitur ini hanya aktif di halaman create menu (`resources/views/pages/menu/create.blade.php`).
