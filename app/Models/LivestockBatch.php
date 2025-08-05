<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\LivestockPurchaseItem;

class LivestockBatch extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'company_id',
        'livestock_id',
        'source_type',
        'source_id',
        'farm_id',
        'coop_id',
        'livestock_strain_id',
        'livestock_strain_standard_id',
        'name',
        'livestock_strain_name',
        'start_date',
        'end_date',
        'initial_quantity',
        'quantity_depletion',
        'quantity_sales',
        'quantity_mutated',
        'quantity_available',
        'initial_weight', // konversi nilai dari weight / initial_quantity jika weight_type = total
        'weight',
        'weight_type',
        'weight_per_unit',
        'weight_total',
        'price_per_unit',
        'price_total',
        'price_value',
        'price_type',
        'data',
        'status',
        'notes',
        'number',
        'number_full',
        'created_by',
        'updated_by',
        'livestock_purchase_item_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price_per_unit' => 'decimal:2',
        'price_total' => 'decimal:2',
        'price_value' => 'decimal:2',
        'data' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->data) && $model->livestock_breed_standard_id) {
                $breedStandard = LivestockStrainStandard::find($model->livestock_breed_standard_id);
                if ($breedStandard) {
                    $model->data = [
                        'standar_data' => $breedStandard->standar_data,
                        'current_data' => [
                            'umur' => 0,
                            'bobot' => $model->berat_awal,
                            'feed_intake' => 0,
                            'fcr' => 0,
                            'mortality' => 0,
                            'last_update' => now()->toDateTimeString()
                        ],
                        'history' => []
                    ];
                }
            }

            // Set initial quantity_available
            $model->quantity_available = $model->initial_quantity ?? 0;
        });

        static::updating(function ($model) {
            // Auto-calculate quantity_available when any quantity field changes
            if ($model->isDirty(['initial_quantity', 'quantity_depletion', 'quantity_sales', 'quantity_mutated'])) {
                $model->quantity_available = $model->calculateQuantityAvailable();
            }
        });

        static::saved(function ($model) {
            // Ensure quantity_available is always up-to-date after save
            if ($model->quantity_available !== $model->calculateQuantityAvailable()) {
                $model->updateQuietly(['quantity_available' => $model->calculateQuantityAvailable()]);
            }
        });
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class);
    }

    public function livestockStrain()
    {
        return $this->belongsTo(LivestockStrain::class);
    }

    public function livestockStrainStandard()
    {
        return $this->belongsTo(LivestockStrainStandard::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(LivestockPurchaseItem::class, 'livestock_purchase_item_id');
    }

    /**
     * Get source purchase if batch is from purchase
     */
    public function sourcePurchase()
    {
        return $this->belongsTo(LivestockPurchase::class, 'source_id')
            ->when($this->source_type === 'purchase');
    }

    /**
     * Get source mutation if batch is from mutation
     */
    public function sourceMutation()
    {
        return $this->belongsTo(LivestockMutation::class, 'source_id')
            ->when($this->source_type === 'mutation');
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class, 'livestock_id', 'livestock_id');
    }

    public function depletions()
    {
        return $this->hasMany(LivestockDepletion::class, 'livestock_id', 'livestock_id');
    }

    public function updateData($newData)
    {
        $currentData = $this->data ?? [];
        $currentData['current_data'] = array_merge($currentData['current_data'] ?? [], $newData);
        $currentData['current_data']['last_update'] = now()->toDateTimeString();

        // Add to history
        if (!isset($currentData['history'])) {
            $currentData['history'] = [];
        }
        $currentData['history'][] = [
            'data' => $newData,
            'timestamp' => now()->toDateTimeString()
        ];

        $this->data = $currentData;
        return $this->save();
    }

    public function getCurrentData()
    {
        return $this->data['current_data'] ?? null;
    }

    public function getStandardData()
    {
        return $this->data['standar_data'] ?? null;
    }

    public function getHistory()
    {
        return $this->data['history'] ?? [];
    }

    /**
     * Calculate the available quantity for this batch
     * Formula: initial_quantity - quantity_depletion - quantity_sales - quantity_mutated
     * 
     * @return int
     */
    public function calculateQuantityAvailable(): int
    {
        $initialQuantity = $this->initial_quantity ?? 0;
        $depletionQuantity = $this->quantity_depletion ?? 0;
        $salesQuantity = $this->quantity_sales ?? 0;
        $mutatedQuantity = $this->quantity_mutated ?? 0;

        $available = $initialQuantity - $depletionQuantity - $salesQuantity - $mutatedQuantity;

        // Ensure quantity doesn't go negative
        return max(0, $available);
    }

    /**
     * Get the current available quantity (cached value)
     * 
     * @return int
     */
    public function getQuantityAvailable(): int
    {
        return $this->quantity_available ?? $this->calculateQuantityAvailable();
    }

    /**
     * Force recalculate and update quantity_available
     * 
     * @return bool
     */
    public function recalculateQuantityAvailable(): bool
    {
        $newQuantity = $this->calculateQuantityAvailable();

        if ($this->quantity_available !== $newQuantity) {
            $this->quantity_available = $newQuantity;
            return $this->save();
        }

        return true;
    }

    /**
     * Check if batch has available quantity
     * 
     * @return bool
     */
    public function hasAvailableQuantity(): bool
    {
        return $this->getQuantityAvailable() > 0;
    }

    /**
     * Get batch status based on available quantity
     * 
     * @return string
     */
    public function getAvailabilityStatus(): string
    {
        $available = $this->getQuantityAvailable();

        if ($available <= 0) {
            return 'exhausted';
        } elseif ($available <= ($this->initial_quantity * 0.1)) { // Less than 10%
            return 'low';
        } elseif ($available <= ($this->initial_quantity * 0.5)) { // Less than 50%
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Get percentage of available quantity
     * 
     * @return float
     */
    public function getAvailabilityPercentage(): float
    {
        if ($this->initial_quantity <= 0) {
            return 0;
        }

        return round(($this->getQuantityAvailable() / $this->initial_quantity) * 100, 2);
    }

    /**
     * Get detailed quantity breakdown
     * 
     * @return array
     */
    public function getQuantityBreakdown(): array
    {
        return [
            'initial_quantity' => $this->initial_quantity ?? 0,
            'quantity_depletion' => $this->quantity_depletion ?? 0,
            'quantity_sales' => $this->quantity_sales ?? 0,
            'quantity_mutated' => $this->quantity_mutated ?? 0,
            'quantity_available' => $this->getQuantityAvailable(),
            'availability_percentage' => $this->getAvailabilityPercentage(),
            'availability_status' => $this->getAvailabilityStatus(),
            'last_calculated' => now()->toDateTimeString()
        ];
    }

    // ========================================
    // TRACKING DATA HELPER METHODS
    // ========================================

    /**
     * Get source object based on source_type
     * 
     * @return mixed
     */
    public function getSourceObject()
    {
        switch ($this->source_type) {
            case 'purchase':
                return $this->belongsTo(LivestockPurchase::class, 'source_id')->first();
            case 'mutation':
                return $this->belongsTo(LivestockMutation::class, 'source_id')->first();
            default:
                return null;
        }
    }

    /**
     * Get purchase information if batch is from purchase
     * 
     * @return array|null
     */
    public function getPurchaseInfo(): ?array
    {
        if ($this->source_type !== 'purchase') {
            return null;
        }

        $purchase = $this->belongsTo(LivestockPurchase::class, 'source_id')->first();
        if (!$purchase) {
            return null;
        }

        return [
            'purchase' => $purchase,
            'purchase_item' => $this->purchaseItem,
            'invoice_number' => $purchase->invoice_number,
            'purchase_date' => $purchase->tanggal,
            'supplier' => $purchase->supplier,
            'expedition' => $purchase->expedition,
            'expedition_fee' => $purchase->expedition_fee,
            'batch_name' => $purchase->batch_name,
            'status' => $purchase->status
        ];
    }

    /**
     * Get mutation information if batch is from mutation
     * 
     * @return array|null
     */
    public function getMutationInfo(): ?array
    {
        if ($this->source_type !== 'mutation') {
            return null;
        }

        $mutation = $this->belongsTo(LivestockMutation::class, 'source_id')->first();
        if (!$mutation) {
            return null;
        }

        return [
            'mutation' => $mutation,
            'mutation_items' => $mutation->items ?? [],
            'date' => $mutation->tanggal,
            'direction' => $mutation->direction,
            'type' => $mutation->jenis,
            'source_livestock' => $mutation->sourceLivestock,
            'destination_livestock' => $mutation->destinationLivestock,
            'total_quantity' => $mutation->jumlah,
            'notes' => $mutation->keterangan
        ];
    }

    /**
     * Get source information summary
     * 
     * @return array
     */
    public function getSourceInfo(): array
    {
        $info = [
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'livestock_purchase_item_id' => $this->livestock_purchase_item_id,
            'is_from_purchase' => $this->source_type === 'purchase',
            'is_from_mutation' => $this->source_type === 'mutation'
        ];

        if ($this->source_type === 'purchase') {
            $info['purchase_info'] = $this->getPurchaseInfo();
        } elseif ($this->source_type === 'mutation') {
            $info['mutation_info'] = $this->getMutationInfo();
        }

        return $info;
    }

    /**
     * Check if batch is from purchase
     * 
     * @return bool
     */
    public function isFromPurchase(): bool
    {
        return $this->source_type === 'purchase';
    }

    /**
     * Check if batch is from mutation
     * 
     * @return bool
     */
    public function isFromMutation(): bool
    {
        return $this->source_type === 'mutation';
    }

    /**
     * Get purchase ID if available
     * 
     * @return string|null
     */
    public function getPurchaseId(): ?string
    {
        if ($this->source_type === 'purchase') {
            return $this->source_id;
        }
        return null;
    }

    /**
     * Get mutation ID if available
     * 
     * @return string|null
     */
    public function getMutationId(): ?string
    {
        if ($this->source_type === 'mutation') {
            return $this->source_id;
        }
        return null;
    }

    /**
     * Get invoice number if batch is from purchase
     * 
     * @return string|null
     */
    public function getInvoiceNumber(): ?string
    {
        $purchaseInfo = $this->getPurchaseInfo();
        return $purchaseInfo['invoice_number'] ?? null;
    }

    /**
     * Get supplier information if batch is from purchase
     * 
     * @return array|null
     */
    public function getSupplierInfo(): ?array
    {
        $purchaseInfo = $this->getPurchaseInfo();
        if (!$purchaseInfo || !$purchaseInfo['supplier']) {
            return null;
        }

        return [
            'id' => $purchaseInfo['supplier']->id,
            'name' => $purchaseInfo['supplier']->name,
            'code' => $purchaseInfo['supplier']->code ?? null,
            'type' => $purchaseInfo['supplier']->type ?? null
        ];
    }

    /**
     * Get expedition information if batch is from purchase
     * 
     * @return array|null
     */
    public function getExpeditionInfo(): ?array
    {
        $purchaseInfo = $this->getPurchaseInfo();
        if (!$purchaseInfo || !$purchaseInfo['expedition']) {
            return null;
        }

        return [
            'id' => $purchaseInfo['expedition']->id,
            'name' => $purchaseInfo['expedition']->name,
            'code' => $purchaseInfo['expedition']->code ?? null,
            'fee' => $purchaseInfo['expedition_fee']
        ];
    }

    /**
     * Get source livestock information if batch is from mutation
     * 
     * @return array|null
     */
    public function getSourceLivestockInfo(): ?array
    {
        $mutationInfo = $this->getMutationInfo();
        if (!$mutationInfo || !$mutationInfo['source_livestock']) {
            return null;
        }

        $sourceLivestock = $mutationInfo['source_livestock'];
        return [
            'id' => $sourceLivestock->id,
            'name' => $sourceLivestock->name,
            'farm' => $sourceLivestock->farm->name ?? 'Unknown',
            'coop' => $sourceLivestock->coop->name ?? 'Unknown',
            'status' => $sourceLivestock->status
        ];
    }

    /**
     * Get destination livestock information if batch is from mutation
     * 
     * @return array|null
     */
    public function getDestinationLivestockInfo(): ?array
    {
        $mutationInfo = $this->getMutationInfo();
        if (!$mutationInfo || !$mutationInfo['destination_livestock']) {
            return null;
        }

        $destinationLivestock = $mutationInfo['destination_livestock'];
        return [
            'id' => $destinationLivestock->id,
            'name' => $destinationLivestock->name,
            'farm' => $destinationLivestock->farm->name ?? 'Unknown',
            'coop' => $destinationLivestock->coop->name ?? 'Unknown',
            'status' => $destinationLivestock->status
        ];
    }

    /**
     * Get complete tracking summary
     * 
     * @return array
     */
    public function getTrackingSummary(): array
    {
        return [
            'batch_id' => $this->id,
            'batch_name' => $this->name,
            'livestock_id' => $this->livestock_id,
            'livestock_name' => $this->livestock->name ?? 'Unknown',
            'source_info' => $this->getSourceInfo(),
            'quantity_info' => $this->getQuantityBreakdown(),
            'strain_info' => [
                'strain_id' => $this->livestock_strain_id,
                'strain_name' => $this->livestock_strain_name,
                'strain_standard_id' => $this->livestock_strain_standard_id
            ],
            'location_info' => [
                'farm_id' => $this->farm_id,
                'farm_name' => $this->farm->name ?? 'Unknown',
                'coop_id' => $this->coop_id,
                'coop_name' => $this->coop->name ?? 'Unknown'
            ],
            'timing_info' => [
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ]
        ];
    }
}
