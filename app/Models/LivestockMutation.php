<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LivestockMutation extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'tanggal',
        'from_livestock_id',
        'to_livestock_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function fromLivestock()
    {
        return $this->belongsTo(Livestock::class,'from_livestock_id');
    }
    public function toLivestock()
    {
        return $this->belongsTo(Livestock::class,'to_livestock_id');
    }

    public function mutationItem()
    {
        return $this->hasMany(LivestockMutationItem::class);
    }
}
