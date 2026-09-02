<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionName::cases())->mapWithKeys(function (PermissionName $permission) {
            $model = Permission::findOrCreate($permission->value, 'api');

            return [$permission->value => $model];
        });

        $admin = Role::findOrCreate(RoleName::Admin->value, 'api');
        $user = Role::findOrCreate(RoleName::User->value, 'api');

        $admin->syncPermissions($permissions->values());
        $user->syncPermissions($permissions->only([
            PermissionName::GroceriesView->value,
            PermissionName::OrdersCreate->value,
            PermissionName::OrdersViewOwn->value,
        ])->values());
    }
}
