# Manual Feed Usage Update Error Fix

## Tanggal: 2025-01-23 16:30:00 WIB

## Masalah yang Ditemukan

User melaporkan error saat mengedit data feed usage yang sudah ada (existing data), terutama ketika jumlah penggunaan dikurangi. Error yang muncul:

```
Error processing usage: Non-numeric value passed to decrement method.
```

## Analisis Root Cause

### 1. Error "Non-numeric value passed to decrement method"

-   Terjadi di method `updateExistingFeedUsage()` pada service
-   Error muncul saat memanggil `$stock->decrement('quantity_used', $detail->quantity)`
-   `$detail->quantity` tidak divalidasi sebagai numeric sebelum operasi decrement

### 2. Kurangnya Validasi Numeric

-   Component tidak memvalidasi input quantity sebagai numeric secara comprehensive
-   Service tidak melakukan konversi numeric yang safe
-   Update stock menggunakan increment/decrement yang rentan terhadap non-numeric values

### 3. Error Handling Kurang Informatif

-   Error message tidak memberikan informasi spesifik kepada user
-   Tidak ada fallback handling untuk different error types

## Solusi yang Diterapkan

### 1. Fix Service Layer - ManualFeedUsageService.php

#### A. Method `updateExistingFeedUsage()`

```php
// BEFORE (problematic)
$stock->decrement('quantity_used', $detail->quantity);
$livestock->decrement('total_feed_consumed', $usage->total_quantity);

// AFTER (safe)
$quantityToRestore = floatval($detail->quantity);
if ($quantityToRestore > 0) {
    $newQuantityUsed = max(0, $stock->quantity_used - $quantityToRestore);
    $stock->update(['quantity_used' => $newQuantityUsed]);
}

$totalQuantity = floatval($usage->total_quantity ?? 0);
if ($totalQuantity > 0) {
    $newFeedConsumed = max(0, $livestock->total_feed_consumed - $totalQuantity);
    $livestock->update(['total_feed_consumed' => $newFeedConsumed]);
}
```

#### B. Method `processManualFeedUsage()`

```php
// Enhanced numeric validation
$availableQuantity = floatval($stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated);
$requestedQuantity = floatval($manualStock['quantity']);

// Validate numeric values
if (!is_numeric($manualStock['quantity']) || $requestedQuantity <= 0) {
    throw new Exception("Invalid quantity for {$stock->feed->name}: must be a positive number");
}

// Safe stock quantity update
$newQuantityUsed = floatval($stock->quantity_used) + $requestedQuantity;
$stock->update(['quantity_used' => $newQuantityUsed]);
```

#### C. New Method `validateUsageDataForUpdate()`

```php
private function validateUsageDataForUpdate(array $data): void
{
    // Additional validation specifically for updates
    foreach ($data['manual_stocks'] as $index => $stock) {
        if (!isset($stock['quantity']) || !is_numeric($stock['quantity']) || floatval($stock['quantity']) <= 0) {
            throw new Exception("Valid quantity is required for manual stock at index {$index}");
        }

        // Validate stock availability
        $stockRecord = FeedStock::find($stock['stock_id']);
        $availableQuantity = $stockRecord->quantity_in - $stockRecord->quantity_used - $stockRecord->quantity_mutated;
        $requestedQuantity = floatval($stock['quantity']);

        if ($requestedQuantity > $availableQuantity) {
            throw new Exception("Insufficient stock available for {$stockRecord->feed->name}");
        }
    }
}
```

### 2. Fix Component Layer - ManualFeedUsage.php

#### A. Enhanced Error Handling in `processUsage()`

```php
catch (Exception $e) {
    // Provide more specific error messages
    $errorMessage = $e->getMessage();
    if (str_contains($errorMessage, 'Non-numeric value')) {
        $errorMessage = 'Invalid quantity values detected. Please check all quantity inputs are valid numbers.';
    } elseif (str_contains($errorMessage, 'Insufficient stock')) {
        $errorMessage = 'Insufficient stock available. Please adjust quantities or refresh the data.';
    } elseif (str_contains($errorMessage, 'decrement method')) {
        $errorMessage = 'Error updating stock quantities. Please refresh and try again.';
    }

    $this->errors = ['process' => 'Error processing usage: ' . $errorMessage];
}
```

#### B. New Method `validateSelectedStockQuantities()`

```php
private function validateSelectedStockQuantities(): array
{
    $errors = [];

    foreach ($this->selectedStocks as $index => $stock) {
        $stockLabel = "Stock " . ($index + 1) . " ({$stock['feed_name']})";

        // Comprehensive validation
        if (!isset($stock['quantity'])) {
            $errors[] = "{$stockLabel} - Quantity is required.";
            continue;
        }

        $quantity = is_string($stock['quantity']) ? trim($stock['quantity']) : strval($stock['quantity']);

        if (!is_numeric($quantity)) {
            $errors[] = "{$stockLabel} - Quantity must be a valid number.";
            continue;
        }

        $numericQuantity = floatval($quantity);

        if ($numericQuantity <= 0) {
            $errors[] = "{$stockLabel} - Quantity must be greater than 0.";
            continue;
        }

        $availableQuantity = floatval($stock['available_quantity']);
        if ($numericQuantity > $availableQuantity) {
            $errors[] = "{$stockLabel} - Requested {$numericQuantity} {$stock['unit']} exceeds available {$availableQuantity} {$stock['unit']}.";
            continue;
        }

        // Ensure quantity is properly formatted as float
        $this->selectedStocks[$index]['quantity'] = $numericQuantity;
    }

    return $errors;
}
```

## Key Improvements

### 1. Numeric Safety

-   All quantity values are now validated and converted to float before operations
-   Direct update operations instead of increment/decrement to avoid type issues
-   Comprehensive is_numeric() checks before processing

### 2. Error Prevention

-   Added `max(0, ...)` to prevent negative values
-   Enhanced validation at multiple levels (component and service)
-   Safe type conversion with floatval()

### 3. Better User Experience

-   More informative error messages
-   Specific error handling for different error types
-   Better validation feedback to users

### 4. Robust Update Process

-   Safe restoration of stock quantities during updates
-   Proper validation before processing updates
-   Transaction safety maintained

## Testing Scenarios

### 1. Normal Update

-   Edit existing usage data
-   Change quantities (increase/decrease)
-   Verify successful update

### 2. Edge Cases

-   Reduce quantity to lower value
-   Set quantity to exact available amount
-   Invalid numeric inputs (letters, special chars)
-   Zero or negative quantities

### 3. Error Scenarios

-   Insufficient stock after reduction
-   Invalid stock IDs
-   Network/database errors

## Files Modified

1. `app/Services/Feed/ManualFeedUsageService.php`

    - Enhanced `updateExistingFeedUsage()` method
    - Improved `processManualFeedUsage()` method
    - Added `validateUsageDataForUpdate()` method

2. `app/Livewire/FeedUsages/ManualFeedUsage.php`
    - Enhanced error handling in `processUsage()` method
    - Added `validateSelectedStockQuantities()` method
    - Improved quantity validation flow

## Status: ✅ RESOLVED

Update functionality sekarang dapat menangani pengurangan quantity dengan aman dan memberikan error message yang informatif kepada user.
