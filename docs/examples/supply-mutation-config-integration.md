# Supply Mutation Configuration Integration Example

## Overview

This document shows how to integrate the Supply Mutation Configuration System into the existing Mutation Livewire component to make it more robust and future-proof.

## Integration Examples

### 1. Basic Configuration Integration

```php
<?php

namespace App\Livewire\MasterData\Supply;

use App\Services\SupplyMutationConfigService;
use Livewire\Component;

class Mutation extends Component
{
    // ... existing properties ...

    public function mount()
    {
        // Check if supply mutation is enabled
        if (!SupplyMutationConfigService::get('enabled', true)) {
            $this->addError('system', 'Supply mutation is currently disabled.');
            return;
        }

        // Get default mutation type
        $this->mutationType = SupplyMutationConfigService::getDefaultMutationType() ?? 'batch';

        // Load enabled mutation types
        $this->enabledMutationTypes = SupplyMutationConfigService::getEnabledMutationTypes();

        // Check if flexible destination is enabled
        $this->flexibleDestinationEnabled = SupplyMutationConfigService::isFeatureEnabled('flexible_destination');

        // ... existing mount logic ...
    }

    public function render()
    {
        // Get configuration for UI rendering
        $config = [
            'batch_tracking' => SupplyMutationConfigService::isBatchTrackingEnabled(),
            'require_approval' => SupplyMutationConfigService::isApprovalRequired(),
            'allow_coop_destination' => SupplyMutationConfigService::isDestinationTypeAllowed('coop'),
            'allow_livestock_destination' => SupplyMutationConfigService::isDestinationTypeAllowed('livestock'),
            'validation_rules' => [
                'quantity' => SupplyMutationConfigService::getQuantityValidationRules(),
                'date' => SupplyMutationConfigService::getDateValidationRules(),
                'stock' => SupplyMutationConfigService::getStockValidationRules(),
            ],
        ];

        return view('livewire.master-data.supply.mutation', compact('config'));
    }
}
```

### 2. Enhanced Validation Rules

```php
class Mutation extends Component
{
    protected function getValidationRules()
    {
        $baseRules = [
            'supply_id' => 'required|uuid',
            'tanggal' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ];

        // Get configuration-based validation rules
        $quantityRules = SupplyMutationConfigService::getQuantityValidationRules();
        $dateRules = SupplyMutationConfigService::getDateValidationRules();
        $stockRules = SupplyMutationConfigService::getStockValidationRules();

        // Build dynamic quantity validation
        $quantityValidation = 'required|numeric';

        if ($quantityRules['require_positive'] ?? true) {
            $quantityValidation .= '|min:' . ($quantityRules['min_quantity'] ?? 0.01);
        }

        if ($quantityRules['max_quantity'] ?? false) {
            $quantityValidation .= '|max:' . $quantityRules['max_quantity'];
        }

        $baseRules['quantity'] = $quantityValidation;

        // Add date validation
        if ($dateRules['allow_future_dates'] ?? false) {
            $baseRules['tanggal'] .= '|after_or_equal:today';
        } else {
            $baseRules['tanggal'] .= '|before_or_equal:today';
        }

        // Add destination validation
        if (SupplyMutationConfigService::isFeatureEnabled('flexible_destination')) {
            $baseRules['destination_coop_id'] = 'nullable|uuid';
            $baseRules['destination_livestock_id'] = 'nullable|uuid';
        }

        return $baseRules;
    }

    protected function getValidationMessages()
    {
        $quantityRules = SupplyMutationConfigService::getQuantityValidationRules();

        return [
            'quantity.min' => 'Jumlah minimal adalah ' . ($quantityRules['min_quantity'] ?? 0.01),
            'quantity.max' => 'Jumlah maksimal adalah ' . ($quantityRules['max_quantity'] ?? 999999.99),
            'tanggal.before_or_equal' => 'Tanggal tidak boleh di masa depan',
            'destination_coop_id.uuid' => 'Kandang tujuan tidak valid',
            'destination_livestock_id.uuid' => 'Ternak tujuan tidak valid',
        ];
    }
}
```

### 3. Dynamic UI Rendering

```php
// In the Livewire component
public function getUiConfigProperty()
{
    return [
        'show_batch_tracking' => SupplyMutationConfigService::isBatchTrackingEnabled(),
        'show_approval_workflow' => SupplyMutationConfigService::isFeatureEnabled('approval_workflow'),
        'show_cost_tracking' => SupplyMutationConfigService::isCostTrackingEnabled(),
        'show_document_upload' => SupplyMutationConfigService::get('documents.require_do_number', false),
        'mutation_types' => SupplyMutationConfigService::getEnabledMutationTypes(),
        'destination_options' => [
            'coop' => SupplyMutationConfigService::isDestinationTypeAllowed('coop'),
            'livestock' => SupplyMutationConfigService::isDestinationTypeAllowed('livestock'),
            'farm' => SupplyMutationConfigService::isDestinationTypeAllowed('farm'),
        ],
    ];
}
```

