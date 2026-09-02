<?php

namespace App\Contracts\Repositories;

use App\Models\GroceryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface GroceryRepositoryInterface
{
    public function paginateCatalog(?string $search, int $perPage, int $page): LengthAwarePaginator;

    public function paginateAdmin(?string $search, ?bool $isActive, int $perPage, int $page): LengthAwarePaginator;

    public function findOrFail(int $id): GroceryItem;

    public function findAvailableOrFail(int $id): GroceryItem;

    public function lockById(int $id): GroceryItem;

    /** @param list<int> $ids @return Collection<int, GroceryItem> */
    public function lockAvailableByIds(array $ids): Collection;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): GroceryItem;

    /** @param array<string, mixed> $attributes */
    public function update(GroceryItem $item, array $attributes): GroceryItem;

    public function delete(GroceryItem $item): void;

    public function setStock(GroceryItem $item, int $quantity): GroceryItem;

    public function decrementLockedStock(GroceryItem $item, int $quantity): void;
}
