<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserIndexRequest extends FormRequest
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
            'role' => ['nullable', 'string', 'max:255', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
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
        return $this->has('is_active') ? $this->boolean('is_active') : null;
    }
}
