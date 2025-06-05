<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LivestockStrainStandard extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'livestock_strain_id',
        'livestock_strain_name',
        'standar_data', // JSON column untuk menyimpan semua standar
        'description',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'standar_data' => 'array'
    ];

    // Format standar data yang diharapkan
    public static $defaultStandarFormat = [
        'umur' => 0,
        'bobot' => [
            'min' => 0,
            'max' => 0,
            'target' => 0
        ],
        'feed_intake' => [
            'min' => 0,
            'max' => 0,
            'target' => 0
        ],
        'fcr' => [
            'min' => 0,
            'max' => 0,
            'target' => 0
        ]
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }


    public function strain()
    {
        return $this->belongsTo(LivestockStrain::class, 'strain_id');
    }

    public function livestocks()
    {
        return $this->hasMany(Livestock::class, 'livestock_strain_standard_id');
    }
}
