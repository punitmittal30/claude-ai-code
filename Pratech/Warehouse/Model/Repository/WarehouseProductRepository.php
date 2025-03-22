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
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Pratech\Warehouse\Api\Data\WarehouseInterface;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterfaceFactory;
use Pratech\Warehouse\Api\WarehouseProductRepositoryInterface;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Pratech\Warehouse\Helper\Config;
use Pratech\Warehouse\Helper\ProductResponseFormatter;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

class WarehouseProductRepository implements WarehouseProductRepositoryInterface
{
    /**
     * Cache lifetime for static data (1 day)
     */
    private const STATIC_CACHE_LIFETIME = 86400;

    /**
     * Cache lifetime for dynamic data (5 minutes)
     */
    private const DYNAMIC_CACHE_LIFETIME = 300;

    /**
     * Cache tags
     */
    private const CACHE_TAG_STATIC = 'warehouse_products_static';
    private const CACHE_TAG_DYNAMIC = 'warehouse_products_dynamic';
    private const CACHE_TAG_FILTERS = 'warehouse_filters';

    /**
     * Dynamic attributes (frequently changing)
     */
    private const DYNAMIC_ATTRIBUTES = [
        'price', 'special_price', 'special_from_date', 'special_to_date'
    ];

    /**
     * @param ResourceConnection $resource
     * @param CollectionFactory $productCollectionFactory
     * @param WarehouseProductResultInterfaceFactory $resultFactory
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param LoggerInterface $logger
     * @param ProductResponseFormatter $responseFormatter
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param Config $helperConfig
     */
    public function __construct(
        private ResourceConnection                     $resource,
        private CollectionFactory                      $productCollectionFactory,
        private WarehouseProductResultInterfaceFactory $resultFactory,
        private WarehouseRepositoryInterface           $warehouseRepository,
        private LoggerInterface                        $logger,
        private ProductResponseFormatter               $responseFormatter,
        private ProductAttributeRepositoryInterface    $attributeRepository,
        private CacheInterface                         $cache,
        private SerializerInterface                    $serializer,
        private Config                                 $configHelper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getProductsByPincode(
        int     $pincode,
        int     $pageSize = 20,
        int     $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = 'ASC',
        ?array  $filters = null
    )
    {
        try {
            // Get nearest dark store for this pincode
            $darkStore = $this->findNearestDarkStore($pincode);

            if (!$darkStore) {
                throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
            }

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
     * Find nearest dark store for a pincode
     *
     * @param int $pincode
     * @return array|null
     */
    private function findNearestDarkStore(int $pincode)
    {
        $cacheKey = 'dark_store_' . $pincode;
        $cachedData = $this->cache->load($cacheKey);

        if ($cachedData) {
            return $this->serializer->unserialize($cachedData);
        }

        try {
            // Get all dark stores
            $darkStores = $this->warehouseRepository->getAvailableDarkStores();

            if (empty($darkStores)) {
                return null;
            }

            $warehousePincodes = array_column($darkStores, 'warehouse_pincode');

            // Find SLA data to determine nearest warehouse by delivery time
            $connection = $this->resource->getConnection();
            $slaTable = $this->resource->getTableName('pratech_warehouse_sla');

            $select = $connection->select()
                ->from($slaTable, ['warehouse_pincode', 'delivery_time', 'priority'])
                ->where('customer_pincode = ?', $pincode)
                ->where('warehouse_pincode IN (?)', $warehousePincodes)
                ->order('priority ASC')
                ->order('delivery_time ASC')
                ->limit(1);

            $slaData = $connection->fetchRow($select);

            $darkStore = null;

            if (!$slaData) {
                // No SLA data found, return first dark store
                $darkStore = reset($darkStores);
            } else {
                // Get warehouse by pincode
                $warehousePincode = $slaData['warehouse_pincode'];

                foreach ($darkStores as $store) {
                    if ((int)$store['warehouse_pincode'] === (int)$warehousePincode) {
                        $darkStore = $store;
                        break;
                    }
                }

                // If no matching warehouse found, return first dark store
                if (!$darkStore) {
                    $darkStore = reset($darkStores);
                }
            }

            // Cache the result for a day since store locations rarely change
            if ($darkStore) {
                $this->cache->save(
                    $this->serializer->serialize($darkStore),
                    $cacheKey,
                    [self::CACHE_TAG_STATIC],
                    self::STATIC_CACHE_LIFETIME
                );
            }

            return $darkStore;
        } catch (Exception $e) {
            $this->logger->error('Error finding nearest dark store: ' . $e->getMessage());
            return null;
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
    )
    {
        try {
            // Generate static and dynamic cache keys
            $staticCacheKey = $this->buildStaticCacheKey($warehouseCode, $pageSize, $currentPage, $sortField, $sortDirection, $filters);
            $dynamicCacheKey = $this->buildDynamicCacheKey($warehouseCode, $pageSize, $currentPage, $sortField, $sortDirection, $filters);

            // Get warehouse info for validation and result data
            $warehouse = $this->getWarehouseByCode($warehouseCode);

            // Try to get from static cache first
            $staticData = $this->cache->load($staticCacheKey);
            $dynamicData = $this->cache->load($dynamicCacheKey);

            if ($staticData && $dynamicData) {
                // Both caches hit - combine and return
                $staticItems = $this->serializer->unserialize($staticData);
                $dynamicItems = $this->serializer->unserialize($dynamicData);

                // Merge static and dynamic data
                $mergedItems = $this->mergeStaticAndDynamicData($staticItems, $dynamicItems);

                // Get filters (these can be cached separately)
                $availableFilters = $this->getCachedAvailableFilters($warehouseCode, $filters);

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
            $collection = $this->getOptimizedProductCollection($warehouseCode, $pageSize, $currentPage, $sortField, $sortDirection, $filters);

            // Get count before loading to optimize
            $totalCount = $collection->getSize();

            // Separate static and dynamic data
            $staticItems = [];
            $dynamicItems = [];

            foreach ($collection->getItems() as $product) {
                $productId = $product->getId();

                // Extract static data (rarely changes)
                $staticItems[$productId] = $this->extractStaticData($product);

                // Extract dynamic data (frequently changes)
                $dynamicItems[$productId] = $this->extractDynamicData($product);
            }

            // Cache static data (long lifetime)
            $this->cache->save(
                $this->serializer->serialize($staticItems),
                $staticCacheKey,
                [self::CACHE_TAG_STATIC],
                self::STATIC_CACHE_LIFETIME
            );

            // Cache dynamic data (short lifetime)
            $this->cache->save(
                $this->serializer->serialize($dynamicItems),
                $dynamicCacheKey,
                [self::CACHE_TAG_DYNAMIC],
                self::DYNAMIC_CACHE_LIFETIME
            );

            // Merge for response
            $formattedItems = $this->mergeStaticAndDynamicData($staticItems, $dynamicItems);

            // Get available filters
            $availableFilters = $this->getCachedAvailableFilters($warehouseCode, $filters);

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
     * Build cache key for static data
     *
     * @param string $warehouseCode
     * @param int $pageSize
     * @param int $currentPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param mixed $filters
     * @return string
     */
    private function buildStaticCacheKey(
        string  $warehouseCode,
        int     $pageSize,
        int     $currentPage,
        ?string $sortField,
        ?string $sortDirection,
        mixed   $filters
    ): string
    {
        $filterHash = md5($this->serializer->serialize($filters ?? []));
        return "warehouse_static_{$warehouseCode}_p{$pageSize}_c{$currentPage}_s{$sortField}_{$sortDirection}_{$filterHash}";
    }

    /**
     * Build cache key for dynamic data
     *
     * @param string $warehouseCode
     * @param int $pageSize
     * @param int $currentPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param mixed $filters
     * @return string
     */
    private function buildDynamicCacheKey(
        string  $warehouseCode,
        int     $pageSize,
        int     $currentPage,
        ?string $sortField,
        ?string $sortDirection,
                $filters
    ): string
    {
        $filterHash = md5($this->serializer->serialize($filters ?? []));
        return "warehouse_dynamic_{$warehouseCode}_p{$pageSize}_c{$currentPage}_s{$sortField}_{$sortDirection}_{$filterHash}";
    }

    /**
     * Get warehouse by code with caching
     *
     * @param string $warehouseCode
     * @return WarehouseInterface
     * @throws NoSuchEntityException
     */
    private function getWarehouseByCode(string $warehouseCode)
    {
        $cacheKey = "warehouse_{$warehouseCode}";
        $cachedData = $this->cache->load($cacheKey);

        if ($cachedData) {
            $warehouseData = $this->serializer->unserialize($cachedData);
            return $this->warehouseRepository->getById($warehouseData['warehouse_id']);
        }

        try {
            $warehouse = $this->warehouseRepository->getByCode($warehouseCode);

            // Cache warehouse data for a long time (1 day) since it rarely changes
            $this->cache->save(
                $this->serializer->serialize([
                    'warehouse_id' => $warehouse->getWarehouseId(),
                    'warehouse_code' => $warehouse->getWarehouseCode(),
                    'name' => $warehouse->getName()
                ]),
                $cacheKey,
                [self::CACHE_TAG_STATIC],
                self::STATIC_CACHE_LIFETIME
            );

            return $warehouse;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Warehouse not found: ' . $e->getMessage());
            throw new NoSuchEntityException(__('Warehouse with code "%1" does not exist.', $warehouseCode));
        }
    }

    /**
     * Merge static and dynamic data for products
     *
     * @param array $staticItems
     * @param array $dynamicItems
     * @return array
     */
    private function mergeStaticAndDynamicData(array $staticItems, array $dynamicItems): array
    {
        $mergedItems = [];

        foreach ($staticItems as $productId => $staticData) {
            if (isset($dynamicItems[$productId])) {
                $mergedItems[] = array_merge($staticData, $dynamicItems[$productId]);
            } else {
                // If dynamic data is missing, use static data with defaults
                $mergedItems[] = array_merge($staticData, [
                    'price' => 0,
                    'special_price' => 0,
                    'price_range' => [
                        'minimum_price' => [
                            'regular_price' => ['value' => 0],
                            'final_price' => ['value' => 0],
                            'discount' => ['amount_off' => 0, 'percent_off' => 0]
                        ]
                    ],
                    'stock_info' => [
                        'qty' => 0,
                        'min_sale_qty' => 1,
                        'max_sale_qty' => 10000,
                        'is_in_stock' => false
                    ],
                    'stock_status' => 'OUT_OF_STOCK',
                    'warehouse_quantity' => 0
                ]);
            }
        }

        return $mergedItems;
    }

    /**
     * Get cached available filters or generate them
     *
     * @param string $warehouseCode
     * @param array $filters
     * @return array
     */
    private function getCachedAvailableFilters(string $warehouseCode, array $filters): array
    {
        $filterHash = md5($this->serializer->serialize($filters));
        $cacheKey = "warehouse_filters_{$warehouseCode}_{$filterHash}";

        $cachedFilters = $this->cache->load($cacheKey);
        if ($cachedFilters) {
            return $this->serializer->unserialize($cachedFilters);
        }

        // Generate filters efficiently
        $collection = $this->getProductCollection();
        $this->joinWithWarehouseInventory($collection, $warehouseCode);

        // Apply filters if any
        if (!empty($filters)) {
            $this->applyFilters($collection, $filters);
        }

        // Generate filters efficiently
        $availableFilters = $this->generateEfficientFilters($collection);

        // Cache the filters - for longer period since they rarely change
        $this->cache->save(
            $this->serializer->serialize($availableFilters),
            $cacheKey,
            [self::CACHE_TAG_FILTERS],
            self::STATIC_CACHE_LIFETIME // Use longer cache for filters
        );

        return $availableFilters;
    }

    /**
     * Get base product collection
     *
     * @return Collection
     */
    private function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['neq' => 1]);
        $collection->addStoreFilter();

        return $collection;
    }

    /**
     * Join product collection with warehouse inventory
     *
     * @param Collection $collection
     * @param string $warehouseCode
     * @return void
     */
    private function joinWithWarehouseInventory(Collection $collection, string $warehouseCode)
    {
        $collection->getSelect()->join(
            ['inventory' => $this->resource->getTableName('pratech_warehouse_inventory')],
            'e.sku = inventory.sku',
            ['warehouse_quantity' => 'inventory.quantity']
        )->where('inventory.warehouse_code = ?', $warehouseCode)
            ->where('inventory.quantity > ?', 0);
    }

    /**
     * Apply filters to product collection
     *
     * @param Collection $collection
     * @param array $filters
     * @return void
     */
    private function applyFilters(Collection $collection, array $filters)
    {
        foreach ($filters as $field => $condition) {
            if (is_array($condition)) {
                // If the condition is an array with value and condition type
                if (isset($condition['value'])) {
                    $value = $condition['value'];
                    $conditionType = $condition['condition_type'] ?? 'eq';

                    if ($field === 'price' && is_array($value) && count($value) == 2) {
                        $collection->addFieldToFilter($field, ['from' => $value[0], 'to' => $value[1]]);
                    } elseif ($field === 'category_id') {
                        $this->addCategoryFilter($collection, $value);
                    } else {
                        $collection->addFieldToFilter($field, [$conditionType => $value]);
                    }
                }
            } else {
                // Simple equals condition
                $collection->addFieldToFilter($field, $condition);
            }
        }
    }

    /**
     * Add category filter to collection
     *
     * @param Collection $collection
     * @param int|array $categoryIds
     * @return void
     */
    private function addCategoryFilter(Collection $collection, $categoryIds)
    {
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $collection->addCategoriesFilter(['in' => $categoryIds]);
    }

    /**
     * Generate efficient filters using selective queries
     *
     * @param Collection $collection
     * @return array
     */
    private function generateEfficientFilters(Collection $collection): array
    {
        $filters = [];

        try {
            // Get price ranges
            $priceRanges = $this->getEfficientPriceRanges($collection);
            if (!empty($priceRanges)) {
                $filters[] = [
                    'name' => 'Price',
                    'code' => 'price',
                    'ranges' => $priceRanges
                ];
            }

            // Get product IDs from collection first
            $productIds = $this->getProductIdsFromCollection($collection);

            if (empty($productIds)) {
                return $filters;
            }

            // List of attribute codes to include as filters
            $priorityAttributeCodes = [
                'brand',
                'dietary_preference',
                'form',
                'gender',
                'flavour',
                'concern',
                'is_hm_verified',
                'is_hl_verified',
                'pack_size',
                'weight_quantity',
                'primary_l2_category'
            ];

            $connection = $this->resource->getConnection();

            foreach ($priorityAttributeCodes as $attributeCode) {
                try {
                    $attribute = $this->attributeRepository->get($attributeCode);

                    if (!$attribute->getIsFilterable()) {
                        continue;
                    }

                    $attributeId = $attribute->getAttributeId();
                    $attributeTable = $attribute->getBackendTable();
                    $optionValueTable = $connection->getTableName('eav_attribute_option_value');
                    $optionTable = $connection->getTableName('eav_attribute_option');

                    // Get all option values used by these products
                    $select = $connection->select()
                        ->distinct()
                        ->from(
                            ['attr' => $attributeTable],
                            ['value']
                        )
                        ->where('attr.attribute_id = ?', $attributeId)
                        ->where('attr.entity_id IN (?)', $productIds)
                        ->where('attr.value IS NOT NULL')
                        ->where('attr.value != ?', '');

                    // Get used option values
                    $usedOptionValues = $connection->fetchCol($select);

                    if (empty($usedOptionValues)) {
                        continue;
                    }

                    // Now get the option labels using a direct join
                    $select = $connection->select()
                        ->from(
                            ['option_value' => $optionValueTable],
                            [
                                'value' => 'option.option_id',
                                'label' => 'option_value.value'
                            ]
                        )
                        ->join(
                            ['option' => $optionTable],
                            'option_value.option_id = option.option_id',
                            []
                        )
                        ->where('option.attribute_id = ?', $attributeId)
                        ->where('option_value.option_id IN (?)', $usedOptionValues);

                    $result = $connection->fetchAll($select);

                    // Count products for each option value
                    $options = [];
                    foreach ($result as $option) {
                        // Count products with this option value
                        $countSelect = $connection->select()
                            ->from(
                                ['attr' => $attributeTable],
                                [new Zend_Db_Expr('COUNT(DISTINCT attr.entity_id)')]
                            )
                            ->where('attr.attribute_id = ?', $attributeId)
                            ->where('attr.entity_id IN (?)', $productIds)
                            ->where('attr.value = ?', $option['value']);

                        $count = (int)$connection->fetchOne($countSelect);

                        if ($count > 0) {
                            $options[] = [
                                'value' => $option['value'],
                                'label' => $option['label'],
                                'count' => $count
                            ];
                        }
                    }

                    if (!empty($options)) {
                        $filters[] = [
                            'name' => $attribute->getStoreLabel(),
                            'code' => $attributeCode,
                            'options' => $options
                        ];
                    }

                } catch (Exception $e) {
                    $this->logger->error(
                        'Error getting filter for attribute ' . $attributeCode . ': ' . $e->getMessage()
                    );
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error generating efficient filters: ' . $e->getMessage());
        }

        return $filters;
    }

    /**
     * Get product IDs from collection efficiently
     *
     * @param Collection $collection
     * @return array
     */
    private function getProductIdsFromCollection(Collection $collection): array
    {
        try {
            $select = clone $collection->getSelect();
            $select->reset(Select::COLUMNS);
            $select->columns(['entity_id' => 'e.entity_id']);
            $select->reset(Select::ORDER);
            $select->reset(Select::LIMIT_COUNT);
            $select->reset(Select::LIMIT_OFFSET);

            return $this->resource->getConnection()->fetchCol($select);
        } catch (Exception $e) {
            $this->logger->error('Error getting product IDs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get optimized product collection
     *
     * @param string $warehouseCode
     * @param int $pageSize
     * @param int $currentPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     * @param array $filters
     * @return Collection
     */
    private function getOptimizedProductCollection(
        string  $warehouseCode,
        int     $pageSize,
        int     $currentPage,
        ?string $sortField,
        ?string $sortDirection,
        array   $filters
    ): Collection
    {
        $collection = $this->getProductCollection();

        // Only select needed attributes
        $staticAttributes = $this->configHelper->getStaticAttributes();

        $dynamicAttributes = $this->configHelper->getDynamicAttributes();

        $collection->addAttributeToSelect(array_merge($staticAttributes, $dynamicAttributes));

        // Add warehouse inventory join
        $this->joinWithWarehouseInventory($collection, $warehouseCode);

        // Apply filters if any
        if (!empty($filters)) {
            $this->applyFilters($collection, $filters);
        }

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

        return $collection;
    }

    /**
     * Extract static data that rarely changes
     *
     * @param Product $product
     * @return array
     */
    private function extractStaticData($product): array
    {
        return [
            '__typename' => $this->getProductType($product),
            'id' => (int)$product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'url_key' => $product->getUrlKey(),
            'image' => $this->getProductImage($product),
            'deal_of_the_day' => (bool)$product->getData('deal_of_the_day'),
            'badges' => $product->getData('badges') ?? '',
            'is_hl_verified' => (int)$product->getData('is_hl_verified') ?? 0,
            'is_hm_verified' => (int)$product->getData('is_hm_verified') ?? 0,
            'number_of_servings' => $product->getData('number_of_servings') ?? '',
            'star_ratings' => (int)$product->getData('star_ratings') ?? 0,
            'review_count' => (int)$product->getData('review_count') ?? 0,
            'price_per_count' => $product->getData('price_per_count') ?? '',
            'price_per_100_ml' => $product->getData('price_per_100_ml') ?? '',
            'price_per_100_gram' => $product->getData('price_per_100_gram') ?? '',
            'price_per_gram_protein' => $product->getData('price_per_gram_protein') ?? '',
            // Add attribute values and their labels
            'color' => $this->getAttributeOptionLabelByCode('color', $product->getData('color')),
            'dietary_preference' => $this->getAttributeOptionLabelByCode(
                'dietary_preference',
                $product->getData('dietary_preference')
            ),
            'material' => $this->getAttributeOptionLabelByCode(
                'material',
                $product->getData('material')
            ),
            'size' => $this->getAttributeOptionLabelByCode('size', $product->getData('size')),
            'flavour' => $this->getAttributeOptionLabelByCode('flavour', $product->getData('flavour')),
            'item_weight' => $this->getAttributeOptionLabelByCode(
                'item_weight',
                $product->getData('item_weight')
            ),
            'pack_of' => $this->getAttributeOptionLabelByCode('pack_of', $product->getData('pack_of')),
            'pack_size' => $this->getAttributeOptionLabelByCode(
                'pack_size',
                $product->getData('pack_size')
            )
        ];
    }

    /**
     * Get product type for __typename
     *
     * @param Product $product
     * @return string
     */
    private function getProductType($product): string
    {
        return match ($product->getTypeId()) {
            'configurable' => 'ConfigurableProduct',
            'bundle' => 'BundleProduct',
            'grouped' => 'GroupedProduct',
            default => 'SimpleProduct',
        };
    }

    /**
     * Get product image data
     *
     * @param Product $product
     * @return array
     */
    private function getProductImage($product): array
    {
        $imageUrl = $product->getImage() ? $product->getMediaConfig()->getMediaUrl($product->getImage()) : '';
        return [
            'url' => $imageUrl,
            'label' => $product->getName()
        ];
    }

    /**
     * Get attribute option label by code
     *
     * @param string $attributeCode
     * @param mixed $value
     * @return string
     */
    private function getAttributeOptionLabelByCode(string $attributeCode, $value): string
    {
        if (empty($value)) {
            return '';
        }

        $cacheKey = "attr_option_{$attributeCode}_{$value}";
        $cachedLabel = $this->cache->load($cacheKey);

        if ($cachedLabel) {
            return $cachedLabel;
        }

        try {
            $attribute = $this->attributeRepository->get($attributeCode);
            $options = $attribute->getSource()->getAllOptions(false);

            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    $label = (string)$option['label'];

                    // Cache attribute option for a long time
                    $this->cache->save(
                        $label,
                        $cacheKey,
                        [self::CACHE_TAG_STATIC],
                        self::STATIC_CACHE_LIFETIME
                    );

                    return $label;
                }
            }

            // For multiselect attributes
            if (is_string($value) && strpos($value, ',') !== false) {
                $valueArray = explode(',', $value);
                $labels = [];

                foreach ($valueArray as $singleValue) {
                    foreach ($options as $option) {
                        if ($option['value'] == $singleValue) {
                            $labels[] = (string)$option['label'];
                            break;
                        }
                    }
                }

                if (!empty($labels)) {
                    $label = implode(', ', $labels);

                    // Cache multiselect attribute option for a long time
                    $this->cache->save(
                        $label,
                        $cacheKey,
                        [self::CACHE_TAG_STATIC],
                        self::STATIC_CACHE_LIFETIME
                    );

                    return $label;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error getting attribute option label: ' . $e->getMessage());
        }

        return (string)$value;
    }

    /**
     * Extract dynamic data that changes frequently
     *
     * @param Product $product
     * @return array
     */
    private function extractDynamicData($product): array
    {
        // Get stock information
        $stockInfo = $this->getProductStockInfo($product->getId());

        return [
            'price' => (float)$product->getPrice(),
            'special_price' => (float)$product->getSpecialPrice(),
            'price_range' => $this->getPriceRange($product),
            'special_from_date_formatted' => $product->getSpecialFromDate() ?? '',
            'special_to_date_formatted' => $product->getSpecialToDate() ?? '',
            'stock_info' => [
                'qty' => $stockInfo['qty'] ?? 0,
                'min_sale_qty' => $stockInfo['min_sale_qty'] ?? 1,
                'max_sale_qty' => $stockInfo['max_sale_qty'] ?? 10000,
                'is_in_stock' => ($stockInfo['is_in_stock'] ?? false) && $product->getStatus() == 1
            ],
            'stock_status' => ($stockInfo['is_in_stock'] ?? false) ? 'IN_STOCK' : 'OUT_OF_STOCK',
            'estimated_delivery_time' => [
                "warehouse_code" => '',
                "delivery_time" => 12,
                "quantity" => 0
            ]
        ];
    }

    /**
     * Get product stock info
     *
     * @param int $productId
     * @return array
     */
    private function getProductStockInfo(int $productId): array
    {
        $cacheKey = "stock_info_{$productId}";
        $cachedStock = $this->cache->load($cacheKey);

        if ($cachedStock) {
            return $this->serializer->unserialize($cachedStock);
        }

        try {
            // Use direct SQL query for better performance
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from(
                    ['cataloginventory_stock_item'],
                    ['qty', 'is_in_stock', 'min_sale_qty', 'max_sale_qty']
                )
                ->where('product_id = ?', $productId)
                ->where('stock_id = ?', 1);

            $stockData = $connection->fetchRow($select);

            if (!$stockData) {
                return [
                    'qty' => 0,
                    'is_in_stock' => false,
                    'min_sale_qty' => 1,
                    'max_sale_qty' => 10000
                ];
            }

            $stockInfo = [
                'qty' => (float)$stockData['qty'],
                'is_in_stock' => (bool)$stockData['is_in_stock'],
                'min_sale_qty' => (float)$stockData['min_sale_qty'],
                'max_sale_qty' => (float)$stockData['max_sale_qty']
            ];

            // Cache stock info for a short time (5 minutes)
            $this->cache->save(
                $this->serializer->serialize($stockInfo),
                $cacheKey,
                [self::CACHE_TAG_DYNAMIC],
                self::DYNAMIC_CACHE_LIFETIME
            );

            return $stockInfo;
        } catch (Exception $e) {
            $this->logger->error('Error loading stock item: ' . $e->getMessage());
            return [
                'qty' => 0,
                'is_in_stock' => false,
                'min_sale_qty' => 1,
                'max_sale_qty' => 10000
            ];
        }
    }

    /**
     * Get product price range data
     *
     * @param Product $product
     * @return array
     */
    private function getPriceRange($product): array
    {
        $regularPrice = (float)$product->getPrice();
        $finalPrice = (float)$product->getFinalPrice();
        $discount = $regularPrice - $finalPrice;
        $percentOff = $regularPrice > 0 ? round(($discount / $regularPrice) * 100, 2) : 0;

        return [
            'minimum_price' => [
                'regular_price' => [
                    'value' => $regularPrice
                ],
                'final_price' => [
                    'value' => $finalPrice
                ],
                'discount' => [
                    'amount_off' => $discount,
                    'percent_off' => $percentOff
                ]
            ]
        ];
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
        try {
            // Get product IDs for these SKUs
            if (empty($skus)) {
                return true;
            }

            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from(
                    ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                    ['entity_id']
                )
                ->where('sku IN (?)', $skus);

            $productIds = $connection->fetchCol($select);

            if (empty($productIds)) {
                return true;
            }

            // Clear dynamic data caches for these products
            foreach ($productIds as $productId) {
                $this->cache->remove("stock_info_{$productId}");
            }

            // Clear all dynamic caches
            $this->cache->clean([self::CACHE_TAG_DYNAMIC]);

            return true;
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
            $this->cache->clean([self::CACHE_TAG_DYNAMIC]);

            // Clear all filter caches
            $this->cache->clean([self::CACHE_TAG_FILTERS]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Error invalidating all caches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get price ranges efficiently using optimized queries
     *
     * @param Collection $collection
     * @return array
     */
    private function getEfficientPriceRanges(Collection $collection): array
    {
        $priceRanges = [];

        try {
            $connection = $this->resource->getConnection();

            // We need to join with the price index table to get accurate prices
            $priceAttributeId = $this->getAttributeId('price');
            if (!$priceAttributeId) {
                return [];
            }

            // Clone the collection's select to avoid modifying the original query
            $select = clone $collection->getSelect();

            // Reset columns to avoid loading unnecessary data
            $select->reset(\Magento\Framework\DB\Select::COLUMNS);
            $select->reset(\Magento\Framework\DB\Select::ORDER);
            $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
            $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

            // Join with price table to get the price data
            $select->joinLeft(
                ['price_table' => $this->resource->getTableName('catalog_product_entity_decimal')],
                "e.entity_id = price_table.entity_id AND price_table.attribute_id = {$priceAttributeId} AND price_table.store_id = 0",
                []
            );

            // Add only the columns needed for price calculation
            $select->columns([
                'min_price' => new \Zend_Db_Expr('MIN(price_table.value)'),
                'max_price' => new \Zend_Db_Expr('MAX(price_table.value)')
            ]);

            // Execute query efficiently
            $result = $connection->fetchRow($select);

            // Get min/max prices
            $minPrice = floor((float)($result['min_price'] ?? 0));
            $maxPrice = ceil((float)($result['max_price'] ?? 0));

            if ($minPrice == $maxPrice || $minPrice > $maxPrice || $minPrice <= 0) {
                return [];
            }

            // Create nice rounded price ranges
            $priceRange = $maxPrice - $minPrice;

            // Determine appropriate step size based on price range
            if ($priceRange <= 100) {
                $stepSize = 20;  // Small price range: use steps of 20
            } elseif ($priceRange <= 500) {
                $stepSize = 100; // Medium price range: use steps of 100
            } elseif ($priceRange <= 2000) {
                $stepSize = 500; // Larger price range: use steps of 500
            } elseif ($priceRange <= 10000) {
                $stepSize = 1000; // Large price range: use steps of 1000
            } else {
                $stepSize = 5000; // Very large price range: use steps of 5000
            }

            // Generate fixed number of price ranges to avoid overhead
            $start = floor($minPrice / $stepSize) * $stepSize;
            $rangeCount = min(5, ceil(($maxPrice - $start) / $stepSize));

            for ($i = 0; $i < $rangeCount; $i++) {
                $rangeStart = $start + ($i * $stepSize);
                $rangeEnd = $rangeStart + $stepSize;

                // Skip ranges outside min/max bounds
                if ($rangeEnd < $minPrice || $rangeStart > $maxPrice) {
                    continue;
                }

                $priceRanges[] = [
                    'from' => $rangeStart,
                    'to' => $rangeEnd,
                    'label' => $rangeStart . ' - ' . $rangeEnd,
                    'count' => $this->getPriceRangeCount($collection, $rangeStart, $rangeEnd, $priceAttributeId)
                ];
            }
        } catch (Exception $e) {
            $this->logger->error('Error getting price ranges: ' . $e->getMessage());
        }

        return $priceRanges;
    }

    /**
     * Get count of products in a price range efficiently
     *
     * @param Collection $collection
     * @param float $from
     * @param float $to
     * @param int $priceAttributeId
     * @return int
     */
    private function getPriceRangeCount(Collection $collection, float $from, float $to, int $priceAttributeId): int
    {
        try {
            $connection = $collection->getConnection();
            $select = clone $collection->getSelect();

            // Reset columns and order by to improve query performance
            $select->reset(\Magento\Framework\DB\Select::COLUMNS);
            $select->reset(\Magento\Framework\DB\Select::ORDER);
            $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
            $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

            // Join with price table if not already joined
            $fromPart = $select->getPart(\Magento\Framework\DB\Select::FROM);

            if (!isset($fromPart['price_table'])) {
                $select->joinLeft(
                    ['price_table' => $this->resource->getTableName('catalog_product_entity_decimal')],
                    "e.entity_id = price_table.entity_id AND price_table.attribute_id = {$priceAttributeId} AND price_table.store_id = 0",
                    []
                );
            }

            // Just count the rows with price in range
            $select->columns(['count' => new \Zend_Db_Expr('COUNT(DISTINCT e.entity_id)')])
                ->where('price_table.value >= ?', $from)
                ->where('price_table.value < ?', $to);

            return (int)$connection->fetchOne($select);
        } catch (Exception $e) {
            $this->logger->error('Error getting price range count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get attribute ID by code
     *
     * @param string $attributeCode
     * @return int|null
     */
    private function getAttributeId(string $attributeCode): ?int
    {
        try {
            $attribute = $this->attributeRepository->get($attributeCode);
            return $attribute->getAttributeId();
        } catch (Exception $e) {
            $this->logger->error('Error getting attribute ID: ' . $e->getMessage());
            return null;
        }
    }
}
