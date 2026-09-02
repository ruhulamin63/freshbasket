<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Exceptions\AccessManagementException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlService
{
    /** @return Collection<int, Role> */
    public function roles(): Collection
    {
        return Role::query()->where('guard_name', 'api')->with('permissions')->withCount('users')->orderBy('id')->get();
    }

    /** @return Collection<int, Permission> */
    public function permissions(): Collection
    {
        return Permission::query()->where('guard_name', 'api')->orderBy('name')->get();
    }

    /** @param array{name: string, permissions: list<string>} $data */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::query()->create(['name' => $data['name'], 'guard_name' => 'api']);
            $role->syncPermissions($this->permissionModels($data['permissions']));
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->load('permissions')->loadCount('users');
        });
    }

    /** @param array{name: string, permissions: list<string>} $data */
    public function update(Role $role, array $data): Role
    {
        $this->ensureCustom($role);

        return DB::transaction(function () use ($role, $data) {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($this->permissionModels($data['permissions']));
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->load('permissions')->loadCount('users');
        });
    }

    public function delete(Role $role): void
    {
        $this->ensureCustom($role);
        if ($role->users()->exists()) {
            throw new AccessManagementException('Reassign users before deleting this role.', 'role_in_use');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensureCustom(Role $role): void
    {
        if (in_array($role->name, array_column(RoleName::cases(), 'value'), true)) {
            throw new AccessManagementException('System roles cannot be changed or deleted.', 'system_role_protected');
        }
    }

    /** @param list<string> $names @return Collection<int, Permission> */
    private function permissionModels(array $names): Collection
    {
        $allowed = array_column(PermissionName::cases(), 'value');
        if (array_diff($names, $allowed)) {
            throw new AccessManagementException('One or more selected permissions are invalid.', 'invalid_permission');
        }

        return Permission::query()->where('guard_name', 'api')->whereIn('name', $names)->get();
    }
}
