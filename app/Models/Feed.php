<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Feed extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'code',
        'name',
        'payload',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => ucfirst($value),
            set: fn(string $value) => strtolower($value),
        );
    }

    public function conversionUnits()
    {
        return $this->hasMany(UnitConversion::class, 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
