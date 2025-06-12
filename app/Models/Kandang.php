<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\BaseModel;

class Kandang extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'coops';

    protected $fillable = [
        'farm_id',
        'code',
        'name',
        'capacity',
        'notes',
        'livestock_id',
        'quantity',
        'weight',
        'status',
        'created_by',
        'updated_by',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function livestocks()
    {
        return $this->hasMany(Livestock::class);
    }

    public function livestockBatch()
    {
        return $this->hasMany(LivestockBatch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
