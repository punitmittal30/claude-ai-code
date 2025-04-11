<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Service;

use Exception;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Centralized cache service for warehouse module
 */
class CacheService
{
    /**
     * Cache tags
     */
    public const CACHE_TAG_DYNAMIC = 'warehouse_products_dynamic';
    public const CACHE_TAG_FILTERS = 'warehouse_filters';
    public const CACHE_TAG_PINCODE = 'pratech_serviceable_pincodes';
    public const CACHE_TAG_DARK_STORE = 'dark_store';

    /**
     * Cache lifetimes
     */
    public const CACHE_LIFETIME_STATIC = 604800; // 1 week
    public const CACHE_LIFETIME_DYNAMIC = 300;  // 5 minutes
    public const CACHE_LIFETIME_FILTERS = 3600; // 1 hour
    public const CACHE_LIFETIME_PINCODE = 3600; // 1 hour

    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheInterface      $cache,
        private SerializerInterface $serializer,
        private LoggerInterface     $logger
    ) {
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found
     */
    public function get(string $key)
    {
        $data = $this->cache->load($key);
        if ($data) {
            try {
                return $this->serializer->unserialize($data);
            } catch (Exception $e) {
                $this->logger->error('Error un-serializing cached data: ' . $e->getMessage());
                return null;
            }
        }

        return null;
    }

    /**
     * Save data to cache
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param array $tags Cache tags
     * @param int|null $lifetime Cache lifetime in seconds
     * @return bool Success status
     */
    public function save(string $key, mixed $data, array $tags = [], ?int $lifetime = null): bool
    {
        try {
            $serializedData = $this->serializer->serialize($data);
            $this->cache->save(
                $serializedData,
                $key,
                $tags,
                $lifetime
            );
            return true;
        } catch (Exception $e) {
            $this->logger->error('Error saving data to cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove specific cache entry
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function remove(string $key): bool
    {
        try {
            return $this->cache->remove($key);
        } catch (Exception $e) {
            $this->logger->error('Error removing cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean cache by tags
     *
     * @param array $tags Cache tags to clean
     * @return bool Success status
     */
    public function clean(array $tags): bool
    {
        try {
            return $this->cache->clean($tags);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache key for dark store by pincode
     *
     * @param int $pincode
     * @return string
     */
    public function getDarkStoreCacheKey(int $pincode): string
    {
        return "dark_store_{$pincode}";
    }

    /**
     * Get cache key for category products with listing parameters
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
        int     $pincode,
        string  $categorySlug,
        int     $pageSize = 20,
        int     $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = null,
        mixed   $filters = []
    ): string {
        // Create a hash of the filters to keep the cache key shorter
        $filterHash = md5($this->serializer->serialize($filters ?? []));

        // Build a cache key that resembles the carousel key but includes pagination and sorting
        return "category_listing_{$pincode}_{$categorySlug}_p{$pageSize}_c{$currentPage}" .
            "_s" . ($sortField ?? 'default') . "_" . ($sortDirection ?? 'ASC') .
            "_{$filterHash}";
    }

    /**
     * Get cache key for pincode serviceability
     *
     * @param int $pincode
     * @return string
     */
    public function getPincodeCacheKey(int $pincode): string
    {
        return self::CACHE_TAG_PINCODE . '_' . $pincode;
    }

    /**
     * Get cache key for warehouse filters
     *
     * @param string $warehouseCode
     * @param array $filters
     * @return string
     */
    public function getWarehouseFiltersCacheKey(string $warehouseCode, array $filters): string
    {
        $filterHash = md5($this->serializer->serialize($filters));
        return "warehouse_filters_{$warehouseCode}_{$filterHash}";
    }

    /**
     * Get cache key for subcategory filters
     *
     * @param int $categoryId
     * @param string $warehouseCode
     * @return string
     */
    public function getSubcategoryCacheKey(int $categoryId, string $warehouseCode): string
    {
        return "subcategory_filters_{$categoryId}_{$warehouseCode}";
    }

    /**
     * Get cache key for categories list by pincode
     *
     * @param int $pincode
     * @return string
     */
    public function getCategoriesCacheKey(int $pincode): string
    {
        return "categories_list_{$pincode}";
    }

    /**
     * Get cache key for category products
     *
     * @param int $pincode
     * @param string $categorySlug
     * @return string
     */
    public function getCategoryProductsCacheKey(int $pincode, string $categorySlug): string
    {
        return "{$pincode}_{$categorySlug}";
    }
}
