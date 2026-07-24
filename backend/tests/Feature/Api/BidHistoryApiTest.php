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

class BidHistoryApiTest extends TestCase
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

    private function createOpenLot(string $auctionStatus = 'live'): Lot
    {
        $auction = Auction::create(['title' => 'Subasta', 'starts_at' => now(), 'status' => $auctionStatus]);
        $product = Product::create(['title' => 'Producto', 'status' => 'published']);

        $lot = Lot::create([
            'auction_id' => $auction->id,
            'lot_number' => 'LOT-1',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'open',
        ]);
        $lot->products()->attach($product->id, ['quantity' => 1]);

        return $lot->fresh(['auction']);
    }

    private function placeBid(Lot $lot, string $amount): User
    {
        $bidder = User::factory()->create(['first_name' => 'Bidder', 'last_name' => (string) random_int(1, 999999)]);
        $bidder->assignRole('bidder');
        Sanctum::actingAs($bidder);

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => $amount])->assertCreated();

        return $bidder;
    }

    public function test_historial_completo_visible_para_operador_y_bidder(): void
    {
        $lot = $this->createOpenLot();
        $bidderA = $this->placeBid($lot, '110.00');
        $this->placeBid($lot, '120.00');

        $this->actingAsRole('operador');
        $response = $this->getJson("/api/v1/lots/{$lot->guid}/bids")->assertOk();
        $this->assertCount(2, $response->json('data.data'));
        // Not filtered to the caller's own bids — both bidders' amounts appear.
        $amounts = collect($response->json('data.data'))->pluck('amount');
        $this->assertTrue($amounts->contains('120.00'));
        $this->assertTrue($amounts->contains('110.00'));

        Sanctum::actingAs($bidderA);
        $this->getJson("/api/v1/lots/{$lot->guid}/bids")->assertOk()->assertJsonCount(2, 'data.data');
    }

    public function test_historial_ordenado_por_monto_descendente(): void
    {
        $lot = $this->createOpenLot();
        $this->placeBid($lot, '110.00');
        $this->placeBid($lot, '120.00');

        $this->actingAsRole('admin');
        $response = $this->getJson("/api/v1/lots/{$lot->guid}/bids")->assertOk();

        $amounts = collect($response->json('data.data'))->pluck('amount')->all();
        $this->assertSame(['120.00', '110.00'], $amounts);
    }

    public function test_historial_404_para_lote_oculto_a_bidder(): void
    {
        $lot = $this->createOpenLot('draft');

        $this->actingAsRole('bidder');
        $this->getJson("/api/v1/lots/{$lot->guid}/bids")->assertNotFound();
    }
}
