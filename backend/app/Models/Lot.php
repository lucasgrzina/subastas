<?php

namespace App\Models;

use App\Traits\HasGuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lot extends Model
{
    use HasGuid, SoftDeletes;

    protected $fillable = [
        'auction_id',
        'lot_number',
        'title',
        'starting_price',
        'bid_increment',
        'reserve_price',
        'status',
        'current_price',
        'current_bid_id',
        'current_winner_user_id',
    ];

    protected $hidden = ['id'];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'current_price' => 'decimal:2',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'lot_product')->withPivot('quantity');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function currentBid(): BelongsTo
    {
        return $this->belongsTo(Bid::class, 'current_bid_id');
    }

    public function currentWinner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_winner_user_id');
    }

    /**
     * 'single' iff exactly one attached product at pivot quantity 1, else
     * 'bundle'. Guarded against an unloaded `products` relation to avoid a
     * surprise lazy-load/N+1 — the two real read paths (findByGuid/list)
     * always eager-load it, so the fallback below is defensive only.
     */
    public function getTypeAttribute(): string
    {
        if (! $this->relationLoaded('products')) {
            return 'bundle';
        }

        $products = $this->products;

        if ($products->count() === 1 && (int) $products->first()->pivot->quantity === 1) {
            return 'single';
        }

        return 'bundle';
    }

    /**
     * Explicit `title` override wins; otherwise derives from the products
     * composition. Never null — always a display-ready string.
     */
    public function getDisplayTitleAttribute(): string
    {
        if ($this->title !== null && $this->title !== '') {
            return $this->title;
        }

        return $this->deriveTitle();
    }

    /**
     * Same relationLoaded guard as getTypeAttribute() — see note there.
     */
    protected function deriveTitle(): string
    {
        if (! $this->relationLoaded('products')) {
            return "Lote {$this->lot_number}";
        }

        $products = $this->products;

        if ($products->count() === 1 && (int) $products->first()->pivot->quantity === 1) {
            return $products->first()->title;
        }

        return "Lote {$this->lot_number} — {$products->count()} productos";
    }
}
