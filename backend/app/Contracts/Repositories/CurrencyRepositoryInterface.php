<?php

namespace App\Contracts\Repositories;

use App\Contracts\QueryCriterion;
use App\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface CurrencyRepositoryInterface
{
    public function findByGuid(string $guid): ?Currency;

    public function list(int $perPage, QueryCriterion ...$criteria): LengthAwarePaginator;

    public function create(array $data): Currency;

    public function update(Model $model, array $data): Currency;

    public function destroy(Model $model): ?bool;
}
