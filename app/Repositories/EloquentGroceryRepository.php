<?php

namespace App\Repositories;

use App\Contracts\Repositories\GroceryRepositoryInterface;
use App\Models\GroceryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentGroceryRepository implements GroceryRepositoryInterface
{
    public function paginateCatalog(?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        return GroceryItem::query()
            ->available()
            ->when($search, fn ($query, $term) => $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function paginateAdmin(?string $search, ?bool $isActive, int $perPage, int $page): LengthAwarePaginator
    {
        return GroceryItem::query()
            ->when($search, fn ($query, $term) => $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findOrFail(int $id): GroceryItem
    {
        return GroceryItem::query()->findOrFail($id);
    }

    public function findAvailableOrFail(int $id): GroceryItem
    {
        return GroceryItem::query()->available()->findOrFail($id);
    }

    public function lockById(int $id): GroceryItem
    {
        return GroceryItem::query()->lockForUpdate()->findOrFail($id);
    }

    public function lockAvailableByIds(array $ids): Collection
    {
        sort($ids, SORT_NUMERIC);

        return GroceryItem::query()
            ->available()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function create(array $attributes): GroceryItem
    {
        return GroceryItem::query()->create($attributes);
    }

    public function update(GroceryItem $item, array $attributes): GroceryItem
    {
        $item->update($attributes);

        return $item->refresh();
    }

    public function delete(GroceryItem $item): void
    {
        $item->delete();
    }

    public function setStock(GroceryItem $item, int $quantity): GroceryItem
    {
        $item->update(['stock_quantity' => $quantity]);

        return $item->refresh();
    }

    public function decrementLockedStock(GroceryItem $item, int $quantity): void
    {
        $item->decrement('stock_quantity', $quantity);
    }
}
