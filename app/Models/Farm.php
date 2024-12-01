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

    protected $table = 'master_farms';

    protected $fillable = [
        'id',
        'kode',
        'nama',
        'alamat',
        'telp',
        'pic',
        'telp_pic',
        'status',
        'created_by',
        'updated_by',
    ];

    public function kandangs()
    {
        return $this->hasMany(Kandang::class);
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
}
