# Manual Feed Usage Configuration Integration

**Date:** 2024-12-19 16:15:00 WIB  
**Feature:** Integration of CompanyConfig with ManualFeedUsage component  
**Priority:** High  
**Status:** ✅ COMPLETED

## Overview

Mengintegrasikan konfigurasi perusahaan (`CompanyConfig`) dengan component `ManualFeedUsage` untuk memberikan kontrol yang lebih fleksibel terhadap input restrictions, validation rules, dan workflow settings untuk metode manual feed usage.

## Configuration Structure Added

### 1. **Main Feed Usage Configuration**

```php
'feed_usage' => [
    'enabled' => true,
    'methods' => [
        'auto' => [...],
        'manual' => [
            'enabled' => true,
            'require_batch_selection' => true,
            'allow_multiple_batches' => true,
            'validation_rules' => [...],
            'input_restrictions' => [...],
            'workflow_settings' => [...],
            'batch_selection' => [...],
            'stock_selection' => [...],
        ],
    ],
    'tracking' => [...],
    'notifications' => [...],
],
```

### 2. **Validation Rules Configuration**

```php
'validation_rules' => [
    'require_usage_date' => true,
    'require_usage_purpose' => true,
    'require_quantity' => true,
    'require_notes' => false,
    'min_quantity' => 0.1,
    'max_quantity' => 10000,
    'allow_zero_quantity' => false,
],
```

### 3. **Input Restrictions Configuration**

```php
'input_restrictions' => [
    'allow_same_day_repeated_input' => true,
    'allow_same_batch_repeated_input' => true,
    'max_usage_per_day_per_batch' => 50,
    'max_usage_per_day_per_livestock' => 100,
    'require_unique_purpose' => false,
    'min_interval_minutes' => 0,
    'max_entries_per_session' => 20,
    'prevent_duplicate_stocks' => true,
    'allow_partial_stock_usage' => true,
    'require_stock_availability_check' => true,
    'max_stock_age_days' => 365,
    'warn_on_old_stock' => true,
    'old_stock_threshold_days' => 90,
],
```

### 4. **Workflow Settings Configuration**

```php
'workflow_settings' => [
    'enable_preview_step' => true,
    'require_confirmation' => true,
    'auto_save_draft' => false,
    'enable_batch_info_display' => true,
    'show_stock_details' => true,
    'show_cost_information' => true,
    'enable_usage_history' => true,
],
```

### 5. **Batch Selection Settings**

```php
'batch_selection' => [
    'show_batch_details' => true,
    'show_current_quantity' => true,
    'show_age_information' => true,
    'show_coop_information' => true,
    'show_strain_information' => true,
    'enable_batch_filtering' => true,
    'default_sort' => 'age_asc', // age_asc, age_desc, quantity_asc, quantity_desc
    'hide_inactive_batches' => true,
    'min_batch_quantity' => 1,
],
```

### 6. **Stock Selection Settings**

```php
'stock_selection' => [
    'show_stock_details' => true,
    'show_availability' => true,
    'show_cost_per_unit' => true,
    'show_batch_info' => true,
    'show_age_days' => true,
    'enable_stock_filtering' => true,
    'default_sort' => 'age_asc',
    'hide_unavailable_stocks' => true,
    'warn_on_low_stock' => true,
    'low_stock_threshold' => 10,
],
```

## Implementation Details

### 1. **CompanyConfig Helper Methods Added**

```php
// Get complete manual feed usage configuration
public static function getManualFeedUsageConfig(): array

// Get specific configuration sections
public static function getManualFeedUsageInputRestrictions(): array
public static function getManualFeedUsageValidationRules(): array
public static function getManualFeedUsageWorkflowSettings(): array
public static function getManualFeedUsageBatchSelectionSettings(): array
public static function getManualFeedUsageStockSelectionSettings(): array
```

### 2. **Service Layer Enhancements**

#### **ManualFeedUsageService.php Updates:**

**Enhanced Input Restrictions Validation:**

```php
public function validateFeedUsageInputRestrictions(string $livestockId, array $selectedStocks): array
{
    $restrictions = CompanyConfig::getManualFeedUsageInputRestrictions();

    // New validations added:
    // - Maximum usage per day per livestock
    // - Maximum entries per session
    // - Duplicate stocks prevention
    // - Stock availability check
    // - Stock age restrictions
    // - Old stock warnings
}
```

