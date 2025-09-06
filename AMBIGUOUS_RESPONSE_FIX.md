# Fix: Respons AI Ambigu dan Penamaan Tidak Tepat

## Masalah yang Ditemukan

### 1. Respons Ambigu
AI memberikan respons yang kontradiktif:
- Di awal: "Maaf, saya tidak dapat menampilkan daftar perusahaan..."
- Di tengah: "Namun, saya dapat menampilkan daftar kompanyi yang tersedia..."
- Kemudian menampilkan data perusahaan

### 2. Penamaan Tidak Tepat
- Menggunakan "kompanyi" yang tidak ada dalam kamus bahasa Indonesia
- Seharusnya menggunakan "perusahaan" sesuai input user

## Root Cause Analysis

### 1. Prompt Instruction Tidak Tegas
Di `AiPlanningService.php`, ketika `response_approach = 'no_data_response'`:
```php
// SEBELUM (BERMASALAH)
$prompt .= "Politely explain that no farm data is available. ";
```

Instruksi ini tidak melarang AI untuk menampilkan data, sehingga AI bisa:
1. Mengatakan "tidak ada data"
2. Kemudian tetap menampilkan data yang ada

### 2. Tidak Ada Aturan Terminologi
Tidak ada instruksi khusus untuk menggunakan "perusahaan" instead of "kompanyi".

### 3. Consistency Rule Kurang Kuat
Aturan konsistensi di `AiChatService.php` belum cukup eksplisit mencegah kontradiksi.

## Solusi yang Diterapkan

### 1. Perbaikan Prompt di AiPlanningService.php

**SEBELUM:**
```php
elseif ($approach === 'no_data_response') {
    $prompt .= "Politely explain that no farm data is available. ";
}
elseif ($approach === 'confident_detailed_response') {
    $prompt .= "Provide a comprehensive response based on livestock management principles. ";
}
```

**SESUDAH:**
```php
elseif ($approach === 'no_data_response') {
    $prompt .= "CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information. ";
    $prompt .= "Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. ";
}
elseif ($approach === 'confident_detailed_response') {
    $prompt .= "Provide a comprehensive response based on livestock management principles. ";
    $prompt .= "Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. ";
}
```

### 2. Perbaikan Consistency Rule di AiChatService.php

**SEBELUM:**
```php
$prompt .= "11. CONSISTENCY RULE: If relevant data exists in the context, present it directly. If no relevant data exists, state 'Tidak ada data yang tersedia' (Indonesian) or 'No data available' (English). NEVER mix both statements. ";
```

**SESUDAH:**
```php
$prompt .= "11. CONSISTENCY RULE: If relevant data exists in the context, present it directly. If no relevant data exists, state 'Tidak ada data yang tersedia' (Indonesian) or 'No data available' (English). NEVER mix both statements. NEVER say 'no data' and then show data. ";
```

### 3. Penambahan Aturan Terminologi

**DITAMBAHKAN:**
```php
$prompt .= "13. TERMINOLOGY: Always use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. Use proper Indonesian terminology. ";
```

## Hasil Setelah Fix

### Behavior yang Diharapkan:

**Jika Data Tersedia:**
- ✅ Langsung tampilkan data perusahaan
- ✅ Gunakan terminologi "perusahaan"
- ✅ Tidak ada statement "tidak ada data"

**Jika Data Tidak Tersedia:**
- ✅ State "Tidak ada data yang tersedia"
- ✅ TIDAK menampilkan data apapun
- ✅ Tidak ada kontradiksi

### Contoh Respons yang Benar:

**Scenario 1 - Data Tersedia:**
```
Berikut adalah daftar perusahaan yang tersedia dalam sistem XiMoPet:

1. System Template

Jika Anda memerlukan informasi lebih lanjut tentang perusahaan ini, silakan bertanya.
```

**Scenario 2 - Data Tidak Tersedia:**
```
Tidak ada data perusahaan yang tersedia dalam sistem saat ini.
```

## Files Modified

1. **AiPlanningService.php** (lines 495-502)
   - Menambahkan instruksi CRITICAL untuk no_data_response
   - Menambahkan aturan terminologi "perusahaan"

2. **AiChatService.php** (lines 792-795)
   - Memperkuat consistency rule
   - Menambahkan aturan terminologi

## Testing

Membuat `test_ambiguous_response_fix.php` yang memverifikasi:
- ✅ Instruksi CRITICAL untuk no_data_response
- ✅ Aturan terminologi "perusahaan"
- ✅ Consistency rule yang diperkuat
- ✅ Aplikasi aturan pada semua response approach

## Impact

- ✅ Eliminasi respons ambigu/kontradiktif
- ✅ Penggunaan terminologi bahasa Indonesia yang benar
- ✅ Konsistensi respons berdasarkan ketersediaan data
- ✅ Pengalaman user yang lebih baik dan tidak membingungkan

## Prevention

Untuk mencegah masalah serupa:
1. Selalu gunakan instruksi CRITICAL untuk behavior yang mutlak
2. Tambahkan aturan terminologi yang eksplisit
3. Test respons dengan berbagai scenario data
4. Monitor log untuk deteksi respons ambigu

---

**Date Fixed**: 2025-01-09  
**Fixed By**: AI Assistant  
**Severity**: Medium (User experience affected)  
**Status**: ✅ Resolved