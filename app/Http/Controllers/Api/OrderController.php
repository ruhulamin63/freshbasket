<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CatalogIndexRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(CatalogIndexRequest $request)
    {
        return OrderResource::collection(
            $this->orders->history($request->user('api'), $request->perPage(), $request->page())
        );
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orders->place($request->user('api'), $request->validated('items'));

        return (new OrderResource($order))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $order)
    {
        return new OrderResource($this->orders->findOwn($request->user('api'), $order));
    }
}
