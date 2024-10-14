<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KelompokTernak extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'kelompok_ternak';

    protected $fillable = [
        'transaksi_id',
        'name',
        'breed',
        'start_date',
        'estimated_end_date',
        'initial_quantity',
        'current_quantity',
        'death_quantity',
        'slaughter_quantity',
        'sold_quantity',
        'remaining_quantity',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'estimated_end_date' => 'datetime',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id','id');
    }
    
    
}
