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
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Hyuga\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface as CoreCategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
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
use Pratech\Warehouse\Service\DarkStoreLocatorService;
use Pratech\Warehouse\Service\FilterService;
use Pratech\Warehouse\Service\ProductCollectionService;
use Pratech\Warehouse\Service\ProductFormatterService;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

/**
 * Repository for warehouse products
 */
class WarehouseProductRepository implements WarehouseProductRepositoryInterface
{
    /**
     * Constant for Category By Pincode Title
     */
    public const CATEGORY_BY_PINCODE_TITLE = 'Our Top Categories';

    /**
     * WarehouseProductRepository Constructor
     *
     * @param CollectionFactory $productCollectionFactory
     * @param WarehouseProductResultInterfaceFactory $resultFactory
     * @param CategoryListResultInterfaceFactory $categoryListResultFactory
     * @param Config $configHelper
     * @param CacheServiceInterface $cacheService
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
        private CacheServiceInterface                                           $cacheService,
        private DarkStoreLocatorService                                         $darkStoreLocator,
        private FilterService                                                   $filterService,
        private ProductCollectionService                                        $collectionService,
        private ProductFormatterService                                         $productFormatter,
        private LoggerInterface                                                 $logger,
        private CategoryRepositoryInterface                                     $categoryRepository,
        private CoreCategoryRepositoryInterface                                 $coreCategoryRepository,
        private StoreManagerInterface                                           $storeManager,
        private \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
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
            $cacheKey = $this->cacheService->getDarkStoreCarouselProductsCacheKey($pincode, $categorySlug);
            $cachedResult = $this->cacheService->get($cacheKey);

            if ($cachedResult) {
                /** @var WarehouseProductResultInterface $result */
                $result = $this->resultFactory->create();
                $result->setIsCached(true);
                $result->setWarehouseCode($cachedResult['warehouse_code']);
                $result->setWarehouseName($cachedResult['warehouse_name']);
                $result->setTitle($cachedResult['title'] ?? '');
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

            if (empty($darkStore)) {
                throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
            }

            $warehouseCode = $darkStore['warehouse_code'];

            /** @var Collection $collection */
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collection->addCategoriesFilter(['eq' => $categoryId]);

            // Join with warehouse inventory and filter for active products with stock
            $this->collectionService->joinWithWarehouseInventory($collection, $warehouseCode, false);

            // Add other needed attributes and conditions
            $collection->getSelect()->columns(['inventory_quantity' => 'inventory.quantity']);
            $collection->addAttributeToSelect('*');
            $collection->setOrder('qty_sold', 'desc');
            $collection->setPageSize($this->getConfigValue('product/general/no_of_products_in_carousel'));

            // Count total results before loading data
            $totalCount = $collection->getSize();

            // Format items for the response
            $formattedItems = [];
            foreach ($collection as $product) {
                $staticData = $this->productFormatter->extractStaticData($product);
                $dynamicData = $this->productFormatter->extractDynamicData($product, $pincode);
                $formattedItems[] = array_merge($staticData, $dynamicData);
            }

            /** @var WarehouseProductResultInterface $result */
            $result = $this->resultFactory->create();
            $result->setIsCached(false);
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($darkStore['warehouse_name']);
            $result->setTitle($categoryName);
            $result->setItems($formattedItems);
            $result->setTotalCount($totalCount);

