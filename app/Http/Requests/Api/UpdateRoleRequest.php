<?php

namespace App\Http\Requests\Api;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('roles', 'name')->where('guard_name', 'api')->ignore($this->route('role'))],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(array_column(PermissionName::cases(), 'value'))],
        ];
    }
}
