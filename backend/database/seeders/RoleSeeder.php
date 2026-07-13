<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['guid' => Str::uuid()->toString()],
        );

        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['guid' => Str::uuid()->toString()],
        );

        $operador = Role::firstOrCreate(
            ['name' => 'operador', 'guard_name' => 'web'],
            ['guid' => Str::uuid()->toString()],
        );

        $bidder = Role::firstOrCreate(
            ['name' => 'bidder', 'guard_name' => 'web'],
            ['guid' => Str::uuid()->toString()],
        );

        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions(Permission::whereIn('name', [
            'users.read',
            'users.create',
            'users.update',
            'roles.read',
            'api-clients.read',
            'api-clients.create',
            'api-clients.update',
            'api-clients.delete',
            'products.read',
            'products.create',
            'products.update',
            'products.delete',
            'auctions.read',
            'auctions.create',
            'auctions.update',
            'auctions.delete',
            'lots.read',
            'lots.create',
            'lots.update',
            'lots.delete',
            'lots.close',
            'bids.read',
            'currencies.read',
            'currencies.create',
            'currencies.update',
            'currencies.delete',
        ])->get());

        $operador->syncPermissions(Permission::whereIn('name', [
            'users.read',
            'support-messages.read',
            'products.read',
            'auctions.read',
            'lots.read',
            'bids.read',
            'currencies.read',
        ])->get());

        $bidder->syncPermissions(Permission::whereIn('name', [
            'auctions.read',
            'lots.read',
            'bids.create',
        ])->get());
    }
}
