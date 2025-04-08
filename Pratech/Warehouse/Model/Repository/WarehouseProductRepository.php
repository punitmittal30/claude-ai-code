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

namespace Pratech\Warehouse\Model\Repository;

use Exception;
use Hyuga\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterfaceFactory;
use Pratech\Warehouse\Api\WarehouseProductRepositoryInterface;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Pratech\Warehouse\Helper\Config;
use Pratech\Warehouse\Service\CacheService;
use Pratech\Warehouse\Service\DarkStoreLocatorService;
use Pratech\Warehouse\Service\FilterService;
use Pratech\Warehouse\Service\ProductCollectionService;
use Pratech\Warehouse\Service\ProductFormatterService;
use Psr\Log\LoggerInterface;

/**
 * Repository for warehouse products
 */
class WarehouseProductRepository implements WarehouseProductRepositoryInterface
{
    /**
     * @param CollectionFactory $productCollectionFactory
     * @param WarehouseProductResultInterfaceFactory $resultFactory
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param Config $configHelper
     * @param CacheService $cacheService
     * @param DarkStoreLocatorService $darkStoreLocator
     * @param FilterService $filterService
     * @param ProductCollectionService $collectionService
     * @param ProductFormatterService $productFormatter
     * @param LoggerInterface $logger
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        private CollectionFactory                      $productCollectionFactory,
        private WarehouseProductResultInterfaceFactory $resultFactory,
        private WarehouseRepositoryInterface           $warehouseRepository,
        private Config                                 $configHelper,
        private CacheService                           $cacheService,
        private DarkStoreLocatorService                $darkStoreLocator,
        private FilterService                          $filterService,
        private ProductCollectionService               $collectionService,
        private ProductFormatterService                $productFormatter,
        private LoggerInterface                        $logger,
        private CategoryRepositoryInterface            $categoryRepository,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getDarkStoreProductsByPincode(
        int     $pincode,
        int     $pageSize = 20,
        int     $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = 'ASC',
        mixed   $filters = []
    ): WarehouseProductResultInterface
    {
        try {
            // Get nearest dark store for this pincode
            $darkStore = $this->darkStoreLocator->findNearestDarkStore($pincode);

            // Use the warehouse code to get products
            return $this->getProductsByWarehouse(
                $darkStore['warehouse_code'],
                $pageSize,
                $currentPage,
                $sortField,
                $sortDirection,
                $filters
            );
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Dark store not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving products by pincode: ' . $e->getMessage());
            throw new LocalizedException(__('Could not retrieve products for pincode: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritDoc
     */
    public function getProductsByWarehouse(
        string  $warehouseCode,
        int     $pageSize = 20,
        int     $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = 'ASC',
        mixed   $filters = []
    ): WarehouseProductResultInterface
    {
        try {
            // Generate static and dynamic cache keys
            $staticCacheKey = $this->cacheService->getWarehouseProductsCacheKey(
                $warehouseCode,
                $pageSize,
                $currentPage,
                $sortField,
                $sortDirection,
                $filters,
                false
            );

            $dynamicCacheKey = $this->cacheService->getWarehouseProductsCacheKey(
                $warehouseCode,
                $pageSize,
                $currentPage,
                $sortField,
                $sortDirection,
                $filters,
                true
            );

            // Get warehouse info for validation and result data
            $warehouse = $this->warehouseRepository->getByCode($warehouseCode);

            // Try to get from static cache first
            $staticData = $this->cacheService->get($staticCacheKey);
            $dynamicData = $this->cacheService->get($dynamicCacheKey);

            if ($staticData && $dynamicData) {
                // Both caches hit - combine and return
                $mergedItems = $this->productFormatter->mergeStaticAndDynamicData($staticData, $dynamicData);

                // Get filters (these can be cached separately)
                $filtersCacheKey = $this->cacheService->getWarehouseFiltersCacheKey($warehouseCode, (array)$filters);
                $availableFilters = $this->cacheService->get($filtersCacheKey) ?: [];

                // Create result object
                $result = $this->resultFactory->create();
                $result->setWarehouseCode($warehouseCode);
                $result->setWarehouseName($warehouse->getName());
                $result->setItems($mergedItems);
                $result->setTotalCount(count($mergedItems));
                $result->setAvailableFilters($availableFilters);

                return $result;
            }

            // Cache miss, need to fetch from database
            $collection = $this->collectionService->getOptimizedProductCollection(
                $warehouseCode,
                $pageSize,
                $currentPage,
                $sortField,
                $sortDirection,
                (array)$filters
            );

            // Get count before loading to optimize
            $totalCount = $collection->getSize();

            // Separate static and dynamic data
            $staticItems = [];
            $dynamicItems = [];

            foreach ($collection->getItems() as $product) {
                $productId = $product->getId();

                // Extract static data (rarely changes)
                $staticItems[$productId] = $this->productFormatter->extractStaticData($product);

                // Extract dynamic data (frequently changes)
                $dynamicItems[$productId] = $this->productFormatter->extractDynamicData($product);
            }

            // Cache static data (long lifetime)
            $this->cacheService->save(
                $staticCacheKey,
                $staticItems,
                [CacheService::CACHE_TAG_STATIC],
                CacheService::CACHE_LIFETIME_STATIC
            );

            // Cache dynamic data (short lifetime)
            $this->cacheService->save(
                $dynamicCacheKey,
                $dynamicItems,
                [CacheService::CACHE_TAG_DYNAMIC],
                CacheService::CACHE_LIFETIME_DYNAMIC
            );

            // Merge for response
            $formattedItems = $this->productFormatter->mergeStaticAndDynamicData($staticItems, $dynamicItems);

            // Generate and cache filters
            $availableFilters = $this->filterService->generateEfficientFilters($collection);
            $filtersCacheKey = $this->cacheService->getWarehouseFiltersCacheKey($warehouseCode, (array)$filters);

            $this->cacheService->save(
                $filtersCacheKey,
                $availableFilters,
                [CacheService::CACHE_TAG_FILTERS],
                CacheService::CACHE_LIFETIME_FILTERS
            );

            // Create result object
            $result = $this->resultFactory->create();
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($warehouse->getName());
            $result->setItems($formattedItems);
            $result->setTotalCount($totalCount);
            $result->setAvailableFilters($availableFilters);

            return $result;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Warehouse not found: ' . $e->getMessage());
            throw new NoSuchEntityException(__('Warehouse with code "%1" does not exist.', $warehouseCode));
        } catch (Exception $e) {
            $this->logger->error('Error retrieving products by warehouse: ' . $e->getMessage());
            throw new LocalizedException(__('Could not retrieve products for warehouse: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritDoc
     */
    public function getCarouselProductsByPincode(
        int    $pincode,
        string $categorySlug
    ): WarehouseProductResultInterface
    {
        try {
            // Generate a cache key for faster lookups
            $cacheKey = $this->cacheService->getCategoryProductsCacheKey($pincode, $categorySlug);
            $cachedResult = $this->cacheService->get($cacheKey);

            if ($cachedResult) {
                $result = $this->resultFactory->create();
                $result->setWarehouseCode($cachedResult['warehouse_code']);
                $result->setWarehouseName($cachedResult['warehouse_name']);
                $result->setItems($cachedResult['items']);
                $result->setTotalCount($cachedResult['total_count']);
                return $result;
            }

            // Get category ID from slug
            $categoryId = $this->getCategoryIdFromSlug($categorySlug);

            if (!$categoryId) {
                throw new NoSuchEntityException(__('Category with slug "%1" does not exist.', $categorySlug));
            }

            // Get nearest dark store for this pincode
            $darkStore = $this->darkStoreLocator->findNearestDarkStore($pincode);
            $warehouseCode = $darkStore['warehouse_code'];

            // Create a collection with the category filter
            $collection = $this->productCollectionFactory->create();
            $collection->addCategoriesFilter(['eq' => $categoryId]);

            // Join with warehouse inventory and filter for active products with stock
            $this->collectionService->joinWithWarehouseInventory($collection, $warehouseCode);

            // Add other needed attributes and conditions
            $collection->addAttributeToSelect('*');
            $collection->setPageSize($this->getConfigValue('product/general/no_of_products_in_carousel'));

            // Count total results before loading data
            $totalCount = $collection->getSize();

            // Format items for the response
            $formattedItems = [];
            foreach ($collection as $product) {
                $staticData = $this->productFormatter->extractStaticData($product);
                $dynamicData = $this->productFormatter->extractDynamicData($product);
                $formattedItems[] = array_merge($staticData, $dynamicData);
            }

            // Create result object
            $result = $this->resultFactory->create();
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($darkStore['warehouse_name']);
            $result->setItems($formattedItems);
            $result->setTotalCount($totalCount);

            // Cache the result data for 5 minutes
            $this->cacheService->save(
                $cacheKey,
                [
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $darkStore['warehouse_name'],
                    'items' => $formattedItems,
                    'total_count' => $totalCount
                ],
                ['category_products'],
                300 // 5 minutes
            );

            return $result;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Category or dark store not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving category products by pincode: ' . $e->getMessage());
            throw new LocalizedException(
                __('Could not retrieve category products for pincode: %1', $e->getMessage())
            );
        }
    }

    /**
     * Get category ID from slug
     *
     * @param string $categorySlug
     * @return int|null
     */
    private function getCategoryIdFromSlug(string $categorySlug): ?int
    {
        try {
            $mapping = $this->categoryRepository->getMapping();
            return isset($mapping['map_by_slug'][$categorySlug]) ? (int)$mapping['map_by_slug'][$categorySlug] : null;
        } catch (Exception $e) {
            $this->logger->error('Error getting category ID from slug: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Scope Config Value
     *
     * @param string $config
     * @return mixed
     */
    private function getConfigValue(string $config): mixed
    {
        return $this->configHelper->getConfig($config);
    }

    /**
     * Get Category Products For Listing.
     *
     * @param int $pincode
     * @param string $categorySlug
     * @param int $pageSize
     * @param int $currentPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param mixed $filters
     * @return WarehouseProductResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getListingProductsByPincode(
        int     $pincode,
        string  $categorySlug,
        int     $pageSize = 20,
        int     $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = 'ASC',
        mixed   $filters = []
    ): WarehouseProductResultInterface
    {
        try {
            // Get category ID from slug
            $categoryId = $this->getCategoryIdFromSlug($categorySlug);

            if (!$categoryId) {
                throw new NoSuchEntityException(__('Category with slug "%1" does not exist.', $categorySlug));
            }

            // Get nearest dark store for this pincode
            $darkStore = $this->darkStoreLocator->findNearestDarkStore($pincode);
            $warehouseCode = $darkStore['warehouse_code'];

            // Generate cache keys for the request
            $cacheKey = $this->cacheService->getCategoryProductsCacheKey($pincode, $categorySlug);
            $filtersCacheKey = $this->cacheService->getWarehouseFiltersCacheKey($warehouseCode, (array)$filters);

            // Try to get from cache first
            $cachedResult = $this->cacheService->get($cacheKey);
            $cachedFilters = $this->cacheService->get($filtersCacheKey);

            if ($cachedResult && $cachedFilters) {
                $result = $this->resultFactory->create();
                $result->setWarehouseCode($cachedResult['warehouse_code']);
                $result->setWarehouseName($cachedResult['warehouse_name']);
                $result->setItems($cachedResult['items']);
                $result->setTotalCount($cachedResult['total_count']);
                $result->setAvailableFilters($cachedFilters);
                return $result;
            }

            // Create a collection with the category filter
            $collection = $this->productCollectionFactory->create();
            $collection->addCategoriesFilter(['eq' => $categoryId]);
            $collection->addAttributeToSelect('*');

            // Add filters if provided
            if (!empty($filters)) {
                $this->collectionService->applyFilters($collection, (array)$filters);
            }

            // Join with warehouse inventory and filter for active products with stock
            $this->collectionService->joinWithWarehouseInventory($collection, $warehouseCode);

            // Apply pagination
            $collection->setPageSize($pageSize);
            $collection->setCurPage($currentPage);

            // Apply sorting
            if ($sortField) {
                $collection->setOrder($sortField, $sortDirection ?: 'ASC');
            } else {
                // Default sort by name
                $collection->setOrder('name', 'ASC');
            }

            // Count total results before loading data
            $totalCount = $collection->getSize();

            // Generate filters for the collection
            $availableFilters = $this->filterService->generateEfficientFilters($collection);

            // Cache the filters
            $this->cacheService->save(
                $filtersCacheKey,
                $availableFilters,
                [CacheService::CACHE_TAG_FILTERS],
                CacheService::CACHE_LIFETIME_FILTERS
            );

            // Format items for the response
            $formattedItems = [];
            foreach ($collection as $product) {
                $staticData = $this->productFormatter->extractStaticData($product);
                $dynamicData = $this->productFormatter->extractDynamicData($product);
                $formattedItems[] = array_merge($staticData, $dynamicData);
            }

            // Create result object
            $result = $this->resultFactory->create();
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($darkStore['warehouse_name']);
            $result->setItems($formattedItems);
            $result->setTotalCount($totalCount);
            $result->setAvailableFilters($availableFilters);

            // Cache the result data
            $this->cacheService->save(
                $cacheKey,
                [
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $darkStore['warehouse_name'],
                    'items' => $formattedItems,
                    'total_count' => $totalCount
                ],
                ['category_products'],
                CacheService::CACHE_LIFETIME_DYNAMIC
            );

            return $result;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Category or dark store not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving category products by pincode: ' . $e->getMessage());
            throw new LocalizedException(
                __('Could not retrieve category products for pincode: %1', $e->getMessage())
            );
        }
    }

    /**
     * Clear product caches for specific SKUs
     *
     * This method can be called when prices or inventory are updated
     *
     * @param array $skus Array of SKUs to clear from cache
     * @return bool Success indicator
     */
    public function clearProductCaches(array $skus): bool
    {
        if (empty($skus)) {
            return true;
        }

        try {
            // Clear all dynamic caches - simpler than trying to be selective
            return $this->cacheService->clean([CacheService::CACHE_TAG_DYNAMIC]);
        } catch (Exception $e) {
            $this->logger->error('Error clearing product caches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate all caches
     *
     * This method can be called when warehouses or major data changes occur
     *
     * @return bool Success indicator
     */
    public function invalidateAllCaches(): bool
    {
        try {
            // Clear all dynamic caches
            $this->cacheService->clean([CacheService::CACHE_TAG_DYNAMIC]);

            // Clear all filter caches
            $this->cacheService->clean([CacheService::CACHE_TAG_FILTERS]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Error invalidating all caches: ' . $e->getMessage());
            return false;
        }
    }
}
