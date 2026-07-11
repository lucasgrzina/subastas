<?php

namespace Tests\Feature\Api;

use App\Models\Auction;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LotStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);
        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    private function createLot(array $overrides = []): Lot
    {
        $auction = Auction::create(['title' => 'Subasta', 'starts_at' => now(), 'status' => 'live']);
        $product = Product::create(['title' => 'Producto', 'status' => 'published']);

        $lot = Lot::create(array_merge([
            'auction_id' => $auction->id,
            'lot_number' => 'LOT-1',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'scheduled',
        ], $overrides));

        $lot->products()->attach($product->id, ['quantity' => 1]);

        return $lot->fresh();
    }

    public function test_update_generico_rechaza_transicion_directa_a_sold(): void
    {
        $this->actingAsRole('admin');
        $lot = $this->createLot(['status' => 'open']);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['status' => 'sold'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'status' => 'open']);
    }

    public function test_update_generico_rechaza_transicion_directa_a_unsold(): void
    {
        $this->actingAsRole('admin');
        $lot = $this->createLot(['status' => 'open']);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['status' => 'unsold'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'status' => 'open']);
    }

    public function test_guarda_de_reversa_rechaza_cualquier_cambio_de_estado_en_lote_ya_finalizado(): void
    {
        $this->actingAsRole('admin');
        $lot = $this->createLot(['status' => 'sold', 'current_price' => '150.00']);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['status' => 'scheduled'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'status' => 'sold']);
    }

    public function test_creacion_rechaza_status_terminal_directo(): void
    {
        $this->actingAsRole('admin');
        $auction = Auction::create(['title' => 'Subasta', 'starts_at' => now(), 'status' => 'live']);
        $product = Product::create(['title' => 'Producto', 'status' => 'published']);

        $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-2',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'sold',
            'products' => [['product_guid' => $product->guid, 'quantity' => 1]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('lots', 0);
    }
}
