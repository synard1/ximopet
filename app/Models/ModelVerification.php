<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelVerification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'model_type',
        'status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'verified_data',
        'required_documents',
        'verified_documents',
        'is_locked'
    ];

    protected $casts = [
        'verified_data' => 'array',
        'required_documents' => 'array',
        'verified_documents' => 'array',
        'is_locked' => 'boolean',
        'verified_at' => 'datetime'
    ];

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VerificationLog::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function canBeModified(): bool
    {
        return !$this->is_locked;
    }
}
