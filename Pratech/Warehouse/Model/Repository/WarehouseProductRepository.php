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
use Magento\Catalog\Api\CategoryRepositoryInterface as CoreCategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Warehouse\Api\Data\CategoryListResultInterface;
use Pratech\Warehouse\Api\Data\CategoryListResultInterfaceFactory;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterfaceFactory;
use Pratech\Warehouse\Api\WarehouseProductRepositoryInterface;
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
     * WarehouseProductRepository Constructor
     *
     * @param CollectionFactory $productCollectionFactory
     * @param WarehouseProductResultInterfaceFactory $resultFactory
     * @param CategoryListResultInterfaceFactory $categoryListResultFactory
     * @param Config $configHelper
     * @param CacheService $cacheService
     * @param DarkStoreLocatorService $darkStoreLocator
     * @param FilterService $filterService
     * @param ProductCollectionService $collectionService
     * @param ProductFormatterService $productFormatter
     * @param LoggerInterface $logger
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CoreCategoryRepositoryInterface $coreCategoryRepository
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        private CollectionFactory                                               $productCollectionFactory,
        private WarehouseProductResultInterfaceFactory                          $resultFactory,
        private CategoryListResultInterfaceFactory                              $categoryListResultFactory,
        private Config                                                          $configHelper,
        private CacheService                                                    $cacheService,
        private DarkStoreLocatorService                                         $darkStoreLocator,
        private FilterService                                                   $filterService,
        private ProductCollectionService                                        $collectionService,
        private ProductFormatterService                                         $productFormatter,
        private LoggerInterface                                                 $logger,
        private CategoryRepositoryInterface                                     $categoryRepository,
        private CoreCategoryRepositoryInterface                                 $coreCategoryRepository,
        private StoreManagerInterface                                           $storeManager,
        private \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCarouselProductsByPincode(
        int    $pincode,
        string $categorySlug
    ): WarehouseProductResultInterface {
        try {
            // Generate a cache key for faster lookups
            $cacheKey = $this->cacheService->getCategoryProductsCacheKey($pincode, $categorySlug);
            $cachedResult = $this->cacheService->get($cacheKey);

            if ($cachedResult) {
                $result = $this->resultFactory->create();
                $result->setWarehouseCode($cachedResult['warehouse_code']);
                $result->setWarehouseName($cachedResult['warehouse_name']);
                $result->setCategoryName($cachedResult['category_name'] ?? '');
                $result->setItems($cachedResult['items']);
                $result->setTotalCount($cachedResult['total_count']);
                return $result;
            }

            // Get category ID from slug
            $categoryId = $this->getCategoryIdFromSlug($categorySlug);

            if (!$categoryId) {
                throw new NoSuchEntityException(__('Category with slug "%1" does not exist.', $categorySlug));
            }

            // Get category name
            $category = $this->coreCategoryRepository->get($categoryId);
            $categoryName = $category->getName();

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
            $result->setCategoryName($categoryName);
            $result->setItems($formattedItems);
            $result->setTotalCount($totalCount);

            // Cache the result data for 5 minutes
            $this->cacheService->save(
                $cacheKey,
                [
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $darkStore['warehouse_name'],
                    'category_name' => $categoryName,
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
    ): WarehouseProductResultInterface {
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
            $cacheKey = $this->cacheService->getCategoryListingCacheKey(
                $pincode,
                $categorySlug,
                $pageSize,
                $currentPage,
                $sortField,
                $sortDirection,
                $filters
            );

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

            // Add subcategory filter if this category has children
            $subcategories = $this->getSubcategoriesForFilter($categoryId, $warehouseCode);
            if (!empty($subcategories)) {
                // Add subcategories as a new filter
                $availableFilters[] = [
                    'label' => 'Category',
                    'attribute_code' => 'subcategory',
                    'options' => $subcategories
                ];
            }

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
                ['category_products_listing'],
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
     * Get Subcategories for filter with accurate product counts for the given warehouse
     *
     * @param int $categoryId
     * @param string $warehouseCode
     * @return array
     */
    private function getSubcategoriesForFilter(int $categoryId, string $warehouseCode): array
    {
        try {
            // Generate cache key for subcategories
            $cacheKey = $this->cacheService->getSubcategoryCacheKey($categoryId, $warehouseCode);

            // Try to get from cache first
            $cachedSubcategories = $this->cacheService->get($cacheKey);
            if ($cachedSubcategories) {
                return $cachedSubcategories;
            }

            $result = [];

            // Get the category
            $category = $this->coreCategoryRepository->get($categoryId);

            // Get subcategories that are included in menu
            $subCategories = $category->getChildrenCategories()
                ->addAttributeToFilter('include_in_menu', ['eq' => 1])
                ->addAttributeToSelect(['name', 'url_key', 'category_icon']);

            // Add each subcategory to the result
            foreach ($subCategories as $subCategory) {
                // Get product count for this subcategory in the current warehouse
                $subcategoryId = (int)$subCategory->getId();
                $productCount = $this->getProductCountForSubcategory($subcategoryId, $warehouseCode);

                // Only add subcategories that have products in this warehouse
                if ($productCount > 0) {
                    // Get category icon, if available
                    $categoryIcon = $subCategory->getCategoryIcon();
                    $iconUrl = '';

                    // If category icon exists, get the URL
                    if ($categoryIcon) {
                        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                        $iconUrl = $mediaUrl . 'catalog/category/' . $categoryIcon;
                    }

                    $result[] = [
                        'id' => $subcategoryId,
                        'label' => $subCategory->getName(),
                        'slug' => $subCategory->getUrlKey(),
                        'count' => $productCount,
                        'icon' => $iconUrl  // Add the icon URL to the result
                    ];
                }
            }

            // Cache the result
            if (!empty($result)) {
                $this->cacheService->save(
                    $cacheKey,
                    $result,
                    [CacheService::CACHE_TAG_FILTERS],
                    CacheService::CACHE_LIFETIME_FILTERS
                );
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error getting subcategories: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get accurate product count for a subcategory in a specific warehouse
     *
     * @param int $categoryId
     * @param string $warehouseCode
     * @return int
     */
    private function getProductCountForSubcategory(int $categoryId, string $warehouseCode): int
    {
        try {
            // Create a collection filtered by the category
            $collection = $this->productCollectionFactory->create();
            $collection->addCategoriesFilter(['eq' => $categoryId]);

            // Join with warehouse inventory and filter for products with stock
            $this->collectionService->joinWithWarehouseInventory($collection, $warehouseCode);

            // Return the count
            return $collection->getSize();
        } catch (Exception $e) {
            $this->logger->error('Error getting product count for subcategory: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get categories available in a dark store by pincode
     *
     * @param int $pincode
     * @return CategoryListResultInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCategoriesByPincode(
        int $pincode
    ): CategoryListResultInterface {
        try {
            // Generate cache key for categories
            $cacheKey = $this->cacheService->getCategoriesCacheKey($pincode);

            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)
                ->debug('CUSTOM_LOGGING', ['Category Cache Key' => $cacheKey]);

            // Try to get from cache first
            $cachedResult = $this->cacheService->get($cacheKey);

            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)
                ->debug('CUSTOM_LOGGING', ['Cached Result for Category Cache' => $cachedResult]);

            if ($cachedResult) {
                $result = $this->categoryListResultFactory->create();
                $result->setWarehouseCode($cachedResult['warehouse_code']);
                $result->setWarehouseName($cachedResult['warehouse_name']);

                // Important: use the cached categories directly without re-encoding
                $result->setCategories($cachedResult['categories']);
                $result->setTotalCount($cachedResult['total_count']);

                return $result;
            }

            // Get nearest dark store for this pincode
            $darkStore = $this->darkStoreLocator->findNearestDarkStore($pincode);

            $warehouseCode = $darkStore['warehouse_code'];

            // Get main "categories" category
            $rootCategoryId = $this->getCategoriesRootId();

            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)
                ->debug('CUSTOM_LOGGING', ['Category Root ID' => $rootCategoryId]);

            if (!$rootCategoryId) {
                throw new NoSuchEntityException(
                    __('Root "categories" category not found')
                );
            }

            // Get subcategories with products in the current warehouse
            $categories = $this->getCategoriesWithProducts($rootCategoryId, $warehouseCode);

            // Create result object
            $result = $this->categoryListResultFactory->create();
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($darkStore['warehouse_name']);
            $result->setCategories($categories);
            $result->setTotalCount(count($categories));

            // Cache the result - store raw data
            $cacheData = [
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $darkStore['warehouse_name'],
                'categories' => $categories,
                'total_count' => count($categories)
            ];

            $this->cacheService->save(
                $cacheKey,
                $cacheData,
                ['categories_list'],
                CacheService::CACHE_LIFETIME_STATIC
            );

            return $result;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Dark store not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving categories by pincode: ' . $e->getMessage());
            throw new LocalizedException(
                __('Could not retrieve categories for pincode: %1', $e->getMessage())
            );
        }
    }

    /**
     * Get ID of the "categories" root category
     *
     * @return int|null
     */
    private function getCategoriesRootId(): ?int
    {
        try {
            // Try to find the "categories" category by URL key
            $categoryId = $this->getCategoryIdFromSlug('categories');

            if ($categoryId) {
                return $categoryId;
            }

            // If not found by URL key, try to find it by name
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToFilter('name', 'Categories')
                ->addAttributeToFilter('level', 2) // Second level, just below root
                ->addAttributeToFilter('is_active', 1)
                ->setPageSize(1);

            $category = $collection->getFirstItem();

            if ($category->getId()) {
                return (int)$category->getId();
            }

            return null;
        } catch (Exception $e) {
            $this->logger->error('Error finding categories root: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get subcategories that have products in the specified warehouse
     *
     * @param int $categoryId
     * @param string $warehouseCode
     * @return array
     */
    private function getCategoriesWithProducts(int $categoryId, string $warehouseCode): array
    {
        try {
            $result = [];

            // Get the category
            $category = $this->coreCategoryRepository->get($categoryId);

            // Get subcategories that are included in menu
            $subCategories = $category->getChildrenCategories()
                ->addAttributeToFilter('include_in_menu', ['eq' => 1])
                ->addAttributeToSelect(['name', 'url_key', 'category_icon']);

            // Add each subcategory to the result
            foreach ($subCategories as $subCategory) {
                // Get product count for this subcategory in the current warehouse
                $subcategoryId = (int)$subCategory->getId();
                $productCount = $this->getProductCountForSubcategory($subcategoryId, $warehouseCode);

                // Only add subcategories that have products in this warehouse
                if ($productCount > 0) {
                    // Get category icon if available
                    $categoryIcon = $subCategory->getCategoryIcon();
                    $iconUrl = '';

                    // If category icon exists, get the URL
                    if ($categoryIcon) {
                        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                        $iconUrl = $mediaUrl . 'catalog/category/' . $categoryIcon;
                    }

                    $result[] = [
                        'id' => $subcategoryId,
                        'name' => $subCategory->getName(),
                        'slug' => $subCategory->getUrlKey(),
                        'product_count' => $productCount,
                        'icon' => $iconUrl
                    ];
                }
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error getting categories with products: ' . $e->getMessage());
            return [];
        }
    }
}
