# Livestock Depletion Configuration System

## Overview

Sistem konfigurasi terpusat untuk mengelola jenis deplesi ternak yang mendukung backward compatibility antara terminologi bahasa Indonesia (legacy) dan bahasa Inggris (standar).

## Masalah yang Diselesaikan

**Sebelum:**

-   Inkonsistensi penggunaan istilah deplesi: "Mati" vs "mortality", "Afkir" vs "culling"
-   Data tidak dapat di-load dengan baik ketika ada perubahan terminologi
-   Tidak ada standarisasi penamaan di seluruh aplikasi
-   Kesulitan maintenance ketika perlu mengubah terminology

**Sesudah:**

-   Konfigurasi terpusat dengan mapping yang jelas
-   Backward compatibility penuh dengan data legacy
-   Standarisasi penamaan dengan fallback ke format lama
-   Mudah untuk menambah jenis deplesi baru

## Arsitektur

### 1. Komponen Utama

```
LivestockDepletionConfig.php    // Core configuration class
├── HasDepletionTypeConfig.php  // Trait for models
├── LivestockDepletionService.php // Service for data operations
├── MigrateDepletionTypes.php   // Command for data migration
└── docs/livestock-depletion-config.md // Documentation
```

## Konfigurasi

### 1. LivestockDepletionConfig Class

Kelas utama yang mengelola mapping dan validasi jenis deplesi:

```php
// Standard types (English - Primary)
const TYPE_MORTALITY = 'mortality';
const TYPE_CULLING = 'culling';
const TYPE_SALES = 'sales';

// Legacy types (Indonesian - Backward compatibility)
const LEGACY_TYPE_MATI = 'Mati';
const LEGACY_TYPE_AFKIR = 'Afkir';
const LEGACY_TYPE_JUAL = 'Jual';
```

### 2. Method Utama

#### normalize($type)

Mengubah input apapun ke format standar:

```php
LivestockDepletionConfig::normalize('Mati') // returns 'mortality'
LivestockDepletionConfig::normalize('mortality') // returns 'mortality'
```

#### toLegacy($type)

Mengubah format standar ke format legacy:

```php
LivestockDepletionConfig::toLegacy('mortality') // returns 'Mati'
```

#### getDisplayName($type, $short = false)

Mendapat nama display yang user-friendly:

```php
LivestockDepletionConfig::getDisplayName('mortality') // returns 'Kematian Ternak'
LivestockDepletionConfig::getDisplayName('mortality', true) // returns 'Mati'
```

## Implementasi

### 1. Di Records.php

```php
use App\Config\LivestockDepletionConfig;

// Menggunakan konstanta saat menyimpan deplesi
$mortalityResult = $this->storeDeplesiWithDetails(
    LivestockDepletionConfig::TYPE_MORTALITY,
    $this->mortality,
    $recording->id
);

// Normalisasi saat membaca data
$mortalityTypes = [
    LivestockDepletionConfig::LEGACY_TYPE_MATI,
    LivestockDepletionConfig::TYPE_MORTALITY
];
$mortality = $deplesi->whereIn('jenis', $mortalityTypes)->sum('jumlah');
```

### 2. Di ManualDepletion.php

```php
// Validasi menggunakan config
public function updatedDepletionType($value)
{
    if (!LivestockDepletionConfig::isValidType($value)) {
        $this->addError('depletionType', 'Invalid depletion type selected.');
    }
}

// Normalisasi sebelum menyimpan
$normalizedType = LivestockDepletionConfig::normalize($this->depletionType);
```

### 3. Di Model dengan Trait

```php
use App\Traits\HasDepletionTypeConfig;

class LivestockDepletion extends Model
{
    use HasDepletionTypeConfig;

    // Sekarang model memiliki method:
    // $depletion->normalized_type
    // $depletion->getDisplayName()
    // $depletion->category
    // LivestockDepletion::ofType('mortality')
}
```

## Backward Compatibility

### 1. Data Loading

Sistem dapat membaca data lama dan baru:

```php
// Query yang mendukung kedua format
$mortality = LivestockDepletion::ofType('mortality')->sum('jumlah');
// Sama dengan:
$mortality = LivestockDepletion::whereIn('jenis', ['Mati', 'mortality'])->sum('jumlah');
```

### 2. Data Saving

Data baru disimpan dalam format legacy untuk kompatibilitas:

```php
// Input: 'mortality'
// Disimpan di database: 'Mati'
// Metadata: menyimpan kedua format untuk tracking
```

### 3. Migration Support

