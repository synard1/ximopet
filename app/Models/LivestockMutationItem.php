<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * LivestockMutationItem Model
 * 
 * Handles individual items within a livestock mutation
 * Each mutation can have multiple items representing different batches or portions
 * 
 * @property string $id
 * @property string $livestock_mutation_id
 * @property string|null $batch_id
 * @property int $quantity
 * @property float|null $weight
 * @property string|null $keterangan
 * @property array|null $payload
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class LivestockMutationItem extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'livestock_mutation_items';

    protected $fillable = [
        'livestock_mutation_id',
        'batch_id',
        'quantity',
        'weight',
        'keterangan',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'decimal:2',
        'payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set created_by from authenticated user
        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
        });

        // Update parent mutation total when item is saved/deleted
        static::saved(function ($model) {
            if ($model->livestockMutation) {
                $model->livestockMutation->calculateTotalQuantity();
            }
        });

        static::deleted(function ($model) {
            if ($model->livestockMutation) {
                $model->livestockMutation->calculateTotalQuantity();
            }
        });
    }

    /**
     * Get the parent livestock mutation
     */
    public function livestockMutation(): BelongsTo
    {
        return $this->belongsTo(LivestockMutation::class, 'livestock_mutation_id');
    }

    /**
     * Get the batch (if applicable)
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LivestockBatch::class, 'batch_id');
    }

    /**
     * Get the creator user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater user
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get batch information from payload or relationship
     */
    public function getBatchInfoAttribute(): ?array
    {
        if ($this->batch) {
            return [
                'batch_id' => $this->batch->id,
                'batch_name' => $this->batch->name,
                'batch_start_date' => $this->batch->start_date,
                'age_days' => $this->batch->start_date ? now()->diffInDays($this->batch->start_date) : null,
            ];
        }

        // Fallback to payload data
        $payload = $this->payload ?? [];
        if (isset($payload['batch_info'])) {
            return $payload['batch_info'];
        }

        return null;
    }

    /**
     * Get item summary for display
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'keterangan' => $this->keterangan,
            'batch_info' => $this->batch_info,
            'mutation_id' => $this->livestock_mutation_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Scope for specific batch
     */
    public function scopeForBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope for minimum quantity
     */
    public function scopeMinQuantity($query, int $minQuantity)
    {
        return $query->where('quantity', '>=', $minQuantity);
    }

    /**
     * Create mutation item with batch validation
     */
    public static function createMutationItem(array $data): self
    {
        // Validate required fields
        $required = ['livestock_mutation_id', 'quantity'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Required field missing: {$field}");
            }
        }

        // Validate quantity
        if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            throw new \Exception("Quantity must be a positive number");
        }

        // Validate batch if provided
        if (isset($data['batch_id'])) {
            $batch = LivestockBatch::find($data['batch_id']);
            if (!$batch) {
                throw new \Exception("Batch not found: {$data['batch_id']}");
            }

            // Add batch info to payload
            $data['payload'] = array_merge($data['payload'] ?? [], [
                'batch_info' => [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'batch_start_date' => $batch->start_date,
                    'initial_quantity' => $batch->initial_quantity,
                ]
            ]);
        }

        return self::create($data);
    }
}
