<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LoginLog extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_status',
        'login_type',
        'login_details'
    ];

    protected $casts = [
        'login_details' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
