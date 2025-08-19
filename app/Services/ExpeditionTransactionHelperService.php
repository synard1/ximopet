<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Models\Supply;
use App\Models\Feed;
use App\Models\Unit;

/**
 * ExpeditionTransactionHelperService
 * 
 * Reusable service for handling expedition transaction operations across Livewire components.
 * Provides standardized methods for validation, creation, and calculation operations.
 * 
 * @package App\Services
 * @author System Generated
 */
class ExpeditionTransactionHelperService
{
    protected ExpeditionService $expeditionService;

    public function __construct(ExpeditionService $expeditionService)
    {
        $this->expeditionService = $expeditionService;
    }

    /**
     * Validate expedition input using ExpeditionService
     * 
     * @param array $inputData - Expedition input data
     * @param string $transactionType - Type of transaction (e.g., 'supply_purchase', 'feed_purchase', 'livestock_purchase')
     * @param array $context - Additional context for validation
     * @return array - Array of validation errors (empty if valid)
     */
    public function validateExpeditionInput(array $inputData, string $transactionType, array $context = []): array
    {
        try {
            $defaultContext = [
                'require_expedition' => false,
                'company_id' => Auth::user()->company_id ?? null,
            ];

            $mergedContext = array_merge($defaultContext, $context);

            return $this->expeditionService->validateLivewireInput(
                $inputData,
                $transactionType,
                $mergedContext
            );
        } catch (\Exception $e) {
            Log::error('Error validating expedition input', [
                'error' => $e->getMessage(),
                'input_data' => $inputData,
                'transaction_type' => $transactionType,
                'context' => $context
            ]);
            return ['expedition_error' => 'Terjadi kesalahan saat validasi data ekspedisi'];
        }
    }

