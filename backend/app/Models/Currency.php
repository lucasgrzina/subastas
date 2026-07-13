<?php

namespace App\Models;

use App\Traits\HasGuid;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasGuid;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
    ];

    protected $hidden = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
