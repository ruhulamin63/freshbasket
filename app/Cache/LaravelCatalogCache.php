<?php

namespace App\Cache;

use App\Contracts\Cache\CatalogCacheInterface;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class LaravelCatalogCache implements CatalogCacheInterface
{
    private const VERSION_KEY = 'catalog:version';

    public function __construct(private readonly Repository $cache) {}

    public function rememberPage(string $key, Closure $resolver): LengthAwarePaginator
    {
        $version = $this->cache->rememberForever(self::VERSION_KEY, fn () => (string) Str::uuid());

        return $this->cache->remember("catalog:{$version}:{$key}", now()->addMinutes(5), $resolver);
    }

    public function invalidate(): void
    {
        $this->cache->forever(self::VERSION_KEY, (string) Str::uuid());
    }
}
