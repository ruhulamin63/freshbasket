<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroceryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'unit' => ['sometimes', 'required', 'string', 'max:32'],
            'unit_price_cents' => ['sometimes', 'required', 'integer', 'min:0', 'max:999999999'],
            'stock_quantity' => ['prohibited'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
