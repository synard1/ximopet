<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\LivestockPurchaseItem;

class LivestockBatch extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'livestock_id',
        'source_type',
        'source_id',
        'farm_id',
        'coop_id',
        'livestock_strain_id',
        'livestock_strain_standard_id',
        'name',
        'livestock_strain_name',
        'start_date',
        'end_date',
        'initial_quantity',
        'quantity_depletion',
        'quantity_sales',
        'quantity_mutated',
        'initial_weight', // konversi nilai dari weight / initial_quantity jika weight_type = total
        'weight',
        'weight_type',
        'weight_per_unit',
        'weight_total',
        'data',
        'status',
        'notes',
        'created_by',
        'updated_by',
        'livestock_purchase_item_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'data' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->data) && $model->livestock_breed_standard_id) {
                $breedStandard = LivestockStrainStandard::find($model->livestock_breed_standard_id);
                if ($breedStandard) {
                    $model->data = [
                        'standar_data' => $breedStandard->standar_data,
                        'current_data' => [
                            'umur' => 0,
                            'bobot' => $model->berat_awal,
                            'feed_intake' => 0,
                            'fcr' => 0,
                            'mortality' => 0,
                            'last_update' => now()->toDateTimeString()
                        ],
                        'history' => []
                    ];
                }
            }
        });
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class);
    }

    public function livestockStrain()
    {
        return $this->belongsTo(LivestockStrain::class);
    }

    public function livestockStrainStandard()
    {
        return $this->belongsTo(LivestockStrainStandard::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(LivestockPurchaseItem::class, 'livestock_purchase_item_id');
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class, 'livestock_id', 'livestock_id');
    }

    public function depletions()
    {
        return $this->hasMany(LivestockDepletion::class, 'livestock_id', 'livestock_id');
    }

    public function updateData($newData)
    {
        $currentData = $this->data ?? [];
        $currentData['current_data'] = array_merge($currentData['current_data'] ?? [], $newData);
        $currentData['current_data']['last_update'] = now()->toDateTimeString();

        // Add to history
        if (!isset($currentData['history'])) {
            $currentData['history'] = [];
        }
        $currentData['history'][] = [
            'data' => $newData,
            'timestamp' => now()->toDateTimeString()
        ];

        $this->data = $currentData;
        return $this->save();
    }

    public function getCurrentData()
    {
        return $this->data['current_data'] ?? null;
    }

    public function getStandardData()
    {
        return $this->data['standar_data'] ?? null;
    }

    public function getHistory()
    {
        return $this->data['history'] ?? [];
    }
}
