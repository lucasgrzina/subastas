<?php

namespace App\Criteria\Users;

use App\Contracts\QueryCriterion;
use Illuminate\Database\Eloquent\Builder;

class UserRoleCriteria implements QueryCriterion
{
    public function __construct(private ?string $roleGuid) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->roleGuid) {
            return $query;
        }

        $roleGuid = $this->roleGuid;

        return $query->whereHas('roles', fn(Builder $q) => $q->where('guid', $roleGuid));
    }
}
