<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Mutation extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'type',
        'from_livestock_id',
        'to_livestock_id',
        'from_farm_id',
        'from_kandang_id',
        'to_farm_id',
        'to_kandang_id',
        'date',
        'mutation_scope',
        'notes',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'payload' => 'array',
    ];

    public function mutationItems()
    {
        return $this->hasMany(MutationItem::class, 'mutation_id', 'id');
    }

    public function fromLivestock()
    {
        return $this->belongsTo(Livestock::class, 'from_livestock_id');
    }

    public function toLivestock()
    {
        return $this->belongsTo(Livestock::class, 'to_livestock_id');
    }

    public function fromFarm()
    {
        return $this->belongsTo(Farm::class, 'from_farm_id');
    }
    public function toFarm()
    {
        return $this->belongsTo(Farm::class, 'to_farm_id');
    }
}
