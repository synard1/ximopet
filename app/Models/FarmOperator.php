<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmOperator extends Model
{
    // Specify the table name if it's not the conventional 'farm_operators'
    protected $table = 'farm_operators'; 

    // If you don't have 'created_at' and 'updated_at' columns in your pivot table
    public $timestamps = false; 

    // Define the fillable attributes (if you have any additional columns besides the foreign keys)
    protected $fillable = [
        'farm_id',
        'user_id',
        // ... other fillable attributes if needed
    ];

    // Define relationships with Farm and User models
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
