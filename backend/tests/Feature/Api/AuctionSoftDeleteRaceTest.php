<?php

namespace Tests\Feature\Api;

use App\Models\Auction;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use App\Services\LotService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * tasks obs#41 risk #6 (SUGGESTION): placeBid() against a Lot whose parent
 * Auction was soft-deleted mid-flight must return a 422-equivalent
 * ValidationException, not crash with a null-pointer error — the
 * sharedLock() re-read of Auction excludes soft-deleted rows via
 * Eloquent's default global scope.
 *
 * This exercises LotService::placeBid() directly (not through the HTTP
 * layer) because the controller-level bidder-visibility gate would itself
 * intercept a request for a hidden auction with 404 before ever reaching
 * placeBid() — this test isolates the SERVICE-layer guard specifically.
 */
class AuctionSoftDeleteRaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_placebid_sobre_lote_con_subasta_borrada_lanza_validation_exception_no_crash(): void
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

        // Simulates the race: the parent Auction is soft-deleted between the
        // Lot fetch and the bid's transactional shared-lock re-read.
        $auction->delete();

        $bidder = User::factory()->create(['first_name' => 'Bidder', 'last_name' => 'User']);
        $bidder->assignRole('bidder');

        $threw = false;

        try {
            app(LotService::class)->placeBid($lot->fresh(), $bidder, '110.00');
        } catch (ValidationException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected a ValidationException (422-equivalent), not a crash.');
        $this->assertDatabaseCount('bids', 0);
    }
}
