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

class LotCloseApiTest extends TestCase
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

    private function createOpenLot(array $overrides = []): Lot
    {
        $auction = Auction::create(['title' => 'Subasta', 'starts_at' => now(), 'status' => 'live']);
        $product = Product::create(['title' => 'Producto', 'status' => 'published']);

        $lot = Lot::create(array_merge([
            'auction_id' => $auction->id,
            'lot_number' => 'LOT-1',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'open',
        ], $overrides));
        $lot->products()->attach($product->id, ['quantity' => 1]);

        return $lot->fresh(['auction']);
    }

    private function placeBidAs(Lot $lot, string $amount): User
    {
        $bidder = User::factory()->create(['first_name' => 'Bidder', 'last_name' => (string) random_int(1, 999999)]);
        $bidder->assignRole('bidder');
        Sanctum::actingAs($bidder);

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => $amount])->assertCreated();

        return $bidder;
    }

    public function test_cierre_con_reserva_cumplida_marca_vendido(): void
    {
        $lot = $this->createOpenLot(['reserve_price' => '100.00']);
        $winner = $this->placeBidAs($lot, '120.00');

        $this->actingAsRole('admin');
        $response = $this->postJson("/api/v1/lots/{$lot->guid}/close")->assertOk();

        $response->assertJsonPath('data.status', 'sold');
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'status' => 'sold',
            'current_winner_user_id' => $winner->id,
        ]);
    }

    public function test_cierre_con_reserva_no_cumplida_marca_no_vendido(): void
    {
        $lot = $this->createOpenLot(['reserve_price' => '200.00']);
        $this->placeBidAs($lot, '120.00');

        $this->actingAsRole('admin');
        $response = $this->postJson("/api/v1/lots/{$lot->guid}/close")->assertOk();

        $response->assertJsonPath('data.status', 'unsold');
        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'status' => 'unsold']);
    }

    public function test_cierre_sin_ofertas_marca_no_vendido_sin_importar_reserva(): void
    {
        $lot = $this->createOpenLot(['reserve_price' => null]);

        $this->actingAsRole('admin');
        $response = $this->postJson("/api/v1/lots/{$lot->guid}/close")->assertOk();

        $response->assertJsonPath('data.status', 'unsold');
    }

    public function test_cierre_en_el_limite_exacto_de_la_reserva_marca_vendido(): void
    {
        $lot = $this->createOpenLot(['reserve_price' => '120.00']);
        $this->placeBidAs($lot, '120.00');

        $this->actingAsRole('admin');
        $this->postJson("/api/v1/lots/{$lot->guid}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'sold');
    }

    public function test_cierre_bloqueado_para_no_admin(): void
    {
        $lot = $this->createOpenLot();

        $this->actingAsRole('operador');
        $this->postJson("/api/v1/lots/{$lot->guid}/close")->assertForbidden();

        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'status' => 'open']);
    }
}
