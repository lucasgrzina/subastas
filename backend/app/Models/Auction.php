<?php

namespace App\Models;

use App\Traits\HasGuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auction extends Model
{
    use HasGuid, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $hidden = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