**Enhanced Validation Rules:**

```php
private function validateUsageData(array $data): void
{
    $validationRules = CompanyConfig::getManualFeedUsageValidationRules();

    // Dynamic validation based on config:
    // - Usage date (optional/required)
    // - Usage purpose (optional/required)
    // - Notes (optional/required)
    // - Quantity limits (min/max/zero allowed)
}
```

**New Helper Methods:**

```php
public function getFeedUsageInputRestrictions(): array
public function getFeedUsageValidationRules(): array
public function getFeedUsageWorkflowSettings(): array
private function getTodayUsageCountForLivestock(string $livestockId): int
```

### 3. **Component Enhancements**

#### **ManualFeedUsage.php Updates:**

**Dynamic Validation Rules:**

```php
private function initializeValidationRules()
{
    $validationRules = CompanyConfig::getManualFeedUsageValidationRules();

    // Build Laravel validation rules dynamically based on config
    // - Batch selection rules
    // - Usage purpose rules (conditional)
    // - Usage date rules (conditional)
    // - Notes rules (conditional/required)
    // - Stock quantity rules (with min/max from config)
}
```

**Enhanced Batch Loading:**

```php
private function loadAvailableBatches()
{
    $batchSettings = $this->getBatchSelectionSettings();

    // Apply config-based filtering and sorting:
    // - Hide inactive batches (configurable)
    // - Minimum batch quantity filter
    // - Dynamic sorting (age_asc, age_desc, quantity_asc, quantity_desc)
    // - Optional fields display (coop, strain info)
}
```

**Enhanced Stock Addition:**

```php
public function addStock($stockId)
{
    $restrictions = CompanyConfig::getManualFeedUsageInputRestrictions();

    // Apply config-based restrictions:
    // - Prevent duplicate stocks
    // - Maximum entries per session
    // - Stock age restrictions
    // - Old stock warnings
    // - Default quantity from validation rules
}
```

**New Helper Methods:**

```php
private function getWorkflowSettings(): array
private function getBatchSelectionSettings(): array
private function getStockSelectionSettings(): array
```

## Key Features Implemented

### 1. **Flexible Validation Rules**

-   ✅ **Dynamic Laravel Validation**: Rules generated from config
-   ✅ **Conditional Requirements**: Fields can be optional/required based on config
-   ✅ **Quantity Limits**: Min/max quantity with zero-quantity option
-   ✅ **Field Requirements**: Usage date, purpose, notes configurable

### 2. **Comprehensive Input Restrictions**

-   ✅ **Session Limits**: Maximum entries per session
-   ✅ **Daily Limits**: Max usage per day per batch/livestock
-   ✅ **Duplicate Prevention**: Configurable duplicate stock prevention
-   ✅ **Stock Age Control**: Maximum stock age and old stock warnings
-   ✅ **Availability Checks**: Automatic stock availability validation
-   ✅ **Interval Control**: Minimum time between usage entries

### 3. **Enhanced Batch Management**

-   ✅ **Dynamic Sorting**: Age-based or quantity-based sorting
-   ✅ **Filtering Options**: Hide inactive batches, minimum quantity filter
-   ✅ **Display Control**: Show/hide coop info, strain info, age details
-   ✅ **Performance Optimization**: Query optimization based on config

### 4. **Improved Stock Selection**

-   ✅ **Smart Defaults**: Default quantity from validation rules
-   ✅ **Age Warnings**: Visual warnings for old stock
-   ✅ **Availability Display**: Real-time availability information
-   ✅ **Cost Information**: Optional cost display based on config

### 5. **Enhanced User Experience**

-   ✅ **Contextual Errors**: Specific error messages based on restrictions
-   ✅ **Smart Warnings**: Non-blocking warnings for old stock
-   ✅ **Progressive Disclosure**: Show/hide features based on config
-   ✅ **Consistent Behavior**: All restrictions applied consistently

## Configuration Benefits

### 1. **Business Flexibility**

