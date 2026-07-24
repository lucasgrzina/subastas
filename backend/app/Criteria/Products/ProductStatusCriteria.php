<?php

namespace App\Criteria\Products;

use App\Contracts\QueryCriterion;
use Illuminate\Database\Eloquent\Builder;

class ProductStatusCriteria implements QueryCriterion
{
    public function __construct(private ?string $status) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->status) {
            return $query;
        }

        return $query->where('status', $this->status);
    }
}
