<?php

namespace App\Http\Requests\Api;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminOrderIndexRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'date_range' => ['nullable', Rule::in(['today', '7days', '30days'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function status(): ?OrderStatus
    {
        $status = $this->validated('status');

        return $status ? OrderStatus::from($status) : null;
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 15);
    }

    public function page(): int
    {
        return (int) $this->validated('page', 1);
    }
}
