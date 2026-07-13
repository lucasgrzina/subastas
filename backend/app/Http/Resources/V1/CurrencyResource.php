<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'guid' => $this->guid,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),

        ];
    }
}
