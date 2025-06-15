<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationLog extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'action',
        'notes',
        'changes',
        'context'
    ];

    protected $casts = [
        'changes' => 'array',
        'context' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
