<?php

namespace Tests\Feature\Api;

use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Services\AllergenDerivationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllergenDerivationTest extends TestCase
{
    use RefreshDatabase;

    private AllergenDerivationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->service = app(AllergenDerivationService::class);
    }

    public function test_deriva_alergenos_desde_ingredientes_no_opcionales(): void
    {
        $recipe = Recipe::where('slug', 'panqueques-clasicos')->first();

        // El seeder ya corrió la derivación. Verificamos el resultado.
        $allergens = $recipe->allergens()->where('relation_type', 'contiene')->pluck('clave');

        $this->assertContains('gluten', $allergens);   // harina 0000
        $this->assertContains('huevos', $allergens);   // huevo
        $this->assertContains('leche', $allergens);    // leche entera + manteca
    }

    public function test_ingrediente_opcional_no_genera_contiene(): void
    {
        $recipe = Recipe::where('slug', 'panqueques-clasicos')->first();
        $maní = Ingredient::where('slug', 'mani')->first()
            ?? Ingredient::create(['nombre' => 'Maní', 'slug' => 'mani']);
        $maní->allergens()->syncWithoutDetaching(
            [Allergen::where('clave', 'mani')->first()->id]
        );

        // Agregar maní como ingrediente OPCIONAL.
        $recipe->ingredients()->create([
            'ingredient_id' => $maní->id,
            'cantidad' => 50,
            'unit_id' => Unit::where('clave', 'g')->first()->id,
            'opcional' => true,
            'orden' => 99,
        ]);

        $this->service->deriveForRecipe($recipe->fresh());

        $contiene = $recipe->fresh()->allergens()
            ->where('relation_type', 'contiene')->pluck('clave');

        $this->assertNotContains('mani', $contiene, 'Un ingrediente opcional no debe generar alérgeno "contiene".');
    }

    public function test_observer_dispara_derivacion_al_guardar_ingrediente(): void
    {
        $recipe = Recipe::where('slug', 'ensalada-tomate-albahaca')->first();

        // La ensalada no tiene alérgenos derivados.
        $this->assertEmpty($recipe->allergens()->where('relation_type', 'contiene')->get());

        // Agregamos un ingrediente con alérgeno (queso parmesano → leche).
        $parmesano = Ingredient::where('slug', 'queso-parmesano')->first();
        $recipe->ingredients()->create([
            'ingredient_id' => $parmesano->id,
            'cantidad' => 30,
            'unit_id' => Unit::where('clave', 'g')->first()->id,
            'opcional' => false,
            'orden' => 99,
        ]);

        // El observer debe haber derivado automáticamente.
        $contiene = $recipe->fresh()->allergens()
            ->where('relation_type', 'contiene')->pluck('clave');

        $this->assertContains('leche', $contiene);
    }

    public function test_eliminar_ingrediente_recalcula_alergenos(): void
    {
        $recipe = Recipe::where('slug', 'panqueques-clasicos')->first();

        // Verificamos que gluten está derivado (por la harina).
        $this->assertContains(
            'gluten',
            $recipe->allergens()->where('relation_type', 'contiene')->pluck('clave')
        );

        // Eliminamos la harina.
        $harina = Ingredient::where('slug', 'harina-0000')->first();
        $recipe->ingredients()->where('ingredient_id', $harina->id)->first()->delete();

        // Gluten ya no debe estar como 'contiene'.
        $contiene = $recipe->fresh()->allergens()
            ->where('relation_type', 'contiene')->pluck('clave');

        $this->assertNotContains('gluten', $contiene);
    }

    public function test_puede_contener_manual_no_es_borrado_por_derivacion(): void
    {
        $recipe = Recipe::where('slug', 'ensalada-tomate-albahaca')->first();
        $sesamo = Allergen::where('clave', 'sesamo')->first();

        // Editor agrega 'puede_contener' manualmente (trazas por contaminación cruzada).
        $recipe->allergens()->attach($sesamo->id, ['relation_type' => 'puede_contener']);

        // Corremos derivación (no hay ingredientes con sésamo).
        $this->service->deriveForRecipe($recipe->fresh());

        $tipo = \Illuminate\Support\Facades\DB::table('recipe_allergen')
            ->where('recipe_id', $recipe->id)
            ->where('allergen_id', $sesamo->id)
            ->value('relation_type');

        $this->assertEquals('puede_contener', $tipo, 'La derivación no debe borrar entradas manuales de puede_contener.');
    }

    public function test_upgrade_de_puede_contener_a_contiene(): void
    {
        $recipe = Recipe::where('slug', 'ensalada-tomate-albahaca')->first();
        $leche  = Allergen::where('clave', 'leche')->first();

        // Editor cargó manualmente 'puede_contener' leche.
        $recipe->allergens()->attach($leche->id, ['relation_type' => 'puede_contener']);

        // Ahora agregamos un ingrediente no-opcional que tiene leche (manteca).
        $manteca = Ingredient::where('slug', 'manteca')->first();
        $recipe->ingredients()->create([
            'ingredient_id' => $manteca->id,
            'cantidad' => 20,
            'unit_id' => Unit::where('clave', 'g')->first()->id,
            'opcional' => false,
            'orden' => 99,
        ]);

        // Observer disparó derivación. 'puede_contener' debe haberse upgrado a 'contiene'.
        $tipo = \Illuminate\Support\Facades\DB::table('recipe_allergen')
            ->where('recipe_id', $recipe->id)
            ->where('allergen_id', $leche->id)
            ->value('relation_type');

        $this->assertEquals('contiene', $tipo, '"puede_contener" debe upgradarse a "contiene" cuando el ingrediente es no-opcional.');
    }
}
