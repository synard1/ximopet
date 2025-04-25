<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedMutation extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'date',
        'from_livestock_id',
        'to_livestock_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function fromLivestock()
    {
        return $this->belongsTo(Livestock::class,'from_livestock_id');
    }

    public function toLivestock()
    {
        return $this->belongsTo(Livestock::class,'to_livestock_id');
    }

    public function feedMutationDetails()
    {
        return $this->hasMany(FeedMutationItem::class);
    }
}
