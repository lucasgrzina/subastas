<?php

namespace App\Contracts\Repositories;

use App\Contracts\QueryCriterion;
use App\Models\Lot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface LotRepositoryInterface
{
    public function findByGuid(string $guid): ?Lot;

    public function list(int $perPage, QueryCriterion ...$criteria): LengthAwarePaginator;

    public function create(array $data): Lot;

    public function update(Model $model, array $data): Lot;

    public function destroy(Model $model): ?bool;
}
