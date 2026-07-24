<?php

namespace App\Services;

use App\Contracts\Repositories\AuctionRepositoryInterface;
use App\Criteria\Auctions\AuctionStatusCriteria;
use App\Criteria\Shared\DateRangeCriteria;
use App\Criteria\Shared\SearchCriteria;
use App\Models\Auction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AuctionService
{
    public function __construct(
        private AuctionRepositoryInterface $auctionRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 15, bool $isBidder = false): LengthAwarePaginator
    {
        return $this->auctionRepository->list(
            $perPage,
            new SearchCriteria($filters['search'] ?? null, 'title'),
            new DateRangeCriteria($filters['date_from'] ?? null, $filters['date_to'] ?? null, 'starts_at'),
            new AuctionStatusCriteria($isBidder),
        );
    }

    public function findByGuid(string $guid): ?Auction
    {
        return $this->auctionRepository->findByGuid($guid);
    }

    public function create(array $data): Auction
    {
        return $this->auctionRepository->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }

    /**
     * The status-mutation write path takes an exclusive lockForUpdate() on the
     * Auction row before writing status — this is the lock that placeBid()'s
     * sharedLock() re-read conflicts with, excluding a concurrent Auction
     * cancel/close from racing past an in-flight bid (see design "Decision:
     * Lot↔Auction status coupling").
     */
    public function update(Auction $auction, array $data): Auction
    {
        $auctionData = array_intersect_key($data, array_flip(['title', 'description', 'starts_at', 'ends_at', 'status']));

        if (! array_key_exists('status', $auctionData)) {
            return $this->auctionRepository->update($auction, $auctionData);
        }

        return DB::transaction(function () use ($auction, $auctionData) {
            Auction::where('id', $auction->id)->lockForUpdate()->first();

            return $this->auctionRepository->update($auction, $auctionData);
        });
    }

    public function destroy(Auction $auction): void
    {
        $this->auctionRepository->destroy($auction);
    }
}
