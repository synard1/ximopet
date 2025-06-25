# Refactoring Log: Penyesuaian Opsi Metode pada Halaman Pengaturan Ternak

**Tanggal:** 2025-06-23 00:31:00

## Ringkasan Perubahan

Melakukan refactoring pada halaman "Pengaturan Metode Pencatatan Ayam" untuk memastikan opsi yang ditampilkan sesuai dengan konfigurasi yang ada di `CompanyConfig.php`. Sebelumnya, opsi metode (Depletion, Mutasi, Pemakaian Pakan) di-hardcode di dalam file Blade, sehingga tidak sinkron dengan status pengembangan (Ready, Development) yang diatur di backend.

Perubahan ini membuat komponen menjadi dinamis dan selaras dengan `Company Settings`, memastikan konsistensi di seluruh sistem.

## Lokasi File yang Diubah

1.  **Livewire Component:** `app/Livewire/MasterData/Livestock/Settings.php`
2.  **Blade View:** `resources/views/livewire/master-data/livestock/settings.blade.php`

## Analisis Masalah

-   **UI Tidak Konsisten:** Halaman pengaturan per ternak menampilkan opsi metode yang tidak lengkap dan tidak sesuai dengan statusnya (misalnya, hanya menampilkan "FIFO (Tersedia)" dan menyembunyikan metode lain seperti LIFO dan Manual).
-   **Data Hardcoded:** Dropdown pada file Blade (`settings.blade.php`) diisi dengan data statis, bukan dari konfigurasi pusat.
-   **Logika Tidak Dinamis:** Komponen Livewire (`Settings.php`) tidak mengambil data metode secara dinamis dari `CompanyConfig.php`, melainkan menggunakan array yang di-hardcode.

## Detail Perbaikan

### 1. Update Komponen Livewire (`Settings.php`)

Logika untuk memuat konfigurasi diubah untuk mengambil semua metode yang tersedia beserta statusnya dari `CompanyConfig`.

-   **Menghapus Array Hardcoded:** Variabel `$available_methods` yang berisi data statis dikosongkan.
-   **Mengambil Konfigurasi Dinamis:** Di dalam fungsi `loadConfig()`, variabel `$available_methods` sekarang diisi dengan data dari `CompanyConfig::getDefaultLivestockConfig()`. Ini mencakup `depletion_methods`, `mutation_methods`, dan `feed_usage_methods`.
-   **Mengatur Nilai Default dari Config:** Nilai default untuk setiap metode sekarang juga diambil dari konfigurasi, bukan di-hardcode.

```php
// app/Livewire/MasterData/Livestock/Settings.php

// ...
use App\Config\CompanyConfig;

class Settings extends Component
{
    // ...
    public $available_methods = [];
    // ...
    public function loadConfig()
    {
        // ...
        $config = array_merge($defaultConfig, $companyConfig);

        // Load all available methods from config
        $this->available_methods = [
            'recording_method' => ['batch', 'total'],
            'depletion_methods' => $config['recording_method']['batch_settings']['depletion_methods'],
            'mutation_methods' => $config['recording_method']['batch_settings']['mutation_methods'],
            'feed_usage_methods' => $config['recording_method']['batch_settings']['feed_usage_methods'],
        ];

        if ($this->has_single_batch) {
            // ...
        } else {
            $this->recording_method = 'batch';
            $this->depletion_method = $config['recording_method']['batch_settings']['depletion_method_default'];
            $this->mutation_method = $config['recording_method']['batch_settings']['mutation_method_default'];
            $this->feed_usage_method = $config['recording_method']['batch_settings']['feed_usage_method_default'];
        }
    }
    // ...
}
```

### 2. Update Blade View (`settings.blade.php`)

File Blade diubah untuk me-render dropdown secara dinamis berdasarkan data yang sekarang dikirim dari komponen.

-   **Looping Melalui Config:** `foreach` diubah untuk mengiterasi array asosiatif `$available_methods` yang baru.
-   **Logika Pengecekan Status:** Logika ditambahkan untuk memeriksa status (`enabled` dan `status === 'ready'`) dari setiap metode untuk mengaktifkan atau menonaktifkan `<option>` dan menampilkan teks yang sesuai ("Tersedia" atau "Dalam Pengembangan").

```html
<!-- resources/views/livewire/master-data/livestock/settings.blade.php -->

<!-- Contoh untuk Metode Depletion -->
<select class="form-select" wire:model="depletion_method" {{ $has_single_batch ? 'disabled' : '' }}>
    @foreach($available_methods['depletion_methods'] as $method => $config)
        <option value="{{ $method }}" {{ !($config['enabled'] && $config['status'] === 'ready') ? 'disabled' : '' }}>
            {{ strtoupper($method) }}
            @if(!($config['enabled'] && $config['status'] === 'ready'))
                (Dalam Pengembangan)
            @else
                (Tersedia)
            @endif
        </option>
    @endforeach
</select>
```

Perubahan ini berhasil menyelaraskan halaman pengaturan per ternak dengan konfigurasi pusat, menciptakan UI yang konsisten dan _future-proof_.
