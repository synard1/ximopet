<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class LivestockPurchase extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'tanggal',
        'invoice_number',
        'vendor_id',
        'expedition_id',
        'expedition_fee',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'expedition_fee' => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(LivestockPurchaseItem::class, 'livestock_purchase_id', 'id');
    }

    public function livestocks()
    {
        return $this->hasMany(Livestock::class, 'purchase_id', 'id');
    }

    public function livestockBatches()
    {
        return $this->hasMany(LivestockBatch::class, 'source_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    public function expedition()
    {
        return $this->belongsTo(Partner::class, 'expedition_id');
    }

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
