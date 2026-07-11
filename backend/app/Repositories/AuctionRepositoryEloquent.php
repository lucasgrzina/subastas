<?php

namespace App\Repositories;

use App\Contracts\QueryCriterion;
use App\Contracts\Repositories\AuctionRepositoryInterface;
use App\Models\Auction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class AuctionRepositoryEloquent extends BaseRepositoryEloquent implements AuctionRepositoryInterface
{
    protected function model(): string
    {
        return Auction::class;
    }

    public function findByGuid(string $guid): ?Auction
    {
        /** @var ?Auction */
        return $this->newQuery()->where('guid', $guid)->first();
    }

    public function list(int $perPage, QueryCriterion ...$criteria): LengthAwarePaginator
    {
        $query = $this->buildQuery(...$criteria);

        return $query->orderBy('starts_at', 'desc')->paginate($perPage);
    }

    public function create(array $data): Auction
    {
        /** @var Auction */
        return parent::create($data);
    }

    public function update(Model $model, array $data): Auction
    {
        $model->fill($data);
        $model->save();

        /** @var Auction */
        return $model->fresh();
    }

    public function destroy(Model $model): ?bool
    {
        return $model->delete();
    }
}
