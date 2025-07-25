<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\Supply;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplyValidationService
{
    /**
     * Validate supply purchase data
     */
    public function validateSupplyPurchase(array $data): array
    {
        $errors = [];
        $warnings = [];

        // Validate basic required fields
        if (empty($data['invoice_number'])) {
            $errors[] = 'Invoice number is required';
        }

        if (empty($data['date'])) {
            $errors[] = 'Purchase date is required';
        }

        if (empty($data['supplier_id'])) {
            $errors[] = 'Supplier is required';
        }

        if (empty($data['farm_id'])) {
            $errors[] = 'Farm is required';
        }

        // Validate items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'At least one supply item is required';
        } else {
            foreach ($data['items'] as $index => $item) {
                $itemErrors = $this->validateSupplyPurchaseItem($item, $index);
                $errors = array_merge($errors, $itemErrors);
            }
        }

        // Check for duplicate items
        $duplicates = $this->checkDuplicateItems($data['items'] ?? []);
        if (!empty($duplicates)) {
            $errors[] = 'Duplicate items found: ' . implode(', ', $duplicates);
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate supply purchase item
     */
    private function validateSupplyPurchaseItem(array $item, int $index): array
    {
        $errors = [];

        if (empty($item['supply_id'])) {
            $errors[] = "Item {$index}: Supply is required";
        } elseif (!Supply::find($item['supply_id'])) {
            $errors[] = "Item {$index}: Invalid supply selected";
        }

        if (empty($item['quantity']) || $item['quantity'] <= 0) {
            $errors[] = "Item {$index}: Quantity must be greater than 0";
        }

        if (empty($item['unit_id'])) {
            $errors[] = "Item {$index}: Unit is required";
        }

        if (empty($item['price_per_unit']) || $item['price_per_unit'] < 0) {
            $errors[] = "Item {$index}: Price per unit must be 0 or greater";
        }

        return $errors;
    }

    /**
     * Validate supply usage data
     */
    public function validateSupplyUsage(array $data): array
    {
        $errors = [];
        $warnings = [];

        // Validate basic required fields
        if (empty($data['farm_id'])) {
            $errors[] = 'Farm is required';
        }

        if (empty($data['coop_id'])) {
            $errors[] = 'Coop is required';
        }

        if (empty($data['usage_date'])) {
            $errors[] = 'Usage date is required';
        }

        // Validate items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'At least one supply item is required';
        } else {
            foreach ($data['items'] as $index => $item) {
                $itemErrors = $this->validateSupplyUsageItem($item, $index, $data['farm_id']);
                $errors = array_merge($errors, $itemErrors);
            }
        }

        // Validate stock availability
        $stockErrors = $this->validateStockAvailability($data['items'] ?? [], $data['farm_id']);
        $errors = array_merge($errors, $stockErrors);

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate supply usage item
     */
    private function validateSupplyUsageItem(array $item, int $index, string $farmId): array
    {
        $errors = [];

        if (empty($item['supply_stock_id'])) {
            $errors[] = "Item {$index}: Supply stock is required";
        } else {
            $stock = SupplyStock::where('id', $item['supply_stock_id'])
                ->where('farm_id', $farmId)
                ->first();

            if (!$stock) {
                $errors[] = "Item {$index}: Invalid supply stock selected";
            }
        }

        if (empty($item['quantity_taken']) || $item['quantity_taken'] <= 0) {
            $errors[] = "Item {$index}: Quantity taken must be greater than 0";
        }

        if (empty($item['unit_id'])) {
            $errors[] = "Item {$index}: Unit is required";
        }

        return $errors;
    }

    /**
     * Validate supply mutation data
     */
    public function validateSupplyMutation(array $data): array
    {
        $errors = [];
        $warnings = [];

        // Validate basic required fields
        if (empty($data['source_farm_id'])) {
            $errors[] = 'Source farm is required';
        }

        if (empty($data['destination_farm_id'])) {
            $errors[] = 'Destination farm is required';
        }

        if (empty($data['date'])) {
            $errors[] = 'Mutation date is required';
        }

        // Validate destination configuration
        $destinationErrors = $this->validateDestinationConfiguration($data);
        $errors = array_merge($errors, $destinationErrors);

        // Validate items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'At least one supply item is required';
        } else {
            foreach ($data['items'] as $index => $item) {
                $itemErrors = $this->validateSupplyMutationItem($item, $index, $data['source_farm_id']);
                $errors = array_merge($errors, $itemErrors);
            }
        }

        // Validate stock availability for mutation
        $stockErrors = $this->validateMutationStockAvailability($data['items'] ?? [], $data['source_farm_id']);
        $errors = array_merge($errors, $stockErrors);

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate supply mutation item
     */
    private function validateSupplyMutationItem(array $item, int $index, string $sourceFarmId): array
    {
        $errors = [];

        if (empty($item['item_id'])) {
            $errors[] = "Item {$index}: Supply is required";
        } elseif (!Supply::find($item['item_id'])) {
            $errors[] = "Item {$index}: Invalid supply selected";
        }

        if (empty($item['quantity']) || $item['quantity'] <= 0) {
            $errors[] = "Item {$index}: Quantity must be greater than 0";
        }

        if (empty($item['unit_id'])) {
            $errors[] = "Item {$index}: Unit is required";
        }

        return $errors;
    }

    /**
     * Validate destination configuration for mutations
     */
    private function validateDestinationConfiguration(array $data): array
    {
        $errors = [];

        $hasCoopDestination = !empty($data['destination_coop_id']);
        $hasLivestockDestination = !empty($data['destination_livestock_id']);

        if (!$hasCoopDestination && !$hasLivestockDestination) {
            $errors[] = 'Either destination coop or livestock must be specified';
        }

        if ($hasCoopDestination && $hasLivestockDestination) {
            $errors[] = 'Cannot specify both destination coop and livestock';
        }

        // Validate destination exists
        if ($hasCoopDestination) {
            $coop = Coop::find($data['destination_coop_id']);
            if (!$coop) {
                $errors[] = 'Invalid destination coop selected';
            } elseif ($coop->farm_id !== $data['destination_farm_id']) {
                $errors[] = 'Destination coop does not belong to destination farm';
            }
        }

        if ($hasLivestockDestination) {
            $livestock = Livestock::find($data['destination_livestock_id']);
            if (!$livestock) {
                $errors[] = 'Invalid destination livestock selected';
            } elseif ($livestock->farm_id !== $data['destination_farm_id']) {
                $errors[] = 'Destination livestock does not belong to destination farm';
            }
        }

        return $errors;
    }

    /**
     * Check for duplicate items in purchase/usage/mutation
     */
    private function checkDuplicateItems(array $items): array
    {
        $duplicates = [];
        $seen = [];

        foreach ($items as $index => $item) {
            $key = $item['supply_id'] . '-' . ($item['unit_id'] ?? '');
            if (isset($seen[$key])) {
                $duplicates[] = "Item {$index} duplicates item {$seen[$key]}";
            } else {
                $seen[$key] = $index;
            }
        }

        return $duplicates;
    }

    /**
     * Validate stock availability for usage
     */
    private function validateStockAvailability(array $items, string $farmId): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            if (empty($item['supply_stock_id'])) continue;

            $stock = SupplyStock::where('id', $item['supply_stock_id'])
                ->where('farm_id', $farmId)
                ->first();

            if (!$stock) {
                $errors[] = "Item {$index}: Supply stock not found";
                continue;
            }

            $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
            $requested = floatval($item['converted_quantity'] ?? $item['quantity_taken'] ?? 0);

            if ($requested > $available) {
                $errors[] = "Item {$index}: Insufficient stock. Available: {$available}, Requested: {$requested}";
            }
        }

        return $errors;
    }

    /**
     * Validate stock availability for mutation
     */
    private function validateMutationStockAvailability(array $items, string $sourceFarmId): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $supplyId = $item['item_id'];
            $requestedQuantity = floatval($item['quantity'] ?? 0);

            if ($requestedQuantity <= 0) continue;

            // Get total available stock for this supply
            $totalAvailable = SupplyStock::where('farm_id', $sourceFarmId)
                ->where('supply_id', $supplyId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->sum(DB::raw('(quantity_in - quantity_used - quantity_mutated)'));

            if ($requestedQuantity > $totalAvailable) {
                $errors[] = "Item {$index}: Insufficient stock for mutation. Available: {$totalAvailable}, Requested: {$requestedQuantity}";
            }
        }

        return $errors;
    }

    /**
     * Validate status transition
     */
    public function validateStatusTransition(string $currentStatus, string $newStatus, string $entityType = 'supply'): array
    {
        $errors = [];

        $allowedTransitions = $this->getAllowedStatusTransitions($entityType);

        if (!isset($allowedTransitions[$currentStatus])) {
            $errors[] = "Invalid current status: {$currentStatus}";
        } elseif (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            $errors[] = "Invalid status transition from {$currentStatus} to {$newStatus}";
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get allowed status transitions
     */
    private function getAllowedStatusTransitions(string $entityType): array
    {
        switch ($entityType) {
            case 'purchase':
                return [
                    'draft' => ['pending', 'cancelled'],
                    'pending' => ['in_transit', 'confirmed', 'cancelled'],
                    'in_transit' => ['arrived', 'cancelled'],
                    'arrived' => ['completed', 'cancelled'],
                    'confirmed' => ['in_transit', 'arrived', 'cancelled'],
                    'completed' => [],
                    'cancelled' => []
                ];

            case 'usage':
                return [
                    'draft' => ['pending', 'cancelled'],
                    'pending' => ['in_process', 'cancelled'],
                    'in_process' => ['completed', 'partially_used', 'cancelled'],
                    'completed' => [],
                    'partially_used' => ['completed'],
                    'cancelled' => ['draft']
                ];

            case 'mutation':
                return [
                    'draft' => ['pending', 'verified', 'completed', 'cancelled'],
                    'pending' => ['approved', 'rejected', 'verified', 'cancelled'],
                    'verified' => ['completed', 'cancelled'],
                    'approved' => ['completed', 'verified', 'cancelled'],
                    'rejected' => ['draft', 'cancelled'],
                    'completed' => ['verified', 'cancelled'],
                    'cancelled' => []
                ];

            default:
                return [];
        }
    }
}
