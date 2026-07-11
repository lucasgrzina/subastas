<?php

namespace App\Models;

use App\Traits\HasGuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    use HasGuid;

    public const UPDATED_AT = null;

    protected $fillable = [
        'lot_id',
        'user_id',
        'amount',
    ];

    protected $hidden = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Defense-in-depth: bids are append-only. This guard prevents instance-level
     * mutation ($bid->save()/update()/delete()). It does NOT prevent query-builder
     * mass operations or raw DB calls — see design "Decision: Bid immutability
     * enforcement" for the accepted scope of this guard.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Bids are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Bids are immutable and cannot be deleted.');
        });
    }
}
