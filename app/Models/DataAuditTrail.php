<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DataAuditTrail extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'data_audit_trails';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'model_type',
        'model_id',
        'action',
        'before_data',
        'after_data',
        'user_id',
        'rollback_to_id',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rollbackTo()
    {
        return $this->belongsTo(DataAuditTrail::class, 'rollback_to_id');
    }
}
