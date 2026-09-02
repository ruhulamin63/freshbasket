<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /** @param array{name: string, email: string, password: string} $attributes */
    public function create(array $attributes): User;

    public function paginateAdmin(?string $search, ?string $role, ?bool $isActive, int $perPage, int $page): LengthAwarePaginator;

    public function findOrFail(int $id): User;

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User;

    public function countActiveWithRole(string $role): int;
}
