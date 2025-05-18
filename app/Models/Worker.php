<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Worker extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    public function batchWorkers()
    {
        return $this->hasMany(BatchWorker::class);
    }

    public function activeBatches()
    {
        return $this->batchWorkers()
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->whereHas('batch', function ($query) {
                $query->where('status', 'active');
            });
    }
}