```blade
{{-- In the Blade template --}}
@if($this->uiConfig['show_batch_tracking'])
    <div class="mb-3">
        <label class="form-label">Batch Number</label>
        <input type="text" class="form-control" wire:model="batchNumber"
               placeholder="Auto-generated if enabled">
    </div>
@endif

@if($this->uiConfig['show_approval_workflow'])
    <div class="mb-3">
        <label class="form-label">Approval Level</label>
        <select class="form-select" wire:model="approvalLevel">
            <option value="">Select approval level</option>
            @foreach($this->getApprovalLevels() as $level => $config)
                <option value="{{ $level }}">{{ $config['role'] }}</option>
            @endforeach
        </select>
    </div>
@endif

@if($this->uiConfig['destination_options']['coop'])
    <div class="mb-3">
        <label class="form-label">Destination Coop</label>
        <select class="form-select" wire:model="destinationCoopId">
            <option value="">Select coop</option>
            @foreach($this->allCoops as $coop)
                <option value="{{ $coop->id }}">{{ $coop->name }}</option>
            @endforeach
        </select>
    </div>
@endif

@if($this->uiConfig['destination_options']['livestock'])
    <div class="mb-3">
        <label class="form-label">Destination Livestock</label>
        <select class="form-select" wire:model="destinationLivestockId">
            <option value="">Select livestock</option>
            @foreach($this->livestocks as $livestock)
                <option value="{{ $livestock->id }}">{{ $livestock->name }}</option>
            @endforeach
        </select>
    </div>
@endif
```

### 4. Business Logic Integration

```php
class Mutation extends Component
{
    public function validateDestinationConfiguration()
    {
        if (!SupplyMutationConfigService::isFeatureEnabled('flexible_destination')) {
            return; // Skip validation if feature is disabled
        }

        $hasCoopDestination = !empty($this->destinationCoopId);
        $hasLivestockDestination = !empty($this->destinationLivestockId);

        // Check if both destinations are selected
        if ($hasCoopDestination && $hasLivestockDestination) {
            throw new Exception('Tidak dapat memilih kandang dan ternak tujuan secara bersamaan');
        }

        // Check if at least one destination is selected
        if (!$hasCoopDestination && !$hasLivestockDestination) {
            throw new Exception('Harus memilih kandang atau ternak tujuan');
        }

        // Validate destination type is allowed
        if ($hasCoopDestination && !SupplyMutationConfigService::isDestinationTypeAllowed('coop')) {
            throw new Exception('Destination coop tidak diizinkan');
        }

        if ($hasLivestockDestination && !SupplyMutationConfigService::isDestinationTypeAllowed('livestock')) {
            throw new Exception('Destination livestock tidak diizinkan');
        }
    }

    public function validateQuantity($quantity, $availableStock)
    {
        $quantityRules = SupplyMutationConfigService::getQuantityValidationRules();

        // Check minimum quantity
        if ($quantity < ($quantityRules['min_quantity'] ?? 0.01)) {
            throw new Exception('Jumlah minimal adalah ' . ($quantityRules['min_quantity'] ?? 0.01));
        }

        // Check maximum quantity
        if ($quantity > ($quantityRules['max_quantity'] ?? 999999.99)) {
            throw new Exception('Jumlah maksimal adalah ' . ($quantityRules['max_quantity'] ?? 999999.99));
        }

        // Check maximum mutation percentage
        if (($quantityRules['max_mutation_percentage'] ?? 100) < 100) {
            $maxAllowed = $availableStock * ($quantityRules['max_mutation_percentage'] / 100);
            if ($quantity > $maxAllowed) {
                throw new Exception('Jumlah melebihi batas maksimal mutasi (' . $quantityRules['max_mutation_percentage'] . '%)');
            }
        }

        // Check minimum stock remaining
        $remainingStock = $availableStock - $quantity;
        if ($remainingStock < ($quantityRules['min_stock_remaining'] ?? 0)) {
            throw new Exception('Stok tersisa tidak mencukupi minimum yang diizinkan');
        }
    }

    public function validateDate($date)
    {
        $dateRules = SupplyMutationConfigService::getDateValidationRules();

        $mutationDate = Carbon::parse($date);
        $today = Carbon::today();

        // Check future dates
        if (!$dateRules['allow_future_dates'] && $mutationDate->isFuture()) {
            throw new Exception('Tanggal mutasi tidak boleh di masa depan');
        }

        // Check past dates limit
        if ($dateRules['max_days_in_past'] ?? false) {
            $maxPastDate = $today->copy()->subDays($dateRules['max_days_in_past']);
            if ($mutationDate->lt($maxPastDate)) {
                throw new Exception('Tanggal mutasi terlalu lama di masa lalu');
            }
        }

        // Check business days
        if ($dateRules['require_business_days'] ?? false) {
            if ($mutationDate->isWeekend()) {
                throw new Exception('Tanggal mutasi harus hari kerja');
            }
        }
    }
}
```

