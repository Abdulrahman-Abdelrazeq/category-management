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
        // Get existing cache keys from the list, or an empty array if none exist
        $cacheKeys = Cache::get($listKey, []);

        // Add the new key if it's not already in the list
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;

            // Store the updated list back in the cache with a TTL (default: 24 hours)
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
        // Get the list of cache keys and delete each one
        $cacheKeys = Cache::get($listKey, []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Finally, remove the list key itself
        Cache::forget($listKey);
    }
}