Command untuk migrasi data existing:

```bash
# Cek status migrasi
php artisan livestock:migrate-depletion-types --dry-run

# Jalankan migrasi
php artisan livestock:migrate-depletion-types

# Force migrasi ulang
php artisan livestock:migrate-depletion-types --force
```

## Struktur Data

### 1. Database Storage

Data tetap disimpan dalam format legacy untuk backward compatibility:

```sql
-- livestock_depletions table
id | livestock_id | tanggal | jenis | jumlah | metadata
1  | 123         | 2025-01-23 | Mati  | 5      | {...}
```

### 2. Metadata Enhancement

Setiap record diperkaya dengan metadata config:

```json
{
    "depletion_config": {
        "original_type": "mortality",
        "normalized_type": "mortality",
        "legacy_type": "Mati",
        "display_name": "Kematian Ternak",
        "category": "loss",
        "config_version": "1.0"
    }
}
```

### 3. API Response

Fleksibel support multiple format:

```php
// Format legacy
$depletion->toApiFormat('legacy')
// Returns: { "jenis": "Mati", "jenis_display": "Mati" }

// Format standard
$depletion->toApiFormat('standard')
// Returns: { "type": "mortality", "type_display": "Kematian Ternak" }

// Format both (default)
$depletion->toApiFormat('both')
// Returns: Both formats + metadata
```

## Jenis Deplesi Supported

### 1. Loss Category

-   **Mortality** (`mortality` / `Mati`)

    -   Display: "Kematian Ternak"
    -   Requires: tanggal, jumlah
    -   Optional: reason

-   **Culling** (`culling` / `Afkir`)
    -   Display: "Afkir Ternak"
    -   Requires: tanggal, jumlah, reason
    -   Optional: weight

### 2. Transfer Category

-   **Sales** (`sales` / `Jual`)

    -   Display: "Penjualan Ternak"
    -   Requires: tanggal, jumlah, weight, price
    -   Optional: buyer_info

-   **Mutation** (`mutation` / `Mutasi`)
    -   Display: "Mutasi Ternak"
    -   Requires: tanggal, jumlah, destination
    -   Optional: reason

### 3. Other Category

-   **Transfer** (`transfer` / `Transfer`)

    -   Display: "Transfer Ternak"
    -   Requires: tanggal, jumlah, destination

-   **Other** (`other` / `Lainnya`)
    -   Display: "Lainnya"
    -   Requires: tanggal, jumlah, reason

## Validation Rules

Setiap jenis deplesi memiliki aturan validasi spesifik:

```php
// Mortality: basic validation
['tanggal' => 'required|date', 'jumlah' => 'required|integer|min:1']

// Sales: comprehensive validation
[
    'tanggal' => 'required|date',
    'jumlah' => 'required|integer|min:1',
    'weight' => 'required|numeric|min:0',
    'price' => 'required|numeric|min:0'
]
```

## Form Integration

### 1. Dropdown Options

```php
// Di Livewire component
public function getDepletionTypesProperty()
{
    return LivestockDepletionConfig::getTypesForForm();
}
```

### 2. Dynamic Validation

```php
// Validasi berubah berdasarkan jenis deplesi
$rules = LivestockDepletionConfig::getValidationRules($this->depletionType);
```

### 3. Conditional Fields

```php
// Field muncul berdasarkan requirement
@if(LivestockDepletionConfig::requiresField($depletionType, 'reason'))
    <input type="text" wire:model="reason" required>
@endif
```

## Maintenance

### 1. Menambah Jenis Deplesi Baru

1. Tambah konstanta di `LivestockDepletionConfig`:

```php
const TYPE_NEW_TYPE = 'new_type';
const LEGACY_TYPE_NEW_TYPE = 'TipeBaruIndonesia';
```

2. Update mapping arrays:

```php
private static array $typeMapping = [
    // ... existing mappings
    'TipeBaruIndonesia' => self::TYPE_NEW_TYPE,
    'new_type' => self::TYPE_NEW_TYPE,
];
```

3. Update display names dan validation rules

### 2. Mengubah Display Names

Cukup update array `$displayNames` tanpa mempengaruhi data existing.

### 3. Migrasi Data

Gunakan command built-in untuk migrasi data existing:

```bash
php artisan livestock:migrate-depletion-types
```

## Testing

### 1. Unit Tests

