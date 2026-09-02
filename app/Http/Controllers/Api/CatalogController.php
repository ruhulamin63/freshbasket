<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CatalogIndexRequest;
use App\Http\Resources\GroceryItemResource;
use App\Services\GroceryService;

class CatalogController extends Controller
{
    public function __construct(private readonly GroceryService $groceries) {}

    public function index(CatalogIndexRequest $request)
    {
        $items = $this->groceries->browse($request->validated('search'), $request->perPage(), $request->page());

        return GroceryItemResource::collection($items);
    }

    public function show(int $grocery)
    {
        return new GroceryItemResource($this->groceries->findAvailable($grocery));
    }
}
