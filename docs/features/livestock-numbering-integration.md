# Livestock Numbering System Integration

## Overview

Sistem penomoran otomatis untuk livestock management telah diintegrasikan ke dalam `LivestockPurchase/Create.php` Livewire component. Sistem ini menggunakan `LivestockNumberGeneratorService` untuk menghasilkan nomor unik dengan format yang dapat dikonfigurasi.

## Implementasi

### 1. Import Service

```php
use App\Services\Livestock\LivestockNumberGeneratorService;
```

### 2. Integrasi dalam Method `save()`

Sistem penomoran otomatis diterapkan saat pembelian livestock baru dibuat:

```php
// CREATE MODE
$purchase = LivestockPurchase::create($purchaseData);

// Generate automatic numbering for livestock purchase
if (empty($purchase->number) || empty($purchase->number_full)) {
    try {
        $numbering = LivestockNumberGeneratorService::generateNumber('livestock_purchases', $purchase->tanggal ?? now(), []);
        $purchase->number = $numbering['number'];
        $purchase->number_full = $numbering['full_number'];
        $purchase->save();

        Log::info('Generated automatic numbering for LivestockPurchase', [
            'purchase_id' => $purchase->id,
            'number' => $purchase->number,
            'number_full' => $purchase->number_full,
            'date' => $purchase->tanggal
        ]);
    } catch (\Exception $e) {
        Log::warning('Failed to generate automatic numbering for LivestockPurchase', [
            'purchase_id' => $purchase->id,
            'error' => $e->getMessage()
        ]);
        // Continue without numbering - not critical
    }
}
```

### 3. Integrasi dalam Method `generateLivestockAndBatch()`

#### 3.1 Penomoran untuk Livestock

```php
$livestock = \App\Models\Livestock::create([...]);

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

#### 3.2 Penomoran untuk LivestockBatch

```php
$batch = \App\Models\LivestockBatch::create($batchData);

// Generate automatic numbering for livestock batch
if (empty($batch->number) || empty($batch->number_full)) {
    try {
        $numbering = LivestockNumberGeneratorService::generateNumber('livestock_batches', $purchase->tanggal ?? now(), []);
        $batch->number = $numbering['number'];
        $batch->number_full = $numbering['full_number'];
        $batch->save();

        Log::info('Generated automatic numbering for LivestockBatch', [
            'batch_id' => $batch->id,
            'number' => $batch->number,
            'number_full' => $batch->number_full,
            'date' => $purchase->tanggal
        ]);
    } catch (\Exception $e) {
        Log::warning('Failed to generate automatic numbering for LivestockBatch', [
            'batch_id' => $batch->id,
            'error' => $e->getMessage()
        ]);
        // Continue without numbering - not critical
    }
}
```

### 4. Helper Methods

#### 4.1 `generateAutomaticNumbering()`

Method helper untuk menghasilkan nomor otomatis:

```php
/**
 * Generate automatic numbering for livestock purchase records
 *
 * @param string $table Table name (livestock_purchases, livestocks, livestock_batches)
 * @param string $date Date for numbering context
 * @param array $context Additional context for placeholders
 * @return array|null Generated numbering data or null if failed
 */
private function generateAutomaticNumbering(string $table, string $date, array $context = []): ?array
{
    try {
        $numbering = LivestockNumberGeneratorService::generateNumber($table, $date, $context);

        Log::info("Generated automatic numbering for {$table}", [
            'table' => $table,
            'date' => $date,
            'number' => $numbering['number'],
            'number_full' => $numbering['full_number'],
            'context' => $context
        ]);

        return $numbering;
    } catch (\Exception $e) {
        Log::warning("Failed to generate automatic numbering for {$table}", [
            'table' => $table,
            'date' => $date,
            'error' => $e->getMessage(),
            'context' => $context
        ]);

        return null;
    }
}
```

#### 4.2 `applyAutomaticNumbering()`

Method helper untuk menerapkan nomor ke model:

```php
/**
 * Apply automatic numbering to a model
 *
 * @param \Illuminate\Database\Eloquent\Model $model Model to apply numbering to
 * @param string $table Table name for numbering context
 * @param string $date Date for numbering context
 * @param array $context Additional context for placeholders
 * @return bool Success status
 */
