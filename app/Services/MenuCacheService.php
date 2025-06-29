<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MenuCacheService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    const CACHE_DURATION = 3600;

    /**
     * Get cached menu by location with user-specific permissions
     *
     * @param string $location
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCachedMenuByLocation($location, $user)
    {
        $cacheKey = $this->generateCacheKey($location, $user);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($location, $user, $cacheKey) {
            Log::info('Menu cache miss, fetching from database', [
                'location' => $location,
                'user_id' => $user->id,
                'cache_key' => $cacheKey
            ]);

            return Menu::getMenuByLocation($location, $user);
        });
    }

    /**
     * Get optimized cached menu with eager loading
     *
     * @param string $location
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptimizedCachedMenu($location, $user)
    {
        $cacheKey = $this->generateOptimizedCacheKey($location, $user);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($location, $user) {
            return $this->getOptimizedMenuByLocation($location, $user);
        });
    }

    /**
     * Invalidate user-specific menu cache
     *
     * @param string $userId
     * @return void
     */
    public function invalidateUserMenuCache($userId)
    {
        $patterns = [
            "menu:*:user:{$userId}",
            "menu_optimized:*:user:{$userId}"
        ];

        foreach ($patterns as $pattern) {
            $this->forgetCacheByPattern($pattern);
        }

        Log::info('Menu cache invalidated for user', ['user_id' => $userId]);
    }

    /**
     * Invalidate all menu cache for specific location
     *
     * @param string $location
     * @return void
     */
    public function invalidateLocationMenuCache($location)
    {
        $patterns = [
            "menu:{$location}:*",
            "menu_optimized:{$location}:*"
        ];

        foreach ($patterns as $pattern) {
            $this->forgetCacheByPattern($pattern);
        }

        Log::info('Menu cache invalidated for location', ['location' => $location]);
    }

    /**
     * Invalidate all menu cache
     *
     * @return void
     */
    public function invalidateAllMenuCache()
    {
        $patterns = [
            'menu:*',
            'menu_optimized:*'
        ];

        foreach ($patterns as $pattern) {
            $this->forgetCacheByPattern($pattern);
        }

        Log::info('All menu cache invalidated');
    }

    /**
     * Get optimized menu query with eager loading
     *
     * @param string $location
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getOptimizedMenuByLocation($location, $user)
    {
        $query = Menu::with([
            'children' => function ($query) use ($user) {
                $query->orderBy('order_number');
                if (!$user->hasRole('SuperAdmin')) {
                    $query->where(function ($q) use ($user) {
                        $q->whereHas('roles', function ($q) use ($user) {
                            $q->whereIn('roles.id', $user->roles->pluck('id'));
                        })
                            ->orWhereHas('permissions', function ($q) use ($user) {
                                $q->whereIn('permissions.id', $user->getAllPermissions()->pluck('id'));
                            });
                    });
                }
            },
            'roles:id,name',
            'permissions:id,name'
        ])
            ->where('location', $location)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order_number');

        if (!$user->hasRole('SuperAdmin')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('roles', function ($q) use ($user) {
                    $q->whereIn('roles.id', $user->roles->pluck('id'));
                })
                    ->orWhereHas('permissions', function ($q) use ($user) {
                        $q->whereIn('permissions.id', $user->getAllPermissions()->pluck('id'));
                    });
            });
        }

        return $query->get();
    }

    /**
     * Generate cache key for user-specific menu
     *
     * @param string $location
     * @param \App\Models\User $user
     * @return string
     */
    private function generateCacheKey($location, $user)
    {
        $userRoles = $user->roles->pluck('id')->sort()->implode(',');
        $userPermissions = $user->getAllPermissions()->pluck('id')->sort()->implode(',');

        return "menu:{$location}:user:{$user->id}:roles:" . md5($userRoles) . ":permissions:" . md5($userPermissions);
    }

    /**
     * Generate optimized cache key
     *
     * @param string $location
     * @param \App\Models\User $user
     * @return string
     */
    private function generateOptimizedCacheKey($location, $user)
    {
        $userRoles = $user->roles->pluck('id')->sort()->implode(',');
        $userPermissions = $user->getAllPermissions()->pluck('id')->sort()->implode(',');

        return "menu_optimized:{$location}:user:{$user->id}:roles:" . md5($userRoles) . ":permissions:" . md5($userPermissions);
    }

    /**
     * Forget cache by pattern (simplified implementation)
     *
     * @param string $pattern
     * @return void
     */
    private function forgetCacheByPattern($pattern)
    {
        // For Laravel cache stores that support pattern-based deletion
        // This is a simplified implementation - in production you might want to use Redis
        // or implement a more sophisticated cache key tracking system

        try {
            if (method_exists(Cache::getStore(), 'flush')) {
                // If using array or file cache, we might need to flush all
                // In production, consider using Redis with pattern-based deletion
                Log::warning('Pattern-based cache deletion not fully supported, consider using Redis');
            }
        } catch (\Exception $e) {
            Log::error('Error forgetting cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStatistics()
    {
        return [
            'cache_duration' => self::CACHE_DURATION,
            'cache_store' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'menu_cache_keys' => $this->getActiveCacheKeys()
        ];
    }

    /**
     * Get active cache keys (simplified)
     *
     * @return array
     */
    private function getActiveCacheKeys()
    {
        // This would need to be implemented based on your cache store
        // For Redis, you could use KEYS command
        // For other stores, you might need to track keys separately
        return [
            'note' => 'Cache key tracking depends on cache store implementation'
        ];
    }

    /**
     * Warm up cache for specific user and location
     *
     * @param string $location
     * @param \App\Models\User $user
     * @return void
     */
    public function warmUpCache($location, $user)
    {
        Log::info('Warming up menu cache', [
            'location' => $location,
            'user_id' => $user->id
        ]);

        // Pre-load both regular and optimized cache
        $this->getCachedMenuByLocation($location, $user);
        $this->getOptimizedCachedMenu($location, $user);
    }
}
