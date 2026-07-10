<?php

namespace App\Services;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Criteria\Shared\DateRangeCriteria;
use App\Criteria\Shared\SearchCriteria;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->roleRepository->list(
            $perPage,
            new SearchCriteria($filters['search'] ?? null),
            new DateRangeCriteria($filters['date_from'] ?? null, $filters['date_to'] ?? null),
        );
    }

    public function findByGuid(string $guid): ?Role
    {
        return $this->roleRepository->findByGuid($guid);
    }

    public function create(array $data): Role
    {
        $data['type'] = Role::TYPE_PLATFORM;

        $role = $this->roleRepository->create($data);

        if (! empty($data['permissions'])) {
            $permissions = $this->permissionRepository->findManyByGuids($data['permissions']);
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }

    public function update(Role $role, array $data): Role
    {
        if (! empty($data['name'])) {
            $role = $this->roleRepository->update($role, $data);
        }

        if (array_key_exists('permissions', $data)) {
            $permissions = $this->permissionRepository->findManyByGuids($data['permissions'] ?? []);
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }

    public function destroy(Role $role): void
    {
        $this->roleRepository->destroy($role);
    }

    public function syncPermissions(Role $role, array $permissionGuids): Role
    {
        $permissions = $this->permissionRepository->findManyByGuids($permissionGuids);
        $role->syncPermissions($permissions);

        return $role->load('permissions');
    }
}
