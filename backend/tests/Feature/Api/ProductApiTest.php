<?php

namespace Tests\Feature\Api;

use App\Models\GrapeVariety;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\WineRegion;
use App\Models\Winery;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
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

    /** Stages a fake temp image and returns its token, mimicking TempUploadService::store(). */
    private function stageImage(): string
    {
        $token = Str::uuid()->toString().'.webp';
        Storage::disk('public')->put('tmp/uploads/'.$token, 'fake-image-bytes');

        return $token;
    }

    private function wineDetailsPayload(): array
    {
        return [
            'year' => 2020,
            'winery_guid' => Winery::first()->guid,
            'grape_variety_guid' => GrapeVariety::first()->guid,
            'wine_region_guid' => WineRegion::first()->guid,
        ];
    }

    // --- Creation ---

    public function test_crea_producto_con_detalles_de_vino_e_imagen_principal(): void
    {
        $this->actingAsRole('admin');
        $token = $this->stageImage();

        $response = $this->postJson('/api/v1/products', [
            'title' => 'Malbec Reserva 2020',
            'description' => 'Un gran vino',
            'wine_details' => $this->wineDetailsPayload(),
            'images' => [
                ['token' => $token, 'is_main' => true],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Malbec Reserva 2020')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.wine_details.year', 2020)
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.images.0.is_main', true);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('product_wine_details', 1);
        $this->assertDatabaseCount('product_images', 1);

        $product = Product::first();
        Storage::disk('public')->assertExists("products/{$product->guid}/{$token}");
        Storage::disk('public')->assertMissing('tmp/uploads/'.$token);
    }

    public function test_rechaza_creacion_sin_campos_de_vino_requeridos(): void
    {
        $this->actingAsRole('admin');
        $token = $this->stageImage();

        $this->postJson('/api/v1/products', [
            'title' => 'Producto incompleto',
            'wine_details' => [
                'year' => 2020,
                // faltan winery_guid, grape_variety_guid, wine_region_guid
            ],
            'images' => [['token' => $token, 'is_main' => true]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'wine_details.winery_guid',
                'wine_details.grape_variety_guid',
                'wine_details.wine_region_guid',
            ]);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_rechaza_creacion_sin_imagenes(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/api/v1/products', [
            'title' => 'Sin imagen',
            'wine_details' => $this->wineDetailsPayload(),
            'images' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['images']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_rechaza_creacion_con_multiples_is_main(): void
    {
        $this->actingAsRole('admin');
        $tokenA = $this->stageImage();
        $tokenB = $this->stageImage();

        $this->postJson('/api/v1/products', [
            'title' => 'Doble principal',
            'wine_details' => $this->wineDetailsPayload(),
            'images' => [
                ['token' => $tokenA, 'is_main' => true],
                ['token' => $tokenB, 'is_main' => true],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['images']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_rechaza_creacion_sin_ninguna_imagen_principal(): void
    {
        $this->actingAsRole('admin');
        $token = $this->stageImage();

        $this->postJson('/api/v1/products', [
            'title' => 'Sin principal',
            'wine_details' => $this->wineDetailsPayload(),
            'images' => [
                ['token' => $token, 'is_main' => false],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['images']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_promocion_de_imagen_fallida_no_deja_huerfanos(): void
    {
        $this->actingAsRole('admin');
        $validToken = $this->stageImage();
        // Token con formato válido pero sin archivo temporal real -> promote() explota.
        $bogusToken = Str::uuid()->toString().'.webp';

        $this->postJson('/api/v1/products', [
            'title' => 'Falla a mitad de camino',
            'wine_details' => $this->wineDetailsPayload(),
            'images' => [
                ['token' => $validToken, 'is_main' => true],
                ['token' => $bogusToken, 'is_main' => false],
            ],
        ])->assertStatus(500);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_images', 0);
        Storage::disk('public')->assertMissing('tmp/uploads/'.$validToken);

        // The first image WAS promoted before the second one blew up; it must
        // have been cleaned up (no orphan file at any products/* final path).
        $allFiles = Storage::disk('public')->allFiles('products');
        $this->assertEmpty($allFiles);
    }

    // --- Listing / filtering ---

    public function test_lista_oculta_borrados_y_filtra_por_estado(): void
    {
        $this->actingAsRole('admin');
        $product1 = $this->createProductDirectly(['status' => 'draft']);
        $product2 = $this->createProductDirectly(['status' => 'published']);
        $deleted = $this->createProductDirectly(['status' => 'published']);
        $deleted->delete();

        $response = $this->getJson('/api/v1/products?per_page=50');
        $response->assertOk();
        $guids = collect($response->json('data.data'))->pluck('guid');

        $this->assertTrue($guids->contains($product1->guid));
        $this->assertTrue($guids->contains($product2->guid));
        $this->assertFalse($guids->contains($deleted->guid));

        $filtered = $this->getJson('/api/v1/products?status=published&per_page=50');
        $filteredGuids = collect($filtered->json('data.data'))->pluck('guid');
        $this->assertTrue($filteredGuids->contains($product2->guid));
        $this->assertFalse($filteredGuids->contains($product1->guid));
    }

    // --- Update ---

    public function test_actualiza_reemplaza_el_set_de_imagenes(): void
    {
        $this->actingAsRole('admin');
        $product = $this->createProductDirectly();
        // Replace the seed main image with a single "old" one we control directly.
        $product->images()->delete();
        $oldImage = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/'.$product->guid.'/old.webp',
            'is_main' => true,
        ]);
        Storage::disk('public')->put($oldImage->path, 'old-bytes');

        $newToken = $this->stageImage();

        $this->putJson("/api/v1/products/{$product->guid}", [
            'title' => $product->title,
            'images' => [
                ['token' => $newToken, 'is_main' => true],
            ],
        ])->assertOk()
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.images.0.is_main', true);

        $this->assertDatabaseCount('product_images', 1);
        Storage::disk('public')->assertMissing($oldImage->path);
        Storage::disk('public')->assertExists("products/{$product->guid}/{$newToken}");
    }

    public function test_actualiza_solo_campos_genericos_sin_tocar_vino_ni_imagenes(): void
    {
        $this->actingAsRole('admin');
        $product = $this->createProductDirectly(['status' => 'draft']);
        $originalYear = $product->wineDetail->year;
        $originalImageCount = $product->images()->count();

        $this->putJson("/api/v1/products/{$product->guid}", [
            'title' => 'Título actualizado',
            'status' => 'published',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Título actualizado')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.wine_details.year', $originalYear);

        $this->assertDatabaseCount('product_images', $originalImageCount);
        $this->assertDatabaseHas('products', ['guid' => $product->guid, 'title' => 'Título actualizado', 'status' => 'published']);
    }

    // --- Soft-delete / restore ---

    public function test_soft_delete_y_restore(): void
    {
        $this->actingAsRole('admin');
        $product = $this->createProductDirectly();

        $this->deleteJson("/api/v1/products/{$product->guid}")->assertOk();
        $this->getJson("/api/v1/products/{$product->guid}")->assertNotFound();

        $this->patchJson("/api/v1/products/{$product->guid}/restore")
            ->assertOk()
            ->assertJsonPath('data.guid', $product->guid);

        $this->getJson("/api/v1/products/{$product->guid}")->assertOk();
    }

    // --- Permission enforcement ---

    public function test_operador_no_puede_escribir_pero_puede_leer(): void
    {
        $this->actingAsRole('operador');
        $product = $this->createProductDirectly();
        $token = $this->stageImage();

        $this->getJson('/api/v1/products')->assertOk();

        $this->postJson('/api/v1/products', [
            'title' => 'Bloqueado',
            'wine_details' => $this->wineDetailsPayload(),
            'images' => [['token' => $token, 'is_main' => true]],
        ])->assertForbidden();

        $this->putJson("/api/v1/products/{$product->guid}", ['title' => 'x'])->assertForbidden();
        $this->deleteJson("/api/v1/products/{$product->guid}")->assertForbidden();
        $this->patchJson("/api/v1/products/{$product->guid}/restore")->assertForbidden();
    }

    /** Creates a product + wine details + one main image directly, bypassing the API. */
    private function createProductDirectly(array $overrides = []): Product
    {
        $product = Product::create(array_merge([
            'title' => 'Producto de prueba',
            'description' => 'desc',
            'status' => 'draft',
        ], $overrides));

        $product->wineDetail()->create([
            'year' => 2019,
            'winery_id' => Winery::first()->id,
            'grape_variety_id' => GrapeVariety::first()->id,
            'wine_region_id' => WineRegion::first()->id,
        ]);

        $product->images()->create([
            'path' => 'products/'.$product->guid.'/seed.webp',
            'is_main' => true,
        ]);

        return $product->fresh(['wineDetail', 'images']);
    }
}
