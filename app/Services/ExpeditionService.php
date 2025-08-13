<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\ExpeditionTariff;
use App\Models\ExpeditionTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ExpeditionService
{
    /**
     * Get all active expeditions
     */
    public function getActiveExpeditions(): Collection
    {
        return Partner::where('type', 'Expedition')->where('status', 'active')->get();
    }

    /**
     * Get expedition by ID
     */
    public function getExpedition(string $id): ?Partner
    {
        return Partner::where('type', 'Expedition')->where('id', $id)->first();
    }

    /**
     * Get expedition by code
     */
    public function getExpeditionByCode(string $code): ?Partner
    {
        return Partner::where('type', 'Expedition')->where('code', $code)->first();
    }

    /**
     * Get available zones for an expedition
     */
    public function getAvailableZones(string $expeditionId): array
    {
        return ExpeditionTariff::getZonesForExpedition($expeditionId);
    }

    /**
     * Get estimated cost for a route (reference only)
     */
    public function getEstimatedCost(
        string $expeditionId,
        string $destinationZone,
        float $weight
    ): array {
        Log::info('Getting estimated expedition cost', [
            'expedition_id' => $expeditionId,
            'destination_zone' => $destinationZone,
            'weight' => $weight
        ]);

        // Find reference tariff for estimation
        $tariff = ExpeditionTariff::where('expedition_id', $expeditionId)
            ->where('zone_name', $destinationZone)
            ->active()
            ->first();

        $expedition = Partner::where('type', 'Expedition')->where('id', $expeditionId)->first();

        if (!$tariff) {
            // Fallback to average rate from historical data
            $avgCostPerKg = ExpeditionTransaction::where('expedition_id', $expeditionId)
                ->where('destination_zone', $destinationZone)
                ->avg('expedition_cost') /
                ExpeditionTransaction::where('expedition_id', $expeditionId)
                ->where('destination_zone', $destinationZone)
                ->avg('total_weight') ?: 3000; // Default 3000 per kg

            return [
                'success' => false,
                'message' => 'Tarif tidak ditemukan, menggunakan estimasi berdasarkan data historis',
                'expedition_name' => $expedition->name ?? 'Unknown',
                'estimated_cost' => $weight * $avgCostPerKg,
                'rate_per_kg' => $avgCostPerKg,
                'note' => 'Estimasi berdasarkan data historis'
            ];
        }

        $estimatedCost = $tariff->getEstimatedCost($weight);

        return [
            'success' => true,
            'expedition_name' => $expedition->name ?? 'Unknown',
            'zone' => $destinationZone,
            'estimated_cost' => $estimatedCost,
            'rate_per_kg' => $tariff->estimated_rate_per_kg,
            'note' => 'Estimasi berdasarkan tarif referensi'
        ];
    }

    /**
     * Record expedition cost for any transaction
     */
    public function recordExpeditionCost(
        $transaction,
        string $transactionType,
        array $expeditionData
    ): ExpeditionTransaction {
        Log::info('=== EXPEDITION SERVICE: recordExpeditionCost START ===', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transaction->id,
            'expedition_id' => $expeditionData['expedition_id'],
            'expedition_cost' => $expeditionData['expedition_cost'],
            'expedition_data_keys' => array_keys($expeditionData),
            'transaction_class' => get_class($transaction)
        ]);

        // Validate required data
        Log::info('Validating expedition data before recording');
        $this->validateExpeditionData($expeditionData);
        Log::info('Expedition data validation passed');

        Log::info('Calling ExpeditionTransaction::createFromTransaction', [
            'transaction_id' => $transaction->id,
            'transaction_type' => $transactionType,
            'expedition_data' => $expeditionData
        ]);

        $expeditionTransaction = ExpeditionTransaction::createFromTransaction(
            $transaction,
            $transactionType,
            $expeditionData
        );

        Log::info('=== EXPEDITION SERVICE: recordExpeditionCost SUCCESS ===', [
            'expedition_transaction_id' => $expeditionTransaction->id,
            'expedition_cost' => $expeditionTransaction->expedition_cost,
            'expedition_id' => $expeditionTransaction->expedition_id,
            'status' => $expeditionTransaction->status
        ]);

        return $expeditionTransaction;
    }

    /**
     * Get expedition cost summary for reporting
     */
    public function getExpeditionCostSummary(array $filters = []): array
    {
        $query = ExpeditionTransaction::query();

        // Apply filters
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange(
                Carbon::parse($filters['start_date']),
                Carbon::parse($filters['end_date'])
            );
        }

        if (isset($filters['expedition_id'])) {
            $query->forExpedition($filters['expedition_id']);
        }

        if (isset($filters['transaction_type'])) {
            $query->ofType($filters['transaction_type']);
        }

        if (isset($filters['zone'])) {
            $query->inZone($filters['zone']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        $transactions = $query->with(['expedition'])->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_cost' => $transactions->sum('expedition_cost'),
            'total_weight' => $transactions->sum('total_weight'),
            'average_cost_per_kg' => $transactions->avg('cost_per_kg'),
            'breakdown_by_expedition' => $this->groupByExpedition($transactions),
            'breakdown_by_zone' => $this->groupByZone($transactions),
            'breakdown_by_transaction_type' => $this->groupByTransactionType($transactions),
            'monthly_trend' => $this->getMonthlyTrend($transactions),
        ];
    }

    /**
     * Get expedition transactions with pagination
     */
    public function getExpeditionTransactions(array $filters = [], int $perPage = 15): array
    {
        $query = ExpeditionTransaction::with(['expedition']);

        // Apply filters
        if (isset($filters['expedition_id'])) {
            $query->forExpedition($filters['expedition_id']);
        }

        if (isset($filters['transaction_type'])) {
            $query->ofType($filters['transaction_type']);
        }

        if (isset($filters['zone'])) {
            $query->inZone($filters['zone']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange(
                Carbon::parse($filters['start_date']),
                Carbon::parse($filters['end_date'])
            );
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        $transactions = $query->orderBy('shipping_date', 'desc')->paginate($perPage);

        return [
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ];
    }

    /**
     * Get expedition performance metrics
     */
    public function getExpeditionPerformance(string $expeditionId, array $filters = []): array
    {
        $query = ExpeditionTransaction::where('expedition_id', $expeditionId);

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange(
                Carbon::parse($filters['start_date']),
                Carbon::parse($filters['end_date'])
            );
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            return [
                'total_transactions' => 0,
                'total_cost' => 0,
                'total_weight' => 0,
                'average_cost_per_kg' => 0,
                'delivery_success_rate' => 0,
                'average_delivery_time' => 0,
            ];
        }

        $deliveredTransactions = $transactions->where('status', 'delivered');
        $deliveryTime = $deliveredTransactions->map(function ($transaction) {
            if ($transaction->shipping_date && $transaction->delivered_at) {
                return Carbon::parse($transaction->shipping_date)
                    ->diffInDays(Carbon::parse($transaction->delivered_at));
            }
            return null;
        })->filter();

        return [
            'total_transactions' => $transactions->count(),
            'total_cost' => $transactions->sum('expedition_cost'),
            'total_weight' => $transactions->sum('total_weight'),
            'average_cost_per_kg' => $transactions->avg('cost_per_kg'),
            'delivery_success_rate' => $deliveredTransactions->count() / $transactions->count() * 100,
            'average_delivery_time' => $deliveryTime->isNotEmpty() ? $deliveryTime->avg() : 0,
        ];
    }

    /**
     * Update expedition transaction status
     */
    public function updateTransactionStatus(string $transactionId, string $status, array $additionalData = []): bool
    {
        $transaction = ExpeditionTransaction::find($transactionId);

        if (!$transaction) {
            throw new \InvalidArgumentException('Expedition transaction not found');
        }

        $updateData = ['status' => $status];

        if ($status === 'delivered' && !isset($additionalData['delivered_at'])) {
            $updateData['delivered_at'] = now();
        }

        if (isset($additionalData['tracking_number'])) {
            $updateData['tracking_number'] = $additionalData['tracking_number'];
        }

        if (isset($additionalData['notes'])) {
            $updateData['notes'] = $additionalData['notes'];
        }

        return $transaction->update($updateData);
    }

    /**
     * Get expedition cost comparison between expeditions
     */
    public function getExpeditionCostComparison(array $expeditionIds, array $filters = []): array
    {
        $query = ExpeditionTransaction::whereIn('expedition_id', $expeditionIds);

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange(
                Carbon::parse($filters['start_date']),
                Carbon::parse($filters['end_date'])
            );
        }

        if (isset($filters['zone'])) {
            $query->inZone($filters['zone']);
        }

        $transactions = $query->with('expedition')->get();

        $comparison = [];
        foreach ($expeditionIds as $expeditionId) {
            $expeditionTransactions = $transactions->where('expedition_id', $expeditionId);
            $expedition = $expeditionTransactions->first()?->expedition;

            if ($expeditionTransactions->isNotEmpty()) {
                $comparison[] = [
                    'expedition_id' => $expeditionId,
                    'expedition_name' => $expedition->name ?? 'Unknown',
                    'total_transactions' => $expeditionTransactions->count(),
                    'total_cost' => $expeditionTransactions->sum('expedition_cost'),
                    'total_weight' => $expeditionTransactions->sum('total_weight'),
                    'average_cost_per_kg' => $expeditionTransactions->avg('cost_per_kg'),
                ];
            }
        }

        return $comparison;
    }

    /**
     * Validate expedition data
     * For draft status, only expedition_id and expedition_cost are required
     * Other fields can be filled later when status changes to complete
     */
    private function validateExpeditionData(array $data): void
    {
        Log::info('=== EXPEDITION SERVICE: validateExpeditionData START ===', [
            'data_keys' => array_keys($data),
            'data_values' => $data
        ]);

        // Core required fields for any expedition transaction
        $coreRequired = ['expedition_id', 'expedition_cost'];

        foreach ($coreRequired as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                Log::warning("Core required field '{$field}' is missing or empty", [
                    'field' => $field,
                    'value' => $data[$field] ?? 'NOT_SET'
                ]);
                throw new \InvalidArgumentException("Field '{$field}' is required for expedition transaction");
            }
        }

        // Validate expedition_cost is positive
        if ($data['expedition_cost'] <= 0) {
            Log::warning("Expedition cost must be greater than 0", [
                'expedition_cost' => $data['expedition_cost']
            ]);
            throw new \InvalidArgumentException("Expedition cost must be greater than 0");
        }

        // For draft status, other fields are optional
        // These will be required when status changes to complete
        if (isset($data['total_weight']) && $data['total_weight'] <= 0) {
            Log::warning("Total weight must be greater than 0 if provided", [
                'total_weight' => $data['total_weight']
            ]);
            throw new \InvalidArgumentException("Total weight must be greater than 0 if provided");
        }

        Log::info('=== EXPEDITION SERVICE: validateExpeditionData PASSED ===', [
            'validated_fields' => array_keys($data)
        ]);
    }

    /**
     * Group transactions by expedition
     */
    private function groupByExpedition($transactions): array
    {
        return $transactions->groupBy('expedition.name')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                    'avg_cost_per_kg' => $group->avg('cost_per_kg'),
                ];
            })
            ->toArray();
    }

    /**
     * Group transactions by zone
     */
    private function groupByZone($transactions): array
    {
        return $transactions->groupBy('destination_zone')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                    'avg_cost_per_kg' => $group->avg('cost_per_kg'),
                ];
            })
            ->toArray();
    }

    /**
     * Group transactions by transaction type
     */
    private function groupByTransactionType($transactions): array
    {
        return $transactions->groupBy('transaction_type')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                    'avg_cost_per_kg' => $group->avg('cost_per_kg'),
                ];
            })
            ->toArray();
    }

    /**
     * Get monthly trend
     */
    private function getMonthlyTrend($transactions): array
    {
        return $transactions->groupBy(function ($transaction) {
            return $transaction->shipping_date->format('Y-m');
        })
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                ];
            })
            ->toArray();
    }

    // ========================================
    // MODULAR EXPEDITION TRANSACTION HANDLERS
    // ========================================

    /**
     * Create or update expedition transaction from Livewire component input
     * Modular function that can be used across different transaction types
     * 
     * @param array $inputData - Raw input from Livewire component
     * @param string $transactionType - Type of transaction (livestock_purchase, supply_purchase, feed_purchase, sales, etc.)
     * @param string $transactionId - ID of the main transaction
     * @param array $context - Additional context data
     * @return ExpeditionTransaction|null
     */
    public function createFromLivewireInput(
        array $inputData,
        string $transactionType,
        string $transactionId,
        array $context = []
    ): ?ExpeditionTransaction {
        Log::info('=== EXPEDITION SERVICE: createFromLivewireInput START ===', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'input_data' => $inputData,
            'input_data_types' => [
                'expedition_id' => isset($inputData['expedition_id']) ? gettype($inputData['expedition_id']) : 'NOT_SET',
                'expedition_fee' => isset($inputData['expedition_fee']) ? gettype($inputData['expedition_fee']) : 'NOT_SET'
            ],
            'context' => $context,
            'context_types' => [
                'shipping_date' => isset($context['shipping_date']) ? gettype($context['shipping_date']) : 'NOT_SET',
                'company_id' => isset($context['company_id']) ? gettype($context['company_id']) : 'NOT_SET'
            ]
        ]);

        try {
            // Validate and normalize input data
            $normalizedData = $this->normalizeLivewireInput($inputData, $transactionType);

            if (!$normalizedData) {
                Log::warning('Invalid expedition input data, skipping expedition transaction creation', [
                    'transaction_type' => $transactionType,
                    'transaction_id' => $transactionId,
                    'input_data' => $inputData
                ]);
                return null;
            }

            // Validate expedition exists before proceeding
            if (!$this->expeditionExists($normalizedData['expedition_id'])) {
                Log::error('=== EXPEDITION SERVICE: EXPEDITION NOT FOUND ===', [
                    'expedition_id' => $normalizedData['expedition_id'],
                    'transaction_type' => $transactionType,
                    'transaction_id' => $transactionId,
                    'error' => 'Expedition ID does not exist in expeditions table'
                ]);
                return null;
            }

            // Get transaction model for context
            $transaction = $this->getTransactionModel($transactionType, $transactionId);

            if (!$transaction) {
                Log::error('Transaction model not found for expedition creation', [
                    'transaction_type' => $transactionType,
                    'transaction_id' => $transactionId
                ]);
                return null;
            }

            // Check if expedition transaction already exists for this transaction
            $existingExpeditionTransaction = $this->getExistingExpeditionTransaction($transactionType, $transactionId);

            if ($existingExpeditionTransaction) {
                Log::info('=== EXPEDITION SERVICE: UPDATING EXISTING TRANSACTION ===', [
                    'existing_id' => $existingExpeditionTransaction->id,
                    'transaction_type' => $transactionType,
                    'transaction_id' => $transactionId,
                    'old_cost' => $existingExpeditionTransaction->expedition_cost,
                    'new_cost' => $normalizedData['expedition_fee']
                ]);

                // Update existing expedition transaction
                $expeditionTransaction = $this->updateExistingExpeditionTransaction(
                    $existingExpeditionTransaction,
                    $normalizedData,
                    $context
                );
            } else {
                Log::info('=== EXPEDITION SERVICE: CREATING NEW TRANSACTION ===', [
                    'transaction_type' => $transactionType,
                    'transaction_id' => $transactionId,
                    'expedition_id' => $normalizedData['expedition_id'],
                    'expedition_cost' => $normalizedData['expedition_fee']
                ]);

                // Prepare expedition data for new transaction
                $expeditionData = $this->prepareExpeditionDataFromInput(
                    $normalizedData,
                    $transaction,
                    $transactionType,
                    $context
                );

                // Create new expedition transaction
                $expeditionTransaction = $this->recordExpeditionCost(
                    $transaction,
                    $transactionType,
                    $expeditionData
                );
            }

            $action = $existingExpeditionTransaction ? 'updated' : 'created';
            Log::info("Expedition transaction {$action} from Livewire input", [
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId,
                'expedition_transaction_id' => $expeditionTransaction->id,
                'expedition_cost' => $expeditionTransaction->expedition_cost,
                'action' => $action
            ]);

            return $expeditionTransaction;
        } catch (\Exception $e) {
            Log::error('Error creating expedition transaction from Livewire input', [
                'error' => $e->getMessage(),
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId,
                'input_data' => $inputData
            ]);

            return null;
        }
    }

    /**
     * Update expedition transaction from Livewire component input
     * 
     * @param string $expeditionTransactionId - ID of existing expedition transaction
     * @param array $inputData - Updated input from Livewire component
     * @param array $context - Additional context data
     * @return ExpeditionTransaction|null
     */
    public function updateFromLivewireInput(
        string $expeditionTransactionId,
        array $inputData,
        array $context = []
    ): ?ExpeditionTransaction {
        Log::info('Updating expedition transaction from Livewire input', [
            'expedition_transaction_id' => $expeditionTransactionId,
            'input_data' => $inputData,
            'context' => $context
        ]);

        try {
            $expeditionTransaction = ExpeditionTransaction::find($expeditionTransactionId);

            if (!$expeditionTransaction) {
                Log::warning('Expedition transaction not found for update', [
                    'expedition_transaction_id' => $expeditionTransactionId
                ]);
                return null;
            }

            // Validate and normalize input data
            $normalizedData = $this->normalizeLivewireInput($inputData, $expeditionTransaction->transaction_type);

            if (!$normalizedData) {
                Log::warning('Invalid expedition input data for update', [
                    'expedition_transaction_id' => $expeditionTransactionId,
                    'input_data' => $inputData
                ]);
                return null;
            }

            // Update expedition transaction
            $updatedData = $this->prepareUpdateDataFromInput($normalizedData, $context);

            $expeditionTransaction->update($updatedData);

            Log::info('Expedition transaction updated from Livewire input', [
                'expedition_transaction_id' => $expeditionTransactionId,
                'updated_data' => $updatedData
            ]);

            return $expeditionTransaction->fresh();
        } catch (\Exception $e) {
            Log::error('Error updating expedition transaction from Livewire input', [
                'error' => $e->getMessage(),
                'expedition_transaction_id' => $expeditionTransactionId,
                'input_data' => $inputData
            ]);

            return null;
        }
    }

    /**
     * Validate expedition input from Livewire component
     * 
     * @param array $inputData - Raw input from Livewire component
     * @param string $transactionType - Type of transaction
     * @param array $context - Additional context for validation
     * @return array - Array of validation errors (empty if valid)
     */
    public function validateLivewireInput(
        array $inputData,
        string $transactionType,
        array $context = []
    ): array {
        $errors = [];

        // Basic validation
        if (isset($inputData['expedition_id']) && !empty($inputData['expedition_id'])) {
            // Validate expedition ID format
            if (!$this->isValidUuid($inputData['expedition_id'])) {
                $errors['expedition_id'] = 'Format ID ekspedisi tidak valid';
            }

            // Validate expedition exists
            if (!$this->expeditionExists($inputData['expedition_id'])) {
                $errors['expedition_id'] = 'Ekspedisi tidak ditemukan';
            }

            // Validate expedition fee if expedition is selected
            if (isset($inputData['expedition_fee'])) {
                if (!is_numeric($inputData['expedition_fee']) || $inputData['expedition_fee'] < 0) {
                    $errors['expedition_fee'] = 'Biaya ekspedisi harus berupa angka dan tidak boleh negatif';
                }
            } else {
                $errors['expedition_fee'] = 'Biaya ekspedisi harus diisi jika ekspedisi dipilih';
            }
        }

        // Context-specific validation
        if (isset($context['require_expedition']) && $context['require_expedition']) {
            if (empty($inputData['expedition_id'])) {
                $errors['expedition_id'] = 'Ekspedisi wajib dipilih untuk status ini';
            }
            if (empty($inputData['expedition_fee']) || $inputData['expedition_fee'] <= 0) {
                $errors['expedition_fee'] = 'Biaya ekspedisi wajib diisi untuk status ini';
            }
        }

        return $errors;
    }

    /**
     * Get expedition data for Livewire component
     * 
     * @param string $companyId - Company ID for filtering
     * @param array $filters - Additional filters
     * @return array
     */
    public function getExpeditionDataForLivewire(string $companyId, array $filters = []): array
    {
        try {
            $query = Partner::where('type', 'Expedition');

            // Apply company filter
            if (!empty($companyId)) {
                $query->where('company_id', $companyId);
            }

            // Apply additional filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['active_only']) && $filters['active_only']) {
                $query->where('status', 'active');
            }

            $expeditions = $query->orderBy('name')->get();

            Log::info('Expedition data retrieved for Livewire component', [
                'company_id' => $companyId,
                'filters' => $filters,
                'count' => $expeditions->count()
            ]);

            return [
                'expeditions' => $expeditions,
                'total' => $expeditions->count(),
                'filters_applied' => $filters
            ];
        } catch (\Exception $e) {
            Log::error('Error getting expedition data for Livewire component', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'filters' => $filters
            ]);

            return [
                'expeditions' => collect(),
                'total' => 0,
                'filters_applied' => $filters,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate estimated expedition cost for Livewire component
     * 
     * @param string $expeditionId - Expedition ID
     * @param float $weight - Total weight
     * @param string $destinationZone - Destination zone
     * @param array $context - Additional context
     * @return array
     */
    public function getEstimatedCostForLivewire(
        string $expeditionId,
        float $weight,
        string $destinationZone = '',
        array $context = []
    ): array {
        try {
            $estimatedCost = $this->getEstimatedCost($expeditionId, $destinationZone, $weight);

            // Add context-specific information
            $result = array_merge($estimatedCost, [
                'context' => $context,
                'calculation_timestamp' => now()->toISOString(),
                'source' => 'livewire_component'
            ]);

            Log::info('Estimated cost calculated for Livewire component', [
                'expedition_id' => $expeditionId,
                'weight' => $weight,
                'destination_zone' => $destinationZone,
                'estimated_cost' => $estimatedCost['estimated_cost'] ?? null
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error calculating estimated cost for Livewire component', [
                'error' => $e->getMessage(),
                'expedition_id' => $expeditionId,
                'weight' => $weight,
                'destination_zone' => $destinationZone
            ]);

            return [
                'success' => false,
                'message' => 'Gagal menghitung estimasi biaya',
                'error' => $e->getMessage(),
                'context' => $context,
                'calculation_timestamp' => now()->toISOString(),
                'source' => 'livewire_component'
            ];
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Normalize Livewire input data
     * 
     * @param array $inputData - Raw input data
     * @param string $transactionType - Transaction type
     * @return array|null - Normalized data or null if invalid
     */
    private function normalizeLivewireInput(array $inputData, string $transactionType): ?array
    {
        Log::info('=== EXPEDITION SERVICE: normalizeLivewireInput START ===', [
            'input_data' => $inputData,
            'transaction_type' => $transactionType
        ]);

        $normalized = [];

        // Normalize expedition_id
        if (isset($inputData['expedition_id'])) {
            Log::info('Normalizing expedition_id', [
                'raw_expedition_id' => $inputData['expedition_id'],
                'raw_type' => gettype($inputData['expedition_id'])
            ]);

            $expeditionId = $this->normalizeExpeditionId($inputData['expedition_id']);

            Log::info('Expedition ID normalization result', [
                'raw_expedition_id' => $inputData['expedition_id'],
                'normalized_expedition_id' => $expeditionId,
                'is_false' => $expeditionId === false
            ]);

            if ($expeditionId === false) {
                Log::warning('Expedition ID normalization failed - returning null', [
                    'raw_expedition_id' => $inputData['expedition_id']
                ]);
                return null; // Invalid expedition ID
            }
            $normalized['expedition_id'] = $expeditionId;
        } else {
            Log::warning('expedition_id not found in input data');
        }

        // Normalize expedition_fee
        if (isset($inputData['expedition_fee'])) {
            Log::info('Normalizing expedition_fee', [
                'raw_expedition_fee' => $inputData['expedition_fee'],
                'raw_type' => gettype($inputData['expedition_fee'])
            ]);

            $fee = $this->normalizeExpeditionFee($inputData['expedition_fee']);

            Log::info('Expedition fee normalization result', [
                'raw_expedition_fee' => $inputData['expedition_fee'],
                'normalized_fee' => $fee,
                'is_false' => $fee === false
            ]);

            if ($fee === false) {
                Log::warning('Expedition fee normalization failed - returning null', [
                    'raw_expedition_fee' => $inputData['expedition_fee']
                ]);
                return null; // Invalid fee
            }
            $normalized['expedition_fee'] = $fee;
        } else {
            Log::warning('expedition_fee not found in input data');
        }

        // Add transaction type context
        $normalized['transaction_type'] = $transactionType;

        Log::info('=== EXPEDITION SERVICE: normalizeLivewireInput RESULT ===', [
            'normalized_data' => $normalized,
            'is_valid' => !empty($normalized['expedition_id']) && !empty($normalized['expedition_fee'])
        ]);

        return $normalized;
    }

    /**
     * Normalize expedition ID
     * 
     * @param mixed $expeditionId - Raw expedition ID
     * @return string|false - Normalized ID or false if invalid
     */
    private function normalizeExpeditionId($expeditionId)
    {
        if ($expeditionId === null || $expeditionId === '') {
            return null;
        }

        if (is_string($expeditionId) && $this->isValidUuid($expeditionId)) {
            return $expeditionId;
        }

        Log::warning('Invalid expedition_id format detected', [
            'expedition_id' => $expeditionId,
            'type' => gettype($expeditionId)
        ]);

        return false;
    }

    /**
     * Normalize expedition fee
     * 
     * @param mixed $fee - Raw expedition fee
     * @return float|false - Normalized fee or false if invalid
     */
    private function normalizeExpeditionFee($fee)
    {
        if ($fee === null || $fee === '') {
            return 0.0;
        }

        if (is_numeric($fee) && $fee >= 0) {
            return (float) $fee;
        }

        Log::warning('Invalid expedition_fee format detected', [
            'expedition_fee' => $fee,
            'type' => gettype($fee)
        ]);

        return false;
    }

    /**
     * Check if UUID is valid
     * 
     * @param string $uuid - UUID string to validate
     * @return bool
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Get existing expedition transaction for a specific transaction
     * 
     * @param string $transactionType - Type of transaction
     * @param string $transactionId - Transaction ID
     * @return ExpeditionTransaction|null
     */
    private function getExistingExpeditionTransaction(string $transactionType, string $transactionId): ?ExpeditionTransaction
    {
        Log::info('=== EXPEDITION SERVICE: getExistingExpeditionTransaction START ===', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId
        ]);

        $existingTransaction = ExpeditionTransaction::where('transaction_type', $transactionType)
            ->where('transaction_id', $transactionId)
            ->first();

        Log::info('=== EXPEDITION SERVICE: getExistingExpeditionTransaction RESULT ===', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'found' => $existingTransaction !== null,
            'existing_id' => $existingTransaction ? $existingTransaction->id : 'NULL',
            'existing_cost' => $existingTransaction ? $existingTransaction->expedition_cost : 'NULL'
        ]);

        return $existingTransaction;
    }

    /**
     * Update existing expedition transaction
     * 
     * @param ExpeditionTransaction $existingTransaction - Existing expedition transaction
     * @param array $normalizedData - Normalized input data
     * @param array $context - Additional context
     * @return ExpeditionTransaction
     */
    private function updateExistingExpeditionTransaction(
        ExpeditionTransaction $existingTransaction,
        array $normalizedData,
        array $context = []
    ): ExpeditionTransaction {
        Log::info('=== EXPEDITION SERVICE: updateExistingExpeditionTransaction START ===', [
            'existing_id' => $existingTransaction->id,
            'old_cost' => $existingTransaction->expedition_cost,
            'new_cost' => $normalizedData['expedition_fee'],
            'normalized_data' => $normalizedData
        ]);

        // Update expedition cost
        $existingTransaction->expedition_cost = $normalizedData['expedition_fee'];

        // Update other fields if provided in context
        if (isset($context['shipping_date'])) {
            $existingTransaction->shipping_date = $context['shipping_date'];
        }

        if (isset($context['destination_address']) && !empty($context['destination_address'])) {
            $existingTransaction->destination_address = $context['destination_address'];
        }

        if (isset($context['destination_zone']) && !empty($context['destination_zone'])) {
            $existingTransaction->destination_zone = $context['destination_zone'];
        }

        if (isset($context['notes']) && !empty($context['notes'])) {
            $existingTransaction->notes = $context['notes'];
        }

        // Update total weight if available
        if (isset($context['total_weight']) && $context['total_weight'] > 0) {
            $existingTransaction->total_weight = $context['total_weight'];
        }

        // Update updated_by and updated_at
        $existingTransaction->updated_by = \Illuminate\Support\Facades\Auth::id();
        $existingTransaction->updated_at = now();

        // Save the updated transaction
        $existingTransaction->save();

        // Append capped JSON history (max 5) under cost_details.history
        try {
            $before = [
                'expedition_cost' => (float) ($existingTransaction->getOriginal('expedition_cost')),
                'total_weight' => (float) ($existingTransaction->getOriginal('total_weight')),
                'shipping_date' => (string) ($existingTransaction->getOriginal('shipping_date')),
                'destination_zone' => (string) ($existingTransaction->getOriginal('destination_zone')),
                'notes' => (string) ($existingTransaction->getOriginal('notes')),
            ];

            $after = [
                'expedition_cost' => (float) ($existingTransaction->expedition_cost),
                'total_weight' => (float) ($existingTransaction->total_weight),
                'shipping_date' => (string) ($existingTransaction->shipping_date),
                'destination_zone' => (string) ($existingTransaction->destination_zone),
                'notes' => (string) ($existingTransaction->notes),
            ];

            $entry = [
                'action' => 'expedition_cost_update',
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'before' => $before,
                'after' => $after,
                'context' => $context,
            ];

            // Ensure cost_details is an array to hold history
            $existingTransaction->cost_details = is_array($existingTransaction->cost_details) ? $existingTransaction->cost_details : [];
            $existingTransaction->appendJsonHistory('cost_details', 'history', $entry, 5);
            $existingTransaction->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to append expedition JSON history', [
                'transaction_id' => $existingTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('=== EXPEDITION SERVICE: updateExistingExpeditionTransaction SUCCESS ===', [
            'updated_id' => $existingTransaction->id,
            'new_cost' => $existingTransaction->expedition_cost,
            'updated_at' => $existingTransaction->updated_at
        ]);

        return $existingTransaction;
    }

    /**
     * Clean up duplicate expedition transactions for a specific transaction
     * Keep only the latest one and delete older duplicates
     * 
     * @param string $transactionType - Type of transaction
     * @param string $transactionId - Transaction ID
     * @return bool
     */
    public function cleanupDuplicateExpeditionTransactions(string $transactionType, string $transactionId): bool
    {
        Log::info('=== EXPEDITION SERVICE: cleanupDuplicateExpeditionTransactions START ===', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId
        ]);

        try {
            // Get all expedition transactions for this transaction
            $duplicates = ExpeditionTransaction::where('transaction_type', $transactionType)
                ->where('transaction_id', $transactionId)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($duplicates->count() <= 1) {
                Log::info('No duplicates found, skipping cleanup', [
                    'transaction_type' => $transactionType,
                    'transaction_id' => $transactionId,
                    'count' => $duplicates->count()
                ]);
                return true;
            }

            // Keep the latest one, delete the rest
            $latest = $duplicates->first();
            $toDelete = $duplicates->slice(1);

            Log::info('Found duplicate expedition transactions', [
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId,
                'total_count' => $duplicates->count(),
                'keeping_id' => $latest->id,
                'deleting_count' => $toDelete->count()
            ]);

            foreach ($toDelete as $duplicate) {
                Log::info('Deleting duplicate expedition transaction', [
                    'duplicate_id' => $duplicate->id,
                    'cost' => $duplicate->expedition_cost,
                    'created_at' => $duplicate->created_at
                ]);
                $duplicate->delete();
            }

            Log::info('=== EXPEDITION SERVICE: cleanupDuplicateExpeditionTransactions SUCCESS ===', [
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId,
                'deleted_count' => $toDelete->count(),
                'remaining_id' => $latest->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error cleaning up duplicate expedition transactions', [
                'error' => $e->getMessage(),
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId
            ]);
            return false;
        }
    }

    /**
     * Check if expedition exists
     * 
     * @param string $expeditionId - Expedition ID to check
     * @return bool
     */
    private function expeditionExists(string $expeditionId): bool
    {
        $exists = Partner::where('type', 'Expedition')->where('id', $expeditionId)->exists();

        Log::info('=== EXPEDITION SERVICE: expeditionExists CHECK ===', [
            'expedition_id' => $expeditionId,
            'exists' => $exists,
            'check_timestamp' => now()
        ]);

        return $exists;
    }

    /**
     * Get transaction model for context
     * 
     * @param string $transactionType - Transaction type
     * @param string $transactionId - Transaction ID
     * @return mixed|null - Transaction model or null
     */
    private function getTransactionModel(string $transactionType, string $transactionId)
    {
        Log::info('=== EXPEDITION SERVICE: getTransactionModel START ===', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId
        ]);

        $modelMap = [
            'livestock_purchase' => \App\Models\LivestockPurchase::class,
            'supply_purchase' => \App\Models\SupplyPurchase::class,
            'feed_purchase' => \App\Models\FeedPurchase::class,
            'sales' => \App\Models\SalesTransaction::class,
            'livestock_sales' => \App\Models\LivestockSales::class,
        ];

        $modelClass = $modelMap[$transactionType] ?? null;

        Log::info('Model mapping result', [
            'transaction_type' => $transactionType,
            'model_class' => $modelClass,
            'model_exists' => class_exists($modelClass ?? '')
        ]);

        if (!$modelClass) {
            Log::warning('Unknown transaction type for expedition context', [
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId
            ]);
            return null;
        }

        $transaction = $modelClass::find($transactionId);

        Log::info('Transaction model lookup result', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'model_class' => $modelClass,
            'transaction_found' => $transaction !== null,
            'transaction_id_from_db' => $transaction ? $transaction->id : 'NULL',
            'transaction_status' => $transaction ? $transaction->status : 'NULL'
        ]);

        return $transaction;
    }

    /**
     * Prepare expedition data from normalized input
     * 
     * @param array $normalizedData - Normalized input data
     * @param mixed $transaction - Transaction model
     * @param string $transactionType - Transaction type
     * @param array $context - Additional context
     * @return array
     */
    private function prepareExpeditionDataFromInput(
        array $normalizedData,
        $transaction,
        string $transactionType,
        array $context = []
    ): array {
        Log::info('=== EXPEDITION SERVICE: prepareExpeditionDataFromInput START ===', [
            'normalized_data' => $normalizedData,
            'transaction_type' => $transactionType,
            'context' => $context
        ]);

        $expeditionData = [
            'expedition_id' => $normalizedData['expedition_id'],
            'expedition_cost' => $normalizedData['expedition_fee'],
            'transaction_type' => $transactionType,
            'shipping_date' => $context['shipping_date'] ?? now(),
            'status' => 'pending',
            'company_id' => $context['company_id'] ?? $transaction->company_id ?? null,
        ];

        // Add destination information if available (optional for draft status)
        if (isset($context['destination_address']) && !empty($context['destination_address'])) {
            $expeditionData['destination_address'] = $context['destination_address'];
        } else {
            // Set default destination address for draft status
            $expeditionData['destination_address'] = 'To be determined';
        }

        if (isset($context['destination_zone']) && !empty($context['destination_zone'])) {
            $expeditionData['destination_zone'] = $context['destination_zone'];
        } else {
            // Set default destination zone for draft status
            $expeditionData['destination_zone'] = 'To be determined';
        }

        // Calculate total weight from transaction items if available
        $totalWeight = $this->calculateTotalWeightFromTransaction($transaction, $transactionType, $context);
        if ($totalWeight > 0) {
            $expeditionData['total_weight'] = $totalWeight;
        } else {
            // Set default weight for draft status (can be updated later)
            $expeditionData['total_weight'] = 0.0;
        }

        // Add notes if available
        if (isset($context['notes']) && !empty($context['notes'])) {
            $expeditionData['notes'] = $context['notes'];
        } else {
            // Set default notes for draft status
            $expeditionData['notes'] = 'Draft expedition transaction - details to be filled later';
        }

        Log::info('=== EXPEDITION SERVICE: prepareExpeditionDataFromInput RESULT ===', [
            'expedition_data' => $expeditionData,
            'data_keys' => array_keys($expeditionData)
        ]);

        return $expeditionData;
    }

    /**
     * Prepare update data from normalized input
     * 
     * @param array $normalizedData - Normalized input data
     * @param array $context - Additional context
     * @return array
     */
    private function prepareUpdateDataFromInput(array $normalizedData, array $context = []): array
    {
        $updateData = [];

        if (isset($normalizedData['expedition_id'])) {
            $updateData['expedition_id'] = $normalizedData['expedition_id'];
        }

        if (isset($normalizedData['expedition_fee'])) {
            $updateData['expedition_cost'] = $normalizedData['expedition_fee'];
        }

        // Add other updatable fields from context
        if (isset($context['shipping_date'])) {
            $updateData['shipping_date'] = $context['shipping_date'];
        }

        if (isset($context['destination_address'])) {
            $updateData['destination_address'] = $context['destination_address'];
        }

        if (isset($context['destination_zone'])) {
            $updateData['destination_zone'] = $context['destination_zone'];
        }

        if (isset($context['notes'])) {
            $updateData['notes'] = $context['notes'];
        }

        return $updateData;
    }

    /**
     * Calculate total weight from transaction
     * 
     * @param mixed $transaction - Transaction model
     * @param string $transactionType - Transaction type
     * @param array $context - Additional context
     * @return float
     */
    private function calculateTotalWeightFromTransaction($transaction, string $transactionType, array $context = []): float
    {
        try {
            switch ($transactionType) {
                case 'livestock_purchase':
                    return $this->calculateLivestockPurchaseWeight($transaction, $context);

                case 'supply_purchase':
                    return $this->calculateSupplyPurchaseWeight($transaction, $context);

                case 'feed_purchase':
                    return $this->calculateFeedPurchaseWeight($transaction, $context);

                case 'sales':
                case 'livestock_sales':
                    return $this->calculateSalesWeight($transaction, $context);

                default:
                    // Try to get weight from context or transaction data
                    if (isset($context['total_weight'])) {
                        return (float) $context['total_weight'];
                    }

                    if (isset($transaction->total_weight)) {
                        return (float) $transaction->total_weight;
                    }

                    return 0.0;
            }
        } catch (\Exception $e) {
            Log::warning('Error calculating total weight from transaction', [
                'error' => $e->getMessage(),
                'transaction_type' => $transactionType,
                'transaction_id' => $transaction->id ?? 'unknown'
            ]);

            return 0.0;
        }
    }

    /**
     * Calculate weight for livestock purchase
     * 
     * @param mixed $transaction - LivestockPurchase model
     * @param array $context - Additional context
     * @return float
     */
    private function calculateLivestockPurchaseWeight($transaction, array $context = []): float
    {
        if (isset($context['total_weight'])) {
            return (float) $context['total_weight'];
        }

        if (isset($transaction->data['total_weight'])) {
            return (float) $transaction->data['total_weight'];
        }

        // Calculate from items if available
        if (isset($transaction->items) && is_array($transaction->items)) {
            $totalWeight = 0;
            foreach ($transaction->items as $item) {
                if (isset($item['weight_value']) && isset($item['quantity'])) {
                    if (isset($item['weight_type']) && $item['weight_type'] === 'per_unit') {
                        $totalWeight += $item['weight_value'] * $item['quantity'];
                    } else {
                        $totalWeight += $item['weight_value'];
                    }
                }
            }
            return $totalWeight;
        }

        return 0.0;
    }

    /**
     * Calculate weight for supply purchase
     * 
     * @param mixed $transaction - SupplyPurchase model
     * @param array $context - Additional context
     * @return float
     */
    private function calculateSupplyPurchaseWeight($transaction, array $context = []): float
    {
        if (isset($context['total_weight'])) {
            return (float) $context['total_weight'];
        }

        if (isset($transaction->total_weight)) {
            return (float) $transaction->total_weight;
        }

        // Calculate from batches if available
        if (isset($transaction->batches) && $transaction->batches->isNotEmpty()) {
            $totalWeight = 0;
            foreach ($transaction->batches as $batch) {
                if (isset($batch->weight)) {
                    $totalWeight += (float) $batch->weight;
                }
            }
            return $totalWeight;
        }

        return 0.0;
    }

    /**
     * Calculate weight for feed purchase
     * 
     * @param mixed $transaction - FeedPurchase model
     * @param array $context - Additional context
     * @return float
     */
    private function calculateFeedPurchaseWeight($transaction, array $context = []): float
    {
        if (isset($context['total_weight'])) {
            return (float) $context['total_weight'];
        }

        if (isset($transaction->total_weight)) {
            return (float) $transaction->total_weight;
        }

        // Calculate from items if available
        if (isset($transaction->items) && $transaction->items->isNotEmpty()) {
            $totalWeight = 0;
            foreach ($transaction->items as $item) {
                if (isset($item->weight)) {
                    $totalWeight += (float) $item->weight;
                }
            }
            return $totalWeight;
        }

        return 0.0;
    }

    /**
     * Calculate weight for sales
     * 
     * @param mixed $transaction - Sales model
     * @param array $context - Additional context
     * @return float
     */
    private function calculateSalesWeight($transaction, array $context = []): float
    {
        if (isset($context['total_weight'])) {
            return (float) $context['total_weight'];
        }

        if (isset($transaction->total_weight)) {
            return (float) $transaction->total_weight;
        }

        // Calculate from items if available
        if (isset($transaction->items) && $transaction->items->isNotEmpty()) {
            $totalWeight = 0;
            foreach ($transaction->items as $item) {
                if (isset($item->weight)) {
                    $totalWeight += (float) $item->weight;
                }
            }
            return $totalWeight;
        }

        return 0.0;
    }
}
