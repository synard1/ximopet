<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyUsageDetail extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'supply_usage_id',
        'supply_id',
        'supply_stock_id',
        'quantity_taken',
        'created_by',
        'updated_by',
    ];

    public function supplyUsage()
    {
        return $this->belongsTo(SupplyUsage::class, 'supply_usage_id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function supplyStock()
    {
        return $this->belongsTo(SupplyStock::class, 'supply_stock_id');
    }
}
