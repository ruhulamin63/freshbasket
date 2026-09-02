<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\RoleName;
use App\Exceptions\AccessManagementException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function paginate(?string $search, ?string $role, ?bool $isActive, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->users->paginateAdmin($search, $role, $isActive, $perPage, $page);
    }

    public function find(int $id): User
    {
        return $this->users->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roles = $this->roles($data['roles']);
            $user = $this->users->create(Arr::except($data, ['roles', 'password_confirmation']));
            $user->syncRoles($roles);

            return $this->users->findOrFail($user->id);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, User $user, array $data): User
    {
        return DB::transaction(function () use ($actor, $user, $data) {
            $roles = $this->roles($data['roles']);
            $roleNames = $roles->pluck('name');
            $removesAdminAccess = $user->hasRole(RoleName::Admin->value)
                && (! $data['is_active'] || ! $roleNames->contains(RoleName::Admin->value));

            if ($actor->is($user) && (! $data['is_active'] || ! $roleNames->contains(RoleName::Admin->value))) {
                throw new AccessManagementException('You cannot deactivate yourself or remove your own administrator role.', 'self_access_removal');
            }

            if ($removesAdminAccess && $this->users->countActiveWithRole(RoleName::Admin->value) <= 1) {
                throw new AccessManagementException('At least one active administrator must remain.', 'last_administrator');
            }

            $attributes = Arr::except($data, ['roles', 'password_confirmation']);
            if (blank($attributes['password'] ?? null)) {
                unset($attributes['password']);
            }
            $this->users->update($user, $attributes);
            $user->syncRoles($roles);

            return $this->users->findOrFail($user->id);
        });
    }

    /** @param list<string> $names */
    private function roles(array $names)
    {
        $roles = Role::query()->where('guard_name', 'api')->whereIn('name', $names)->get();
        if ($roles->count() !== count($names)) {
            throw new AccessManagementException('One or more selected roles are invalid.', 'invalid_role');
        }

        return $roles;
    }
}
