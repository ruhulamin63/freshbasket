<?php

namespace App\Http\Resources;

use App\Enums\RoleName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_system' => in_array($this->name, array_column(RoleName::cases(), 'value'), true),
            'users_count' => (int) ($this->users_count ?? 0),
            'permissions' => $this->permissions->pluck('name')->values(),
        ];
    }
}
