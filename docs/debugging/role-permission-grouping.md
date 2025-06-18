# Dokumentasi Refactor Grouping Role Permission

**Tanggal:** 2024-06-09
**Waktu:**

## Perubahan

Dilakukan refactor pada file `RoleModal.php` dan `role-modal.blade.php` untuk mengelompokkan permission berdasarkan kategori utama berikut:

-   Master Data
-   Purchasing
-   Management
-   Usage
-   Mutation
-   Report
-   Other (untuk yang tidak masuk kategori di atas)

## Alasan Perubahan

Agar tampilan dan pengelolaan permission lebih terstruktur, mudah dipahami, dan sesuai kebutuhan bisnis.

## Contoh Struktur Array Grouping

```php
[
  'Master Data' => [
    'Unit Master Data' => [...],
    'Feed Master Data' => [...],
    ...
  ],
  'Purchasing' => [
    'Livestock Purchasing' => [...],
    ...
  ],
  ...
  'Other' => [
    'Operator Assignment' => [...],
    ...
  ]
]
```

## File Terkait

-   app/Livewire/Permission/RoleModal.php
-   resources/views/livewire/permission/role-modal.blade.php

## Catatan

Grouping dilakukan berdasarkan keyword pada nama ability. Jika tidak cocok, masuk ke grup 'Other'.
