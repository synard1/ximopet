<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class LivestockPurchaseItem extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'livestock_purchase_id',
        'livestock_id',
        'tanggal',
        'livestock_strain_id',
        'livestock_strain_standard_id',
        'quantity',
        'price_value',
        'price_type',
        'price_per_unit',
        'price_total',
        'tax_amount',
        'tax_percentage',
        'weight_value',
        'weight_type',
        'weight_per_unit',
        'weight_total',
        'notes',
        'data',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'price_value' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'price_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'weight_value' => 'decimal:2',
        'weight_per_unit' => 'decimal:2',
        'weight_total' => 'decimal:2',
        'data' => 'array',
    ];

    public function livestockPurchase()
    {
        return $this->belongsTo(LivestockPurchase::class);
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    public function livestockStrain()
    {
        return $this->belongsTo(LivestockStrain::class, 'livestock_strain_id');
    }

    public function livestockBatches()
    {
        return $this->hasMany(LivestockBatch::class, 'livestock_purchase_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function batch()
    {
        return $this->hasOne(LivestockBatch::class, 'livestock_purchase_item_id');
    }

    protected function calculatePriceValues()
    {
        if ($this->price_type === 'per_unit') {
            $this->price_per_unit = $this->price_value;
            $this->price_total = $this->price_value * $this->quantity;
        } else {
            $this->price_total = $this->price_value;
            $this->price_per_unit = $this->quantity > 0 ? $this->price_value / $this->quantity : 0;
        }

        // Calculate tax if present
        if ($this->tax_percentage) {
            $this->tax_amount = ($this->price_total * $this->tax_percentage) / 100;
        }
    }

    protected function calculateWeightValues()
    {
        if ($this->weight_type === 'per_unit') {
            $this->weight_per_unit = $this->weight_value;
            $this->weight_total = $this->weight_value * $this->quantity;
        } else {
            $this->weight_total = $this->weight_value;
            $this->weight_per_unit = $this->quantity > 0 ? $this->weight_value / $this->quantity : 0;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->calculatePriceValues();
            $model->calculateWeightValues();
        });
    }
}
