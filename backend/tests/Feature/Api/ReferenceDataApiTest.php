<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferenceDataApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_lista_bodegas_seeded(): void
    {
        $this->actingAsRole('admin');

        $this->getJson('/api/v1/wineries')
            ->assertOk()
            ->assertJsonStructure(['data' => [['guid', 'name']]])
            ->assertJsonCount(5, 'data');
    }

    public function test_lista_variedades_de_uva_seeded(): void
    {
        $this->actingAsRole('admin');

        $this->getJson('/api/v1/grape-varieties')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_lista_regiones_vitivinicolas_seeded(): void
    {
        $this->actingAsRole('admin');

        $this->getJson('/api/v1/wine-regions')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_operador_con_products_read_puede_listar(): void
    {
        $this->actingAsRole('operador');

        $this->getJson('/api/v1/wineries')->assertOk();
    }

    public function test_rechaza_sin_autenticacion(): void
    {
        $this->getJson('/api/v1/wineries')->assertUnauthorized();
    }
}
