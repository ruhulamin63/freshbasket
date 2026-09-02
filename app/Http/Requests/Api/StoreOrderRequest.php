<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.grocery_item_id' => ['required', 'integer', 'distinct', 'exists:grocery_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
