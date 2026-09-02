<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CatalogIndexRequest;
use App\Http\Requests\Api\StoreGroceryRequest;
use App\Http\Requests\Api\UpdateGroceryRequest;
use App\Http\Requests\Api\UpdateStockRequest;
use App\Http\Resources\GroceryItemResource;
use App\Models\GroceryItem;
use App\Services\GroceryService;
use Illuminate\Http\Response;

class GroceryController extends Controller
{
    public function __construct(private readonly GroceryService $groceries) {}

    public function index(CatalogIndexRequest $request)
    {
        return GroceryItemResource::collection(
            $this->groceries->adminList($request->validated('search'), $request->activeStatus(), $request->perPage(), $request->page())
        );
    }

    public function store(StoreGroceryRequest $request)
    {
        return (new GroceryItemResource($this->groceries->create($request->validated())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(GroceryItem $grocery)
    {
        return new GroceryItemResource($grocery);
    }

    public function update(UpdateGroceryRequest $request, GroceryItem $grocery)
    {
        return new GroceryItemResource($this->groceries->update($grocery, $request->validated()));
    }

    public function destroy(GroceryItem $grocery): Response
    {
        $this->groceries->delete($grocery);

        return response()->noContent();
    }

    public function updateStock(UpdateStockRequest $request, GroceryItem $grocery)
    {
        return new GroceryItemResource(
            $this->groceries->updateStock($grocery, (int) $request->validated('stock_quantity'))
        );
    }
}
