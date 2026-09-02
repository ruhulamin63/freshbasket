<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $attributes): Order
    {
        return Order::query()->create($attributes);
    }

    public function createItems(Order $order, array $items): void
    {
        $order->items()->createMany($items);
    }

    public function paginateForUser(User $user, int $perPage, int $page): LengthAwarePaginator
    {
        return $user->orders()
            ->with('items')
            ->latest('placed_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findForUserOrFail(User $user, int $orderId): Order
    {
        return $user->orders()->with('items')->findOrFail($orderId);
    }
}
