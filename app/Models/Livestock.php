<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LivestockLockCheck;
use Illuminate\Support\Facades\Log;

class Livestock extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use LivestockLockCheck;
    /**
     * NOTE UNTUK AI DAN DEVELOPER:
     * 
     * - Satu entitas Livestock dapat memiliki lebih dari satu LivestockBatch (relasi one-to-many).
     * - Setiap LivestockBatch digunakan untuk menyimpan detail data batch/periode dari Livestock tersebut.
     * - Untuk setiap periode atau perubahan batch, WAJIB dibuat LivestockBatch baru agar histori dan detail data tetap terjaga.
     * - Semua proses mutasi, pencatatan, dan pelacakan stok harus mengacu pada LivestockBatch, BUKAN hanya Livestock saja.
     * - Pastikan setiap operasi yang melibatkan detail periode, stok, atau mutasi livestock SELALU menggunakan relasi LivestockBatch.
     * 
     * Dokumentasi dan diagram relasi dapat dilihat di docs/models/livestock-batch-relation.md
     * 
     * [Last updated: 2025-06-24 10:00 WIB]
     */

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_IN_USE = 'in_use';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_READY = 'ready'; // New status for when quantity stock is ready to be used
    const STATUS_ACTIVE = 'active'; // New status for when quantity is fully used

    // Status Labels
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_IN_USE => 'In Use',
        self::STATUS_ARRIVED => 'Arrived',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_READY => 'Ready',
        self::STATUS_ACTIVE => 'Active' // Label for the new status
    ];

    protected $table = 'livestocks';

    protected $fillable = [
        'company_id',
        'farm_id',
        'coop_id',
        'name',
        'start_date',
        'end_date',
        'initial_quantity',
        'quantity_depletion',
        'quantity_sales',
        'quantity_mutated_out',
        'quantity_mutated_in',
        'initial_weight',
        'price',
        'notes',
        'status',
        'data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'data' => 'array'
    ];

    // Helper Methods
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isInTransit()
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isInUse()
    {
        return $this->status === self::STATUS_IN_USE;
    }

    public function isArrived()
    {
        return $this->status === self::STATUS_ARRIVED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isReady()
    {
        return $this->status === self::STATUS_READY;
    }

    public function isActive() // New method to check if the status is exhausted
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canBeEdited()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING
        ]);
    }

    public function canBeCancelled()
    {
        return !in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED
        ]);
    }

    public function getStatusLabel()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function batches()
    {
        return $this->hasMany(LivestockBatch::class);
    }

    public function standardWeight()
    {
        return $this->belongsTo(StandarBobot::class, 'standar_bobot_id', 'id');
    }

    public function livestockDepletion()
    {
        return $this->hasMany(LivestockDepletion::class, 'livestock_id', 'id');
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class, 'livestock_id', 'id');
    }

    public function currentLivestock()
    {
        return $this->hasOne(CurrentLivestock::class, 'livestock_id', 'id');
    }

    public function livestockPurchaseItems()
    {
        return $this->hasMany(LivestockPurchaseItem::class, 'livestock_id', 'id');
    }

    /**
     * Get outgoing mutations (this livestock as source)
     */
    public function outgoingMutations()
    {
        return $this->hasMany(LivestockMutation::class, 'source_livestock_id', 'id');
    }

    /**
     * Get incoming mutations (this livestock as destination)
     */
    public function incomingMutations()
    {
        return $this->hasMany(LivestockMutation::class, 'destination_livestock_id', 'id');
    }

    /**
     * Get all mutations (both incoming and outgoing)
     */
    public function allMutations()
    {
        return LivestockMutation::where('source_livestock_id', $this->id)
            ->orWhere('destination_livestock_id', $this->id);
    }

    /**
     * Get total outgoing mutation quantity
     */
    public function getTotalOutgoingMutations(): int
    {
        return $this->outgoingMutations()
            ->where('direction', 'out')
            ->sum('jumlah');
    }

    /**
     * Get total incoming mutation quantity
     */
    public function getTotalIncomingMutations(): int
    {
        return $this->incomingMutations()
            ->where('direction', 'in')
            ->sum('jumlah');
    }

    /**
     * Get net mutation (incoming - outgoing)
     */
    public function getNetMutations(): int
    {
        return $this->getTotalIncomingMutations() - $this->getTotalOutgoingMutations();
    }

    /**
     * Check if livestock has any mutations
     */
    public function hasMutations(): bool
    {
        return $this->allMutations()->exists();
    }

    /**
     * Get mutation summary
     */
    public function getMutationSummary(): array
    {
        return [
            'outgoing' => $this->getTotalOutgoingMutations(),
            'incoming' => $this->getTotalIncomingMutations(),
            'net' => $this->getNetMutations(),
            'has_mutations' => $this->hasMutations(),
            'total_records' => $this->allMutations()->count()
        ];
    }

    public function isLocked()
    {
        return $this->status === 'locked';
    }

    // Helper method to get total current population
    public function getTotalPopulation()
    {
        return $this->batches()
            ->where('status', 'active')
            ->sum('populasi_awal');
    }

    // Helper method to get total current weight
    public function getTotalWeight()
    {
        return $this->batches()
            ->where('status', 'active')
            ->sum('berat_awal');
    }

    /**
     * Get default recording method configuration
     */
    public static function getDefaultRecordingConfig(): array
    {
        return \App\Config\CompanyConfig::getDefaultLivestockConfig()['recording_method'] ?? [
            'type' => 'total',
            'allow_multiple_batches' => false,
            'batch_settings' => [
                'enabled' => false,
                'auto_generate_batch' => false,
                'require_batch_number' => false,
                'batch_details' => [
                    'weight' => false,
                    'age' => false,
                    'breed' => false,
                    'health_status' => false,
                    'notes' => false,
                ]
            ],
            'total_settings' => [
                'enabled' => true,
                'track_total_only' => true,
                'total_details' => [
                    'total_count' => true,
                    'average_weight' => true,
                    'total_weight' => true,
                ]
            ]
        ];
    }

    /**
     * Get recording method for this livestock
     */
    public function getRecordingMethod(): string
    {
        $batchCount = $this->batches()->where('status', 'active')->count();
        return $batchCount > 1 ? 'batch' : 'total';
    }

    /**
     * Get recommended recording method based on batch count and configuration
     */
    public function getRecommendedRecordingMethod(): array
    {
        $batchCount = $this->getActiveBatchesCount();
        $config = $this->getRecordingMethodConfig();

        if ($batchCount === 1) {
            return [
                'method' => 'total',
                'reason' => 'Single batch livestock - use total recording for simplicity',
                'depletion_method' => null,
                'batch_selection' => 'not_applicable'
            ];
        }

        if ($batchCount > 1) {
            $depletionMethod = $config['batch_settings']['depletion_method'] ?? 'fifo';
            return [
                'method' => 'batch',
                'reason' => 'Multiple batches detected - use batch recording for better tracking',
                'depletion_method' => $depletionMethod,
                'batch_selection' => $this->getBatchSelectionMethod($depletionMethod)
            ];
        }

        return [
            'method' => 'total',
            'reason' => 'No active batches - use total recording',
            'depletion_method' => null,
            'batch_selection' => 'not_applicable'
        ];
    }

    /**
     * Get batch selection method based on depletion method
     */
    public function getBatchSelectionMethod(string $depletionMethod): string
    {
        switch ($depletionMethod) {
            case 'fifo':
                return 'oldest_first';
            case 'lifo':
                return 'newest_first';
            case 'manual':
                return 'user_choice';
            default:
                return 'oldest_first';
        }
    }

    /**
     * Get available batches for recording based on depletion method
     */
    public function getAvailableBatchesForRecording(string $depletionMethod = 'fifo'): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->batches()
            ->where('status', 'active')
            ->whereRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) > 0');

        switch ($depletionMethod) {
            case 'fifo':
                return $query->orderBy('start_date', 'asc')->get();
            case 'lifo':
                return $query->orderBy('start_date', 'desc')->get();
            case 'manual':
                return $query->orderBy('start_date', 'asc')->get();
            default:
                return $query->orderBy('start_date', 'asc')->get();
        }
    }

    /**
     * Get next batch to use based on depletion method
     */
    public function getNextBatchForDepletion(string $depletionMethod = 'fifo'): ?LivestockBatch
    {
        $availableBatches = $this->getAvailableBatchesForRecording($depletionMethod);
        return $availableBatches->first();
    }

    /**
     * Check if batch recording is required
     */
    public function requiresBatchRecording(): bool
    {
        return $this->getActiveBatchesCount() > 1;
    }

    /**
     * Get batch recording configuration
     */
    public function getBatchRecordingConfig(): array
    {
        $config = $this->getRecordingMethodConfig();
        return $config['batch_settings'] ?? [];
    }

    /**
     * Get depletion method configuration
     */
    public function getDepletionMethodConfig(string $method): array
    {
        $config = $this->getBatchRecordingConfig();
        $depletionMethods = $config['depletion_methods'] ?? [];

        if ($method) {
            return $depletionMethods[$method] ?? [];
        }

        return $depletionMethods;
    }

    /**
     * Validate batch recording requirements
     */
    public function validateBatchRecording(): array
    {
        $result = [
            'valid' => true,
            'method' => 'total',
            'depletion_method' => null,
            'available_batches' => [],
            'message' => '',
            'warnings' => []
        ];

        $batchCount = $this->getActiveBatchesCount();
        $config = $this->getRecordingMethodConfig();

        if ($batchCount === 0) {
            $result['valid'] = false;
            $result['message'] = 'No active batches found for this livestock.';
            return $result;
        }

        if ($batchCount === 1) {
            $result['method'] = 'total';
            $result['message'] = 'Single batch livestock - using total recording method.';
            return $result;
        }

        // Multiple batches - use batch recording
        $result['method'] = 'batch';
        $depletionMethod = $config['batch_settings']['depletion_method'] ?? 'fifo';
        $result['depletion_method'] = $depletionMethod;

        // Get available batches
        $availableBatches = $this->getAvailableBatchesForRecording($depletionMethod);
        $result['available_batches'] = $availableBatches->map(function ($batch) {
            return [
                'id' => $batch->id,
                'name' => $batch->name,
                'start_date' => $batch->start_date,
                'available_quantity' => $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated,
                'age_days' => now()->diffInDays($batch->start_date),
            ];
        })->toArray();

        if ($availableBatches->isEmpty()) {
            $result['valid'] = false;
            $result['message'] = 'No batches with available quantity for recording.';
            return $result;
        }

        // Check depletion method configuration
        $depletionConfig = $this->getDepletionMethodConfig($depletionMethod);
        if (!($depletionConfig['enabled'] ?? false)) {
            $result['warnings'][] = "Depletion method '{$depletionMethod}' is not enabled in configuration.";
        }

        $result['message'] = "Multiple batches detected - using {$depletionMethod} depletion method.";
        return $result;
    }

    /**
     * Check if livestock has multiple batches
     */
    public function hasMultipleBatches(): bool
    {
        return $this->batches()->where('status', 'active')->count() > 1;
    }

    /**
     * Get active batches count
     */
    public function getActiveBatchesCount(): int
    {
        return $this->batches()->where('status', 'active')->count();
    }

    /**
     * Get recording method configuration for this livestock's company
     */
    public function getRecordingMethodConfig(): array
    {
        // Try to get company through farm relationship first
        $company = null;
        if ($this->farm && method_exists($this->farm, 'company')) {
            $company = $this->farm->company;
        }

        // If no company through farm, try to get current user's company
        if (!$company) {
            $mapping = \App\Models\CompanyUser::getUserMapping();
            $company = $mapping->company ?? null;
        }

        if (!$company) {
            Log::info('No company found, using default recording config', [
                'livestock_id' => $this->id,
                'config_source' => 'default_fallback'
            ]);
            return self::getDefaultRecordingConfig();
        }

        $companyConfig = $company->getLivestockRecordingConfig();
        if (!empty($companyConfig)) {
            Log::info('Using company recording config', [
                'livestock_id' => $this->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'config_source' => 'company_database'
            ]);
            return $companyConfig;
        }

        Log::info('Company config empty, using default recording config', [
            'livestock_id' => $this->id,
            'company_id' => $company->id,
            'config_source' => 'default_fallback'
        ]);
        return self::getDefaultRecordingConfig();
    }

    /**
     * Check if recording method is configured for this livestock's company
     */
    public function isRecordingMethodConfigured(): bool
    {
        // Try to get company through farm relationship first
        $company = null;
        if ($this->farm && method_exists($this->farm, 'company')) {
            $company = $this->farm->company;
        }

        // If no company through farm, try to get current user's company
        if (!$company) {
            $mapping = \App\Models\CompanyUser::getUserMapping();
            $company = $mapping->company ?? null;
        }

        if (!$company) {
            return false;
        }

        $config = $company->getLivestockRecordingConfig();
        return !empty($config);
    }

    /**
     * Validate recording method for this livestock
     */
    public function validateRecordingMethod(): array
    {
        $result = [
            'valid' => true,
            'method' => 'total',
            'message' => '',
            'config' => null
        ];

        // Try to get company through farm relationship first
        $company = null;
        if ($this->farm && method_exists($this->farm, 'company')) {
            $company = $this->farm->company;
        }

        // If no company through farm, try to get current user's company
        if (!$company) {
            $mapping = \App\Models\CompanyUser::getUserMapping();
            $company = $mapping->company ?? null;
        }

        // Check if company exists
        if (!$company) {
            $result['valid'] = false;
            $result['message'] = 'Company configuration not found for this livestock.';
            return $result;
        }

        $config = $company->getLivestockRecordingConfig();
        Log::info('Using company recording config for validation', [
            'livestock_id' => $this->id,
            'company_id' => $company->id,
            'config_source' => 'company_database'
        ]);

        // Check if recording method is configured
        if (empty($config)) {
            $result['valid'] = false;
            $result['message'] = 'Recording method configuration is not set. Please configure it in company settings first.';
            return $result;
        }

        $result['config'] = $config;
        $recordingType = $config['type'] ?? 'total';
        $allowsMultipleBatches = $config['allow_multiple_batches'] ?? false;
        $hasMultipleBatches = $this->hasMultipleBatches();

        // Determine recording method
        if ($hasMultipleBatches) {
            if ($recordingType === 'batch' && !$allowsMultipleBatches) {
                $result['valid'] = false;
                $result['message'] = 'Multiple batches are not allowed in the current recording method configuration.';
                return $result;
            }
            $result['method'] = 'batch';
        } else {
            $result['method'] = 'total';
        }

        return $result;
    }

    /**
     * Get depletion method from recording config
     */
    public function getDepletionMethod(): string
    {
        $config = $this->getRecordingMethodConfig();
        return $config['batch_settings']['depletion_method'] ?? 'fifo';
    }

    /**
     * Get batch settings from recording config
     */
    public function getBatchSettings(): array
    {
        $config = $this->getRecordingMethodConfig();
        return $config['batch_settings'] ?? [];
    }

    /**
     * Get total settings from recording config
     */
    public function getTotalSettings(): array
    {
        $config = $this->getRecordingMethodConfig();
        return $config['total_settings'] ?? [];
    }

    /**
     * Check if batch recording is enabled
     */
    public function isBatchRecordingEnabled(): bool
    {
        $config = $this->getRecordingMethodConfig();
        return ($config['type'] ?? 'total') === 'batch' &&
            ($config['batch_settings']['enabled'] ?? false);
    }

    /**
     * Check if total recording is enabled
     */
    public function isTotalRecordingEnabled(): bool
    {
        $config = $this->getRecordingMethodConfig();
        return ($config['type'] ?? 'total') === 'total' &&
            ($config['total_settings']['enabled'] ?? false);
    }

    /**
     * Get company info from data column
     */
    public function getCompanyInfoFromData(): ?array
    {
        return $this->data['company_config'] ?? null;
    }

    /**
     * Get purchase info from data column
     */
    public function getPurchaseInfoFromData(): ?array
    {
        return $this->data['purchase_info'] ?? null;
    }

    /**
     * Get initial data from data column
     */
    public function getInitialDataFromData(): ?array
    {
        return $this->data['initial_data'] ?? null;
    }

    /**
     * Update recording config in data column
     */
    public function updateRecordingConfig(array $newConfig): bool
    {
        $currentData = $this->data ?? [];
        $currentData['recording_config'] = $newConfig;
        $currentData['updated_at'] = now()->toDateTimeString();

        $this->data = $currentData;
        return $this->save();
    }

    /**
     * Add or update data in livestock data column
     */
    public function updateDataColumn(string $key, $value): bool
    {
        $currentData = $this->data ?? [];
        $currentData[$key] = $value;
        $currentData['updated_at'] = now()->toDateTimeString();

        $this->data = $currentData;
        return $this->save();
    }

    /**
     * Get data from livestock data column
     */
    public function getDataColumn(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Get total feed consumed from data column
     *
     * @return float
     */
    public function getTotalFeedConsumed(): float
    {
        return floatval($this->getDataColumn('feed_stats.total_consumed', 0));
    }

    /**
     * Get total feed cost from data column
     *
     * @return float
     */
    public function getTotalFeedCost(): float
    {
        return floatval($this->getDataColumn('feed_stats.total_cost', 0));
    }

    /**
     * Increment feed consumption in data column
     *
     * @param float $quantity
     * @param float $cost
     * @return bool
     */
    public function incrementFeedConsumption(float $quantity, float $cost = 0): bool
    {
        $oldStats = $this->getFeedStats();
        $currentData = $this->data ?? [];

        // Initialize feed_stats if not exists
        if (!isset($currentData['feed_stats'])) {
            $currentData['feed_stats'] = [
                'total_consumed' => 0,
                'total_cost' => 0,
                'last_updated' => now()->toISOString(),
                'usage_count' => 0
            ];
        }

        // Increment values
        $currentData['feed_stats']['total_consumed'] = floatval($currentData['feed_stats']['total_consumed'] ?? 0) + $quantity;
        $currentData['feed_stats']['total_cost'] = floatval($currentData['feed_stats']['total_cost'] ?? 0) + $cost;
        $currentData['feed_stats']['usage_count'] = intval($currentData['feed_stats']['usage_count'] ?? 0) + 1;
        $currentData['feed_stats']['last_updated'] = now()->toISOString();

        $result = $this->update(['data' => $currentData]);

        if ($result) {
            Log::info('📈 Incremented feed consumption', [
                'livestock_id' => $this->id,
                'added_quantity' => $quantity,
                'added_cost' => $cost,
                'old_stats' => $oldStats,
                'new_stats' => $this->getFeedStats()
            ]);
        }

        return $result;
    }

    /**
     * Decrement feed consumption in data column
     *
     * @param float $quantity
     * @param float $cost
     * @return bool
     */
    public function decrementFeedConsumption(float $quantity, float $cost = 0): bool
    {
        $oldStats = $this->getFeedStats();
        $currentData = $this->data ?? [];

        // Initialize feed_stats if not exists
        if (!isset($currentData['feed_stats'])) {
            $currentData['feed_stats'] = [
                'total_consumed' => 0,
                'total_cost' => 0,
                'last_updated' => now()->toISOString(),
                'usage_count' => 0
            ];
        }

        // Decrement values (ensure not negative)
        $currentData['feed_stats']['total_consumed'] = max(0, floatval($currentData['feed_stats']['total_consumed'] ?? 0) - $quantity);
        $currentData['feed_stats']['total_cost'] = max(0, floatval($currentData['feed_stats']['total_cost'] ?? 0) - $cost);
        $currentData['feed_stats']['last_updated'] = now()->toISOString();

        $result = $this->update(['data' => $currentData]);

        if ($result) {
            Log::info('📉 Decremented feed consumption', [
                'livestock_id' => $this->id,
                'subtracted_quantity' => $quantity,
                'subtracted_cost' => $cost,
                'old_stats' => $oldStats,
                'new_stats' => $this->getFeedStats()
            ]);
        }

        return $result;
    }

    /**
     * Set feed consumption values in data column
     *
     * @param float $totalConsumed
     * @param float $totalCost
     * @return bool
     */
    public function setFeedConsumption(float $totalConsumed, float $totalCost): bool
    {
        $currentData = $this->data ?? [];

        // Initialize or update feed_stats
        $currentData['feed_stats'] = [
            'total_consumed' => max(0, $totalConsumed),
            'total_cost' => max(0, $totalCost),
            'last_updated' => now()->toISOString(),
            'usage_count' => $currentData['feed_stats']['usage_count'] ?? 0
        ];

        return $this->update(['data' => $currentData]);
    }

    /**
     * Get feed consumption statistics
     *
     * @return array
     */
    public function getFeedStats(): array
    {
        $feedStats = $this->getDataColumn('feed_stats', []);

        return [
            'total_consumed' => floatval($feedStats['total_consumed'] ?? 0),
            'total_cost' => floatval($feedStats['total_cost'] ?? 0),
            'usage_count' => intval($feedStats['usage_count'] ?? 0),
            'last_updated' => $feedStats['last_updated'] ?? null,
            'average_cost_per_unit' => $this->getAverageFeedCostPerUnit()
        ];
    }

    /**
     * Get average feed cost per unit
     *
     * @return float
     */
    public function getAverageFeedCostPerUnit(): float
    {
        $totalConsumed = $this->getTotalFeedConsumed();
        $totalCost = $this->getTotalFeedCost();

        return $totalConsumed > 0 ? $totalCost / $totalConsumed : 0;
    }

    /**
     * Check if manual depletion is configured for this livestock
     *
     * @return bool
     */
    public function isManualDepletionEnabled(): bool
    {
        $config = $this->getDataColumn('config');

        if (!$config || !isset($config['depletion_method'])) {
            return false;
        }

        return $config['depletion_method'] === 'manual';
    }

    /**
     * Check if FIFO feed usage is configured for this livestock
     *
     * @return bool
     */
    public function isFifoFeedUsageEnabled(): bool
    {
        $config = $this->getDataColumn('config');

        if (!$config || !isset($config['feed_usage_method'])) {
            return false;
        }

        return $config['feed_usage_method'] === 'fifo';
    }

    /**
     * Check if FIFO depletion is configured for this livestock
     *
     * @return bool
     */
    public function isManualMutationEnabled(): bool
    {
        $config = $this->getDataColumn('config');

        if (!$config || !isset($config['mutation_method'])) {
            return false;
        }

        return $config['mutation_method'] === 'manual';
    }

    /**
     * Check if FIFO depletion is configured for this livestock
     *
     * @return bool
     */
    public function isFifoDepletionEnabled(): bool
    {
        $config = $this->getDataColumn('config');

        if (!$config || !isset($config['depletion_method'])) {
            return false;
        }

        return $config['depletion_method'] === 'fifo';
    }

    /**
     * Check if manual feed usage is configured for this livestock
     *
     * @return bool
     */
    public function isManualFeedUsageEnabled(): bool
    {
        $config = $this->getDataColumn('config');

        if (!$config || !isset($config['feed_usage_method'])) {
            return false;
        }

        return $config['feed_usage_method'] === 'manual';
    }

    /**
     * Check if FIFO mutation is configured for this livestock
     *
     * @return bool
     */
    public function isFifoMutationEnabled(): bool
    {
        $config = $this->getDataColumn('config');

        if (!$config || !isset($config['mutation_method'])) {
            return false;
        }

        return $config['mutation_method'] === 'fifo';
    }

    /**
     * Get the configured recording method for this livestock
     *
     * @return string|null
     */
    public function getConfiguredRecordingMethod(): ?string
    {
        $config = $this->getDataColumn('config');
        return $config['recording_method'] ?? null;
    }

    /**
     * Get the configured depletion method for this livestock
     *
     * @return string|null
     */
    public function getConfiguredDepletionMethod(): ?string
    {
        $config = $this->getDataColumn('config');
        return $config['depletion_method'] ?? null;
    }

    /**
     * Get the configured mutation method for this livestock
     *
     * @return string|null
     */
    public function getConfiguredMutationMethod(): ?string
    {
        $config = $this->getDataColumn('config');
        return $config['mutation_method'] ?? null;
    }

    /**
     * Get the configured feed usage method for this livestock
     *
     * @return string|null
     */
    public function getConfiguredFeedUsageMethod(): ?string
    {
        $config = $this->getDataColumn('config');
        return $config['feed_usage_method'] ?? null;
    }

    /**
     * Check if livestock has any configuration saved
     *
     * @return bool
     */
    public function hasConfiguration(): bool
    {
        $config = $this->getDataColumn('config');
        return !empty($config);
    }

    /**
     * Get full configuration for this livestock
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->getDataColumn('config', []);
    }

    /**
     * Validate feed_stats accuracy against actual feed usage records
     *
     * @return array
     */
    public function validateFeedStats(): array
    {
        $currentStats = $this->getFeedStats();

        // Calculate actual totals from feed usage records
        $feedUsages = \App\Models\FeedUsage::where('livestock_id', $this->id)->with('details')->get();

        $actualQuantity = 0;
        $actualCost = 0;
        $actualCount = $feedUsages->count();

        foreach ($feedUsages as $usage) {
            $usageQuantity = floatval($usage->total_quantity ?? 0);
            $usageCost = floatval($usage->total_cost ?? 0);

            // If main fields are empty, calculate from details
            if ($usageQuantity == 0 || $usageCost == 0) {
                $detailQuantity = 0;
                $detailCost = 0;

                foreach ($usage->details as $detail) {
                    $detailQuantity += floatval($detail->quantity_taken);
                    $detailCost += floatval($detail->metadata['cost_calculation']['total_cost'] ?? 0);
                }

                $usageQuantity = $usageQuantity ?: $detailQuantity;
                $usageCost = $usageCost ?: $detailCost;
            }

            $actualQuantity += $usageQuantity;
            $actualCost += $usageCost;
        }

        $quantityDiff = $actualQuantity - $currentStats['total_consumed'];
        $costDiff = $actualCost - $currentStats['total_cost'];
        $countDiff = $actualCount - $currentStats['usage_count'];

        return [
            'is_valid' => abs($quantityDiff) < 0.01 && abs($costDiff) < 0.01 && $countDiff == 0,
            'current_stats' => $currentStats,
            'actual_stats' => [
                'total_consumed' => $actualQuantity,
                'total_cost' => $actualCost,
                'usage_count' => $actualCount
            ],
            'discrepancies' => [
                'quantity_diff' => $quantityDiff,
                'cost_diff' => $costDiff,
                'count_diff' => $countDiff
            ]
        ];
    }

    /**
     * Auto-fix feed_stats based on actual feed usage data
     *
     * @return bool
     */
    public function fixFeedStats(): bool
    {
        $validation = $this->validateFeedStats();

        if ($validation['is_valid']) {
            return true; // No fix needed
        }

        $actualStats = $validation['actual_stats'];

        // Fix the feed_stats
        $result = $this->setFeedConsumption($actualStats['total_consumed'], $actualStats['total_cost']);

        if ($result) {
            // Update usage count manually
            $currentData = $this->data ?? [];
            $currentData['feed_stats']['usage_count'] = $actualStats['usage_count'];
            $currentData['feed_stats']['last_updated'] = now()->toISOString();
            $this->update(['data' => $currentData]);

            Log::info('🔧 Auto-fixed feed_stats discrepancy', [
                'livestock_id' => $this->id,
                'old_stats' => $validation['current_stats'],
                'new_stats' => $actualStats,
                'discrepancies' => $validation['discrepancies']
            ]);
        }

        return $result;
    }
}
