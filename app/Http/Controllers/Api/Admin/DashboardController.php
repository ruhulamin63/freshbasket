<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminOrderResource;
use App\Http\Resources\GroceryItemResource;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboard) {}

    public function __invoke(): JsonResponse
    {
        $summary = $this->dashboard->summary();

        return response()->json(['data' => [
            'metrics' => $summary['metrics'],
            'recent_orders' => AdminOrderResource::collection($summary['recent_orders'])->resolve(),
            'stock_attention' => GroceryItemResource::collection($summary['stock_attention'])->resolve(),
        ]]);
    }
}
