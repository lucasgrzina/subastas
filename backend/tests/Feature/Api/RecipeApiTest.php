<?php

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use Database\Seeders\ApiClientSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.ApiClientSeeder::DEMO_TOKEN];
    }

    public function test_rechaza_request_sin_token(): void
    {
        $this->getJson('/api/v1/recipes')->assertUnauthorized();
    }

    public function test_rechaza_token_invalido(): void
    {
        $this->getJson('/api/v1/recipes', ['Authorization' => 'Bearer token_falso'])
            ->assertUnauthorized();
    }

    public function test_rechaza_cliente_inactivo(): void
    {
        ApiClient::query()->update(['active' => false]);

        $this->getJson('/api/v1/recipes', $this->authHeaders())->assertUnauthorized();
    }

    public function test_lista_recetas_publicadas(): void
    {
        $this->getJson('/api/v1/recipes', $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'titulo', 'tiempo_total_min', 'dificultad', 'dietas', 'alergenos_contiene']],
                'meta' => ['total'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filtra_por_dieta(): void
    {
        $this->getJson('/api/v1/recipes?dieta=vegano', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'ensalada-tomate-albahaca');
    }

    public function test_filtro_sin_alergeno_excluye_recetas(): void
    {
        // La ensalada no tiene gluten; los panqueques sí.
        $this->getJson('/api/v1/recipes?sin_alergeno[]=gluten', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'ensalada-tomate-albahaca');
    }

    public function test_muestra_ficha_completa(): void
    {
        $this->getJson('/api/v1/recipes/panqueques-clasicos', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.titulo', 'Panqueques clásicos')
            ->assertJsonPath('data.tiempos.total_min', 55)
            ->assertJsonPath('data.alergenos.contiene.0.clave', 'gluten')
            ->assertJsonStructure([
                'data' => [
                    'secciones' => [['nombre', 'ingredientes', 'pasos']],
                    'nutricion' => ['energia_kcal'],
                ],
            ]);
    }

    public function test_404_en_receta_inexistente(): void
    {
        $this->getJson('/api/v1/recipes/no-existe', $this->authHeaders())->assertNotFound();
    }

    public function test_meta_devuelve_catalogos(): void
    {
        $this->getJson('/api/v1/meta', $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure(['data' => ['categorias', 'cocinas', 'dietas', 'alergenos', 'unidades']])
            ->assertJsonPath('data.alergenos.0.clave', 'gluten');
    }
}
