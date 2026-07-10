<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'guid'              => $this->guid,
            'name'              => $this->name,
            'permissions'       => PermissionResource::collection($this->whenLoaded('permissions')),
            'users_count'       => $this->whenCounted('users'),
            'permissions_count' => $this->whenCounted('permissions'),
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
