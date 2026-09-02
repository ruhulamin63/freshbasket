<?php

namespace App\Services;

use App\Contracts\Cache\CatalogCacheInterface;
use App\Contracts\Repositories\GroceryRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Enums\OrderStatus;
use App\Exceptions\GroceryUnavailableException;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly GroceryRepositoryInterface $groceries,
        private readonly OrderRepositoryInterface $orders,
        private readonly CatalogCacheInterface $cache,
    ) {}

    /** @param list<array{grocery_item_id: int, quantity: int}> $requestedItems */
    public function place(User $user, array $requestedItems): Order
    {
        return DB::transaction(function () use ($user, $requestedItems) {
            $quantities = collect($requestedItems)->mapWithKeys(
                fn (array $item) => [(int) $item['grocery_item_id'] => (int) $item['quantity']]
            );
            $ids = $quantities->keys()->sort()->values()->all();
            $groceries = $this->groceries->lockAvailableByIds($ids);

            if ($groceries->count() !== count($ids)) {
                throw new GroceryUnavailableException;
            }

            $total = 0;
            $snapshots = [];

            foreach ($ids as $id) {
                $grocery = $groceries->get($id);
                $quantity = $quantities->get($id);

                if ($grocery->stock_quantity < $quantity) {
                    throw new InsufficientStockException($grocery->name);
                }

                $subtotal = $grocery->unit_price_cents * $quantity;
                $total += $subtotal;
                $snapshots[] = [
                    'grocery_item_id' => $grocery->id,
                    'item_name' => $grocery->name,
                    'unit' => $grocery->unit,
                    'unit_price_cents' => $grocery->unit_price_cents,
                    'quantity' => $quantity,
                    'subtotal_cents' => $subtotal,
                ];
            }

            $order = $this->orders->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Confirmed,
                'total_amount_cents' => $total,
                'placed_at' => now(),
            ]);
            $this->orders->createItems($order, $snapshots);

            foreach ($ids as $id) {
                $this->groceries->decrementLockedStock($groceries->get($id), $quantities->get($id));
            }

            DB::afterCommit(fn () => $this->cache->invalidate());

            return $order->load('items');
        }, 3);
    }

    public function history(User $user, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->orders->paginateForUser($user, $perPage, $page);
    }

    public function findOwn(User $user, int $orderId): Order
    {
        return $this->orders->findForUserOrFail($user, $orderId);
    }
}
