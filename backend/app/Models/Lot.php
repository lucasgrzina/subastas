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
}
