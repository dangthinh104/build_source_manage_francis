<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Parameter Model with Smart Caching (Cache-Aside Pattern)
 * 
 * Implements intelligent caching to reduce database load:
 * - Read: Cache::rememberForever for indefinite storage until value changes
 * - Write: Auto-invalidation on save/delete via model events
 * 
 * Usage:
 *   $value = Parameter::getValue('key_name', 'default_value');
 */
class Parameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Cache key prefix for parameters
     */
    private const CACHE_PREFIX = 'parameter_value_';

    /**
     * Boot model events for auto-invalidation
     */
    protected static function booted(): void
    {
        // Auto-invalidate cache when parameter is saved (created/updated)
        static::saved(function (Parameter $parameter) {
            $cacheKey = self::CACHE_PREFIX . $parameter->key;
            Cache::forget($cacheKey);
            
            \Log::channel('build')->debug('Parameter cache invalidated', [
                'key' => $parameter->key,
                'cache_key' => $cacheKey,
            ]);
        });

        // Auto-invalidate cache when parameter is deleted
        static::deleted(function (Parameter $parameter) {
            $cacheKey = self::CACHE_PREFIX . $parameter->key;
            Cache::forget($cacheKey);
            
            \Log::channel('build')->debug('Parameter cache invalidated (deleted)', [
                'key' => $parameter->key,
                'cache_key' => $cacheKey,
            ]);
        });
    }

    /**
     * Get parameter value with smart caching (Cache-Aside Pattern)
     * 
     * Strategy:
     * 1. Check cache first (using unique key: "parameter_value_{$key}")
     * 2. If cache miss, query database
     * 3. Store in cache indefinitely (until value is updated/deleted)
     * 4. Return value or default if not found
     * 
     * @param string $key Parameter key to retrieve
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed Parameter value or default
     */
    public static function getValue(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $parameter = self::where('key', $key)->first();
            
            if (!$parameter) {
                \Log::channel('build')->debug('Parameter not found in database', [
                    'key' => $key,
                    'using_default' => true,
                ]);
                return $default;
            }

            \Log::channel('build')->debug('Parameter loaded from database and cached', [
                'key' => $key,
                'cache_key' => self::CACHE_PREFIX . $key,
            ]);

            return $parameter->value;
        });
    }

    /**
     * Clear all parameter caches (useful for manual cache clearing if needed)
     * 
     * @return void
     */
    public static function clearAllCache(): void
    {
        $parameters = self::all();
        
        foreach ($parameters as $parameter) {
            $cacheKey = self::CACHE_PREFIX . $parameter->key;
            Cache::forget($cacheKey);
        }
        
        \Log::info('All parameter caches cleared', [
            'count' => $parameters->count(),
        ]);
    }
}
