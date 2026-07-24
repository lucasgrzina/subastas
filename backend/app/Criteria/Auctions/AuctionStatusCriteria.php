<?php

namespace App\Criteria\Auctions;

use App\Contracts\QueryCriterion;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bidder-visibility scope for Auction listings. A no-op for any caller
 * without the `bidder` role — admin/operador see every status.
 */
class AuctionStatusCriteria implements QueryCriterion
{
    public const BIDDER_VISIBLE_STATUSES = ['scheduled', 'live', 'closed'];

    public function __construct(private bool $isBidder) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->isBidder) {
            return $query;
        }

        return $query->whereIn('status', self::BIDDER_VISIBLE_STATUSES);
    }
}
