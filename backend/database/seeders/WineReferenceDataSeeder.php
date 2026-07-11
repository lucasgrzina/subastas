<?php

namespace Database\Seeders;

use App\Models\GrapeVariety;
use App\Models\WineRegion;
use App\Models\Winery;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WineReferenceDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $wineries = ['Bodega Catena Zapata', 'Bodega Norton', 'Trapiche', 'Bodega Luigi Bosca', 'Zuccardi'];
        $grapeVarieties = ['Malbec', 'Cabernet Sauvignon', 'Torrontés', 'Chardonnay', 'Bonarda'];
        $wineRegions = ['Valle de Uco', 'Luján de Cuyo', 'Maipú', 'Cafayate', 'San Rafael'];

        foreach ($wineries as $name) {
            Winery::firstOrCreate(['name' => $name], ['guid' => Str::uuid()->toString()]);
        }

        foreach ($grapeVarieties as $name) {
            GrapeVariety::firstOrCreate(['name' => $name], ['guid' => Str::uuid()->toString()]);
        }

        foreach ($wineRegions as $name) {
            WineRegion::firstOrCreate(['name' => $name], ['guid' => Str::uuid()->toString()]);
        }
    }
}
