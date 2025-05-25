<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use App\Models\User;

class QaTodoList extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'module_name',
        'feature_name',
        'description',
        'environment',
        'priority',
        'status',
        'assigned_to',
        'reviewed_by',
        'created_by',
        'url',
        'due_date',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklists()
    {
        return $this->hasMany(QaChecklist::class, 'todo_list_id');
    }

    public function comments()
    {
        return $this->hasMany(QaTodoComment::class, 'todo_id');
    }
}
