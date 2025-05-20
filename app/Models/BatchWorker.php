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
        'farm_id',
        'start_date',
        'end_date',
        'role',
        'notes',
        'status',
        'notes',
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

    /**
     * Get the worker assigned to the batch
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Get the farm where the worker is assigned
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Get the user who created the record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
