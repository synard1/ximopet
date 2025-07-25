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
}
