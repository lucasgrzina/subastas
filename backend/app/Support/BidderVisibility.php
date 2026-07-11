<?php

namespace App\Support;

use App\Criteria\Auctions\AuctionStatusCriteria;
use App\Criteria\Lots\LotStatusCriteria;
use App\Models\Auction;
use App\Models\Lot;

/**
 * Shared single-resource bidder-visibility checks, reused by
 * AuctionController::show(), LotController::show()/bids(), and
 * CreateBidRequest::authorize() so the status sets never drift apart
 * from the list-scoping Criteria (see design "Read gate — bidder").
 */
class BidderVisibility
{
    public static function auctionVisible(Auction $auction): bool
    {
        return in_array($auction->status, AuctionStatusCriteria::BIDDER_VISIBLE_STATUSES, true);
    }

    /**
     * A Lot is visible to a bidder only when its own status is in the
     * bidder-visible set AND its parent Auction is also bidder-visible —
     * a Lot can never be more visible than the Auction it belongs to.
     */
    public static function lotVisible(Lot $lot): bool
    {
        if (! in_array($lot->status, LotStatusCriteria::BIDDER_VISIBLE_STATUSES, true)) {
            return false;
        }

        return $lot->auction !== null && self::auctionVisible($lot->auction);
    }
}
