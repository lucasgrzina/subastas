<?php

namespace App\Services;

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Criteria\Shared\DateRangeCriteria;
use App\Criteria\Shared\SearchCriteria;
use App\Models\Currency;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CurrencyService
{
    public function __construct(
        private CurrencyRepositoryInterface $currencyRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->currencyRepository->list(
            $perPage,
            new SearchCriteria($filters['search'] ?? null, 'code'),
            new DateRangeCriteria($filters['date_from'] ?? null, $filters['date_to'] ?? null),
        );
    }

    public function findByGuid(string $guid): ?Currency
    {
        return $this->currencyRepository->findByGuid($guid);
    }

    public function create(array $data): Currency
    {
        $payload = [
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'symbol' => $data['symbol'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ];

        return $this->currencyRepository->create($payload);
    }

    public function update(Currency $currency, array $data): Currency
    {
        $payload = array_intersect_key($data, array_flip(['code', 'name', 'symbol', 'is_active']));

        return $this->currencyRepository->update($currency, $payload);
    }

    public function destroy(Currency $currency): void
    {
        $this->currencyRepository->destroy($currency);
    }
}
