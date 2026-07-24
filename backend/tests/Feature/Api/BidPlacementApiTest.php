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

class BidPlacementApiTest extends TestCase
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

    private function createOpenLot(?Auction $auction = null, array $overrides = []): Lot
    {
        $auction ??= Auction::create(['title' => 'Subasta', 'starts_at' => now(), 'status' => 'live']);
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

    // --- Valid bid ---

    public function test_oferta_valida_actualiza_columnas_de_cache_en_la_misma_transaccion(): void
    {
        $lot = $this->createOpenLot();
        $bidder = $this->actingAsRole('bidder');

        $response = $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00']);

        $response->assertCreated()->assertJsonPath('data.amount', '110.00');

        $this->assertDatabaseCount('bids', 1);
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'current_price' => 110.00,
            'current_winner_user_id' => $bidder->id,
        ]);
    }

    public function test_primera_oferta_puede_igualar_el_precio_base(): void
    {
        $lot = $this->createOpenLot();
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '100.00'])
            ->assertCreated();
    }

    // --- Rejections ---

    public function test_rechaza_oferta_bajo_el_incremento(): void
    {
        $lot = $this->createOpenLot();
        $this->actingAsRole('bidder');

        // First bid establishes current_price=110 (floor was starting_price=100).
        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])
            ->assertCreated();

        // New floor is 110 + 10 = 120; 115 is below it.
        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '115.00'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('bids', 1);
        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'current_price' => 110.00]);
    }

    public function test_primera_oferta_bajo_el_precio_base_menciona_el_precio_base(): void
    {
        $lot = $this->createOpenLot();
        $this->actingAsRole('bidder');

        // No current price yet, so the floor is the base price (100.00); the
        // message must name the base price, not a non-existent "precio actual".
        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '50.00'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', 'La primera oferta debe ser mayor o igual al precio base ($100.00).');

        $this->assertDatabaseCount('bids', 0);
    }

    public function test_oferta_posterior_bajo_el_minimo_menciona_precio_actual_mas_incremento(): void
    {
        $lot = $this->createOpenLot();
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])
            ->assertCreated();

        // Floor is now 110 + 10 = 120; a below-floor bid names that minimum.
        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '115.00'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', 'La oferta debe ser mayor o igual al mínimo ($120.00): precio actual más el incremento.');
    }

    public function test_rechaza_oferta_en_lote_no_abierto(): void
    {
        $lot = $this->createOpenLot(overrides: ['status' => 'scheduled']);
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('bids', 0);
    }

    public function test_rechaza_oferta_cuando_subasta_padre_no_esta_en_vivo(): void
    {
        $auction = Auction::create(['title' => 'Subasta', 'starts_at' => now(), 'status' => 'scheduled']);
        $lot = $this->createOpenLot($auction);
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('bids', 0);
    }

    public function test_rechaza_monto_con_tres_decimales(): void
    {
        $lot = $this->createOpenLot();
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.505'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('bids', 0);
    }

    public function test_rechaza_monto_enviado_como_numero_json_en_vez_de_string(): void
    {
        $lot = $this->createOpenLot();
        $this->actingAsRole('bidder');

        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => 110.50])
            ->assertUnprocessable();

        $this->assertDatabaseCount('bids', 0);
    }

    // --- Concurrency ---

    public function test_segunda_oferta_calculada_contra_piso_desactualizado_es_rechazada(): void
    {
        $lot = $this->createOpenLot();

        $bidderA = User::factory()->create(['first_name' => 'A', 'last_name' => 'Bidder']);
        $bidderA->assignRole('bidder');

        // Both bidders read the SAME stale floor (100 + 10 = 110) before either commits.
        Sanctum::actingAs($bidderA);
        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])->assertCreated();

        $bidderB = User::factory()->create(['first_name' => 'B', 'last_name' => 'Bidder']);
        $bidderB->assignRole('bidder');
        Sanctum::actingAs($bidderB);

        // Stale bid: computed against the OLD floor (110), now below the new floor (120).
        $this->postJson("/api/v1/lots/{$lot->guid}/bids", ['amount' => '110.00'])->assertUnprocessable();

        $this->assertDatabaseCount('bids', 1);
        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'current_price' => 110.00, 'current_winner_user_id' => $bidderA->id]);
    }
}
