<?php

namespace Tests\Unit;

use App\Models\Influencer;
use App\Services\InfluencerService;
use App\Services\TempUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InfluencerServiceTest extends TestCase
{
    use RefreshDatabase;

    private InfluencerService $service;

    private TempUploadService $temp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InfluencerService::class);
        $this->temp = app(TempUploadService::class);
    }

    private function stageImage(): string
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('foto.jpg', 800, 800);

        return $this->temp->store($file)['token'];
    }

    public function test_create_aplica_default_activo(): void
    {
        $influencer = $this->service->create(['nombre' => 'Leandro']);

        $this->assertTrue($influencer->activo);
        $this->assertDatabaseHas('influencers', ['nombre' => 'Leandro']);
    }

    public function test_temp_store_recodifica_a_webp(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('foto.jpg', 3000, 3000);

        $result = $this->temp->store($file);

        $this->assertStringEndsWith('.webp', $result['token']);
        Storage::disk('public')->assertExists("tmp/uploads/{$result['token']}");
    }

    public function test_temp_store_redimensiona_al_maximo(): void
    {
        Storage::fake('public');
        config(['uploads.max_dimension' => 500]);
        $file = UploadedFile::fake()->image('grande.png', 2000, 1000);

        $result = $this->temp->store($file);

        $binary = Storage::disk('public')->get("tmp/uploads/{$result['token']}");
        [$width, $height] = getimagesizefromstring($binary);

        $this->assertLessThanOrEqual(500, $width);
        $this->assertLessThanOrEqual(500, $height);
    }

    public function test_create_con_token_promueve(): void
    {
        $token = $this->stageImage();

        $influencer = $this->service->create(['nombre' => 'Leandro', 'image_token' => $token]);

        $this->assertSame("influencers/{$token}", $influencer->character_image_url);
        Storage::disk('public')->assertExists("influencers/{$token}");
        Storage::disk('public')->assertMissing("tmp/uploads/{$token}");
    }

    public function test_update_con_token_borra_anterior(): void
    {
        $token = $this->stageImage();
        Storage::disk('public')->put('influencers/old.webp', 'viejo');
        $influencer = Influencer::factory()->create(['character_image_url' => 'influencers/old.webp']);

        $this->service->update($influencer, ['nombre' => 'Leandro', 'image_token' => $token]);

        Storage::disk('public')->assertMissing('influencers/old.webp');
        Storage::disk('public')->assertExists("influencers/{$token}");
    }

    public function test_promote_token_invalido_lanza(): void
    {
        Storage::fake('public');
        $this->expectException(\RuntimeException::class);

        $this->temp->promote('no-es-uuid.webp', 'influencers');
    }
}
