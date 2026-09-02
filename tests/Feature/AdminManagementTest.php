<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\GroceryItem;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::Admin->value);
    }

    public function test_admin_can_create_filter_and_update_users(): void
    {
        $userId = $this->actingAs($this->admin, 'api')->postJson('/api/v1/admin/users', [
            'name' => 'Support Operator',
            'email' => 'support@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'is_active' => true,
            'roles' => [RoleName::User->value],
        ])->assertCreated()
            ->assertJsonPath('data.roles.0', RoleName::User->value)
            ->json('data.id');

        $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/users?search=support&role=user&is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $userId);

        $this->actingAs($this->admin, 'api')->patchJson("/api/v1/admin/users/{$userId}", [
            'name' => 'Support Operator Updated',
            'email' => 'support@example.com',
            'password' => null,
            'password_confirmation' => null,
            'is_active' => false,
            'roles' => [RoleName::User->value],
        ])->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertTrue(Hash::check('Password123', User::findOrFail($userId)->password));
    }

    public function test_admin_access_cannot_be_removed_from_self_or_the_last_active_administrator(): void
    {
        $payload = [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'password' => null,
            'password_confirmation' => null,
            'is_active' => false,
            'roles' => [RoleName::User->value],
        ];

        $this->actingAs($this->admin, 'api')->patchJson("/api/v1/admin/users/{$this->admin->id}", $payload)
            ->assertConflict()
            ->assertJsonPath('error', 'self_access_removal');

        $managerRole = Role::create(['name' => 'user-manager', 'guard_name' => 'api']);
        $managerRole->syncPermissions([
            PermissionName::UsersView->value,
            PermissionName::UsersManage->value,
        ]);
        $manager = User::factory()->create();
        $manager->assignRole($managerRole);

        $this->actingAs($manager, 'api')->patchJson("/api/v1/admin/users/{$this->admin->id}", $payload)
            ->assertConflict()
            ->assertJsonPath('error', 'last_administrator');
    }

    public function test_inactive_accounts_cannot_login_or_continue_using_an_existing_token(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'Password123',
            'is_active' => false,
        ]);
        $user->assignRole(RoleName::User->value);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password123',
        ])->assertUnprocessable();

        $this->actingAs($user, 'api')->getJson('/api/v1/auth/me')
            ->assertForbidden();
    }

    public function test_custom_roles_are_permission_driven_and_protected_when_in_use(): void
    {
        $roleId = $this->actingAs($this->admin, 'api')->postJson('/api/v1/admin/roles', [
            'name' => 'order-manager',
            'permissions' => [PermissionName::OrdersViewAll->value, PermissionName::OrdersUpdate->value],
        ])->assertCreated()->json('data.id');

        $manager = User::factory()->create();
        $manager->assignRole('order-manager');

        $this->actingAs($manager, 'api')->getJson('/api/v1/admin/orders')->assertOk();
        $this->actingAs($manager, 'api')->getJson('/api/v1/admin/users')->assertForbidden();

        $this->actingAs($this->admin, 'api')->deleteJson("/api/v1/admin/roles/{$roleId}")
            ->assertConflict()->assertJsonPath('error', 'role_in_use');

        $systemRole = Role::findByName(RoleName::Admin->value, 'api');
        $this->actingAs($this->admin, 'api')->deleteJson("/api/v1/admin/roles/{$systemRole->id}")
            ->assertConflict()->assertJsonPath('error', 'system_role_protected');

        $manager->syncRoles(RoleName::User->value);
        $this->actingAs($this->admin, 'api')->deleteJson("/api/v1/admin/roles/{$roleId}")
            ->assertNoContent();
    }

    public function test_admin_can_follow_order_status_flow_but_cannot_reverse_a_terminal_order(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::User->value);
        $grocery = GroceryItem::factory()->create(['stock_quantity' => 5]);

        $orderId = $this->actingAs($customer, 'api')->postJson('/api/v1/orders', [
            'items' => [['grocery_item_id' => $grocery->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($this->admin, 'api')->patchJson("/api/v1/admin/orders/{$orderId}", [
            'status' => OrderStatus::Processing->value,
        ])->assertOk()->assertJsonPath('data.status', OrderStatus::Processing->value);

        $this->actingAs($this->admin, 'api')->patchJson("/api/v1/admin/orders/{$orderId}", [
            'status' => OrderStatus::Completed->value,
        ])->assertOk()->assertJsonPath('data.status', OrderStatus::Completed->value);

        $this->actingAs($this->admin, 'api')->patchJson("/api/v1/admin/orders/{$orderId}", [
            'status' => OrderStatus::Confirmed->value,
        ])->assertConflict()->assertJsonPath('error', 'invalid_order_status_transition');

        $this->assertSame(OrderStatus::Completed, Order::findOrFail($orderId)->status);
    }
}
