<?php

namespace App\Traits;

use App\Services\ExpeditionTransactionHelperService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * HasExpeditionTransaction Trait
 * 
 * Reusable trait for Livewire components to handle expedition transaction operations.
 * Provides standardized methods for validation, creation, and calculation operations.
 * 
 * @package App\Traits
 * @author System Generated
 */
trait HasExpeditionTransaction
{
    protected ExpeditionTransactionHelperService $expeditionHelper;

    /**
     * Initialize expedition helper service
     */
    public function initializeExpeditionHelper()
    {
        if (!isset($this->expeditionHelper)) {
            $this->expeditionHelper = app(ExpeditionTransactionHelperService::class);
        }
    }

    /**
     * Validate expedition input using helper service
     * 
     * @param array $inputData - Expedition input data
     * @param string $transactionType - Type of transaction
     * @param array $context - Additional context for validation
     * @return array - Array of validation errors (empty if valid)
     */
    public function validateExpeditionInput(array $inputData, string $transactionType, array $context = []): array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->validateExpeditionInput($inputData, $transactionType, $context);
    }

    /**
     * Create expedition transaction using helper service
     * 
     * @param array $inputData - Expedition input data
     * @param string $transactionType - Type of transaction
     * @param string $transactionId - ID of the main transaction
     * @param array $context - Additional context for creation
     * @return mixed - ExpeditionTransaction object or false if failed
     */
    public function createExpeditionTransaction(array $inputData, string $transactionType, string $transactionId, array $context = [])
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->createExpeditionTransaction($inputData, $transactionType, $transactionId, $context);
    }

    /**
     * Get estimated expedition cost using helper service
     * 
     * @param string $expeditionId - Expedition ID
     * @param float $weight - Total weight
     * @param string $transactionType - Type of transaction
     * @param string $destinationZone - Destination zone
     * @param array $context - Additional context
     * @return array|null - Estimated cost data or null if failed
     */
    public function getEstimatedExpeditionCost(string $expeditionId, float $weight, string $transactionType, string $destinationZone = '', array $context = []): ?array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->getEstimatedExpeditionCost($expeditionId, $weight, $transactionType, $destinationZone, $context);
    }

    /**
     * Calculate total weight from items using helper service
     * 
     * @param array $items - Array of items
     * @param string $itemType - Type of items ('supply', 'feed', 'livestock')
     * @return float - Total weight
     */
    public function calculateTotalWeight(array $items, string $itemType = 'supply'): float
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->calculateTotalWeight($items, $itemType);
    }

    /**
     * Normalize expedition_id using helper service
     * 
     * @param mixed $expeditionId - Expedition ID to normalize
     * @return string|null - Normalized expedition ID or null
     */
    public function normalizeExpeditionId($expeditionId): ?string
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->normalizeExpeditionId($expeditionId);
    }

    /**
     * Calculate item sub total using helper service
     * 
     * @param array $item - Item data
     * @return float - Sub total
     */
    public function calculateItemSubTotal(array $item): float
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->calculateItemSubTotal($item);
    }

    /**
     * Calculate total from items with expedition fee using helper service
     * 
     * @param array $items - Array of items
     * @param float $expeditionFee - Expedition fee
     * @param float $discount - Discount amount (optional)
     * @return array - Total calculation result
     */
    public function calculateTotal(array $items, float $expeditionFee = 0, float $discount = 0): array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->calculateTotal($items, $expeditionFee, $discount);
    }

    /**
     * Recalculate all items with sub totals using helper service
     * 
     * @param array $items - Array of items to recalculate
     * @return array - Items with calculated sub totals
     */
    public function recalculateAllItems(array $items): array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->recalculateAllItems($items);
    }

    /**
     * Update item with automatic calculation using helper service
     * 
     * @param array $items - Current items array
     * @param string $key - Livewire key (e.g., '0.quantity')
     * @param mixed $value - New value
     * @param string $field - Field to update
     * @return array - Updated items array
     */
    public function updateItemWithCalculation(array $items, string $key, $value, string $field): array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->updateItemWithCalculation($items, $key, $value, $field);
    }

    /**
     * Get expedition context for transaction creation using helper service
     * 
     * @param array $baseContext - Base context data
     * @param array $items - Items for weight calculation
     * @param string $itemType - Type of items
     * @return array - Complete context for expedition
     */
    public function getExpeditionContext(array $baseContext, array $items = [], string $itemType = 'supply'): array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->getExpeditionContext($baseContext, $items, $itemType);
    }

    /**
     * Validate expedition data before processing using helper service
     * 
     * @param array $expeditionData - Expedition data to validate
     * @param array $items - Items for validation
     * @return array - Validation result with success status and errors
     */
    public function validateExpeditionData(array $expeditionData, array $items = []): array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->validateExpeditionData($expeditionData, $items);
    }

    /**
     * Process expedition transaction with comprehensive validation using helper service
     * 
     * @param array $expeditionData - Expedition data
     * @param string $transactionType - Type of transaction
     * @param string $transactionId - Transaction ID
     * @param array $items - Items for context
     * @param string $itemType - Type of items
     * @return array - Processing result
     */
    public function processExpeditionTransaction(array $expeditionData, string $transactionType, string $transactionId, array $items = [], string $itemType = 'supply'): array
    {
        $this->initializeExpeditionHelper();
        return $this->expeditionHelper->processExpeditionTransaction($expeditionData, $transactionType, $transactionId, $items, $itemType);
    }

    /**
     * Trigger expedition fee update event
     * 
     * @param mixed $value - New expedition fee value
     */
    public function updatedExpeditionFee($value)
    {
        $this->dispatch('expedition-fee-updated');
    }

    /**
     * Trigger recalculation of all items
     */
    public function recalculateTotals()
    {
        $this->dispatch('all-items-recalculated');
        $this->dispatch('totals-recalculated');
    }

    /**
     * Update items quantity with automatic calculation
     * 
     * @param mixed $value - New quantity value
     * @param string $key - Livewire key
     */
    public function updatedItemsQuantity($value, $key)
    {
        [$index] = explode('.', $key);
        $this->items[$index]['quantity'] = $value;
        $this->calculateItemSubTotal($index);
        $this->dispatch('item-updated', index: $index);
    }

    /**
     * Update items price per unit with automatic calculation
     * 
     * @param mixed $value - New price value
     * @param string $key - Livewire key
     */
    public function updatedItemsPricePerUnit($value, $key)
    {
        [$index] = explode('.', $key);
        $this->items[$index]['price_per_unit'] = $value;
        $this->calculateItemSubTotal($index);
        $this->dispatch('item-updated', index: $index);
    }

    /**
     * Get total property for view access
     * 
     * @return array - Total calculation result
     */
    public function getTotalProperty()
    {
        $expeditionFee = floatval($this->expedition_fee ?? 0);
        return $this->calculateTotal($this->items, $expeditionFee);
    }

    /**
     * Get item sub total for specific index
     * 
     * @param int $index - Item index
     * @return float - Sub total
     */
    public function getItemSubTotal($index)
    {
        if (!isset($this->items[$index])) {
            return 0;
        }

        $this->items[$index]['sub_total'] = $this->calculateItemSubTotal($this->items[$index]);
        return $this->items[$index]['sub_total'];
    }

    /**
     * Enhanced updatedItems method with automatic calculations
     * 
     * @param mixed $value - New value
     * @param string $key - Livewire key
     */
    public function updatedItems($value, $key)
    {
        [$index, $field] = explode('.', $key);

        // Handle supply_id changes (existing logic)
        if ($field === 'supply_id') {
            $this->handleSupplyIdChange($index, $value);
        }

        // Handle feed_id changes (existing logic)
        if ($field === 'feed_id') {
            $this->handleFeedIdChange($index, $value);
        }

        // Trigger automatic calculations for quantity and price changes
        if (in_array($field, ['quantity', 'price_per_unit'])) {
            $this->calculateItemSubTotal($index);
            $this->dispatch('item-updated', index: $index);
        }
    }

    /**
     * Handle supply ID change (to be implemented by component)
     * 
     * @param int $index - Item index
     * @param mixed $value - New supply ID value
     */
    protected function handleSupplyIdChange($index, $value)
    {
        // This method should be overridden by the component if needed
        // Default implementation does nothing
    }

    /**
     * Handle feed ID change (to be implemented by component)
     * 
     * @param int $index - Item index
     * @param mixed $value - New feed ID value
     */
    protected function handleFeedIdChange($index, $value)
    {
        // This method should be overridden by the component if needed
        // Default implementation does nothing
    }
}
