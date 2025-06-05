<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SalesTransaction extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    // protected $table = 'sales_transactions';

    protected $fillable = [
        'id',
        'invoice_number',
        'transaction_date',
        'livestock_id',
        'customer_id',
        'expedition_id',
        'livestock_sale_id',
        'weight',
        'quantity',
        'price',
        'expedition_fee',
        'total_price',
        'payload',
        'notes',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'payload' => 'array',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function kandang(): BelongsTo
    {
        return $this->belongsTo(Coop::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
