<?php

namespace App\Services;

use App\Contracts\Repositories\LotRepositoryInterface;
use App\Criteria\Lots\LotStatusCriteria;
use App\Criteria\Shared\SearchCriteria;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotService
{
    /** Statuses only ever reachable through close() — see design "Decision: Terminal status". */
    private const TERMINAL_STATUSES = ['sold', 'unsold'];

    public function __construct(
        private LotRepositoryInterface $lotRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 15, bool $isBidder = false): LengthAwarePaginator
    {
        return $this->lotRepository->list(
            $perPage,
            new SearchCriteria($filters['search'] ?? null, 'lot_number'),
            new LotStatusCriteria($isBidder),
        );
    }

    public function findByGuid(string $guid): ?Lot
    {
        return $this->lotRepository->findByGuid($guid);
    }

    public function create(array $data): Lot
    {
        $auction = Auction::where('guid', $data['auction_guid'])->firstOrFail();
        $status = $data['status'] ?? 'scheduled';

        if (in_array($status, self::TERMINAL_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'El estado vendido/no vendido solo puede establecerse cerrando el lote.',
            ]);
        }

        if ($status === 'open' && $auction->status !== 'live') {
            throw ValidationException::withMessages([
                'status' => 'El lote solo puede abrirse cuando la subasta está en vivo.',
            ]);
        }

        return DB::transaction(function () use ($data, $auction, $status) {
            $lot = $this->lotRepository->create([
                'auction_id' => $auction->id,
                'lot_number' => $data['lot_number'],
                'title' => $data['title'] ?? null,
                'starting_price' => $data['starting_price'],
                'bid_increment' => $data['bid_increment'],
                'reserve_price' => $data['reserve_price'] ?? null,
                'status' => $status,
            ]);

            $this->attachProducts($lot, $data['products']);

            return $lot->fresh(['auction', 'products', 'currentBid.user', 'currentWinner']);
        });
    }

    /**
     * Validates every referenced product is `published` and that at least one
     * product is attached (422 otherwise), then syncs the lot_product pivot
     * with quantities.
     */
    private function attachProducts(Lot $lot, array $products): void
    {
        if (empty($products)) {
            throw ValidationException::withMessages([
                'products' => 'El lote debe tener al menos un producto.',
            ]);
        }

        $sync = [];

        foreach ($products as $item) {
            $product = Product::where('guid', $item['product_guid'])->first();

            if (! $product || $product->status !== 'published') {
                throw ValidationException::withMessages([
                    'products' => 'Todos los productos del lote deben estar publicados.',
                ]);
            }

            $sync[$product->id] = ['quantity' => $item['quantity'] ?? 1];
        }

        $lot->products()->sync($sync);
    }

    /**
     * Generic update path. Mirrors ProductService::update()'s field passthrough,
     * BUT (unlike Product, which has no status-transition semantics) it guards
     * `status`: the terminal statuses sold/unsold are only reachable through
     * close(), and a Lot already in a terminal status may never transition
     * again through this path (reverse-transition guard, tasks obs#41 3.8).
     */
    public function update(Lot $lot, array $data): Lot
    {
        if (array_key_exists('status', $data)) {
            $newStatus = $data['status'];

            if (in_array($lot->status, self::TERMINAL_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede modificar el estado de un lote ya finalizado (vendido/no vendido).',
                ]);
            }

            if (in_array($newStatus, self::TERMINAL_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'El estado vendido/no vendido solo puede establecerse cerrando el lote.',
                ]);
            }

            if ($newStatus === 'open') {
                $auction = $lot->auction ?? Auction::find($lot->auction_id);

                if (! $auction || $auction->status !== 'live') {
                    throw ValidationException::withMessages([
                        'status' => 'El lote solo puede abrirse cuando la subasta está en vivo.',
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($lot, $data) {
            $lotData = array_intersect_key($data, array_flip([
                'lot_number', 'title', 'starting_price', 'bid_increment', 'reserve_price', 'status',
            ]));

            if (! empty($lotData)) {
                $this->lotRepository->update($lot, $lotData);
            }

            if (isset($data['products'])) {
                $this->attachProducts($lot, $data['products']);
            }

            return $lot->fresh(['auction', 'products', 'currentBid.user', 'currentWinner']);
        });
    }

    public function destroy(Lot $lot): void
    {
        $this->lotRepository->destroy($lot);
    }

    /**
     * Transactional, race-safe bid placement — see design "Concurrency —
     * placeBid" for the full lock/compare rationale. $amount is a decimal-safe
     * string end-to-end, never a native float.
     */
    public function placeBid(Lot $lot, User $user, string $amount): Bid
    {
        return DB::transaction(function () use ($lot, $user, $amount) {
            /** @var Lot $locked */
            $locked = Lot::where('id', $lot->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'open') {
                throw ValidationException::withMessages([
                    'amount' => 'El lote no está abierto para recibir ofertas.',
                ]);
            }

            $lockedAuction = Auction::where('id', $locked->auction_id)->sharedLock()->first();

            if (! $lockedAuction || $lockedAuction->status !== 'live') {
                throw ValidationException::withMessages([
                    'amount' => 'La subasta de este lote no está en vivo.',
                ]);
            }

            $isFirstBid = $locked->current_price === null;

            $floor = $isFirstBid
                ? (string) $locked->starting_price
                : bcadd((string) $locked->current_price, (string) $locked->bid_increment, 2);

            if (bccomp($amount, $floor, 2) < 0) {
                // The floor differs by case: the opening bid must meet the base
                // price, while later bids must clear current price + increment.
                // A single generic message that only names "precio actual" is
                // confusing on a fresh lot where there is no current price yet.
                $message = $isFirstBid
                    ? "La primera oferta debe ser mayor o igual al precio base (\${$floor})."
                    : "La oferta debe ser mayor o igual al mínimo (\${$floor}): precio actual más el incremento.";

                throw ValidationException::withMessages([
                    'amount' => $message,
                ]);
            }

            $bid = Bid::create([
                'lot_id' => $locked->id,
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            $locked->update([
                'current_price' => $amount,
                'current_bid_id' => $bid->id,
                'current_winner_user_id' => $user->id,
            ]);

            return $bid;
        });
    }

    public function bidHistory(Lot $lot, int $perPage = 15): LengthAwarePaginator
    {
        return $lot->bids()
            ->with('user')
            ->orderBy('amount', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Sole path to sold/unsold. Idempotent when the lot is not `open`. Uses
     * bccomp() for the reserve comparison — never native `>=` — since both
     * operands are decimal(12,2)-cast strings (see design "Decision: close()").
     */
    public function close(Lot $lot): Lot
    {
        return DB::transaction(function () use ($lot) {
            /** @var Lot $locked */
            $locked = Lot::where('id', $lot->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'open') {
                return $locked ?? $lot;
            }

            $reserveMet = $locked->current_price !== null
                && ($locked->reserve_price === null || bccomp((string) $locked->current_price, (string) $locked->reserve_price, 2) >= 0);

            $locked->update(['status' => $reserveMet ? 'sold' : 'unsold']);

            return $locked->fresh(['auction', 'products', 'currentBid.user', 'currentWinner']);
        });
    }
}
