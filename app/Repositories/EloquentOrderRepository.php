<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    public function paginateAdmin(?string $search, ?OrderStatus $status, ?string $dateRange, int $perPage, int $page): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'items'])
            ->when($search, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    if (ctype_digit($term)) {
                        $query->orWhereKey((int) $term);
                    }
                    $escaped = '%'.addcslashes($term, '%_\\').'%';
                    $query->orWhereHas('user', fn ($users) => $users
                        ->where('name', 'like', $escaped)
                        ->orWhere('email', 'like', $escaped));
                });
            })
            ->when($status, fn ($query, $value) => $query->where('status', $value->value))
            ->when($dateRange === 'today', fn ($query) => $query->whereDate('placed_at', today()))
            ->when($dateRange === '7days', fn ($query) => $query->where('placed_at', '>=', now()->subDays(7)))
            ->when($dateRange === '30days', fn ($query) => $query->where('placed_at', '>=', now()->subDays(30)))
            ->latest('placed_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findAdminOrFail(int $orderId): Order
    {
        return Order::query()->with(['user', 'items'])->findOrFail($orderId);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $order->update(['status' => $status]);

        return $order->refresh()->load(['user', 'items']);
    }

    public function recent(int $limit): Collection
    {
        return Order::query()->with(['user', 'items'])->latest('placed_at')->limit($limit)->get();
    }
}
