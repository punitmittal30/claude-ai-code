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

namespace Hyuga\CacheManagement\Model;

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CacheService implements CacheServiceInterface
{
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function save(string $key, $data, array $tags = [], ?int $lifetime = null): bool
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
     * @inheritDoc
     */
    public function cleanPincodeCache(int $pincode): bool
    {
        try {
            $cacheKey = $this->getPincodeCacheKey($pincode);
            $this->logger->info("Clearing pincode serviceability cache for pincode: {$pincode}");
            return $this->remove($cacheKey);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning pincode cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getPincodeCacheKey(int $pincode): string
    {
        return self::CACHE_TAG_PINCODE . '_' . $pincode;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function cleanAllPincodeCaches(): bool
    {
        try {
            $this->logger->info("Clearing all pincode serviceability caches");
            return $this->clean([self::CACHE_TAG_PINCODE]);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning all pincode caches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function cleanNearestDarkStoreCache(int $pincode): bool
    {
        try {
            $cacheKey = $this->getNearestDarkStoreCacheKey($pincode);
            $this->logger->info("Clearing dark store cache for pincode: {$pincode}");
            return $this->remove($cacheKey);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning dark store cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getNearestDarkStoreCacheKey(int $pincode): string
    {
        return self::CACHE_TAG_NEAREST_DARK_STORE . '_' . $pincode;
    }

    /**
     * @inheritDoc
     */
    public function cleanAllNearestDarkStoreCaches(): bool
    {
        try {
            $this->logger->info("Clearing all dark store caches");
            return $this->clean([self::CACHE_TAG_NEAREST_DARK_STORE]);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning all dark store caches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getWarehouseFiltersCacheKey(string $warehouseCode, array $filters): string
    {
        $filterHash = md5($this->serializer->serialize($filters));
        return "warehouse_filters_{$warehouseCode}_{$filterHash}";
    }

    /**
     * @inheritDoc
     */
    public function cleanWarehouseFiltersCache(string $warehouseCode): bool
    {
        try {
            // Since filters have a hash in their key, we need to clean by tag
            $this->logger->info("Clearing warehouse filters cache for warehouse: {$warehouseCode}");
            return $this->clean([self::CACHE_TAG_WAREHOUSE_FILTERS]);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning warehouse filters cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function cleanAllWarehouseFiltersCaches(): bool
    {
        try {
            $this->logger->info("Clearing all warehouse filters caches");
            return $this->clean([self::CACHE_TAG_WAREHOUSE_FILTERS]);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning all warehouse filters caches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategoryListingCacheKey(
        int     $pincode,
        string  $categorySlug,
        int     $pageSize = 20,
        int     $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = null,
        $filters = []
    ): string {
        // Create a hash of the filters to keep the cache key shorter
        $filterHash = md5($this->serializer->serialize($filters ?? []));

        // Build a cache key that includes pagination and sorting
        return "category_listing_{$pincode}_{$categorySlug}_p{$pageSize}_c{$currentPage}" .
            "_s" . ($sortField ?? 'default') . "_" . ($sortDirection ?? 'ASC') .
            "_{$filterHash}";
    }

    /**
     * @inheritDoc
     */
    public function cleanCategoryProductsCache(int $pincode, string $categorySlug): bool
    {
        try {
            $cacheKey = $this->getCategoryProductsCacheKey($pincode, $categorySlug);
            $this->logger->info("Clearing category products cache for pincode: {$pincode}, category: {$categorySlug}");
            return $this->remove($cacheKey);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning category products cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategoryProductsCacheKey(int $pincode, string $categorySlug): string
    {
        return "{$pincode}_{$categorySlug}";
    }

    /**
     * @inheritDoc
     */
    public function cleanAllWarehouseProductsCaches(): bool
    {
        try {
            $this->logger->info("Clearing all warehouse products caches");
            return $this->clean([self::CACHE_TAG_WAREHOUSE_PRODUCTS]);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning all warehouse products caches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function cleanAvailableDarkStoresCache(): bool
    {
        try {
            $this->logger->info("Clearing available dark store cache");
            return $this->clean([self::CACHE_KEY_AVAILABLE_DARK_STORES]);
        } catch (Exception $e) {
            $this->logger->error('Error cleaning available dark store cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategoriesByPincodeCacheKey(int $pincode): string
    {
        return self::CACHE_KEY_CATEGORIES_BY_PINCODE . '_' . $pincode;
    }

    /**
     * @inheritDoc
     */
    public function getSubcategoryCacheKey(int $categoryId, string $warehouseCode): string
    {
        return self::CACHE_KEY_SUBCATEGORY_FILTERS . '_' . $categoryId . '_' . $warehouseCode;
    }
}
