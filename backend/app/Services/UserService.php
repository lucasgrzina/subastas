<?php

namespace App\Services;

use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Criteria\Shared\DateRangeCriteria;
use App\Criteria\Users\UserRoleCriteria;
use App\Criteria\Users\UserSearchCriteria;
use App\Criteria\Users\UserStatusCriteria;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {}

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->list(
            $perPage,
            new UserSearchCriteria($filters['search'] ?? null),
            new UserStatusCriteria($filters['status'] ?? null),
            new DateRangeCriteria($filters['date_from'] ?? null, $filters['date_to'] ?? null),
            new UserRoleCriteria($filters['role_guid'] ?? null),
        );
    }

    public function findByGuid(string $guid): ?User
    {
        return $this->userRepository->findByGuid($guid);
    }

    public function create(array $data): User
    {
        $user = $this->userRepository->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => $data['first_name'].' '.$data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        return $this->syncRoles($user, $data['role_guids'] ?? []);
    }

    public function update(User $user, array $data): User
    {
        $user = $this->userRepository->update($user, [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => $data['first_name'].' '.$data['last_name'],
            'email' => $data['email'],
        ]);

        if (array_key_exists('role_guids', $data)) {
            return $this->syncRoles($user, $data['role_guids'] ?? []);
        }

        return $user->load('roles');
    }

    public function destroy(User $user): void
    {
        $this->userRepository->destroy($user);
    }

    public function toggleLock(User $user): User
    {
        return $this->userRepository->toggleLock($user);
    }

    public function changePassword(User $user, string $password): void
    {
        $this->userRepository->updatePassword($user, $password);
    }

    public function resetPassword(User $user): string
    {
        $randomPassword = $this->generateRandomPassword();
        $this->userRepository->updatePassword($user, $randomPassword);

        return $randomPassword;
    }

    public function syncRoles(User $user, array $roleGuids): User
    {
        $roles = $this->roleRepository->findManyByGuids($roleGuids);
        $user->syncRoles($roles);

        return $user->load('roles');
    }

    private function generateRandomPassword(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $symbols = '!@#$%&';

        $password = $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        $pool = $uppercase.$lowercase.$digits.$symbols;
        for ($i = 3; $i < 10; $i++) {
            $password .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return str_shuffle($password);
    }
}
