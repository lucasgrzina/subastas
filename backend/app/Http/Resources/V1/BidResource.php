<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'guid' => $this->guid,
            'amount' => $this->amount,
            'user' => $this->whenLoaded('user', fn () => [
                'guid' => $this->user->guid,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