            $this->cacheService->save(
                $cacheKey,
                [
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $darkStore['warehouse_name'],
                    'title' => $categoryName,
                    'items' => $formattedItems,
                    'total_count' => $totalCount
                ],
                ['category_products', 'dark_store_carousel_products', 'dark_store_carousel_products_' . $categorySlug],
                86400
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

            if (empty($darkStore)) {
                throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
            }

            $warehouseCode = $darkStore['warehouse_code'];

            // Generate cache keys for the request - include stock sort information
            $cacheKey = $this->cacheService->getCategoryListingCacheKey(
                $warehouseCode,
                $categorySlug,
                $pageSize,
                $currentPage,
                $sortField,
                $sortDirection,
                $filters
            );

            $filtersCacheKey = $this->cacheService->getWarehouseFiltersCacheKey(
                $warehouseCode,
                $categorySlug,
                (array)$filters
            );

            $cachedResult = $this->cacheService->get($cacheKey);
            $cachedFilters = $this->cacheService->get($filtersCacheKey);

            if ($cachedResult && $cachedFilters) {
                /** @var WarehouseProductResultInterface $result */
                $result = $this->resultFactory->create();
                $result->setIsCached(true);
                $result->setWarehouseCode($cachedResult['warehouse_code']);
                $result->setWarehouseName($cachedResult['warehouse_name']);
                $result->setTitle($cachedResult['title']);
                $result->setCategoryIcon($cachedResult['category_icon']);
                $result->setItems($cachedResult['items']);
                $result->setTotalCount($cachedResult['total_count']);
                $result->setAvailableFilters($cachedFilters);
                return $result;
            }

            $category = $this->coreCategoryRepository->get($categoryId);
            $categoryThumbnailPath = $category->getCategoryThumbnail();
            $categoryIcon = $this->getCategoryIcon($categoryThumbnailPath);

            // Create a collection with the category filter
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collection->addCategoriesFilter(['eq' => $categoryId]);
            $collection->addAttributeToSelect('*');

            // Explicitly add qty_sold attribute to ensure it's available for sorting
            $collection->addAttributeToSelect('qty_sold');

            // Add filters if provided
            if (!empty($filters)) {
                $this->collectionService->applyFilters($collection, (array)$filters);
            }

            // Join with warehouse inventory but DO NOT filter for stock
            // This allows us to get all products, then sort by stock status later
            $this->collectionService->joinWithWarehouseInventory($collection, $warehouseCode, false);

            // Add inventory quantity as a field we can sort on later
            $collection->getSelect()->columns(['inventory_quantity' => 'inventory.quantity']);
            $collection->setPageSize($pageSize);
            $collection->setCurPage($currentPage);

            // First, always sort by stock status
            $collection->getSelect()->order(new Zend_Db_Expr('IF(inventory.quantity > 0, 0, 1) ASC'));

            if ($sortField) {
                // Handle special case for category position
                if ($sortField === 'position' || $sortField === 'recommended') {
                    // Join with category product table to get position
                    $collection->joinField(
                        'position',
                        'catalog_category_product',
                        'position',
                        'product_id=entity_id',
                        ['category_id' => $categoryId],
                        'left'
                    );

                    // Apply the position sort
                    $collection->setOrder('position', $sortDirection ?: 'ASC');
                } elseif ($sortField === 'qty_sold' || $sortField === 'popularity') {
                    // Apply the popularity sort
                    $collection->setOrder('qty_sold', $sortDirection ?: 'DESC');
                } else {
                    // Apply the requested sort field
                    $collection->setOrder($sortField, $sortDirection ?: 'ASC');
                }
            } else {
                // Apply the popularity sort as the default
                $collection->setOrder('qty_sold', 'DESC');
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

            $this->cacheService->save(
                $filtersCacheKey,
                $availableFilters,
                [CacheServiceInterface::CACHE_TAG_FILTERS],
                CacheServiceInterface::CACHE_LIFETIME_1_WEEK
            );

            // Format items for the response
            $formattedItems = [];
            foreach ($collection as $product) {
                $staticData = $this->productFormatter->extractStaticData($product);
                $dynamicData = $this->productFormatter->extractDynamicData($product, $pincode);
                $formattedItems[] = array_merge($staticData, $dynamicData);
            }

            /** @var WarehouseProductResultInterface $result */
            $result = $this->resultFactory->create();
            $result->setIsCached(false);
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($darkStore['warehouse_name']);
            $result->setTitle($category->getName());
            $result->setCategoryIcon($categoryIcon);
            $result->setItems($formattedItems);
            $result->setTotalCount($totalCount);
            $result->setAvailableFilters($availableFilters);

            // Cache the result data
            $this->cacheService->save(
                $cacheKey,
                [
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $darkStore['warehouse_name'],
                    'title' => $category->getName(),
                    'category_icon' => $categoryIcon,
                    'items' => $formattedItems,
                    'total_count' => $totalCount
                ],
                ['category_products_listing'],
                CacheServiceInterface::CACHE_LIFETIME_1_WEEK
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
     * Get Category Details.
     *
     * @param string|null $categoryIcon
     * @return string
     */
    public function getCategoryIcon(?string $categoryIcon): string
    {
        $iconUrl = '';
        try {
            if ($categoryIcon) {
                $categoryIcon = str_replace('/media/', '', $categoryIcon);
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $iconUrl = $mediaUrl . $categoryIcon;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Error fetching category icon: ' . $categoryIcon . " | " . $e->getMessage());
        }
        return $iconUrl;
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
            $cacheKey = $this->cacheService->getSubcategoryCacheKey($categoryId, $warehouseCode);

            $cachedSubcategories = $this->cacheService->get($cacheKey);
            if ($cachedSubcategories) {
                return $cachedSubcategories;
            }

            $result = [];

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
                    $categoryIconPath = $subCategory->getCategoryIcon();
                    $categoryIcon = $this->getCategoryIcon($categoryIconPath);

                    $result[] = [
                        'id' => $subcategoryId,
                        'label' => $subCategory->getName(),
                        'slug' => $subCategory->getUrlKey(),
                        'count' => $productCount,
                        'icon' => $categoryIcon
                    ];
                }
            }

            if (!empty($result)) {
                $this->cacheService->save(
                    $cacheKey,
                    $result,
                    [CacheServiceInterface::CACHE_TAG_FILTERS],
                    CacheServiceInterface::CACHE_LIFETIME_1_WEEK
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
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collection->addCategoriesFilter(['eq' => $categoryId]);

            // Join with warehouse inventory and filter for products with stock
            $this->collectionService->joinWithWarehouseInventory($collection, $warehouseCode, false);

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
            $cacheKey = $this->cacheService->getCategoriesByPincodeCacheKey($pincode);

            $cachedResult = $this->cacheService->get($cacheKey);

            if ($cachedResult) {
                /** @var CategoryListResultInterface $result */
                $result = $this->categoryListResultFactory->create();
                $result->setIsCached(true);
                $result->setTitle($cachedResult['title']);
                $result->setWarehouseCode($cachedResult['warehouse_code']);
                $result->setWarehouseName($cachedResult['warehouse_name']);
                $result->setCategories($cachedResult['categories']);
                $result->setTotalCount($cachedResult['total_count']);

                return $result;
            }

            $darkStore = $this->darkStoreLocator->findNearestDarkStore($pincode);

            if (empty($darkStore)) {
                throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
            }

            $warehouseCode = $darkStore['warehouse_code'];

            $rootCategoryId = $this->getCategoriesRootId();

            if (!$rootCategoryId) {
                throw new NoSuchEntityException(
                    __('Root "categories" category not found')
                );
            }

            // Get subcategories with products in the current warehouse
            $categories = $this->getCategoriesWithProducts($rootCategoryId, $warehouseCode);

            $result = $this->categoryListResultFactory->create();
            $result->setIsCached(false);
            $result->setTitle(self::CATEGORY_BY_PINCODE_TITLE);
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($darkStore['warehouse_name']);
            $result->setCategories($categories);
            $result->setTotalCount(count($categories));

            $cacheData = [
                'title' => self::CATEGORY_BY_PINCODE_TITLE,
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $darkStore['warehouse_name'],
                'categories' => $categories,
                'total_count' => count($categories)
            ];

            $this->cacheService->save(
                $cacheKey,
                $cacheData,
                ['categories_list'],
                CacheServiceInterface::CACHE_LIFETIME_1_DAY
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
            $categoryId = $this->getCategoryIdFromSlug('categories');

            if ($categoryId) {
                return $categoryId;
            }

            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToFilter('name', 'Categories')
                ->addAttributeToFilter('level', 2)
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

            $category = $this->coreCategoryRepository->get($categoryId);

            $subCategories = $category->getChildrenCategories()
                ->addAttributeToFilter('include_in_menu', ['eq' => 1])
                ->addAttributeToSelect(['name', 'url_key', 'shop_by_image_desktop', 'shop_by_image_mobile']);

            foreach ($subCategories as $subCategory) {
                $subcategoryId = (int)$subCategory->getId();
                $productCount = $this->getProductCountForSubcategory($subcategoryId, $warehouseCode);

                if ($productCount > 0) {
                    $shopByImageDesktop = $this->removeMediaFromUrl($subCategory->getShopByImageDesktop());
                    $shopByImageMobile = $this->removeMediaFromUrl($subCategory->getShopByImageMobile());
                    try {
                        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                    } catch (LocalizedException) {
                        $mediaUrl = 'https://assets.hyugalife.com';
                    }
                    $result[] = [
                        'id' => $subcategoryId,
                        'name' => $subCategory->getName(),
                        'slug' => $subCategory->getUrlKey(),
                        'product_count' => $productCount,
                        'shop_by_image_desktop' => $mediaUrl . $shopByImageDesktop,
                        'shop_by_image_mobile' => $mediaUrl . $shopByImageMobile,
                    ];
                }
            }

            // Sort the result array by product_count in descending order
            usort($result, function ($a, $b) {
                return $b['product_count'] <=> $a['product_count'];
            });

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error getting categories with products: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Remove Media From Url
     *
     * @param string|null $url
     * @return string|null
     */
    public function removeMediaFromUrl(?string $url): ?string
    {
        if ($url && str_contains($url, '/media/')) {
            $url = explode('/media/', $url, 2)[1];
        }
        return $url;
    }
}
