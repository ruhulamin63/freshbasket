<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'total_amount_cents' => $this->total_amount_cents,
            'total' => number_format($this->total_amount_cents / 100, 2, '.', ''),
            'placed_at' => $this->placed_at->toISOString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