    /**
     * Create expedition transaction using ExpeditionService
     * 
     * @param array $inputData - Expedition input data
     * @param string $transactionType - Type of transaction
     * @param string $transactionId - ID of the main transaction
     * @param array $context - Additional context for creation
     * @return mixed - ExpeditionTransaction object or false if failed
     */
    public function createExpeditionTransaction(array $inputData, string $transactionType, string $transactionId, array $context = [])
    {
        try {
            Log::info('=== EXPEDITION TRANSACTION CREATION/UPDATE START ===', [
                'transaction_id' => $transactionId,
                'transaction_type' => $transactionType,
                'expedition_id' => $inputData['expedition_id'] ?? null,
                'expedition_fee' => $inputData['expedition_fee'] ?? null,
                'user_company_id' => Auth::user()->company_id ?? 'NULL',
            ]);

            // Validate input data before processing
            if (empty($inputData['expedition_id'])) {
                Log::warning('Expedition ID is empty, cannot create expedition transaction', [
                    'transaction_id' => $transactionId
                ]);
                return false;
            }

            if (empty($inputData['expedition_fee']) || $inputData['expedition_fee'] <= 0) {
                Log::warning('Expedition fee is invalid, cannot create expedition transaction', [
                    'transaction_id' => $transactionId,
                    'expedition_fee' => $inputData['expedition_fee']
                ]);
                return false;
            }

            $defaultContext = [
                'shipping_date' => now()->toDateString(),
                'company_id' => Auth::user()->company_id ?? null,
                'notes' => null,
                'total_weight' => 0,
            ];

            $mergedContext = array_merge($defaultContext, $context);

            Log::info('Calling ExpeditionService::createFromLivewireInput', [
                'input_data' => $inputData,
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId,
                'context' => $mergedContext
            ]);

            $expeditionTransaction = $this->expeditionService->createFromLivewireInput(
                $inputData,
                $transactionType,
                $transactionId,
                $mergedContext
            );

            if ($expeditionTransaction) {
                Log::info('=== EXPEDITION TRANSACTION CREATED/UPDATED SUCCESSFULLY ===', [
                    'transaction_id' => $transactionId,
                    'expedition_transaction_id' => $expeditionTransaction->id,
                    'expedition_id' => $expeditionTransaction->expedition_id,
                    'expedition_cost' => $expeditionTransaction->expedition_cost,
                    'status' => $expeditionTransaction->status,
                    'action' => 'created_or_updated'
                ]);
                return $expeditionTransaction;
            }

            Log::warning('=== EXPEDITION TRANSACTION CREATION/UPDATE FAILED ===', [
                'transaction_id' => $transactionId,
                'input_data' => $inputData,
                'context' => $mergedContext,
                'reason' => 'ExpeditionService::createFromLivewireInput returned null'
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('=== EXPEDITION TRANSACTION CREATION/UPDATE ERROR ===', [
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'transaction_id' => $transactionId,
                'transaction_type' => $transactionType,
                'expedition_id' => $inputData['expedition_id'] ?? null,
                'expedition_fee' => $inputData['expedition_fee'] ?? null,
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_timestamp' => now()
            ]);
            return false;
        }
    }

    /**
     * Get estimated expedition cost using ExpeditionService
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
        try {
            $defaultContext = [
                'company_id' => Auth::user()->company_id ?? null,
                'transaction_type' => $transactionType
            ];

            $mergedContext = array_merge($defaultContext, $context);

            return $this->expeditionService->getEstimatedCostForLivewire(
                $expeditionId,
                $weight,
                $destinationZone,
                $mergedContext
            );
        } catch (\Exception $e) {
            Log::error('Error getting estimated expedition cost', [
                'error' => $e->getMessage(),
                'expedition_id' => $expeditionId,
                'weight' => $weight,
                'transaction_type' => $transactionType
            ]);
            return null;
        }
    }

    /**
     * Calculate total weight from supply items
     * 
     * @param array $items - Array of supply items
     * @param string $itemType - Type of items ('supply', 'feed', 'livestock')
     * @return float - Total weight
     */
    public function calculateTotalWeight(array $items, string $itemType = 'supply'): float
    {
        $totalWeight = 0;

        try {
            foreach ($items as $item) {
                if (empty($item['quantity']) || empty($item['supply_id'] ?? $item['feed_id'] ?? null)) {
                    continue;
                }

                $itemId = $item['supply_id'] ?? $item['feed_id'] ?? null;

                switch ($itemType) {
                    case 'supply':
                        $model = Supply::find($itemId);
                        $weightField = 'weight_per_unit';
                        break;
                    case 'feed':
                        $model = Feed::find($itemId);
                        $weightField = 'weight_per_unit';
                        break;
                    default:
                        $model = null;
                        $weightField = null;
                }

                if ($model && isset($model->data[$weightField])) {
                    $totalWeight += ($item['quantity'] * $model->data[$weightField]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error calculating total weight', [
                'error' => $e->getMessage(),
                'items' => $items,
                'item_type' => $itemType
            ]);
        }

        return $totalWeight;
    }

    /**
     * Normalize expedition_id to ensure it's either a valid UUID or null
     * 
     * @param mixed $expeditionId - Expedition ID to normalize
     * @return string|null - Normalized expedition ID or null
     */
    public function normalizeExpeditionId($expeditionId): ?string
    {
        // If it's null, return null
        if ($expeditionId === null) {
            return null;
        }

        // If it's an empty string, return null
        if ($expeditionId === '') {
            return null;
        }

        // If it's a valid UUID format, return it
        if (is_string($expeditionId) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $expeditionId)) {
            return $expeditionId;
        }

        // If it's not a valid UUID, return null
        Log::warning('Invalid expedition_id format detected', [
            'expedition_id' => $expeditionId,
            'type' => gettype($expeditionId)
        ]);
        return null;
    }

    /**
     * Calculate item sub total
     * 
     * @param array $item - Item data
     * @return float - Sub total
     */
    public function calculateItemSubTotal(array $item): float
    {
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price_per_unit'] ?? 0);
        return $quantity * $price;
    }

    /**
     * Calculate total from items with expedition fee
     * 
     * @param array $items - Array of items
     * @param float $expeditionFee - Expedition fee
     * @param float $discount - Discount amount (optional)
     * @return array - Total calculation result
     */
    public function calculateTotal(array $items, float $expeditionFee = 0, float $discount = 0): array
    {
        $total = 0;
        $calculatedItems = [];

        try {
            foreach ($items as $index => $item) {
                $subTotal = $this->calculateItemSubTotal($item);
                $calculatedItems[$index] = array_merge($item, ['sub_total' => $subTotal]);
                $total += $subTotal;
            }

            return [
                'sub_total' => $total,
                'discount' => $discount,
                'expedition_fee' => $expeditionFee,
                'total' => $total - $discount + $expeditionFee,
                'calculated_items' => $calculatedItems
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating total', [
                'error' => $e->getMessage(),
                'items' => $items,
                'expedition_fee' => $expeditionFee
            ]);
            return [
                'sub_total' => 0,
                'discount' => 0,
                'expedition_fee' => $expeditionFee,
                'total' => $expeditionFee,
                'calculated_items' => $items
            ];
        }
    }

    /**
     * Recalculate all items with sub totals
     * 
     * @param array $items - Array of items to recalculate
     * @return array - Items with calculated sub totals
     */
    public function recalculateAllItems(array $items): array
    {
        $recalculatedItems = [];

        try {
            foreach ($items as $index => $item) {
                $recalculatedItems[$index] = array_merge($item, [
                    'sub_total' => $this->calculateItemSubTotal($item)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error recalculating all items', [
                'error' => $e->getMessage(),
                'items' => $items
            ]);
            return $items;
        }

        return $recalculatedItems;
    }

    /**
     * Update item with automatic calculation
     * 
     * @param array $items - Current items array
     * @param string $key - Livewire key (e.g., '0.quantity')
     * @param mixed $value - New value
     * @param string $field - Field to update
     * @return array - Updated items array
     */
    public function updateItemWithCalculation(array $items, string $key, $value, string $field): array
    {
        try {
            [$index] = explode('.', $key);
            $index = (int) $index;

            if (isset($items[$index])) {
                $items[$index][$field] = $value;

                // Recalculate sub total if quantity or price changed
                if (in_array($field, ['quantity', 'price_per_unit'])) {
                    $items[$index]['sub_total'] = $this->calculateItemSubTotal($items[$index]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating item with calculation', [
                'error' => $e->getMessage(),
                'key' => $key,
                'value' => $value,
                'field' => $field
            ]);
        }

        return $items;
    }

    /**
     * Get expedition context for transaction creation
     * 
     * @param array $baseContext - Base context data
     * @param array $items - Items for weight calculation
     * @param string $itemType - Type of items
     * @return array - Complete context for expedition
     */
    public function getExpeditionContext(array $baseContext, array $items = [], string $itemType = 'supply'): array
    {
        $defaultContext = [
            'shipping_date' => now()->toDateString(),
            'company_id' => Auth::user()->company_id ?? null,
            'notes' => null,
            'total_weight' => 0,
            'is_draft' => true,
            'expedition_fee_is_zero' => false,
        ];

        $mergedContext = array_merge($defaultContext, $baseContext);

        // Calculate weight if items provided
        if (!empty($items)) {
            $mergedContext['total_weight'] = $this->calculateTotalWeight($items, $itemType);
        }

        // Check if expedition fee is zero
        if (isset($baseContext['expedition_fee'])) {
            $mergedContext['expedition_fee_is_zero'] = ($baseContext['expedition_fee'] == 0);
        }

        return $mergedContext;
    }

    /**
     * Validate expedition data before processing
     * 
     * @param array $expeditionData - Expedition data to validate
     * @param array $items - Items for validation
     * @return array - Validation result with success status and errors
     */
    public function validateExpeditionData(array $expeditionData, array $items = []): array
    {
        $errors = [];
        $warnings = [];

        try {
            // Check if expedition data exists
            if (empty($expeditionData['expedition_id']) && !empty($expeditionData['expedition_fee'])) {
                $errors[] = 'Expedition ID harus diisi jika expedition fee diisi';
            }

            if (!empty($expeditionData['expedition_id']) && empty($expeditionData['expedition_fee'])) {
                $warnings[] = 'Expedition fee kosong, tidak akan dibuat expedition transaction';
            }

            // Check if items exist for weight calculation
            if (empty($items) && !empty($expeditionData['expedition_id'])) {
                $warnings[] = 'Tidak ada items untuk perhitungan berat ekspedisi';
            }

            // Validate expedition fee
            if (isset($expeditionData['expedition_fee']) && $expeditionData['expedition_fee'] < 0) {
                $errors[] = 'Expedition fee tidak boleh negatif';
            }
        } catch (\Exception $e) {
            Log::error('Error validating expedition data', [
                'error' => $e->getMessage(),
                'expedition_data' => $expeditionData,
                'items' => $items
            ]);
            $errors[] = 'Terjadi kesalahan saat validasi data ekspedisi';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Process expedition transaction with comprehensive validation
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
        try {
            // Step 1: Validate expedition data
            $validationResult = $this->validateExpeditionData($expeditionData, $items);
            if (!$validationResult['success']) {
                return [
                    'success' => false,
                    'errors' => $validationResult['errors'],
                    'warnings' => $validationResult['warnings'],
                    'step' => 'validation'
                ];
            }

            // Step 2: Check if expedition transaction should be created
            if (empty($expeditionData['expedition_id']) || empty($expeditionData['expedition_fee']) || $expeditionData['expedition_fee'] <= 0) {
                return [
                    'success' => true,
                    'message' => 'Expedition transaction tidak diperlukan',
                    'warnings' => $validationResult['warnings'],
                    'step' => 'skipped'
                ];
            }

            // Step 3: Prepare context
            $context = $this->getExpeditionContext([
                'shipping_date' => now()->toDateString(),
                'expedition_fee' => $expeditionData['expedition_fee']
            ], $items, $itemType);

            // Step 4: Create expedition transaction
            $expeditionTransaction = $this->createExpeditionTransaction(
                $expeditionData,
                $transactionType,
                $transactionId,
                $context
            );

            if ($expeditionTransaction) {
                return [
                    'success' => true,
                    'message' => 'Expedition transaction berhasil dibuat',
                    'expedition_transaction' => $expeditionTransaction,
                    'warnings' => $validationResult['warnings'],
                    'step' => 'created'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Gagal membuat expedition transaction'],
                'step' => 'creation_failed'
            ];
        } catch (\Exception $e) {
            Log::error('Error processing expedition transaction', [
                'error' => $e->getMessage(),
                'expedition_data' => $expeditionData,
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId
            ]);

            return [
                'success' => false,
                'errors' => ['Terjadi kesalahan tidak terduga: ' . $e->getMessage()],
                'step' => 'exception'
            ];
        }
    }
}
