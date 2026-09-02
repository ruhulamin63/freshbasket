<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminOrderIndexRequest;
use App\Http\Requests\Api\UpdateOrderStatusRequest;
use App\Http\Resources\AdminOrderResource;
use App\Models\Order;
use App\Services\AdminOrderService;

class OrderController extends Controller
{
    public function __construct(private readonly AdminOrderService $orders) {}

    public function index(AdminOrderIndexRequest $request)
    {
        return AdminOrderResource::collection($this->orders->paginate(
            $request->validated('search'),
            $request->status(),
            $request->validated('date_range'),
            $request->perPage(),
            $request->page(),
        ));
    }

    public function show(Order $order): AdminOrderResource
    {
        return new AdminOrderResource($this->orders->find($order->id));
    }

    public function update(UpdateOrderStatusRequest $request, Order $order): AdminOrderResource
    {
        return new AdminOrderResource(
            $this->orders->updateStatus($order, OrderStatus::from($request->validated('status')))
        );
    }
}
