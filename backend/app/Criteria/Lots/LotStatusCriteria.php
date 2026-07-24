<?php

namespace App\Criteria\Lots;

use App\Contracts\QueryCriterion;
use App\Criteria\Auctions\AuctionStatusCriteria;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bidder-visibility scope for Lot listings. A Lot's own status set has no
 * draft/cancelled equivalent (per tasks obs#41 1.2a, the 4-value set is
 * scheduled/open/sold/unsold — all visible on their own), BUT a Lot is
 * ALSO hidden whenever its parent Auction's status is outside the Auction
 * bidder-visible set — a Lot can never be more visible than its Auction.
 * A no-op for any caller without the `bidder` role.
 */
class LotStatusCriteria implements QueryCriterion
{
    public const BIDDER_VISIBLE_STATUSES = ['scheduled', 'open', 'sold', 'unsold'];

    public function __construct(private bool $isBidder) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->isBidder) {
            return $query;
        }

        return $query->whereIn('status', self::BIDDER_VISIBLE_STATUSES)
            ->whereHas('auction', fn (Builder $q) => $q->whereIn('status', AuctionStatusCriteria::BIDDER_VISIBLE_STATUSES));
    }
}
