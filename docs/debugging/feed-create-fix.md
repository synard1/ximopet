# [BUGFIX] Feed Create Livewire: Trying to access array offset on value of type null

**Tanggal:** 2024-06-09

## Masalah

Error: `Trying to access array offset on value of type null` pada `app/Livewire/MasterData/Feed/Create.php:222` saat membuka form edit feed.

## Penyebab

-   Kode mengakses `$feed->data[...]` padahal field yang benar adalah `$feed->payload[...]`.
-   Jika field `payload` null, akses array offset menyebabkan error.

## Solusi

-   Ganti semua akses `$feed->data[...]` menjadi `$feed->payload[...]` pada method `showEditForm($id)`.
-   Tambahkan pengecekan null (`?? null` dan `?? []`) untuk future-proof.

## Kode yang Diperbaiki

```php
// Sebelumnya
$this->unit_id = $feed->data['unit_id'];
$this->conversion_units = $feed->data['conversion_units'] ?? [];

// Sesudah
$this->unit_id = $feed->payload['unit_id'] ?? null;
$this->conversion_units = $feed->payload['conversion_units'] ?? [];
```

## Status

-   [x] Fix applied
-   [x] Dokumentasi dibuat
-   [x] Siap production