```php
public function test_normalize_legacy_types()
{
    $this->assertEquals('mortality', LivestockDepletionConfig::normalize('Mati'));
    $this->assertEquals('mortality', LivestockDepletionConfig::normalize('mortality'));
}

public function test_backward_compatibility()
{
    // Test data dengan format lama masih bisa dibaca
    $deplesi = LivestockDepletion::ofType('mortality')->first();
    $this->assertNotNull($deplesi);
}
```

### 2. Integration Tests

```php
public function test_records_save_with_config()
{
    // Test Records.php menggunakan config dengan benar
    $this->actingAs($user)->post('/records/save', [
        'mortality' => 5,
        // ... other data
    ]);

    $this->assertDatabaseHas('livestock_depletions', [
        'jenis' => 'Mati', // Saved in legacy format
        'jumlah' => 5
    ]);
}
```

## Error Handling

### 1. Invalid Types

```php
try {
    $normalized = LivestockDepletionConfig::normalize('InvalidType');
} catch (InvalidArgumentException $e) {
    // Handle unknown depletion type
}
```

### 2. Migration Errors

Command migration menangani error dengan graceful fallback dan detailed logging.

### 3. Validation Errors

Setiap field yang required akan divalidasi berdasarkan config rules.

## Performance Considerations

1. **Static Caching**: Config data di-cache di memory untuk performa
2. **Minimal DB Changes**: Data structure existing tidak berubah
3. **Lazy Loading**: Metadata hanya di-generate saat dibutuhkan
4. **Batch Migration**: Migrasi dilakukan dalam batch untuk dataset besar

## Future Enhancements

1. **Multi-language Support**: Extend untuk bahasa lain
2. **Dynamic Configuration**: Config bisa diubah via admin panel
3. **Audit Trail**: Track semua perubahan terminology
4. **API Versioning**: Support multiple API versions dengan format berbeda

## Troubleshooting

### 1. Data Tidak Terbaca

**Problem**: Data lama tidak muncul setelah implementasi config
**Solution**: Jalankan command migrasi atau pastikan menggunakan method `ofType()` untuk query

### 2. Validation Error

**Problem**: Validation gagal dengan field yang sudah diisi
**Solution**: Cek apakah jenis deplesi memerlukan field tersebut dengan `requiresField()`

### 3. Migration Failed

**Problem**: Command migrasi gagal dengan error
**Solution**: Cek log, gunakan `--dry-run` untuk debug, atau `--force` untuk retry

## UI Implementation

### Recording Form Simplification (January 23, 2025)

The recording form has been simplified to show only **total depletion count** instead of detailed per-type inputs to improve user experience and reduce form complexity.

#### Changes Made:

1. **Simplified Display**:

    - Replaced individual "Mati" and "Afkir" input fields with a single "Total Deplesi" summary display
    - Shows total count: `(mortality + culling) ekor`
    - Displays breakdown: `💀 Mati: X | 🛑 Afkir: Y`
    - Added "Detail" button for future detailed input modal

2. **Yesterday's Context Enhancement**:

    - Added comprehensive yesterday's depletion data display
    - Shows formatted date and total depletion count
    - Displays detailed breakdown of yesterday's mortality and culling
    - Provides "No depletion" message when applicable
    - Clear separation between yesterday's data and current day input

3. **Data Preservation**:

    - Hidden inputs maintain `wire:model="mortality"` and `wire:model="culling"`
    - Backend functionality remains unchanged
    - Data validation and saving logic preserved

4. **User Experience**:
    - Cleaner, less cluttered form interface
    - Quick overview of total depletion at a glance
    - Historical context for better decision making
    - Placeholder for detailed depletion management UI (planned)

#### Implementation Details:

```php
<!-- Before: Separate inputs -->
<x-input.group col="6" label="💀 Mati (Ekor)">
    <input type="number" wire:model="mortality" class="form-control">
</x-input.group>
<x-input.group col="6" label="🛑 Afkir (Ekor)">
    <input type="number" wire:model="culling" class="form-control">
</x-input.group>

<!-- After: Summary display with yesterday's context -->
<x-input.group col="6" label="⚠️ Total Deplesi (Ekor)">
    <div class="form-control bg-light">
        <span>{{ ($mortality ?? 0) + ($culling ?? 0) }} ekor</span>
        <button type="button" onclick="openDepletionModal()">Detail</button>
    </div>

    <!-- Yesterday's Depletion Data -->
    @if($yesterdayData && $yesterdayData['total_depletion'] > 0)
    <small class="text-muted">
        📊 Kemarin ({{ $yesterdayData['formatted_date'] }}): {{ $yesterdayData['total_depletion'] }} ekor deplesi
        @if($yesterdayData['mortality'] > 0 || $yesterdayData['culling'] > 0)
        <br>&nbsp;&nbsp;&nbsp;&nbsp;💀 Mati: {{ $yesterdayData['mortality'] }} | 🛑 Afkir: {{ $yesterdayData['culling'] }}
        @endif
    </small>
    @elseif($date)
    <small class="text-muted">📊 Kemarin: Tidak ada deplesi</small>
    @endif

    <!-- Current Day Breakdown -->
    <small class="text-muted">
        <strong>Hari ini:</strong> 💀 Mati: {{ $mortality ?? 0 }} | 🛑 Afkir: {{ $culling ?? 0 }}
    </small>
</x-input.group>
<input type="hidden" wire:model="mortality">
<input type="hidden" wire:model="culling">
```

