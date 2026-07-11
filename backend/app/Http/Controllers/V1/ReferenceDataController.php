<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ReferenceOptionResource;
use App\Models\GrapeVariety;
use App\Models\WineRegion;
use App\Models\Winery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceDataController extends Controller
{
    public function wineries(Request $request): JsonResponse
    {
        if (! $request->user()->can('products.read')) {
            return $this->makeError(null, 'No tenés permiso para ver esta información.', 403);
        }

        return $this->makeSuccess(ReferenceOptionResource::collection(Winery::orderBy('name')->get()));
    }

    public function grapeVarieties(Request $request): JsonResponse
    {
        if (! $request->user()->can('products.read')) {
            return $this->makeError(null, 'No tenés permiso para ver esta información.', 403);
        }

        return $this->makeSuccess(ReferenceOptionResource::collection(GrapeVariety::orderBy('name')->get()));
    }

    public function wineRegions(Request $request): JsonResponse
    {
        if (! $request->user()->can('products.read')) {
            return $this->makeError(null, 'No tenés permiso para ver esta información.', 403);
        }

        return $this->makeSuccess(ReferenceOptionResource::collection(WineRegion::orderBy('name')->get()));
    }
}
