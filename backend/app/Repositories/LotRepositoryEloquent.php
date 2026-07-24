<?php

namespace App\Repositories;

use App\Contracts\QueryCriterion;
use App\Contracts\Repositories\LotRepositoryInterface;
use App\Models\Lot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class LotRepositoryEloquent extends BaseRepositoryEloquent implements LotRepositoryInterface
{
    protected function model(): string
    {
        return Lot::class;
    }

    /**
     * `auction` is REQUIRED here (not optional eager-loading) — it feeds the
     * bidder single-resource visibility check on findByGuid() (see design
     * "Read gate — bidder").
     */
    private function withRelations(): array
    {
        return ['auction', 'products', 'currentBid.user', 'currentWinner'];
    }

    public function findByGuid(string $guid): ?Lot
    {
        return $this->newQuery()->with($this->withRelations())->where('guid', $guid)->first();
    }

    public function list(int $perPage, QueryCriterion ...$criteria): LengthAwarePaginator
    {
        $query = $this->buildQuery(...$criteria);

        return $query->with($this->withRelations())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Lot
    {
        /** @var Lot */
        return parent::create($data);
    }

    public function update(Model $model, array $data): Lot
    {
        $model->fill($data);
        $model->save();

        /** @var Lot */
        return $model->fresh($this->withRelations());
    }

    public function destroy(Model $model): ?bool
    {
        return $model->delete();
    }
}
