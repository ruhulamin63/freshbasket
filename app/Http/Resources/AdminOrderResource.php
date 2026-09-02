<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'status' => $this->status->value,
            'allowed_statuses' => collect($this->status->allowedTransitions())->pluck('value')->values(),
            'total_amount_cents' => $this->total_amount_cents,
            'placed_at' => $this->placed_at->toISOString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
