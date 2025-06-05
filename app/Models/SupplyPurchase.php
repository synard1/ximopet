<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyPurchase extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'farm_id',
        'supply_purchase_batch_id',
        'supply_id',
        'unit_id',
        'quantity',
        'converted_unit',
        'converted_quantity',
        'price_per_unit',
        'price_per_converted_unit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function supplyItem()
    {
        return $this->belongsTo(Supply::class, 'supply_id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class, 'supply_id');
    }

    public function feedItem()
    {
        return $this->belongsTo(Item::class, 'feed_id');
    }

    public function batch()
    {
        return $this->belongsTo(SupplyPurchaseBatch::class, 'supply_purchase_batch_id');
    }

    // FeedPurchase.php
    public function supplyStocks()
    {
        return $this->hasMany(SupplyStock::class, 'supply_purchase_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    public function convertedUnit()
    {
        return $this->belongsTo(Unit::class, 'converted_unit', 'id');
    }

    /**
     * Hitung konversi quantity ke satuan terkecil
     * @return float
     */
    public function calculateConvertedQuantity()
    {
        // Jika tidak ada converted_unit, return quantity apa adanya
        if (!$this->converted_unit || !$this->unit_id) {
            return (float) $this->quantity;
        }
        // Ambil rasio konversi dari unit ke converted_unit
        $conversion = \App\Models\UnitConversion::where('unit_id', $this->unit_id)
            ->where('conversion_unit_id', $this->converted_unit)
            ->first();
        if ($conversion && $conversion->conversion_value) {
            return (float) $this->quantity * (float) $conversion->conversion_value;
        }
        // Jika tidak ada data konversi, fallback ke quantity
        return (float) $this->quantity;
    }
}
