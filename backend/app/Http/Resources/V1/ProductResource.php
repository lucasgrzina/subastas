<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mainImage = $this->images?->firstWhere('is_main', true);

        return [
            'guid' => $this->guid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'wine_details' => $this->whenLoaded('wineDetail', fn () => $this->wineDetail ? [
                'year' => $this->wineDetail->year,
                'winery' => new ReferenceOptionResource($this->wineDetail->winery),
                'grape_variety' => new ReferenceOptionResource($this->wineDetail->grapeVariety),
                'wine_region' => new ReferenceOptionResource($this->wineDetail->wineRegion),
            ] : null),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'main_image_url' => $mainImage ? Storage::disk(config('uploads.disk'))->url($mainImage->path) : null,
        ];
    }
}
