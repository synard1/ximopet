# Supply Mutation Destination Loading Feature

## Overview

The Supply Mutation Destination Loading feature provides a configurable, step-by-step destination selection process that allows users to choose between different destination types (farm, coop, livestock) based on configuration settings. This ensures data integrity and prevents invalid mutations while providing a smooth user experience.

## Key Features

### 1. **Configurable Destination Types**

-   **Farm Destination**: Mutasi ke farm lain
-   **Coop Destination**: Mutasi ke kandang tertentu
-   **Livestock Destination**: Mutasi ke ternak tertentu

### 2. **Step-by-Step Selection Process**

1. **Source Selection**: User selects source farm
2. **Destination Type Selection**: User chooses destination type (farm/coop/livestock)
3. **Destination Selection**: User selects specific destination from filtered options
4. **Item Selection**: User adds mutation items

### 3. **Smart Data Filtering**

-   Excludes source farm from destination options
-   Loads only relevant data based on selected destination type
-   Respects user permissions and role-based access

### 4. **Real-time Validation**

-   Prevents same source-destination selection
-   Validates destination availability
-   Ensures data consistency

## Implementation Details

### Configuration (`config/supply_mutation.php`)

```php
'destination' => [
    'allow_coop_destination' => true,
    'allow_livestock_destination' => true,
    'allow_farm_destination' => true,
    'require_destination_validation' => true,
    'allow_same_source_destination' => false,
    'max_destination_distance_km' => 100,
    'auto_validate_destination_capacity' => true,
    'destination_validation_rules' => [
        'check_coop_capacity' => true,
        'check_livestock_health' => true,
        'check_farm_availability' => true,
        'validate_transfer_restrictions' => true,
    ],
],
```

### Service Layer (`SupplyMutationConfigService`)

#### New Methods Added:

```php
// Get available destination types from config
public static function getAvailableDestinationTypes(): array

// Validate destination type selection
public static function validateDestinationType(string $destinationType): bool

// Get destination validation rules
public static function getDestinationValidationRules(string $destinationType): array

// Check if same source destination is allowed
public static function isSameSourceDestinationAllowed(): bool

// Get maximum destination distance
public static function getMaxDestinationDistance(): float

// Check if destination validation is required
public static function isDestinationValidationRequired(): bool

// Check if auto validate destination capacity is enabled
public static function isAutoValidateDestinationCapacityEnabled(): bool
```

### Livewire Component (`Mutation.php`)

#### New Properties:

```php
// Destination type selection
public $destinationType = ''; // 'farm', 'coop', 'livestock'
public $destinationTypes = [];
public $destinationFarms = [];
public $destinationLivestocks = [];
public $showDestinationSelection = false;
public $destinationSelectionComplete = false;
```

#### New Methods:

```php
// Load destination types from configuration
public function loadDestinationTypes(): void

// Handle source farm change - trigger destination loading
public function updatedSourceFarmId(): void

// Handle destination type selection
public function updatedDestinationType(): void

// Load destination data based on selected type
public function loadDestinationData(): void

// Load destination farms (excluding source farm)
public function loadDestinationFarms(): void

// Load destination coops (excluding source farm coops)
public function loadDestinationCoops(): void

// Load destination livestocks (excluding source farm livestocks)
public function loadDestinationLivestocks(): void

// Reset destination data
public function resetDestinationData(): void

// Handle destination farm selection
public function updatedDestinationFarmId(): void

// Handle destination coop selection
public function updatedDestinationCoopId(): void

// Handle destination livestock selection
public function updatedDestinationLivestockId(): void
```

### UI Components (`mutation.blade.php`)

#### Destination Type Selection Card:

-   Radio button selection for destination types
-   Dynamic loading based on configuration
-   Visual feedback with icons and descriptions

#### Destination Selection Card:

-   Dynamic form fields based on selected type
-   Real-time validation and error display
-   Information cards showing destination details

#### Conditional Rendering:

-   Items section only shows after destination selection
-   Submit button disabled until all required fields are filled
-   Error and success message display

## User Flow

### 1. **Source Selection**

```
User selects source farm → Triggers destination type selection
```

### 2. **Destination Type Selection**

```
User sees radio buttons for available destination types
- Farm (if enabled in config)
- Coop (if enabled in config)
- Livestock (if enabled in config)
```

### 3. **Destination Loading**

```
User selects destination type → System loads filtered destination data
- Excludes source farm from options
- Applies role-based filtering
- Loads relevant relationships
```

### 4. **Destination Selection**

```
User selects specific destination → System validates and shows details
- Validates against source
- Shows destination information
- Enables item selection
```

### 5. **Item Selection**

```
User adds mutation items → System validates stock availability
- Shows available items from source
- Validates quantities
- Enables form submission
```

## Validation Rules

### 1. **Source-Destination Validation**

-   Prevents same farm selection for source and destination
-   Validates destination type is enabled in configuration
-   Ensures destination is different from source

### 2. **Permission Validation**

