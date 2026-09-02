<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\GroceryItem;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleName::User->value);
    }

    public function test_checkout_uses_locked_database_price_and_stores_historical_snapshots(): void
    {
        $rice = GroceryItem::factory()->create([
            'name' => 'Basmati Rice', 'unit' => '1 kg', 'unit_price_cents' => 12550, 'stock_quantity' => 10,
        ]);
        $oil = GroceryItem::factory()->create([
            'name' => 'Sunflower Oil', 'unit' => '1 litre', 'unit_price_cents' => 9900, 'stock_quantity' => 5,
        ]);

        $orderId = $this->actingAs($this->user, 'api')->postJson('/api/v1/orders', [
            'user_id' => 999,
            'total_amount_cents' => 1,
            'items' => [
                ['grocery_item_id' => $rice->id, 'quantity' => 2, 'unit_price_cents' => 1],
                ['grocery_item_id' => $oil->id, 'quantity' => 1, 'unit_price_cents' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.total_amount_cents', 35000)
            ->json('data.id');

        $order = Order::query()->with('items')->findOrFail($orderId);
        $this->assertSame($this->user->id, $order->user_id);
        $this->assertSame(35000, $order->total_amount_cents);
        $this->assertSame('Basmati Rice', $order->items->firstWhere('grocery_item_id', $rice->id)->item_name);
        $this->assertSame(8, $rice->refresh()->stock_quantity);
        $this->assertSame(4, $oil->refresh()->stock_quantity);

        $rice->update(['name' => 'Renamed Rice', 'unit_price_cents' => 50000]);
        $snapshot = $order->items->firstWhere('grocery_item_id', $rice->id)->refresh();
        $this->assertSame('Basmati Rice', $snapshot->item_name);
        $this->assertSame(12550, $snapshot->unit_price_cents);
    }

    public function test_insufficient_stock_rolls_back_the_entire_order(): void
    {
        $available = GroceryItem::factory()->create(['stock_quantity' => 10]);
        $scarce = GroceryItem::factory()->create(['name' => 'Scarce Eggs', 'stock_quantity' => 1]);

        $this->actingAs($this->user, 'api')->postJson('/api/v1/orders', [
            'items' => [
                ['grocery_item_id' => $available->id, 'quantity' => 3],
                ['grocery_item_id' => $scarce->id, 'quantity' => 2],
            ],
        ])->assertConflict()->assertJsonPath('error', 'insufficient_stock');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $available->refresh()->stock_quantity);
        $this->assertSame(1, $scarce->refresh()->stock_quantity);
    }

    public function test_sequential_competing_checkouts_cannot_oversell(): void
    {
        $item = GroceryItem::factory()->create(['name' => 'Limited Item', 'stock_quantity' => 5]);
        $other = User::factory()->create();
        $other->assignRole(RoleName::User->value);

        $this->actingAs($this->user, 'api')->postJson('/api/v1/orders', [
            'items' => [['grocery_item_id' => $item->id, 'quantity' => 4]],
        ])->assertCreated();

        $this->actingAs($other, 'api')->postJson('/api/v1/orders', [
            'items' => [['grocery_item_id' => $item->id, 'quantity' => 2]],
        ])->assertConflict();

        $this->assertSame(1, $item->refresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_order_history_is_scoped_to_the_authenticated_user(): void
    {
        $other = User::factory()->create();
        $other->assignRole(RoleName::User->value);
        $item = GroceryItem::factory()->create(['stock_quantity' => 5]);

        $otherOrderId = $this->actingAs($other, 'api')->postJson('/api/v1/orders', [
            'items' => [['grocery_item_id' => $item->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($this->user, 'api')->getJson('/api/v1/orders')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->user, 'api')->getJson("/api/v1/orders/{$otherOrderId}")
            ->assertNotFound();
    }
}
