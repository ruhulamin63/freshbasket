<?php

namespace App\Providers;

use App\Cache\LaravelCatalogCache;
use App\Contracts\Cache\CatalogCacheInterface;
use App\Contracts\Repositories\GroceryRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\EloquentGroceryRepository;
use App\Repositories\EloquentOrderRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $bindings = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
        GroceryRepositoryInterface::class => EloquentGroceryRepository::class,
        OrderRepositoryInterface::class => EloquentOrderRepository::class,
        CatalogCacheInterface::class => LaravelCatalogCache::class,
    ];
}
