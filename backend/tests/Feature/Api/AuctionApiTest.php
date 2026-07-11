<?php

namespace Tests\Feature\Api;

use App\Models\Auction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuctionApiTest extends TestCase
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

    private function createAuction(array $overrides = []): Auction
    {
        return Auction::create(array_merge([
            'title' => 'Subasta de prueba',
            'description' => 'desc',
            'starts_at' => now()->addDay(),
            'status' => 'scheduled',
        ], $overrides));
    }

    // --- Seeder sanity (task 2.7) ---

    public function test_rol_bidder_tiene_exactamente_tres_permisos(): void
    {
        $user = $this->actingAsRole('bidder');

        $this->assertCount(3, $user->getAllPermissions());
        $this->assertTrue($user->can('auctions.read'));
        $this->assertTrue($user->can('lots.read'));
        $this->assertTrue($user->can('bids.create'));
    }

    // --- CRUD ---

    public function test_admin_crea_subasta(): void
    {
        $this->actingAsRole('admin');

        $response = $this->postJson('/api/v1/auctions', [
            'title' => 'Gran Subasta',
            'description' => 'Una gran subasta',
            'starts_at' => now()->addDays(3)->toISOString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Gran Subasta')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseCount('auctions', 1);
    }

    public function test_admin_actualiza_y_elimina_subasta(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();

        $this->putJson("/api/v1/auctions/{$auction->guid}", ['title' => 'Título nuevo'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título nuevo');

        $this->deleteJson("/api/v1/auctions/{$auction->guid}")->assertOk();
        $this->assertSoftDeleted('auctions', ['guid' => $auction->guid]);
    }

    // --- Permission matrix ---

    public function test_operador_no_puede_escribir_pero_puede_leer(): void
    {
        $this->actingAsRole('operador');
        $auction = $this->createAuction();

        $this->getJson('/api/v1/auctions')->assertOk();
        $this->getJson("/api/v1/auctions/{$auction->guid}")->assertOk();

        $this->postJson('/api/v1/auctions', [
            'title' => 'Bloqueado',
            'starts_at' => now()->addDay()->toISOString(),
        ])->assertForbidden();

        $this->putJson("/api/v1/auctions/{$auction->guid}", ['title' => 'x'])->assertForbidden();
        $this->deleteJson("/api/v1/auctions/{$auction->guid}")->assertForbidden();
    }

    public function test_bidder_bloqueado_de_gestion(): void
    {
        $this->actingAsRole('bidder');
        $auction = $this->createAuction();

        $this->postJson('/api/v1/auctions', [
            'title' => 'Bloqueado',
            'starts_at' => now()->addDay()->toISOString(),
        ])->assertForbidden();

        $this->putJson("/api/v1/auctions/{$auction->guid}", ['title' => 'x'])->assertForbidden();
        $this->deleteJson("/api/v1/auctions/{$auction->guid}")->assertForbidden();
    }

    // --- Bidder read scope ---

    public function test_bidder_scope_de_lectura_en_listado_y_acceso_directo(): void
    {
        $draft = $this->createAuction(['status' => 'draft']);
        $scheduled = $this->createAuction(['status' => 'scheduled']);
        $live = $this->createAuction(['status' => 'live']);
        $closed = $this->createAuction(['status' => 'closed']);
        $cancelled = $this->createAuction(['status' => 'cancelled']);

        $this->actingAsRole('bidder');

        $response = $this->getJson('/api/v1/auctions?per_page=50')->assertOk();
        $guids = collect($response->json('data.data'))->pluck('guid');

        $this->assertTrue($guids->contains($scheduled->guid));
        $this->assertTrue($guids->contains($live->guid));
        $this->assertTrue($guids->contains($closed->guid));
        $this->assertFalse($guids->contains($draft->guid));
        $this->assertFalse($guids->contains($cancelled->guid));

        $this->getJson("/api/v1/auctions/{$scheduled->guid}")->assertOk();
        $this->getJson("/api/v1/auctions/{$draft->guid}")->assertNotFound();
        $this->getJson("/api/v1/auctions/{$cancelled->guid}")->assertNotFound();
    }

    public function test_admin_ve_todos_los_estados_en_listado(): void
    {
        $this->createAuction(['status' => 'draft']);
        $this->createAuction(['status' => 'cancelled']);

        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/auctions?per_page=50')->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }
}
