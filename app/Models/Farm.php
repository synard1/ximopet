<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;


class Farm extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'code',
        'name',
        'contact_person',
        'phone_number',
        'address',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function kandangs()
    {
        return $this->hasMany(Coop::class);
    }

    public function coops()
    {
        return $this->hasMany(Coop::class);
    }
    public function storages()
    {
        return $this->hasMany(InventoryLocation::class);
    }

    public function farmOperators()
    {
        return $this->hasMany(FarmOperator::class);
    }

    public function operators()
    {
        return $this->belongsToMany(User::class, 'farm_operators');
    }

    public function kelompokTernak()
    {
        return $this->hasMany(KelompokTernak::class);
    }

    public function livestock()
    {
        return $this->hasMany(Livestock::class);
    }

    public function inventoryLocations()
    {
        return $this->hasMany(InventoryLocation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
