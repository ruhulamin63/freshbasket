<?php

namespace App\Contracts\Repositories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Order;

    /** @param list<array<string, mixed>> $items */
    public function createItems(Order $order, array $items): void;

    public function paginateForUser(User $user, int $perPage, int $page): LengthAwarePaginator;

    public function findForUserOrFail(User $user, int $orderId): Order;
}
