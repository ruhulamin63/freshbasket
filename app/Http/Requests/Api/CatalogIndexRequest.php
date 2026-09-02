<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CatalogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 15);
    }

    public function page(): int
    {
        return (int) $this->validated('page', 1);
    }

    public function activeStatus(): ?bool
    {
        if (! $this->has('is_active')) {
            return null;
        }

        return $this->boolean('is_active');
    }
}
