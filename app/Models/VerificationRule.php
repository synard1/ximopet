<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'requirements',
        'is_active'
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_active' => 'boolean'
    ];

    public function modelVerifications(): HasMany
    {
        return $this->hasMany(ModelVerification::class);
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VerificationLog::class);
    }
}