-   **Different Companies**: Each company can have different rules
-   **Operational Needs**: Adjust restrictions based on business requirements
-   **Compliance**: Ensure adherence to company policies
-   **Quality Control**: Prevent usage of old or inappropriate stock

### 2. **Performance Optimization**

-   **Query Optimization**: Load only necessary data based on config
-   **UI Optimization**: Show only relevant fields and information
-   **Validation Efficiency**: Apply only necessary validations
-   **Memory Usage**: Reduce memory footprint with selective loading

### 3. **Maintainability**

-   **Centralized Config**: All rules in one place
-   **Easy Updates**: Change behavior without code changes
-   **Consistent Application**: Same rules applied across all components
-   **Future Proof**: Easy to add new restrictions and features

## Usage Examples

### 1. **Strict Company Configuration**

```php
'input_restrictions' => [
    'allow_same_day_repeated_input' => false,
    'max_usage_per_day_per_livestock' => 1,
    'max_entries_per_session' => 5,
    'prevent_duplicate_stocks' => true,
    'max_stock_age_days' => 30,
    'min_interval_minutes' => 60,
],
'validation_rules' => [
    'require_notes' => true,
    'min_quantity' => 1,
    'allow_zero_quantity' => false,
],
```

### 2. **Flexible Company Configuration**

```php
'input_restrictions' => [
    'allow_same_day_repeated_input' => true,
    'max_usage_per_day_per_livestock' => 50,
    'max_entries_per_session' => 20,
    'prevent_duplicate_stocks' => false,
    'max_stock_age_days' => 365,
    'warn_on_old_stock' => true,
],
'validation_rules' => [
    'require_notes' => false,
    'min_quantity' => 0.1,
    'allow_zero_quantity' => true,
],
```

## Testing Scenarios

### ✅ **Configuration Loading**

-   Config properly loaded in component initialization
-   Service methods return correct configuration values
-   Default values applied when config missing

### ✅ **Validation Rules**

-   Dynamic Laravel validation rules generated correctly
-   Conditional requirements work as expected
-   Quantity limits enforced properly

### ✅ **Input Restrictions**

-   Session limits prevent excessive entries
-   Daily limits enforced correctly
-   Stock age restrictions work as configured
-   Duplicate prevention functions properly

### ✅ **Batch and Stock Selection**

-   Sorting and filtering applied correctly
-   Optional fields shown/hidden based on config
-   Performance optimizations active

## Future Considerations

### 1. **Advanced Features**

-   **Role-based Config**: Different rules for different user roles
-   **Time-based Config**: Different rules for different time periods
-   **Location-based Config**: Different rules for different farms/coops
-   **Dynamic Config**: Runtime configuration changes

### 2. **Performance Enhancements**

-   **Config Caching**: Cache configuration for better performance
-   **Lazy Loading**: Load config sections only when needed
-   **Background Validation**: Validate restrictions in background

### 3. **User Interface**

-   **Config UI**: Admin interface for managing configurations
-   **Real-time Updates**: Live config updates without restart
-   **Config Validation**: Validate config before applying

## Final Status: ✅ COMPLETELY IMPLEMENTED

### All Features Working

-   ✅ **CompanyConfig Integration**: Complete configuration structure
-   ✅ **Service Layer Enhancement**: All restrictions and validations
-   ✅ **Component Refactoring**: Dynamic behavior based on config
-   ✅ **Validation System**: Flexible Laravel validation rules
-   ✅ **Input Restrictions**: Comprehensive restriction system
-   ✅ **Performance Optimization**: Query and UI optimizations

### Production Ready

-   ✅ **Backward Compatibility**: Existing functionality preserved
-   ✅ **Error Handling**: Robust error handling for all scenarios
-   ✅ **Logging**: Comprehensive logging for debugging
-   ✅ **Documentation**: Complete documentation provided
-   ✅ **Future Proof**: Extensible architecture for future enhancements

The Manual Feed Usage component is now fully integrated with CompanyConfig, providing flexible, configurable, and robust feed usage management that can adapt to different business requirements while maintaining excellent performance and user experience.

---

**Documented by:** AI Assistant  
**Reviewed by:** Development Team  
**Last Updated:** 2024-12-19 16:15:00 WIB
