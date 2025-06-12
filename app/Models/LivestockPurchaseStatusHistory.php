<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class LivestockPurchaseStatusHistory extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'livestock_purchase_id',
        'status_from',
        'status_to',
        'notes',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function livestockPurchase()
    {
        return $this->belongsTo(LivestockPurchase::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
