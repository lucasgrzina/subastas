<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            WineReferenceDataSeeder::class,
        ]);

        /*$user = User::factory()->create([
            'guid'       => Str::uuid()->toString(),
            'first_name' => 'Test',
            'last_name'  => 'User',
            'name'       => 'Test User',
            'email'      => 'test@example.com',
        ]);

        $user->assignRole('super-admin');*/
    }
}