-   Respects user role-based access
-   Filters farms/coops/livestocks based on user permissions
-   Validates user can access selected destinations

### 3. **Data Integrity Validation**

-   Ensures destination exists and is active
-   Validates destination capacity (for coops)
-   Checks livestock health status (for livestock)

## Configuration Options

### Enable/Disable Destination Types

```php
'allow_farm_destination' => true,      // Enable farm-to-farm mutations
'allow_coop_destination' => true,      // Enable farm-to-coop mutations
'allow_livestock_destination' => true, // Enable farm-to-livestock mutations
```

### Validation Settings

```php
'require_destination_validation' => true,           // Require validation
'allow_same_source_destination' => false,           // Prevent same source/dest
'auto_validate_destination_capacity' => true,       // Auto-check capacity
'max_destination_distance_km' => 100,               // Distance limit
```

### Validation Rules

```php
'destination_validation_rules' => [
    'check_coop_capacity' => true,        // Check coop capacity
    'check_livestock_health' => true,     // Check livestock health
    'check_farm_availability' => true,    // Check farm availability
    'validate_transfer_restrictions' => true, // Check transfer rules
],
```

## Benefits

### 1. **Data Integrity**

-   Prevents invalid mutations
-   Ensures source-destination separation
-   Validates destination availability

### 2. **User Experience**

-   Clear step-by-step process
-   Visual feedback and guidance
-   Real-time validation

### 3. **Flexibility**

-   Configurable destination types
-   Role-based access control
-   Extensible validation rules

### 4. **Maintainability**

-   Centralized configuration
-   Service layer abstraction
-   Clear separation of concerns

## Usage Examples

### Basic Usage

```php
// In Livewire component
public function mount()
{
    $this->loadDestinationTypes(); // Load from config
}

public function updatedSourceFarmId()
{
    if (!empty($this->source_farm_id)) {
        $this->showDestinationSelection = true;
        $this->loadAvailableItems();
    }
}
```

### Configuration Check

```php
// Check if destination type is enabled
if (SupplyMutationConfigService::validateDestinationType('coop')) {
    // Enable coop destination selection
}

// Get validation rules for destination type
$rules = SupplyMutationConfigService::getDestinationValidationRules('livestock');
```

### UI Integration

```blade
<!-- Destination type selection -->
@foreach($destinationTypes as $type => $config)
<div class="form-check">
    <input type="radio" wire:model.live="destinationType" value="{{ $type }}">
    <label>{{ $config['name'] }} - {{ $config['description'] }}</label>
</div>
@endforeach

<!-- Conditional destination selection -->
@if($destinationType === 'coop')
<select wire:model.live="destination_coop_id">
    @foreach($allCoops as $coop)
    <option value="{{ $coop['id'] }}">{{ $coop['name'] }}</option>
    @endforeach
</select>
@endif
```

## Migration Path

### From Legacy System

1. **Enable new destination types** in configuration
2. **Update existing mutations** to use new destination structure
3. **Migrate validation rules** to new configuration format
4. **Test with existing data** to ensure compatibility

### Future Enhancements

1. **Database migration** for destination configuration
2. **Advanced validation rules** with custom business logic
3. **Bulk destination operations** for multiple mutations
4. **Destination templates** for common mutation patterns

## Troubleshooting

### Common Issues

#### 1. **No Destination Types Available**

-   Check configuration settings in `config/supply_mutation.php`
-   Verify all destination types are enabled
-   Check user permissions and role access

#### 2. **Destination Data Not Loading**

-   Verify source farm selection
-   Check database relationships and foreign keys
-   Review role-based filtering logic

#### 3. **Validation Errors**

-   Check destination validation rules in configuration
-   Verify source-destination separation logic
-   Review permission-based access controls

### Debug Information

```php
// Enable debug logging
'logging' => [
    'enable_debug_logging' => true,
    'log_destination_loading' => true,
],

// Check logs for detailed information
Log::info('Destination loading debug', [
    'source_farm_id' => $this->source_farm_id,
    'destination_type' => $this->destinationType,
    'available_types' => array_keys($this->destinationTypes),
]);
```

## Performance Considerations

### 1. **Caching**

-   Destination types are cached from configuration
-   Farm/coop/livestock data is loaded on-demand
-   Query optimization with eager loading

### 2. **Database Queries**

-   Optimized queries with proper indexing
-   Role-based filtering at database level
-   Efficient relationship loading

### 3. **UI Performance**

-   Conditional rendering reduces DOM complexity
-   Real-time validation with debouncing
-   Progressive enhancement approach

## Security Considerations

### 1. **Access Control**

-   Role-based destination filtering
-   Permission validation for all operations
-   Secure data access patterns

### 2. **Input Validation**

-   Server-side validation for all inputs
-   SQL injection prevention
-   XSS protection in UI components

### 3. **Data Integrity**

-   Transaction-based operations
-   Validation at multiple levels
-   Audit trail for all changes

This implementation provides a robust, configurable, and user-friendly destination loading system for supply mutations that ensures data integrity while maintaining flexibility for future enhancements.
