<?php

namespace App\Contracts\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Order;

    /** @param list<array<string, mixed>> $items */
    public function createItems(Order $order, array $items): void;

    public function paginateForUser(User $user, int $perPage, int $page): LengthAwarePaginator;

    public function findForUserOrFail(User $user, int $orderId): Order;

    public function paginateAdmin(?string $search, ?OrderStatus $status, ?string $dateRange, int $perPage, int $page): LengthAwarePaginator;

    public function findAdminOrFail(int $orderId): Order;

    public function updateStatus(Order $order, OrderStatus $status): Order;

    /** @return Collection<int, Order> */
    public function recent(int $limit): Collection;
}
