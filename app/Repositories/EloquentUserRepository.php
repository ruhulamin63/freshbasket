<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function paginateAdmin(?string $search, ?string $role, ?bool $isActive, int $perPage, int $page): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->withCount('orders')
            ->when($search, fn ($query, $term) => $query->where(function ($query) use ($term) {
                $escaped = '%'.addcslashes($term, '%_\\').'%';
                $query->where('name', 'like', $escaped)->orWhere('email', 'like', $escaped);
            }))
            ->when($role, fn ($query, $name) => $query->role($name, 'api'))
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findOrFail(int $id): User
    {
        return User::query()->with('roles')->withCount('orders')->findOrFail($id);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    public function countActiveWithRole(string $role): int
    {
        return User::query()->where('is_active', true)->role($role, 'api')->count();
    }
}
