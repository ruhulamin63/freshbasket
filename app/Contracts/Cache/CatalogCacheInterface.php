<?php

namespace App\Contracts\Cache;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CatalogCacheInterface
{
    public function rememberPage(string $key, Closure $resolver): LengthAwarePaginator;

    public function invalidate(): void;
}
