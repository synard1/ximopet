# Livestock Number Generator Service Refactor

## Masalah yang Diperbaiki

### Kendala Sebelumnya

Service `LivestockNumberGeneratorService` sebelumnya menggunakan field `created_at` untuk reset dan max number calculation, yang menyebabkan:

1. **Numbering tidak sesuai tanggal transaksi**: Jika user input data pembelian tanggal 15 Januari 2025, tapi input ke sistem tanggal 20 Januari 2025, numbering akan menggunakan tanggal 20 Januari
2. **Inkonsistensi urutan**: Data yang diinput belakangan bisa mendapat nomor urut yang lebih kecil
3. **Tidak sesuai business logic**: Numbering seharusnya berdasarkan tanggal transaksi, bukan tanggal input

### Contoh Masalah

```
Input Order:
1. Input pembelian 15 Jan 2025 → mendapat nomor 001/15/01/2025
2. Input pembelian 10 Jan 2025 → mendapat nomor 002/10/01/2025 (SALAH!)

Expected:
1. Input pembelian 15 Jan 2025 → mendapat nomor 001/15/01/2025
2. Input pembelian 10 Jan 2025 → mendapat nomor 001/10/01/2025 (BENAR!)
```

## Solusi yang Diimplementasikan

### 1. Date Field Mapping

Service sekarang menggunakan field tanggal yang sesuai dengan masing-masing tabel:

```php
private static function getDateFieldForTable(string $table): string
{
    $dateFieldMap = [
        'livestock_purchases' => 'tanggal',
        'livestock_mutations' => 'tanggal',
        'livestock_sales' => 'tanggal',
        'livestock_depletions' => 'tanggal',
        'livestocks' => 'tanggal_masuk',
        'livestock_batches' => 'tanggal_masuk',
    ];

    return $dateFieldMap[$table] ?? 'created_at';
}
```

### 2. Context Override Support

Menambahkan parameter context untuk override behavior:

```php
// Override date field jika ada di context
if (isset($context['date_field'])) {
    $dateField = $context['date_field'];
}
```

### 3. Enhanced Logging

Menambahkan comprehensive logging untuk debugging:

```php
Log::info("LivestockNumberGenerator: Generating number for table {$table}", [
    'date_field' => $dateField,
    'reset_rule' => $resetRule,
    'target_date' => $dateObj->toDateString(),
    'context' => $context
]);
```

### 4. New Utility Methods

#### generateNumberWithDateField()

Untuk generate number dengan date field yang spesifik:

```php
$numbering = LivestockNumberGeneratorService::generateNumberWithDateField(
    'livestock_purchases',
    $purchase->tanggal,
    'tanggal'
);
```

#### validateTableDateField()

Untuk validasi apakah tabel memiliki field tanggal yang diperlukan:

```php
$isValid = LivestockNumberGeneratorService::validateTableDateField('livestock_purchases');
```

#### getNumberingStats()

Untuk mendapatkan statistik numbering:

```php
$stats = LivestockNumberGeneratorService::getNumberingStats('livestock_purchases', '2025-01-15');
```

## Cara Penggunaan

### 1. Basic Usage (Auto-detect date field)

```php
$numbering = LivestockNumberGeneratorService::generateNumber(
    'livestock_purchases',
    $purchase->tanggal
);
```

### 2. With Custom Date Field

```php
$numbering = LivestockNumberGeneratorService::generateNumber(
    'livestock_purchases',
    $purchase->tanggal,
    ['date_field' => 'tanggal']
);
```

### 3. With Context Placeholders

```php
$numbering = LivestockNumberGeneratorService::generateNumber(
    'livestock_purchases',
    $purchase->tanggal,
    [
        'supplier' => $purchase->supplier->code,
        'farm' => $purchase->farm->code
    ]
);
```

## Integration dengan LivestockPurchase

### Di Livewire Component

```php
// Di method save() atau create()
$numbering = LivestockNumberGeneratorService::generateNumber(
    'livestock_purchases',
    $this->tanggal ?? now()
);

$purchase->update([
    'number' => $numbering['number'],
    'number_full' => $numbering['full_number']
]);
```

### Di Model Event (Optional)

```php
// Di LivestockPurchase model
protected static function booted()
{
    static::created(function ($purchase) {
        if (empty($purchase->number)) {
            $numbering = LivestockNumberGeneratorService::generateNumber(
                'livestock_purchases',
                $purchase->tanggal
            );

            $purchase->update([
                'number' => $numbering['number'],
                'number_full' => $numbering['full_number']
            ]);
        }
    });
}
```

## Testing dan Validasi

### 1. Test Non-Sequential Input

```php
// Test case: Input data dengan tanggal yang tidak berurutan
$purchase1 = LivestockPurchase::create([
    'tanggal' => '2025-01-15',
    // ... other fields
]);

$purchase2 = LivestockPurchase::create([
    'tanggal' => '2025-01-10', // Tanggal lebih awal
    // ... other fields
]);

// Expected: purchase2 harus mendapat nomor 001, bukan 002
```

### 2. Test Different Reset Rules

```php
// Test daily reset
$stats = LivestockNumberGeneratorService::getNumberingStats('livestock_purchases', '2025-01-15');

// Test monthly reset
$stats = LivestockNumberGeneratorService::getNumberingStats('livestock_purchases', '2025-01-15');

// Test yearly reset
$stats = LivestockNumberGeneratorService::getNumberingStats('livestock_purchases', '2025-01-15');
```

## Log Output

Service sekarang menghasilkan log yang detail:

```
[2025-01-15 10:30:00] local.INFO: LivestockNumberGenerator: Generating number for table livestock_purchases {
    "date_field": "tanggal",
    "reset_rule": "daily",
    "target_date": "2025-01-15",
    "context": []
}

[2025-01-15 10:30:00] local.INFO: LivestockNumberGenerator: Generated number {
    "table": "livestock_purchases",
    "full_number": "001/15/01/2025",
    "number": 1,
    "date_field_used": "tanggal"
}
```

## Benefits

1. **Numbering sesuai tanggal transaksi**: Menggunakan field `tanggal` bukan `created_at`
2. **Flexible dan extensible**: Support context override dan custom date fields
3. **Better debugging**: Comprehensive logging untuk troubleshooting
4. **Backward compatible**: Tetap support `created_at` sebagai fallback
5. **Production ready**: Error handling dan validation yang robust

## Migration Path

Service ini backward compatible, tidak perlu migration data. Perubahan hanya pada logic numbering generation.

## Future Enhancements

1. **Batch numbering**: Support untuk generate multiple numbers sekaligus
2. **Number reservation**: Reserve numbers untuk transaksi yang sedang diproses
3. **Custom reset rules**: Support untuk reset rules yang lebih kompleks
4. **Number validation**: Validasi format dan uniqueness
5. **Performance optimization**: Caching untuk query yang sering digunakan
