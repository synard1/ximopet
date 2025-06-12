# Smart Analytics - Quick Start Guide

## 🚀 Quick Setup (5 Menit)

### 1. Jalankan Migrasi

```bash
php artisan migrate
```

### 2. Hitung Analytics Pertama Kali

```bash
# Hitung analytics untuk 7 hari terakhir
php artisan analytics:daily-calculate --days=7
```

### 3. Akses Dashboard

Buka browser dan navigasi ke: `/report/smart-analytics`

## 📊 Fitur Utama

### Dashboard Overview

-   **Mortality Rate**: Tingkat kematian rata-rata
-   **Efficiency Score**: Skor efisiensi kandang (0-100)
-   **FCR**: Feed Conversion Ratio
-   **Revenue**: Total pendapatan

### Tab Analytics

1. **Overview**: Ringkasan performa keseluruhan
2. **Mortality**: Analisis kematian ternak
3. **Sales**: Analisis penjualan
4. **Production**: Analisis produksi
5. **Rankings**: Ranking performa kandang
6. **Alerts**: Peringatan dan rekomendasi

## ⚡ Console Commands

```bash
# Hitung analytics harian
php artisan analytics:daily-calculate

# Hitung untuk tanggal spesifik
php artisan analytics:daily-calculate --date=2024-01-15

# Paksa perhitungan ulang
php artisan analytics:daily-calculate --force
```

## 🎯 Key Metrics

### Efficiency Score (0-100)

-   **90-100**: Excellent (A+)
-   **80-89**: Good (A)
-   **70-79**: Average (B)
-   **60-69**: Below Average (C)
-   **<60**: Poor (D)

### Alert Levels

-   **Critical**: Mortalitas >100/hari
-   **High**: Mortalitas >50/hari, Efisiensi <60%
-   **Medium**: FCR >2.5, Pertumbuhan <30g/hari
-   **Low**: Peringatan umum

## 🔧 Troubleshooting

### Data Tidak Muncul?

```bash
# Check apakah ada data livestock
php artisan tinker
>>> App\Models\Livestock::count()

# Check apakah analytics sudah dihitung
>>> App\Models\DailyAnalytics::count()
```

### Performance Lambat?

```bash
# Tambah index database
php artisan migrate:fresh
```

## 📱 Tips Penggunaan

1. **Filter Data**: Gunakan filter farm/kandang untuk analisis spesifik
2. **Rentang Tanggal**: Pilih periode yang sesuai untuk analisis trend
3. **Resolve Alerts**: Selalu resolve alert setelah ditindaklanjuti
4. **Export Data**: Gunakan fitur export untuk laporan

## 🎨 Customization

### Ubah Threshold Alert

Edit di `app/Services/AnalyticsService.php`:

```php
// High mortality alert
if ($analytic->mortality_count > 50) { // Ubah dari 50 ke nilai lain
    // ...
}
```

### Tambah Metric Baru

1. Tambah kolom di migration
2. Update calculation di `AnalyticsService`
3. Tambah di dashboard view

## 📞 Need Help?

-   **Logs**: `storage/logs/laravel.log`
-   **Debug**: `php artisan tinker`
-   **Documentation**: `/docs/SMART_ANALYTICS.md`
