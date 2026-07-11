<?php

namespace App\Contracts\Repositories;

use App\Contracts\QueryCriterion;
use App\Models\Auction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface AuctionRepositoryInterface
{
    public function findByGuid(string $guid): ?Auction;

    public function list(int $perPage, QueryCriterion ...$criteria): LengthAwarePaginator;

    public function create(array $data): Auction;

    public function update(Model $model, array $data): Auction;

    public function destroy(Model $model): ?bool;
}