#### Future Plans:

1. **Dedicated Depletion Management Modal**:

    - Detailed form for inputting mortality and culling data
    - Reason codes and cause tracking
    - Historical depletion analysis
    - FIFO batch allocation for multi-batch livestock

2. **Enhanced Analytics**:
    - Depletion trend analysis
    - Cause-specific reporting
    - Performance impact assessment
    - Alert system for abnormal depletion rates

#### Benefits:

-   **Simplified Interface**: Reduced form complexity and cognitive load
-   **Better UX**: Focus on essential information during recording
-   **Future-Ready**: Prepared for more sophisticated depletion management features
-   **Data Integrity**: All existing functionality preserved
-   **Consistent Backend**: No changes to data processing or validation logic

### Manual Depletion Method Indication (January 23, 2025)

Enhanced the recording form to clearly indicate when Manual Depletion mode is active, providing better user guidance and preventing data entry confusion.

#### UI Enhancements:

1. **Visual Indicators**:

    - Manual depletion badge on current day display
    - Method badges for yesterday's depletion data
    - Color-coded badges: Blue for Manual, Green for Recording

2. **Information Alert**:

    - Clear notice when manual depletion is enabled
    - Guidance to use Manual Depletion menu instead
    - Prevention of duplicate data entry

3. **Interactive Feedback**:
    - Context-aware Detail button behavior
    - Informative alerts based on depletion method
    - Clear instructions for proper data management

#### Implementation Details:

```blade
<!-- Manual Depletion Notice -->
@if($isManualDepletionEnabled)
<div class="alert alert-info mt-2 py-2" role="alert">
    <small class="d-flex align-items-center">
        <i class="fas fa-info-circle me-2"></i>
        <div>
            <strong>Mode Manual Depletion Aktif:</strong>
            Data deplesi dikelola melalui menu <strong>"Manual Depletion"</strong> pada tabel livestock.
            <br>Input deplesi di form recording ini dinonaktifkan untuk mencegah duplikasi data.
        </div>
    </small>
</div>
@endif

<!-- Method Detection for Yesterday's Data -->
$yesterdayManualDepletion = $yesterdayDeplesi->contains(function ($item) {
    $metadata = is_array($item->metadata) ? $item->metadata : json_decode($item->metadata ?? '{}', true);
    return isset($metadata['depletion_method']) && $metadata['depletion_method'] === 'manual';
});
```

#### User Experience Benefits:

-   **Clear Guidance**: Users immediately understand which method is active
-   **Data Consistency**: Prevents accidental duplicate entries
-   **Method Awareness**: Historical data shows which method was used
-   **Seamless Workflow**: Proper direction to correct input interface

### System-Wide Depletion Normalization Refactoring (January 23, 2025)

Comprehensive refactoring of all system components to use the new Livestock Depletion Configuration System for complete backward compatibility and data consistency.

#### Components Refactored:

1. **DataTables Layer**:

    - `LivestockDataTable.php` - Updated jumlah_mati and jumlah_afkir columns
    - Now supports both legacy ("Mati"/"Afkir") and standard ("mortality"/"culling") types
    - Added LivestockDepletionConfig import and usage

2. **Analytics Service**:

    - `AnalyticsService.php` - Updated mortality calculation methods
    - `getMortalityMetrics()` - Daily mortality with config normalization
    - `getCumulativeMortality()` - Cumulative mortality with backward compatibility
    - `getMortalityChartData()` - Chart data generation with proper type filtering

3. **Records Component**:
    - `Records.php` - loadRecordingData method updated
    - Historical data processing with config normalization
    - Consistent terminology handling across all operations

#### Implementation Pattern:

