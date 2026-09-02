<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\GroceryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_cannot_access_admin_catalogue_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::User->value);

        $this->actingAs($user, 'api')->postJson('/api/v1/admin/groceries', [
            'name' => 'Rice',
            'unit' => '1 kg',
            'unit_price_cents' => 10000,
            'stock_quantity' => 10,
            'is_active' => true,
        ])->assertForbidden();
    }

    public function test_guest_can_browse_available_groceries_but_cannot_access_orders(): void
    {
        $available = GroceryItem::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        GroceryItem::factory()->create(['is_active' => true, 'stock_quantity' => 0]);
        GroceryItem::factory()->create(['is_active' => false, 'stock_quantity' => 5]);

        $this->getJson('/api/v1/groceries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $available->id);

        $this->getJson("/api/v1/groceries/{$available->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $available->id);

        $this->getJson('/api/v1/orders')->assertUnauthorized();
        $this->postJson('/api/v1/orders', [
            'items' => [['grocery_item_id' => $available->id, 'quantity' => 1]],
        ])->assertUnauthorized();
    }

    public function test_admin_can_create_and_manage_stock_but_stock_is_not_changed_by_catalogue_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);

        $id = $this->actingAs($admin, 'api')->postJson('/api/v1/admin/groceries', [
            'name' => 'Rice',
            'unit' => '1 kg',
            'unit_price_cents' => 10000,
            'stock_quantity' => 10,
            'is_active' => true,
        ])->assertCreated()->json('data.id');

        $this->actingAs($admin, 'api')->patchJson("/api/v1/admin/groceries/{$id}", [
            'name' => 'Premium Rice',
            'stock_quantity' => 999,
        ])->assertUnprocessable();

        $this->actingAs($admin, 'api')->patchJson("/api/v1/admin/groceries/{$id}/stock", [
            'stock_quantity' => 14,
        ])->assertOk()->assertJsonPath('data.stock_quantity', 14);
    }

    public function test_admin_can_filter_catalogue_by_active_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $active = GroceryItem::factory()->create(['is_active' => true]);
        $inactive = GroceryItem::factory()->create(['is_active' => false]);

        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/groceries?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);

        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/groceries?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactive->id);
    }
}
