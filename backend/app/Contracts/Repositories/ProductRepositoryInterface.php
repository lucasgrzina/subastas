<?php

namespace App\Contracts\Repositories;

use App\Contracts\QueryCriterion;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface ProductRepositoryInterface
{
    public function findByGuid(string $guid): ?Product;

    public function findByGuidWithTrashed(string $guid): ?Product;

    public function list(int $perPage, bool $withTrashed, QueryCriterion ...$criteria): LengthAwarePaginator;

    public function create(array $data): Product;

    public function update(Model $model, array $data): Product;

    public function destroy(Model $model): ?bool;

    public function restore(Product $product): Product;
}
