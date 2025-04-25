<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StandarBobot extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'standar_bobots';

    protected $fillable = [
        'id',
        'strain',
        'standar_data', // JSON column untuk menyimpan semua standar
        'keterangan',
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
}
