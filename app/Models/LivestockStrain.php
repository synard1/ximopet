<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LivestockStrain extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'data',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function breedStandards()
    {
        return $this->hasMany(LivestockStrainStandard::class);
    }

    public function livestock()
    {
        return $this->hasMany(Livestock::class);
    }

    /**
     * Get default sales unit_id from strain data (conversion_units or unit_id)
     */
    public function getDefaultSalesUnitId(): ?string
    {
        $data = $this->data ?? [];
        // Cek conversion_units
        if (!empty($data['conversion_units']) && is_array($data['conversion_units'])) {
            foreach ($data['conversion_units'] as $unit) {
                if (!empty($unit['is_default_sale']) && $unit['is_default_sale']) {
                    return $unit['unit_id'] ?? null;
                }
            }
        }
        // Fallback ke unit_id utama
        if (!empty($data['unit_id'])) {
            return $data['unit_id'];
        }
        return null;
    }
}
