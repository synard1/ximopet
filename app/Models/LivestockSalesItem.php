<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Represents a livestock sales item.
 */
class LivestockSalesItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The fillable fields for the model.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'company_id',
        'livestock_sales_id',
        'livestock_id',
        'livestock_batch_id',
        'date',
        'quantity',
        'weight',
        'unit_id',
        'total_weight',
        'price',
        'total_price',
        'data',
        'created_by',
        'updated_by',
    ];

    /**
     * The data types for the fields.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
        'data' => 'array',
    ];

    /**
     * The relationship to the livestock sales model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function livestockSale()
    {
        return $this->belongsTo(LivestockSales::class, 'livestock_sales_id');
    }
}
