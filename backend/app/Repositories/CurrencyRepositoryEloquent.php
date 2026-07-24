<?php

namespace App\Repositories;

use App\Contracts\QueryCriterion;
use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CurrencyRepositoryEloquent extends BaseRepositoryEloquent implements CurrencyRepositoryInterface
{
    protected function model(): string
    {
        return Currency::class;
    }

    public function findByGuid(string $guid): ?Currency
    {
        return $this->newQuery()->where('guid', $guid)->first();
    }

    public function list(int $perPage, QueryCriterion ...$criteria): LengthAwarePaginator
    {
        return $this->buildQuery(...$criteria)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Currency
    {
        /** @var Currency */
        return parent::create($data);
    }

    public function update(Model $model, array $data): Currency
    {
        $model->fill($data);
        $model->save();

        /** @var Currency */
        return $model->fresh();
    }

    public function destroy(Model $model): ?bool
    {
        return parent::destroy($model);
    }
}