```php
// Standard pattern used across all components
$mortalityTypes = [
    LivestockDepletionConfig::LEGACY_TYPE_MATI,
    LivestockDepletionConfig::TYPE_MORTALITY
];

$cullingTypes = [
    LivestockDepletionConfig::LEGACY_TYPE_AFKIR,
    LivestockDepletionConfig::TYPE_CULLING
];

// Use whereIn instead of where for backward compatibility
$deplesi = LivestockDepletion::where('livestock_id', $livestock->id)
    ->whereIn('jenis', $mortalityTypes)
    ->sum('jumlah');
```

#### Files Modified:

-   `app/DataTables/LivestockDataTable.php` - DataTable depletion columns
-   `app/Services/AnalyticsService.php` - Analytics mortality calculations
-   `app/Livewire/Records.php` - Recording data processing
-   `docs/features/livestock-depletion-config.md` - Documentation update

#### Benefits:

-   **Complete Backward Compatibility**: All existing data remains accessible
-   **Consistent Data Handling**: Unified approach across all system layers
-   **Future-Proof Architecture**: Easy to add new depletion types
-   **Performance Optimized**: Efficient whereIn queries instead of multiple where clauses
-   **Maintainable Code**: Centralized configuration reduces code duplication

#### Data Integrity Assurance:

-   No database migrations required
-   All existing "Mati"/"Afkir" records remain functional
-   New "mortality"/"culling" records are properly handled
-   Mixed terminology in same dataset works seamlessly
-   Historical reports maintain accuracy

### Backend Refactoring for Config System Integration (January 23, 2025)

The Records.php Livewire component has been refactored to fully integrate with the new Livestock Depletion Configuration System for complete backward compatibility and data consistency.

#### Refactored Methods:

1. **loadYesterdayData()** - Enhanced depletion data loading with config normalization
2. **updatedDate()** - Current date depletion processing with config system
3. **checkCurrentLivestockStock()** - Livestock stock calculation with config support

#### Key Improvements:

1. **Config-Based Normalization**:

    ```php
    // Before: Hard-coded type checking
    $totalMati = $deplesi->where('jenis', 'Mati')->sum('jumlah');
    $totalAfkir = $deplesi->where('jenis', 'Afkir')->sum('jumlah');

    // After: Config-based backward compatibility
    $mortalityTypes = [
        LivestockDepletionConfig::LEGACY_TYPE_MATI,
        LivestockDepletionConfig::TYPE_MORTALITY
    ];
    $cullingTypes = [
        LivestockDepletionConfig::LEGACY_TYPE_AFKIR,
        LivestockDepletionConfig::TYPE_CULLING
    ];

    $totalMati = $deplesi->filter(function ($item) use ($mortalityTypes) {
        return in_array($item->jenis, $mortalityTypes) ||
               in_array($item->normalized_type, [LivestockDepletionConfig::TYPE_MORTALITY]);
    })->sum('jumlah');
    ```

2. **Enhanced Data Mapping**:

    - Added normalized_type field for consistency
    - Added display_name for UI presentation
    - Added category for classification
    - Comprehensive logging for debugging

3. **Backward Compatibility**:

    - Supports both Indonesian ('Mati', 'Afkir') and English ('mortality', 'culling') terminology
    - Filters using both legacy and standard types
    - No data migration required
    - Existing data remains accessible

4. **Improved Logging**:
    ```php
    Log::info('Yesterday depletion processed with config system', [
        'livestock_id' => $this->livestockId,
        'yesterday_date' => $yesterdayDate,
        'total_records' => $yesterdayDeplesi->count(),
        'mortality_found' => $this->yesterday_mortality,
        'culling_found' => $this->yesterday_culling,
        'types_found' => $yesterdayDeplesi->pluck('jenis')->unique()->toArray(),
        'normalized_types' => $yesterdayDeplesi->pluck('normalized_type')->unique()->toArray()
    ]);
    ```

#### Refactored Components:

-   **Yesterday Data Loading**: Full config system integration
-   **Current Date Processing**: Config-based depletion type handling
-   **Stock Calculations**: Backward-compatible type filtering
-   **Total Depletion Calculation**: Enhanced with config normalization

#### Benefits of Refactoring:

-   **100% Backward Compatibility**: Works with both old and new terminology
-   **Future-Proof**: Ready for additional depletion types
-   **Data Consistency**: Unified handling across all components
-   **Enhanced Debugging**: Comprehensive logging for troubleshooting
-   **Type Safety**: Config-based validation and normalization

---

**Version**: 1.0  
**Last Updated**: 23 Januari 2025  
**Author**: AI Development Team
