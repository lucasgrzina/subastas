<?php

namespace Tests\Feature\Api;

use App\Models\Influencer;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InfluencerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    /** Crea y autentica un usuario con los permisos dados. */
    private function actingAsUserWith(array $permissions): User
    {
        $user = User::factory()->create([
            'guid'       => Str::uuid()->toString(),
            'first_name' => 'Test',
            'last_name'  => 'User',
            'name'       => 'Test User',
        ]);

        $user->givePermissionTo($permissions);
        Sanctum::actingAs($user);

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nombre'           => 'Leandro',
            'alias'            => 'Lean',
            'edad'             => 33,
            'ciudad'           => 'Buenos Aires',
            'especialidad'     => 'pastas y salsas',
            'frases_positivas' => ['la salsa siempre es protagonista'],
            'video_formato'    => '9:16',
            'video_duracion_seg' => 30,
            'resumen_ia'       => 'Un porteño de 33 años, especialista en pastas y salsas.',
        ], $overrides);
    }

    // --- Autenticación ---

    public function test_rechaza_request_sin_autenticacion(): void
    {
        $this->getJson('/api/v1/influencers')->assertUnauthorized();
    }

    // --- Index ---

    public function test_lista_influencers(): void
    {
        $this->actingAsUserWith(['influencers.read']);
        Influencer::factory()->count(3)->create();

        $this->getJson('/api/v1/influencers')
            ->assertOk()
            ->assertJsonPath('data.total', 3);
    }

    public function test_busca_por_nombre(): void
    {
        $this->actingAsUserWith(['influencers.read']);
        Influencer::factory()->create(['nombre' => 'Leandro']);
        Influencer::factory()->create(['nombre' => 'Sofia']);

        $this->getJson('/api/v1/influencers?search=Leandro')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_filtra_por_activo(): void
    {
        $this->actingAsUserWith(['influencers.read']);
        Influencer::factory()->create(['activo' => true]);
        Influencer::factory()->create(['activo' => false]);

        $this->getJson('/api/v1/influencers?activo=true')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    // --- Show ---

    public function test_muestra_influencer(): void
    {
        $this->actingAsUserWith(['influencers.read']);
        $influencer = Influencer::factory()->create(['nombre' => 'Leandro']);

        $this->getJson("/api/v1/influencers/{$influencer->guid}")
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Leandro')
            ->assertJsonPath('data.guid', $influencer->guid);
    }

    public function test_show_guid_inexistente_da_404(): void
    {
        $this->actingAsUserWith(['influencers.read']);

        $this->getJson('/api/v1/influencers/'.Str::uuid())->assertNotFound();
    }

    // --- Store ---

    public function test_crea_influencer_con_datos_minimos(): void
    {
        $this->actingAsUserWith(['influencers.create']);

        $this->postJson('/api/v1/influencers', ['nombre' => 'Leandro'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Leandro')
            ->assertJsonPath('data.activo', true);

        $this->assertDatabaseHas('influencers', ['nombre' => 'Leandro']);
    }

    public function test_crea_influencer_completo_persiste_arrays(): void
    {
        $this->actingAsUserWith(['influencers.create']);

        $this->postJson('/api/v1/influencers', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.frases_positivas.0', 'la salsa siempre es protagonista')
            ->assertJsonPath('data.video_formato', '9:16');
    }

    public function test_store_sin_nombre_da_422(): void
    {
        $this->actingAsUserWith(['influencers.create']);

        $this->postJson('/api/v1/influencers', ['alias' => 'Lean'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_store_video_formato_invalido_da_422(): void
    {
        $this->actingAsUserWith(['influencers.create']);

        $this->postJson('/api/v1/influencers', $this->validPayload(['video_formato' => '4:3']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('video_formato');
    }

    public function test_store_frase_no_string_da_422(): void
    {
        $this->actingAsUserWith(['influencers.create']);

        $this->postJson('/api/v1/influencers', $this->validPayload(['frases_positivas' => [['no' => 'string']]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('frases_positivas.0');
    }

    // --- Update ---

    public function test_actualiza_influencer(): void
    {
        $this->actingAsUserWith(['influencers.update']);
        $influencer = Influencer::factory()->create(['nombre' => 'Leandro']);

        $this->putJson("/api/v1/influencers/{$influencer->guid}", [
            'nombre'     => 'Leandro',
            'resumen_ia' => 'Nuevo resumen consolidado.',
        ])
            ->assertOk()
            ->assertJsonPath('data.resumen_ia', 'Nuevo resumen consolidado.');
    }

    public function test_update_guid_inexistente_da_404(): void
    {
        $this->actingAsUserWith(['influencers.update']);

        $this->putJson('/api/v1/influencers/'.Str::uuid(), ['nombre' => 'X'])->assertNotFound();
    }

    // --- Toggle active ---

    public function test_toggle_active(): void
    {
        $this->actingAsUserWith(['influencers.update']);
        $influencer = Influencer::factory()->create(['activo' => true]);

        $this->patchJson("/api/v1/influencers/{$influencer->guid}/toggle-active")
            ->assertOk()
            ->assertJsonPath('data.activo', false);
    }

    // --- Destroy ---

    public function test_elimina_influencer_soft_delete(): void
    {
        $this->actingAsUserWith(['influencers.read', 'influencers.delete']);
        $influencer = Influencer::factory()->create();

        $this->deleteJson("/api/v1/influencers/{$influencer->guid}")->assertOk();

        $this->assertSoftDeleted('influencers', ['id' => $influencer->id]);
        $this->getJson('/api/v1/influencers')->assertJsonPath('data.total', 0);
    }

    // --- Upload temporal + promoción ---

    /** Sube una imagen al endpoint temporal y devuelve el token. */
    private function uploadTempImage(): string
    {
        $file = UploadedFile::fake()->image('leandro.jpg', 800, 800);

        return $this->postJson('/api/v1/uploads/images', ['image' => $file])
            ->assertCreated()
            ->json('data.token');
    }

    public function test_upload_temporal_devuelve_token(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read']);

        $file = UploadedFile::fake()->image('foto.jpg', 600, 600);

        $response = $this->postJson('/api/v1/uploads/images', ['image' => $file])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['token', 'url']]);

        $token = $response->json('data.token');
        $this->assertStringEndsWith('.webp', $token);
        Storage::disk('public')->assertExists("tmp/uploads/{$token}");
    }

    public function test_upload_temporal_rechaza_no_imagen(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read']);

        $file = UploadedFile::fake()->create('malware.php', 10, 'application/x-php');

        $this->postJson('/api/v1/uploads/images', ['image' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_upload_temporal_rechaza_svg(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read']);

        $file = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

        $this->postJson('/api/v1/uploads/images', ['image' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_crea_con_imagen_promueve_temporal(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read', 'influencers.create']);

        $token = $this->uploadTempImage();

        $response = $this->postJson('/api/v1/influencers', ['nombre' => 'Leandro', 'image_token' => $token])
            ->assertCreated();

        $path = $response->json('data.character_image_url');
        $this->assertSame("influencers/{$token}", $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing("tmp/uploads/{$token}");
    }

    public function test_actualiza_reemplaza_y_borra_anterior(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read', 'influencers.update']);

        Storage::disk('public')->put('influencers/old.webp', 'contenido-viejo');
        $influencer = Influencer::factory()->create(['character_image_url' => 'influencers/old.webp']);

        $token = $this->uploadTempImage();

        $this->putJson("/api/v1/influencers/{$influencer->guid}", ['nombre' => 'Leandro', 'image_token' => $token])
            ->assertOk()
            ->assertJsonPath('data.character_image_url', "influencers/{$token}");

        Storage::disk('public')->assertMissing('influencers/old.webp');
    }

    public function test_remove_image_borra_y_deja_null(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read', 'influencers.update']);

        Storage::disk('public')->put('influencers/keep.webp', 'contenido');
        $influencer = Influencer::factory()->create(['character_image_url' => 'influencers/keep.webp']);

        $this->putJson("/api/v1/influencers/{$influencer->guid}", ['nombre' => 'Leandro', 'remove_image' => true])
            ->assertOk()
            ->assertJsonPath('data.character_image_url', null);

        Storage::disk('public')->assertMissing('influencers/keep.webp');
    }

    public function test_actualiza_sin_imagen_no_la_toca(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read', 'influencers.update']);

        Storage::disk('public')->put('influencers/keep.webp', 'contenido');
        $influencer = Influencer::factory()->create(['character_image_url' => 'influencers/keep.webp']);

        $this->putJson("/api/v1/influencers/{$influencer->guid}", ['nombre' => 'Nuevo Nombre'])
            ->assertOk()
            ->assertJsonPath('data.character_image_url', 'influencers/keep.webp');

        Storage::disk('public')->assertExists('influencers/keep.webp');
    }

    public function test_token_invalido_da_422(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read', 'influencers.create']);

        $this->postJson('/api/v1/influencers', ['nombre' => 'Leandro', 'image_token' => 'no-existe.webp'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image_token');
    }

    public function test_soft_delete_preserva_imagen(): void
    {
        Storage::fake('public');
        $this->actingAsUserWith(['influencers.read', 'influencers.delete']);

        Storage::disk('public')->put('influencers/keep.webp', 'contenido');
        $influencer = Influencer::factory()->create(['character_image_url' => 'influencers/keep.webp']);

        $this->deleteJson("/api/v1/influencers/{$influencer->guid}")->assertOk();

        Storage::disk('public')->assertExists('influencers/keep.webp');
    }

    // --- Autorización granular ---

    public function test_403_sin_permiso_read(): void
    {
        $this->actingAsUserWith(['influencers.create']);
        $this->getJson('/api/v1/influencers')->assertForbidden();
    }

    public function test_403_sin_permiso_create(): void
    {
        $this->actingAsUserWith(['influencers.read']);
        $this->postJson('/api/v1/influencers', ['nombre' => 'X'])->assertForbidden();
    }

    public function test_403_sin_permiso_update(): void
    {
        $this->actingAsUserWith(['influencers.read']);
        $influencer = Influencer::factory()->create();

        $this->putJson("/api/v1/influencers/{$influencer->guid}", ['nombre' => 'X'])->assertForbidden();
    }

    public function test_403_sin_permiso_delete(): void
    {
        $this->actingAsUserWith(['influencers.read']);
        $influencer = Influencer::factory()->create();

        $this->deleteJson("/api/v1/influencers/{$influencer->guid}")->assertForbidden();
    }
}
