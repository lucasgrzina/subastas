<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWineDetail extends Model
{
    protected $fillable = [
        'product_id',
        'year',
        'winery_id',
        'grape_variety_id',
        'wine_region_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function winery(): BelongsTo
    {
        return $this->belongsTo(Winery::class);
    }

    public function grapeVariety(): BelongsTo
    {
        return $this->belongsTo(GrapeVariety::class);
    }

    public function wineRegion(): BelongsTo
    {
        return $this->belongsTo(WineRegion::class);
    }
}
