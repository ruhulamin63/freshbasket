<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'grocery_item_id' => $this->grocery_item_id,
            'name' => $this->item_name,
            'unit' => $this->unit,
            'unit_price_cents' => $this->unit_price_cents,
            'quantity' => $this->quantity,
            'subtotal_cents' => $this->subtotal_cents,
            'subtotal' => number_format($this->subtotal_cents / 100, 2, '.', ''),
        ];
    }
}
