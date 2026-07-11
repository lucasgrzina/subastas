<?php

namespace Tests\Feature\Api;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BidImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function createBid(): Bid
    {
        $auction = Auction::create(['title' => 'Subasta', 'starts_at' => now(), 'status' => 'live']);
        $product = Product::create(['title' => 'Producto', 'status' => 'published']);
        $lot = Lot::create([
            'auction_id' => $auction->id,
            'lot_number' => 'LOT-1',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'open',
        ]);
        $lot->products()->attach($product->id, ['quantity' => 1]);

        $bidder = User::factory()->create(['first_name' => 'Bidder', 'last_name' => 'User']);
        $bidder->assignRole('bidder');

        return Bid::create(['lot_id' => $lot->id, 'user_id' => $bidder->id, 'amount' => '110.00']);
    }

    public function test_actualizar_una_oferta_cargada_lanza_excepcion(): void
    {
        $bid = $this->createBid();

        $this->expectException(\RuntimeException::class);

        $bid->amount = '999.00';
        $bid->save();
    }

    public function test_eliminar_una_oferta_cargada_lanza_excepcion(): void
    {
        $bid = $this->createBid();

        $this->expectException(\RuntimeException::class);

        $bid->delete();
    }
}
