<?php

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\GroceryItem;
use App\Models\Order;
use App\Models\User;

class AdminDashboardService
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /** @return array<string, mixed> */
    public function summary(): array
    {
        return [
            'metrics' => [
                'products' => GroceryItem::query()->count(),
                'low_stock' => GroceryItem::query()->where('is_active', true)->whereBetween('stock_quantity', [1, 5])->count(),
                'orders' => Order::query()->count(),
                'users' => User::query()->count(),
            ],
            'recent_orders' => $this->orders->recent(5),
            'stock_attention' => GroceryItem::query()
                ->where('is_active', true)
                ->where('stock_quantity', '<=', 6)
                ->orderBy('stock_quantity')
                ->limit(5)
                ->get(),
        ];
    }
}
