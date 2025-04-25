<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyUsageDetail extends Model
{
    use HasFactory;

    public function usage()
    {
        return $this->belongsTo(SupplyUsage::class,'supply_usage_id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }
}
