# Livestock Numbering Generation Feature

## Overview

Fitur ini menambahkan automatic numbering generation untuk model Livestock saat proses update status `generateLivestockAndBatch` di LivestockPurchase. Fitur ini memastikan setiap Livestock memiliki nomor unik (`number` dan `number_full`) untuk keperluan tracking dan referensi.

## Implementasi

### Lokasi Implementasi

-   **File**: `app/Livewire/LivestockPurchase/Create.php`
-   **Method**: `generateLivestockAndBatch($purchaseId)`
-   **Service**: `LivestockNumberGeneratorService`

### Logika Implementasi

#### 1. Existing Livestock Handling

```php
if ($existingLivestock) {
    $livestock = $existingLivestock;

    // Generate automatic numbering for existing livestock if missing
    if (empty($livestock->number) || empty($livestock->number_full)) {
        try {
            $numbering = LivestockNumberGeneratorService::generateNumber('livestocks', $purchase->tanggal ?? now(), []);
            $livestock->number = $numbering['number'];
            $livestock->number_full = $numbering['full_number'];
            $livestock->save();

            Log::info('Generated automatic numbering for existing Livestock', [
                'livestock_id' => $livestock->id,
                'number' => $livestock->number,
                'number_full' => $livestock->number_full,
                'date' => $purchase->tanggal,
                'reason' => 'Missing numbering on existing livestock'
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to generate automatic numbering for existing Livestock', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage(),
                'reason' => 'Missing numbering on existing livestock'
            ]);
            // Continue without numbering - not critical
        }
    }
}
```

#### 2. New Livestock Handling

```php
// Generate automatic numbering for livestock
if (empty($livestock->number) || empty($livestock->number_full)) {
    try {
        $numbering = LivestockNumberGeneratorService::generateNumber('livestocks', $purchase->tanggal ?? now(), []);
        $livestock->number = $numbering['number'];
        $livestock->number_full = $numbering['full_number'];
        $livestock->save();

        Log::info('Generated automatic numbering for Livestock', [
            'livestock_id' => $livestock->id,
            'number' => $livestock->number,
            'number_full' => $livestock->number_full,
            'date' => $purchase->tanggal
        ]);
    } catch (\Exception $e) {
        Log::warning('Failed to generate automatic numbering for Livestock', [
            'livestock_id' => $livestock->id,
            'error' => $e->getMessage()
        ]);
        // Continue without numbering - not critical
    }
}
```

## Fitur Utama

### 1. Automatic Numbering Generation

-   **Existing Livestock**: Jika Livestock sudah ada tapi belum memiliki `number` atau `number_full`, sistem akan generate otomatis
-   **New Livestock**: Setiap Livestock baru akan otomatis mendapat numbering
-   **Fallback**: Jika generation gagal, proses tetap dilanjutkan (non-critical)

### 2. Comprehensive Logging

-   **Success Logging**: Menampilkan informasi numbering yang berhasil di-generate
-   **Error Logging**: Menampilkan error jika generation gagal
-   **Final Status**: Menampilkan status numbering di akhir proses

### 3. Error Handling

-   **Graceful Degradation**: Jika numbering generation gagal, proses tetap dilanjutkan
-   **Detailed Error Info**: Error logging yang detail untuk debugging
-   **Rollback Safety**: Jika ada error, semua perubahan di-rollback

## Log Output Examples

### Success Case

```json
{
    "level": "info",
    "message": "Generated automatic numbering for existing Livestock",
    "context": {
        "livestock_id": "uuid-123",
        "number": "LS-2024-001",
        "number_full": "LS-2024-001-FARM01-COOP01",
        "date": "2024-01-15",
        "reason": "Missing numbering on existing livestock"
    }
}
```

### Final Process Log

```json
{
    "level": "info",
    "message": "Finished generateLivestockAndBatch for purchase ID: 123",
    "context": {
        "createdBatchCount": 3,
        "expectedBatchCount": 3,
        "success": true,
        "livestock_info": {
            "livestock_id": "uuid-123",
            "livestock_name": "PR-FARM01-COOP01-150124",
            "number": "LS-2024-001",
            "number_full": "LS-2024-001-FARM01-COOP01",
            "final_initial_quantity": 1000,
            "final_initial_weight": 2.5,
            "final_price": 15000
        },
        "numbering_status": {
            "has_number": true,
            "has_number_full": true,
            "numbering_generated": true
        }
    }
}
```

## Benefits

### 1. Data Integrity

-   Memastikan setiap Livestock memiliki nomor unik
-   Mencegah data inconsistency
-   Memudahkan tracking dan audit trail

### 2. User Experience

-   Otomatis generate numbering tanpa intervensi manual
-   Transparansi proses melalui comprehensive logging
-   Graceful handling jika ada masalah

### 3. Maintenance

-   Detailed logging untuk debugging
-   Error handling yang robust
-   Non-critical failure tidak menghentikan proses utama

## Configuration

### Service Configuration

-   **Service**: `LivestockNumberGeneratorService`
-   **Table**: `livestocks`
-   **Date Source**: `$purchase->tanggal` atau `now()`
-   **Context**: Empty array `[]`

### Numbering Format

-   **Number**: Format pendek (contoh: `LS-2024-001`)
-   **Number Full**: Format lengkap dengan farm dan coop info
-   **Generation**: Berdasarkan tanggal dan sequence

## Dependencies

### Required Services

-   `LivestockNumberGeneratorService`: Untuk generate numbering
-   `Log`: Untuk comprehensive logging

### Required Models

-   `Livestock`: Model utama
-   `LivestockPurchase`: Model purchase
-   `LivestockBatch`: Model batch

## Testing

### Test Scenarios

1. **Existing Livestock with Numbering**: Pastikan tidak generate ulang
2. **Existing Livestock without Numbering**: Pastikan generate otomatis
3. **New Livestock**: Pastikan generate numbering baru
4. **Service Failure**: Pastikan proses tetap berjalan
5. **Database Error**: Pastikan rollback berfungsi

### Validation Points

-   Numbering format sesuai standar
-   Logging information lengkap
-   Error handling berfungsi
-   Performance tidak terpengaruh

## Future Enhancements

### Potential Improvements

1. **Custom Numbering Format**: Konfigurasi format per company
2. **Numbering Validation**: Validasi uniqueness
3. **Bulk Numbering**: Generate numbering untuk multiple livestock
4. **Numbering History**: Track perubahan numbering
5. **Numbering Templates**: Template numbering yang dapat dikustomisasi

## Related Documentation

-   [Livestock Model Documentation](../models/livestock-model.md)
-   [LivestockPurchase Process Flow](../processes/livestock-purchase-flow.md)
-   [Numbering Service Documentation](../services/numbering-service.md)
