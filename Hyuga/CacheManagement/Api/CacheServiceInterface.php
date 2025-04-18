<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Api;

interface CacheServiceInterface
{
    // Constants for cache tags
    public const CACHE_TAG_WAREHOUSE_PRODUCTS = 'warehouse_products_dynamic';
    public const CACHE_TAG_WAREHOUSE_FILTERS = 'warehouse_filters';
    public const CACHE_TAG_PINCODE = 'serviceable_pincode';
    public const CACHE_TAG_NEAREST_DARK_STORE = 'nearest_dark_store';
    public const CACHE_KEY_AVAILABLE_DARK_STORES = 'available_dark_stores';

    // Constants for cache lifetimes
    public const CACHE_LIFETIME_STATIC = 604800; // 1 week
    public const CACHE_LIFETIME_DYNAMIC = 300;   // 5 minutes
    public const CACHE_LIFETIME_FILTERS = 3600;  // 1 hour
    public const CACHE_LIFETIME_PINCODE = 604800;  // 1 week

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found
     */
    public function get(string $key);

    /**
     * Save data to cache
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param array $tags Cache tags
     * @param int|null $lifetime Cache lifetime in seconds
     * @return bool Success status
     */
    public function save(string $key, mixed $data, array $tags = [], ?int $lifetime = null): bool;

    /**
     * Remove specific cache entry
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function remove(string $key): bool;

    /**
     * Clean cache by tags
     *
     * @param array $tags Cache tags to clean
     * @return bool Success status
     */
    public function clean(array $tags): bool;

    /**
     * Get cache key for pincode serviceability
     *
     * @param int $pincode
     * @return string
     */
    public function getPincodeCacheKey(int $pincode): string;

    /**
     * Clean pincode cache for specific pincode
     *
     * @param int $pincode
     * @return bool
     */
    public function cleanPincodeCache(int $pincode): bool;

    /**
     * Clean all pincode caches
     *
     * @return bool
     */
    public function cleanAllPincodeCaches(): bool;

    /**
     * Get cache key for nearest dark store by pincode
     *
     * @param int $pincode
     * @return string
     */
    public function getNearestDarkStoreCacheKey(int $pincode): string;

    /**
     * Clean dark store cache for specific pincode
     *
     * @param int $pincode
     * @return bool
     */
    public function cleanNearestDarkStoreCache(int $pincode): bool;

    /**
     * Clean all dark store caches
     *
     * @return bool
     */
    public function cleanAllNearestDarkStoreCaches(): bool;

    /**
     * Get cache key for warehouse filters
     *
     * @param string $warehouseCode
     * @param array $filters
     * @return string
     */
    public function getWarehouseFiltersCacheKey(string $warehouseCode, array $filters): string;

    /**
     * Clean warehouse filters cache
     *
     * @param string $warehouseCode
     * @return bool
     */
    public function cleanWarehouseFiltersCache(string $warehouseCode): bool;

    /**
     * Clean all warehouse filters caches
     *
     * @return bool
     */
    public function cleanAllWarehouseFiltersCaches(): bool;

    /**
     * Get cache key for category products
     *
     * @param int $pincode
     * @param string $categorySlug
     * @return string
     */
    public function getCategoryProductsCacheKey(int $pincode, string $categorySlug): string;

    /**
     * Get cache key for category listing with pagination parameters
     *
     * @param int $pincode
     * @param string $categorySlug
     * @param int $pageSize
     * @param int $currentPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param mixed $filters
     * @return string
     */
    public function getCategoryListingCacheKey(
        int $pincode,
        string $categorySlug,
        int $pageSize = 20,
        int $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = null,
        mixed $filters = []
    ): string;

    /**
     * Clean category products cache
     *
     * @param int $pincode
     * @param string $categorySlug
     * @return bool
     */
    public function cleanCategoryProductsCache(int $pincode, string $categorySlug): bool;

    /**
     * Clean all warehouse products caches
     *
     * @return bool
     */
    public function cleanAllWarehouseProductsCaches(): bool;

    /**
     * Clean available dark store cache.
     *
     * @return bool
     */
    public function clearAvailableDarkStoresCache(): bool;
}
