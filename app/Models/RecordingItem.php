<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class RecordingItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * This is a detail model that inherits company_id from Recording parent
     * No need to handle company_id separately
     */
    protected $requiresCompanyId = false;

    /**
     * Additional fillable fields specific to this model
     */
    protected $additionalFillable = [
        'id',
        'recording_id',
        'type',
        'item_id',
        'quantity',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',

    ];

    public function feed()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
