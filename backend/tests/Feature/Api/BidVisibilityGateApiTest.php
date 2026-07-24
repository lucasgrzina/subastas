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

/**
 * tasks obs#41 risk #1 (CRITICAL): the bidder Lot/Auction visibility gate
 * fires via CreateBidRequest::authorize() — BEFORE rules() — so a hidden
 * auction returns 404 independent of the submitted amount's validity.
 */
class BidVisibilityGateApiTest extends TestCase
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

    private function createLotUnder(string $auctionStatus): Lot
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

    public function test_oferta_con_monto_valido_en_lote_de_subasta_borrador_devuelve_404(): void
    {
        $lot = $this->createLotUnder('draft');
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])
            ->assertNotFound();

        $this->assertDatabaseCount('bids', 0);
    }

    public function test_oferta_con_monto_invalido_en_lote_de_subasta_cancelada_devuelve_404_no_422(): void
    {
        $lot = $this->createLotUnder('cancelled');
        $this->actingAsRole('bidder');

        // Invalid decimal precision — but the visibility gate must fire FIRST.
        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.505'])
            ->assertNotFound();

        $this->assertDatabaseCount('bids', 0);
    }

    public function test_oferta_permitida_en_lote_de_subasta_en_vivo(): void
    {
        $lot = $this->createLotUnder('live');
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])
            ->assertCreated();
    }
}