### 5. Performance Optimization

```php
class Mutation extends Component
{
    public function loadFarms()
    {
        // Use caching if enabled
        if (SupplyMutationConfigService::isCachingEnabled()) {
            $cacheKey = 'farms_' . auth()->user()->company_id;
            $this->farms = Cache::remember($cacheKey, SupplyMutationConfigService::getCacheTtl(), function () {
                return $this->loadFarmsFromDatabase();
            });
        } else {
            $this->farms = $this->loadFarmsFromDatabase();
        }
    }

    private function loadFarmsFromDatabase()
    {
        // Use batch size from configuration
        $batchSize = SupplyMutationConfigService::getBatchSize();

        return Farm::where('company_id', auth()->user()->company_id)
            ->with(['coops', 'livestocks'])
            ->chunk($batchSize)
            ->flatten();
    }
}
```

### 6. Audit and Logging

```php
class Mutation extends Component
{
    public function save()
    {
        try {
            // Log the mutation attempt
            if (SupplyMutationConfigService::isAuditTrailEnabled()) {
                Log::info('Supply mutation attempt', [
                    'user_id' => auth()->id(),
                    'supply_id' => $this->supply_id,
                    'quantity' => $this->quantity,
                    'destination_type' => $this->getDestinationType(),
                    'timestamp' => now()->toISOString(),
                ]);
            }

            // Validate configuration
            $this->validateDestinationConfiguration();
            $this->validateQuantity($this->quantity, $this->availableStock);
            $this->validateDate($this->tanggal);

            // Process mutation
            $mutation = $this->processMutation();

            // Log success
            if (SupplyMutationConfigService::isAuditTrailEnabled()) {
                Log::info('Supply mutation completed', [
                    'mutation_id' => $mutation->id,
                    'user_id' => auth()->id(),
                    'status' => $mutation->status,
                ]);
            }

            $this->successMessage = 'Mutasi berhasil disimpan';

        } catch (Exception $e) {
            // Log error
            if (SupplyMutationConfigService::isDebugLoggingEnabled()) {
                Log::error('Supply mutation failed', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->errorMessage = $e->getMessage();
        }
    }
}
```

### 7. Background Job Integration

```php
class Mutation extends Component
{
    public function processMutation()
    {
        $mutationData = [
            'supply_id' => $this->supply_id,
            'quantity' => $this->quantity,
            'date' => $this->tanggal,
            'notes' => $this->notes,
            // ... other data
        ];

        // Use background job if enabled
        if (SupplyMutationConfigService::isBackgroundJobsEnabled()) {
            ProcessSupplyMutationJob::dispatch($mutationData, auth()->id());

            $this->successMessage = 'Mutasi sedang diproses di background';
            return null;
        } else {
            // Process immediately
            return app(SupplyMutationService::class)->processSupplyMutation($mutationData);
        }
    }
}
```

### 8. Notification Integration

```php
class Mutation extends Component
{
    public function sendNotifications($mutation)
    {
        if (!SupplyMutationConfigService::areNotificationsEnabled()) {
            return;
        }

        $notificationSettings = SupplyMutationConfigService::getNotificationSettings();

        // Send creation notification
        if ($notificationSettings['notify_on_creation'] ?? false) {
            $this->sendCreationNotification($mutation);
        }

        // Send approval notification if required
        if (SupplyMutationConfigService::isApprovalRequired($mutation->quantity)) {
            $this->sendApprovalNotification($mutation);
        }
    }

    private function sendCreationNotification($mutation)
    {
        $channels = SupplyMutationConfigService::get('notifications.notification_channels', []);

        if ($channels['database'] ?? false) {
            // Send database notification
        }

        if ($channels['email'] ?? false) {
            // Send email notification
        }
    }
}
```

## Benefits of Integration

### 1. **Flexibility**

-   Easy to enable/disable features
-   Configurable validation rules
-   Dynamic UI rendering

### 2. **Performance**

-   Intelligent caching
-   Background job processing
-   Optimized queries

### 3. **Maintainability**

-   Centralized configuration
-   Easy to modify behavior
-   Clear separation of concerns

### 4. **Future-Proof**

-   Easy migration to database
-   Feature flag support
-   Extensible architecture

### 5. **Monitoring**

-   Comprehensive logging
-   Audit trail
-   Performance metrics

## Migration Path

1. **Phase 1**: Integrate configuration service
2. **Phase 2**: Add feature flags
3. **Phase 3**: Implement database storage
4. **Phase 4**: Add UI management

This integration provides a solid foundation for a robust, scalable supply mutation system that can evolve with business needs.
