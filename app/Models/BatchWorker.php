<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchWorker extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'livestock_id', // Mengganti 'batch_id' dengan 'livestock_id'
        'worker_id',
        'start_date',
        'end_date',
        'role',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function livestock() // Mengganti 'batch()' dengan 'livestock()'
    {
        return $this->belongsTo(Livestock::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
