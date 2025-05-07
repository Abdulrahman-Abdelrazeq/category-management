<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheKeyManager
{
    /**
     * Store a cache key in a list for later invalidation.
     *
     * @param string $cacheKey The unique cache key to store
     * @param string $listKey The key for the cache keys list
     * @param \DateTimeInterface|null $ttl Time to live for the list
     * @return void
     */
    public function storeCacheKey(string $cacheKey, string $listKey, \DateTimeInterface $ttl = null)
    {
        $cacheKeys = Cache::get($listKey, []);
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put($listKey, $cacheKeys, $ttl ?? now()->addHours(24));
        }
    }

    /**
     * Invalidate all cache keys stored in the specified list.
     *
     * @param string $listKey The key for the cache keys list
     * @return void
     */
    public function invalidateCacheKeys(string $listKey)
    {
        $cacheKeys = Cache::get($listKey, []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget($listKey);
    }
}