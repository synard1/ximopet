<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppConfig extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['company_id', 'config','created_by','updated_by'];

    protected $casts = [
        'config' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
