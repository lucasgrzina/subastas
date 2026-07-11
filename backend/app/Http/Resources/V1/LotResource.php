<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'guid' => $this->guid,
            'lot_number' => $this->lot_number,
            'starting_price' => $this->starting_price,
            'bid_increment' => $this->bid_increment,
            'reserve_price' => $this->reserve_price,
            'status' => $this->status,
            'current_price' => $this->current_price,
            'current_bid' => $this->whenLoaded('currentBid', fn () => $this->currentBid ? new BidResource($this->currentBid) : null),
            'current_winner' => $this->whenLoaded('currentWinner', fn () => $this->currentWinner ? [
                'guid' => $this->currentWinner->guid,
                'name' => $this->currentWinner->name,
            ] : null),
            'auction' => $this->whenLoaded('auction', fn () => new AuctionResource($this->auction)),
            'products' => $this->whenLoaded('products', fn () => $this->products->map(fn ($product) => [
                'guid' => $product->guid,
                'title' => $product->title,
                'quantity' => $product->pivot->quantity,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
