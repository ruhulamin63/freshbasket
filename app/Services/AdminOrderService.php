<?php

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminOrderService
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function paginate(?string $search, ?OrderStatus $status, ?string $dateRange, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->orders->paginateAdmin($search, $status, $dateRange, $perPage, $page);
    }

    public function find(int $id): Order
    {
        return $this->orders->findAdminOrFail($id);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        if ($order->status === $status) {
            return $order->load(['user', 'items']);
        }

        if (! in_array($status, $order->status->allowedTransitions(), true)) {
            throw new InvalidOrderStatusTransitionException;
        }

        return $this->orders->updateStatus($order, $status);
    }
}
