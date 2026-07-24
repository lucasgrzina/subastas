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

class LotApiTest extends TestCase
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
            'starts_at' => now()->addDay(),
            'status' => 'live',
        ], $overrides));
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'title' => 'Producto de prueba',
            'status' => 'published',
        ], $overrides));
    }

    private function createLot(Auction $auction, array $overrides = [], ?array $products = null): Lot
    {
        $lot = Lot::create(array_merge([
            'auction_id' => $auction->id,
            'lot_number' => 'LOT-1',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'scheduled',
        ], $overrides));

        $product = $products ?? [$this->createProduct()];
        foreach ($product as $p) {
            $lot->products()->attach($p->id, ['quantity' => 1]);
        }

        return $lot->fresh(['auction', 'products']);
    }

    // --- Composition ---

    public function test_admin_crea_lote_con_composicion_multiproducto_y_cantidades(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $productA = $this->createProduct(['title' => 'Producto A']);
        $productB = $this->createProduct(['title' => 'Producto B']);

        $response = $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-100',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'products' => [
                ['product_guid' => $productA->guid, 'quantity' => 1],
                ['product_guid' => $productB->guid, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()->assertJsonCount(2, 'data.products');

        $this->assertDatabaseCount('lots', 1);
        $this->assertDatabaseHas('lot_product', ['product_id' => $productB->id, 'quantity' => 2]);
    }

    public function test_rechaza_producto_no_publicado(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $draftProduct = $this->createProduct(['status' => 'draft']);

        $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-101',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'products' => [['product_guid' => $draftProduct->guid, 'quantity' => 1]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('lots', 0);
    }

    public function test_rechaza_composicion_vacia(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();

        $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-102',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'products' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['products']);

        $this->assertDatabaseCount('lots', 0);
    }

    public function test_rechaza_producto_duplicado_en_payload(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $product = $this->createProduct();

        $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-103',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'products' => [
                ['product_guid' => $product->guid, 'quantity' => 1],
                ['product_guid' => $product->guid, 'quantity' => 2],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['products.0.product_guid']);

        $this->assertDatabaseCount('lots', 0);
    }

    // --- Open-transition guard ---

    public function test_rechaza_apertura_de_lote_si_subasta_no_esta_en_vivo(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction(['status' => 'scheduled']);
        $product = $this->createProduct();

        $response = $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-104',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'open',
            'products' => [['product_guid' => $product->guid, 'quantity' => 1]],
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('lots', 0);
    }

    public function test_rechaza_transicion_a_open_via_update_si_subasta_no_esta_en_vivo(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction(['status' => 'scheduled']);
        $lot = $this->createLot($auction);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['status' => 'open'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'status' => 'scheduled']);
    }

    public function test_admin_abre_lote_cuando_subasta_esta_en_vivo(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction(['status' => 'live']);
        $lot = $this->createLot($auction);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['status' => 'open'])
            ->assertOk()
            ->assertJsonPath('data.status', 'open');
    }

    // --- Permission matrix ---

    public function test_operador_no_puede_escribir_pero_puede_leer(): void
    {
        $this->actingAsRole('operador');
        $auction = $this->createAuction();
        $lot = $this->createLot($auction);
        $product = $this->createProduct();

        $this->getJson('/api/v1/lots')->assertOk();
        $this->getJson("/api/v1/lots/{$lot->guid}")->assertOk();

        $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'X',
            'starting_price' => '1.00',
            'bid_increment' => '1.00',
            'products' => [['product_guid' => $product->guid, 'quantity' => 1]],
        ])->assertForbidden();

        $this->putJson("/api/v1/lots/{$lot->guid}", ['lot_number' => 'x'])->assertForbidden();
        $this->deleteJson("/api/v1/lots/{$lot->guid}")->assertForbidden();
    }

    public function test_bidder_bloqueado_de_gestion(): void
    {
        $this->actingAsRole('bidder');
        $auction = $this->createAuction();
        $lot = $this->createLot($auction);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['lot_number' => 'x'])->assertForbidden();
        $this->deleteJson("/api/v1/lots/{$lot->guid}")->assertForbidden();
    }

    // --- Bidder visibility scope ---

    public function test_bidder_ve_todos_los_estados_de_lote_propio(): void
    {
        $auction = $this->createAuction(['status' => 'live']);
        $scheduled = $this->createLot($auction, ['lot_number' => 'A', 'status' => 'scheduled']);
        $open = $this->createLot($auction, ['lot_number' => 'B', 'status' => 'open']);

        $this->actingAsRole('bidder');

        $response = $this->getJson('/api/v1/lots?per_page=50')->assertOk();
        $guids = collect($response->json('data.data'))->pluck('guid');

        $this->assertTrue($guids->contains($scheduled->guid));
        $this->assertTrue($guids->contains($open->guid));

        $this->getJson("/api/v1/lots/{$scheduled->guid}")->assertOk();
    }

    public function test_bidder_no_ve_lote_de_subasta_draft_o_cancelada(): void
    {
        $draftAuction = $this->createAuction(['status' => 'draft']);
        $cancelledAuction = $this->createAuction(['status' => 'cancelled']);
        $lotUnderDraft = $this->createLot($draftAuction, ['lot_number' => 'D']);
        $lotUnderCancelled = $this->createLot($cancelledAuction, ['lot_number' => 'C']);

        $this->actingAsRole('bidder');

        $response = $this->getJson('/api/v1/lots?per_page=50')->assertOk();
        $guids = collect($response->json('data.data'))->pluck('guid');

        $this->assertFalse($guids->contains($lotUnderDraft->guid));
        $this->assertFalse($guids->contains($lotUnderCancelled->guid));

        $this->getJson("/api/v1/lots/{$lotUnderDraft->guid}")->assertNotFound();
        $this->getJson("/api/v1/lots/{$lotUnderCancelled->guid}")->assertNotFound();
        $this->getJson("/api/v1/lots/{$lotUnderDraft->guid}/bids")->assertNotFound();
    }

    // --- Editorial title / derived display_title / type ---

    public function test_crea_lote_con_titulo_explicito(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $product = $this->createProduct();

        $response = $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-200',
            'title' => 'Vertical Catena 2015-2020',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'products' => [['product_guid' => $product->guid, 'quantity' => 1]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Vertical Catena 2015-2020')
            ->assertJsonPath('data.display_title', 'Vertical Catena 2015-2020');

        $this->assertDatabaseHas('lots', ['lot_number' => 'LOT-200', 'title' => 'Vertical Catena 2015-2020']);
    }

    public function test_display_title_deriva_del_producto_unico_sin_titulo(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $lot = $this->createLot($auction, ['lot_number' => 'LOT-201'], [$this->createProduct(['title' => 'Malbec Reserva'])]);

        $response = $this->getJson("/api/v1/lots/{$lot->guid}")->assertOk();

        $response->assertJsonPath('data.title', null)
            ->assertJsonPath('data.display_title', 'Malbec Reserva')
            ->assertJsonPath('data.type', 'single');
    }

    public function test_display_title_es_etiqueta_por_cantidad_cuando_producto_unico_tiene_cantidad_mayor_a_uno(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $product = $this->createProduct(['title' => 'Malbec Reserva']);

        $lot = Lot::create([
            'auction_id' => $auction->id,
            'lot_number' => 'LOT-202',
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'status' => 'scheduled',
        ]);
        $lot->products()->attach($product->id, ['quantity' => 2]);

        $response = $this->getJson("/api/v1/lots/{$lot->guid}")->assertOk();

        $response->assertJsonPath('data.display_title', 'Lote LOT-202 — 1 productos')
            ->assertJsonPath('data.type', 'bundle');
    }

    public function test_display_title_es_etiqueta_por_cantidad_cuando_hay_multiples_productos(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $productA = $this->createProduct(['title' => 'Producto A']);
        $productB = $this->createProduct(['title' => 'Producto B']);

        $lot = $this->createLot($auction, ['lot_number' => 'LOT-203'], [$productA, $productB]);

        $response = $this->getJson("/api/v1/lots/{$lot->guid}")->assertOk();

        $response->assertJsonPath('data.display_title', 'Lote LOT-203 — 2 productos')
            ->assertJsonPath('data.type', 'bundle');
    }

    public function test_titulo_explicito_no_afecta_el_type(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $lot = $this->createLot($auction, ['lot_number' => 'LOT-204', 'title' => 'Título editorial']);

        $response = $this->getJson("/api/v1/lots/{$lot->guid}")->assertOk();

        $response->assertJsonPath('data.display_title', 'Título editorial')
            ->assertJsonPath('data.type', 'single');
    }

    public function test_put_actualiza_titulo_y_persiste_regresion_de_whitelist(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $lot = $this->createLot($auction, ['lot_number' => 'LOT-205']);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['title' => 'Reserva Especial'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Reserva Especial')
            ->assertJsonPath('data.display_title', 'Reserva Especial');

        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'title' => 'Reserva Especial']);

        $this->getJson("/api/v1/lots/{$lot->guid}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Reserva Especial');
    }

    public function test_put_limpia_titulo_y_revierte_a_display_title_derivado(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $lot = $this->createLot($auction, ['lot_number' => 'LOT-206', 'title' => 'Reserva Especial'], [$this->createProduct(['title' => 'Malbec Reserva'])]);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['title' => null])
            ->assertOk()
            ->assertJsonPath('data.title', null)
            ->assertJsonPath('data.display_title', 'Malbec Reserva')
            ->assertJsonPath('data.type', 'single');
    }

    public function test_rechaza_titulo_mayor_a_255_caracteres_en_create_y_update(): void
    {
        $this->actingAsRole('admin');
        $auction = $this->createAuction();
        $product = $this->createProduct();
        $longTitle = str_repeat('a', 256);

        $this->postJson('/api/v1/lots', [
            'auction_guid' => $auction->guid,
            'lot_number' => 'LOT-207',
            'title' => $longTitle,
            'starting_price' => '100.00',
            'bid_increment' => '10.00',
            'products' => [['product_guid' => $product->guid, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['title']);

        $lot = $this->createLot($auction, ['lot_number' => 'LOT-208']);

        $this->putJson("/api/v1/lots/{$lot->guid}", ['title' => $longTitle])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }
}