private function applyAutomaticNumbering($model, string $table, string $date, array $context = []): bool
{
    if (empty($model->number) || empty($model->number_full)) {
        $numbering = $this->generateAutomaticNumbering($table, $date, $context);

        if ($numbering) {
            $model->number = $numbering['number'];
            $model->number_full = $numbering['full_number'];
            $model->save();

            Log::info("Applied automatic numbering to {$table}", [
                'model_id' => $model->id,
                'number' => $model->number,
                'number_full' => $model->number_full
            ]);

            return true;
        }
    }

    return false;
}
```

## Konfigurasi

Sistem menggunakan konfigurasi dari `LivestockNumberingConfig`:

### Format Default

-   **Livestock Purchases**: `LSP-0001/14/07/2025`
-   **Livestocks**: `LS-0001/14/07/2025`
-   **Livestock Batches**: `LSB-0001/14/07/2025`

### Placeholders yang Didukung

-   `{urut:x}` - Nomor urut dengan padding x digit
-   `{TGL}` - Tanggal (dd)
-   `{BLN}` - Bulan (mm)
-   `{THN}` - Tahun (yyyy)
-   `{prefix}` - Prefix dari konfigurasi

## Error Handling

Sistem penomoran dirancang dengan error handling yang robust:

1. **Non-Critical**: Jika penomoran gagal, proses tetap dilanjutkan
2. **Logging**: Semua error dan success di-log untuk debugging
3. **Graceful Degradation**: Model tetap dibuat meski tanpa nomor

## Logging

Semua operasi penomoran di-log dengan detail:

```php
Log::info('Generated automatic numbering for LivestockPurchase', [
    'purchase_id' => $purchase->id,
    'number' => $purchase->number,
    'number_full' => $purchase->number_full,
    'date' => $purchase->tanggal
]);
```

## Database Schema

Pastikan tabel memiliki kolom berikut:

```sql
ALTER TABLE livestock_purchases ADD COLUMN number INT NULL;
ALTER TABLE livestock_purchases ADD COLUMN number_full VARCHAR(50) NULL;

ALTER TABLE livestocks ADD COLUMN number INT NULL;
ALTER TABLE livestocks ADD COLUMN number_full VARCHAR(50) NULL;

ALTER TABLE livestock_batches ADD COLUMN number INT NULL;
ALTER TABLE livestock_batches ADD COLUMN number_full VARCHAR(50) NULL;
```

## Usage Examples

### 1. Basic Usage

```php
// Otomatis ter-generate saat create purchase
$purchase = LivestockPurchase::create($data);
// number dan number_full akan terisi otomatis
```

### 2. Manual Generation

```php
$numbering = LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-01-15', []);
$purchase->number = $numbering['number'];
$purchase->number_full = $numbering['full_number'];
```

### 3. With Context

```php
$context = ['farm' => 'FARM001', 'coop' => 'COOP001'];
$numbering = LivestockNumberGeneratorService::generateNumber('livestocks', '2025-01-15', $context);
```

## Benefits

1. **Otomatis**: Nomor ter-generate otomatis saat record dibuat
2. **Konsisten**: Format seragam untuk semua tabel livestock
3. **Konfigurasi**: Mudah dikustomisasi melalui config
4. **Robust**: Error handling yang baik
5. **Audit Trail**: Logging lengkap untuk tracking
6. **Future-Proof**: Mendukung extensibility dan custom placeholders

## Migration Path

Untuk mengaktifkan sistem penomoran di tabel yang sudah ada:

```php
// Update existing records with numbering
$purchases = LivestockPurchase::whereNull('number')->get();
foreach ($purchases as $purchase) {
    $numbering = LivestockNumberGeneratorService::generateNumber('livestock_purchases', $purchase->tanggal);
    $purchase->update([
        'number' => $numbering['number'],
        'number_full' => $numbering['full_number']
    ]);
}
```

## Testing

```php
// Test numbering generation
$numbering = LivestockNumberGeneratorService::generateNumber('livestock_purchases', '2025-01-15');
assert(!empty($numbering['number']));
assert(!empty($numbering['full_number']));
assert(str_contains($numbering['full_number'], 'LSP'));
```

## Troubleshooting

### Common Issues

1. **Number not generated**: Cek konfigurasi di `LivestockNumberingConfig`
2. **Duplicate numbers**: Pastikan reset rule sesuai (daily/monthly/yearly)
3. **Format issues**: Validasi format string di config

### Debug Commands

```php
// Check config
dd(LivestockNumberingConfig::getFor('livestock_purchases'));

// Test generation
dd(LivestockNumberGeneratorService::generateNumber('livestock_purchases', now()));
```

## Future Enhancements

1. **Database Config**: Migrasi konfigurasi ke database
2. **Custom Handlers**: Support untuk custom numbering logic
3. **Bulk Operations**: Optimasi untuk bulk numbering
4. **Validation Rules**: Validasi format dan uniqueness
5. **API Integration**: REST API untuk numbering service
