<?php

namespace App\Services;

use App\Contracts\Cache\CatalogCacheInterface;
use App\Contracts\Repositories\GroceryRepositoryInterface;
use App\Models\GroceryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GroceryService
{
    public function __construct(
        private readonly GroceryRepositoryInterface $groceries,
        private readonly CatalogCacheInterface $cache,
    ) {}

    public function browse(?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        $key = hash('sha256', json_encode([$search, $perPage, $page], JSON_THROW_ON_ERROR));

        return $this->cache->rememberPage($key, fn () => $this->groceries->paginateCatalog($search, $perPage, $page));
    }

    public function adminList(?string $search, ?bool $isActive, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->groceries->paginateAdmin($search, $isActive, $perPage, $page);
    }

    public function find(int $id): GroceryItem
    {
        return $this->groceries->findOrFail($id);
    }

    public function findAvailable(int $id): GroceryItem
    {
        return $this->groceries->findAvailableOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): GroceryItem
    {
        $item = $this->groceries->create($data);
        $this->cache->invalidate();

        return $item;
    }

    /** @param array<string, mixed> $data */
    public function update(GroceryItem $item, array $data): GroceryItem
    {
        $item = $this->groceries->update($item, $data);
        $this->cache->invalidate();

        return $item;
    }

    public function delete(GroceryItem $item): void
    {
        $this->groceries->delete($item);
        $this->cache->invalidate();
    }

    public function updateStock(GroceryItem $item, int $quantity): GroceryItem
    {
        $item = DB::transaction(function () use ($item, $quantity) {
            $locked = $this->groceries->lockById($item->id);
            $updated = $this->groceries->setStock($locked, $quantity);
            DB::afterCommit(fn () => $this->cache->invalidate());

            return $updated;
        }, 3);

        return $item;
    }
}
