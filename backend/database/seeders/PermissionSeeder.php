<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.read',
            'users.create',
            'users.update',
            'users.delete',
            'roles.read',
            'roles.create',
            'roles.update',
            'roles.delete',
            'support-messages.read',
            'support-messages.create',
            'support-messages.update',
            'support-messages.delete',
            'support-messages.reply',
            'support-messages.close',
            'system-settings.manage',
            'products.read',
            'products.create',
            'products.update',
            'products.delete',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['guid' => Str::uuid()->toString()],
            );
        }
    }
}
