<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyMutation extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'date',
        'from_farm_id',
        'to_livestock_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function fromFarm()
    {
        return $this->belongsTo(Farm::class,'from_farm_id');
    }

    public function toFarm()
    {
        return $this->belongsTo(Farm::class,'to_farm_id');
    }

    public function supplyMutationDetails()
    {
        return $this->hasMany(SupplyMutationItem::class);
    }
}
