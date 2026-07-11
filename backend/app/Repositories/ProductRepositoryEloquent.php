<?php

namespace App\Repositories;

use App\Contracts\QueryCriterion;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class ProductRepositoryEloquent extends BaseRepositoryEloquent implements ProductRepositoryInterface
{
    protected function model(): string
    {
        return Product::class;
    }

    private function withRelations(): array
    {
        return ['wineDetail.winery', 'wineDetail.grapeVariety', 'wineDetail.wineRegion', 'images'];
    }

    public function findByGuid(string $guid): ?Product
    {
        return $this->newQuery()->with($this->withRelations())->where('guid', $guid)->first();
    }

    public function findByGuidWithTrashed(string $guid): ?Product
    {
        return $this->newQuery()
            ->withTrashed()
            ->with($this->withRelations())
            ->where('guid', $guid)
            ->first();
    }

    public function list(int $perPage, bool $withTrashed, QueryCriterion ...$criteria): LengthAwarePaginator
    {
        $query = $withTrashed ? $this->newQuery()->withTrashed() : $this->newQuery();

        foreach ($criteria as $criterion) {
            $query = $criterion->apply($query);
        }

        return $query->with($this->withRelations())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Product
    {
        /** @var Product */
        return parent::create($data);
    }

    public function update(Model $model, array $data): Product
    {
        $model->fill($data);
        $model->save();

        /** @var Product */
        return $model->fresh($this->withRelations());
    }

    public function destroy(Model $model): ?bool
    {
        return $model->delete();
    }

    public function restore(Product $product): Product
    {
        $product->restore();

        return $product->fresh($this->withRelations());
    }
}
