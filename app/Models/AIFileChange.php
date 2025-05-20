<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIFileChange extends Model
{

    protected $table = "ai_file_changes";

    protected $fillable = [
        'file_path',
        'original_content',
        'modified_content',
        'changed_at',
        'change_type',
        'description'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
