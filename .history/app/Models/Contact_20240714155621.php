<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Contact extends Model
{
    use HasFactor;

    protected $fillable = [
        'id',
        'kode',
        'nama',
        'email',
        'alamat',
    ];
}
