<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class QaTodoComment extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'todo_id',
        'user_id',
        'comment',
        'attachments'
    ];

    protected $casts = [
        'attachments' => 'array'
    ];

    public function todo()
    {
        return $this->belongsTo(QaTodoList::class, 'todo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
