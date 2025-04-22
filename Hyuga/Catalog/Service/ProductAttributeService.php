<?php
/**
 * Hyuga_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Catalog\Service;

use DateTime;
use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

/**
 * Centralized Product Attribute Cache Service
 */
class ProductAttributeService
{
    /**
     * Cache lifetime for rarely updated attributes (1 week)
     */
    public const CACHE_LIFETIME_LONG = 604800;

    /**
     * Cache lifetime for frequently updated attributes (1 hour)
     */
    public const CACHE_LIFETIME_SHORT = 3600;

    /**
     * Cache key prefix for consolidated product attributes
     */
    private const CACHE_KEY_COMMON_ATTR_PREFIX = 'product_common_attrs_';

    /**
     * Cache key prefix for consolidated product attributes
     */
    private const CACHE_KEY_DYNAMIC_ATTR_PREFIX = 'product_dynamic_attrs_';

    /**
     * Cache tag for all product attributes
     */
    private const CACHE_TAG_ALL_ATTRIBUTES = 'product_all_attributes';

    /**
     * Cache tag for all product attributes
     */
    private const CACHE_TAG_COMMON_ATTRIBUTES = 'product_common_attributes';

    /**
     * Cache key for attribute metadata
     */
    private const ATTRIBUTE_METADATA_CACHE_KEY = 'product_attribute_metadata';

    /**
     * Attribute types and their corresponding database tables
     */
    private const ATTRIBUTE_TYPES = [
        'varchar' => 'catalog_product_entity_varchar',
        'text' => 'catalog_product_entity_text',
        'int' => 'catalog_product_entity_int',
        'decimal' => 'catalog_product_entity_decimal',
        'datetime' => 'catalog_product_entity_datetime'
    ];

    /**
     * Constructor
     *
     * @param CacheServiceInterface $cache
     * @param ResourceConnection $resourceConnection
     * @param EventManager $eventManager
     * @param StockRegistryInterface $stockItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param DeliveryDateCalculator $deliveryDateCalculator
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        private CacheServiceInterface       $cache,
        private ResourceConnection          $resourceConnection,
        private EventManager                $eventManager,
        private StockRegistryInterface      $stockItemRepository,
        private ProductRepositoryInterface  $productRepository,
        private DeliveryDateCalculator      $deliveryDateCalculator,
        private TimezoneInterface           $timezone,
        private ScopeConfigInterface        $scopeConfig,
        private Logger                      $logger,
        private CategoryRepositoryInterface $categoryRepository
    )
    {
    }

    /**
     * Get attribute value for a product
     *
     * @param int $productId
     * @param string $attributeCode
     * @param string $type Type of cache (long or short-lived)
     * @return mixed
     */
    public function getAttribute(int $productId, string $attributeCode, string $type = 'long'): mixed
    {
        $attributes = $this->getAllAttributes($productId, $type);
        return $attributes[$attributeCode] ?? null;
    }

    /**
     * Get all cached attributes for a product
     *
     * @param int $productId
     * @param string $type Type of cache (long or short-lived)
     * @return array
     */
    public function getAllAttributes(int $productId, string $type = 'long'): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $type . '_' . $productId;

        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData) {
            return json_decode($cachedData, true);
        }

        // Load all attributes from database
        $attributes = $this->loadAllAttributesFromDatabase($productId);

        // Save to cache with appropriate lifetime
        $lifetime = ($type === 'long') ? self::CACHE_LIFETIME_LONG : self::CACHE_LIFETIME_SHORT;

        $this->cache->save(
            json_encode($attributes),
            $cacheKey,
            [self::CACHE_TAG_ALL_ATTRIBUTES, 'product_' . $productId],
            $lifetime
        );

        return $attributes;
    }

    /**
     * Load all attributes for a product from database
     *
     * @param int $productId
     * @return array
     */
    private function loadAllAttributesFromDatabase(int $productId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $attributes = [];

        // Get attribute metadata (attributesByType and attributeIdToCodeMap)
        $attributeMetadata = $this->getAttributeMetadata();
        $attributesByType = $attributeMetadata['attributesByType'];
        $attributeIdToCodeMap = $attributeMetadata['attributeIdToCodeMap'];

        // Fetch attribute values for each backend type
        foreach ($attributesByType as $backendType => $attributeIds) {
            if (empty($attributeIds)) {
                continue;
            }

            $tableName = self::ATTRIBUTE_TYPES[$backendType];

            $select = $connection->select()
                ->from(
                    ['attr' => $this->resourceConnection->getTableName($tableName)],
                    ['attribute_id', 'value']
                )
                ->where('attr.entity_id = ?', $productId)
                ->where('attr.attribute_id IN (?)', $attributeIds);

            $result = $connection->fetchAll($select);

            foreach ($result as $row) {
                $attributeCode = $attributeIdToCodeMap[$row['attribute_id']] ?? null;
                if ($attributeCode) {
                    $value = $row['value'];

                    // For boolean attributes stored as int, ensure we return the right type
                    if ($backendType === 'int' && in_array($attributeCode, $this->getBooleanAttributes())) {
                        $value = (bool)$value;
                    }

                    $attributes[$attributeCode] = $value;
                }
            }
        }

        // Also fetch option text for select attributes
        $attributes = $this->addOptionLabels($attributes, $productId);

        // Dispatch event to allow other modules to modify the attributes
        $this->eventManager->dispatch('hyuga_product_attribute_cache_load_after', [
            'attributes' => &$attributes,
            'product_id' => $productId
        ]);

        return $attributes;
    }

    /**
     * Get attribute metadata (attributesByType and attributeIdToCodeMap)
     *
     * @return array
     */
    private function getAttributeMetadata(): array
    {
        $cachedData = $this->cache->get(self::ATTRIBUTE_METADATA_CACHE_KEY);

        if ($cachedData) {
            return json_decode($cachedData, true);
        }

        // Build metadata from database
        $connection = $this->resourceConnection->getConnection();

        // Get all attributes for the product entity type
        $select = $connection->select()
            ->from(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                ['attribute_id', 'attribute_code', 'backend_type', 'frontend_input']
            )
            ->where('ea.entity_type_id = ?', 4); // 4 is for catalog_product

        $attributeData = $connection->fetchAll($select);

        // Group attributes by backend type
        $attributesByType = [];
        $attributeIdToCodeMap = [];
        $attributeInputMap = []; // To store frontend_input for each attribute

        foreach ($attributeData as $attribute) {
            $backendType = $attribute['backend_type'];
            $attributeId = $attribute['attribute_id'];
            $attributeCode = $attribute['attribute_code'];
            $frontendInput = $attribute['frontend_input'];

            if ($backendType !== 'static' && isset(self::ATTRIBUTE_TYPES[$backendType])) {
                $attributesByType[$backendType][] = $attributeId;
                $attributeIdToCodeMap[$attributeId] = $attributeCode;
                $attributeInputMap[$attributeCode] = $frontendInput;
            }
        }

        $metadata = [
            'attributesByType' => $attributesByType,
            'attributeIdToCodeMap' => $attributeIdToCodeMap,
            'attributeInputMap' => $attributeInputMap
        ];

        $this->cache->save(
            json_encode($metadata),
            self::ATTRIBUTE_METADATA_CACHE_KEY,
            ['product_attribute_metadata'],
            self::CACHE_LIFETIME_LONG
        );

        return $metadata;
    }

    /**
     * Get list of boolean attributes
     *
     * @return array
     */
    private function getBooleanAttributes(): array
    {
        return [
            'is_hl_verified',
            'is_hm_verified',
            'deal_of_the_day',
        ];
    }

    /**
     * Add option labels for select attributes
     *
     * @param array $attributes
     * @param int $productId
     * @return array
     */
    private function addOptionLabels(array $attributes, int $productId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $selectAttributes = $this->getSelectAttributes();
        $multiselectAttributes = $this->getMultiselectAttributes();

        $optionIds = [];
        foreach ($selectAttributes as $attributeCode) {
            if (isset($attributes[$attributeCode]) && !empty($attributes[$attributeCode])) {
                if (in_array($attributeCode, $multiselectAttributes)) {
                    // For multiselect, split the comma-separated values
                    $ids = explode(',', $attributes[$attributeCode]);
                    foreach ($ids as $id) {
                        $trimmedId = trim($id);
                        if (!empty($trimmedId)) {
                            $optionIds[] = $trimmedId;
                        }
                    }
                } elseif (is_numeric($attributes[$attributeCode])) {
                    $optionIds[] = $attributes[$attributeCode];
                }
            }
        }

        if (empty($optionIds)) {
            return $attributes;
        }

        // Get option labels
        $select = $connection->select()
            ->from(
                ['eaov' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                ['option_id', 'value']
            )
            ->where('eaov.option_id IN (?)', $optionIds)
            ->where('eaov.store_id = ?', 0); // Default store

        $optionLabels = $connection->fetchPairs($select);

        // Replace option IDs with their text labels
        foreach ($selectAttributes as $attributeCode) {
            if (isset($attributes[$attributeCode]) && !empty($attributes[$attributeCode])) {
                if (in_array($attributeCode, $multiselectAttributes)) {
                    // For multiselect, create a comma-separated list of labels
                    $ids = explode(',', $attributes[$attributeCode]);
                    $labels = [];

                    foreach ($ids as $id) {
                        $trimmedId = trim($id);
                        if (!empty($trimmedId) && isset($optionLabels[$trimmedId])) {
                            $labels[] = $optionLabels[$trimmedId];
                        }
                    }

                    $attributes[$attributeCode . '_text'] = !empty($labels) ? implode(', ', $labels) : '';
                } elseif (isset($optionLabels[$attributes[$attributeCode]])) {
                    $attributes[$attributeCode . '_text'] = $optionLabels[$attributes[$attributeCode]];
                }
            }
        }

        return $attributes;
    }

    /**
     * Get list of select attributes
     *
     * @return array
     */
    private function getSelectAttributes(): array
    {
        return [
            'form',
            'brand',
            'color',
            'dietary_preference',
            'flavour',
            'material',
            'pack_of',
            'pack_size',
            'primary_benefits',
            'size',
            'diet_type',
            'discount',
            'concern',
            'gender',
            'item_weight'
        ];
    }

    /**
     * Get list of multiselect attributes
     *
     * @return array
     */
    private function getMultiselectAttributes(): array
    {
        return [
            'diet_type',
            'discount',
            'concern'
        ];
    }

    /**
     * Get specific attributes for a product
     *
     * @param int $productId
     * @param int|null $pincode
     * @param string $type Type of cache (long or short-lived)
     * @return array
     */
    public function getCommonAttributes(int $productId, int $pincode = null, string $type = 'long'): array
    {
        $commonAttributeCodes = $this->getCarouselStableAttributes();
        $dynamicAttributeCodes = $this->getDynamicAttributes();

        // First check if we have the data in cache
        $cacheKey = self::CACHE_KEY_COMMON_ATTR_PREFIX . $type . '_' . $productId . '_specific';
        $specificCacheKey = $cacheKey . '_' . md5(implode(',', $commonAttributeCodes));

        $dynamicCacheKey = self::CACHE_KEY_DYNAMIC_ATTR_PREFIX . $type . '_' . $productId . '_dynamic';

        $commonAttributeCachedData = $this->cache->get($specificCacheKey);
        if ($commonAttributeCachedData) {
            $commonAttributes = json_decode($commonAttributeCachedData, true);
        } else {
            $commonAttributes = $this->loadSpecificAttributesFromDatabase($productId, $commonAttributeCodes);

            $this->cache->save(
                json_encode($commonAttributes),
                $specificCacheKey,
                [self::CACHE_TAG_COMMON_ATTRIBUTES, 'product_' . $productId],
                self::CACHE_LIFETIME_LONG
            );
        }

        $dynamicAttributeCachedData = $this->cache->get($dynamicCacheKey);
        if ($dynamicAttributeCachedData) {
            $dynamicAttributes = json_decode($dynamicAttributeCachedData, true);
        } else {
            $dynamicAttributes = $this->loadDynamicAttributes($productId, $dynamicAttributeCodes, $pincode);

            $this->cache->save(
                json_encode($dynamicAttributes),
                $dynamicCacheKey,
                [self::CACHE_TAG_COMMON_ATTRIBUTES, 'product_' . $productId],
                self::CACHE_LIFETIME_SHORT
            );
        }
        return array_merge($commonAttributes, $dynamicAttributes);
    }

    public function getCarouselStableAttributes(): array
    {
        return [
            'name',
            'image',
            'badges',
            'deal_of_the_day',
            'is_hl_verified',
            'is_hm_verified',
            'url_key',
            'color',
            'dietary_preference',
            'flavour',
            'material',
            'pack_of',
            'pack_size',
            'size',
            'item_weight',
            'number_of_servings'
        ];
    }

    public function getDynamicAttributes(): array
    {
        return [
            'price',
            'special_price',
            'special_from_date',
            'special_to_date'
        ];
    }

    /**
     * Load specific attributes for a product from database
     *
     * @param int $productId
     * @param array $attributeCodes
     * @return array
     */
    private function loadSpecificAttributesFromDatabase(int $productId, array $attributeCodes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $attributes = [];

        // Get attribute metadata
        $attributeMetadata = $this->getAttributeMetadata();
        $attributeIdToCodeMap = $attributeMetadata['attributeIdToCodeMap'];
        $attributesByType = $attributeMetadata['attributesByType'];
        $attributeInputMap = $attributeMetadata['attributeInputMap'] ?? [];

        // Create a map of attribute code to attribute ID
        $codeToIdMap = array_flip($attributeIdToCodeMap);

        // Group requested attributes by backend type
        $requestedAttributesByType = [];
        $selectAttributes = [];

        foreach ($attributeCodes as $attributeCode) {
            if (isset($codeToIdMap[$attributeCode])) {
                $attributeId = $codeToIdMap[$attributeCode];

                // Check if this is a select/multiselect attribute
                if (isset($attributeInputMap[$attributeCode]) &&
                    in_array($attributeInputMap[$attributeCode], ['select', 'multiselect'])) {
                    $selectAttributes[] = $attributeCode;
                }

                // Find which backend type this attribute belongs to
                foreach ($attributesByType as $backendType => $attributeIds) {
                    if (in_array($attributeId, $attributeIds)) {
                        $requestedAttributesByType[$backendType][] = $attributeId;
                        break;
                    }
                }
            }
        }

        // Fetch only requested attributes for each backend type
        foreach ($requestedAttributesByType as $backendType => $attributeIds) {
            if (empty($attributeIds)) {
                continue;
            }

            $tableName = self::ATTRIBUTE_TYPES[$backendType];

            $select = $connection->select()
                ->from(
                    ['attr' => $this->resourceConnection->getTableName($tableName)],
                    ['attribute_id', 'value']
                )
                ->where('attr.entity_id = ?', $productId)
                ->where('attr.attribute_id IN (?)', $attributeIds);

            $result = $connection->fetchAll($select);

            foreach ($result as $row) {
                $attributeCode = $attributeIdToCodeMap[$row['attribute_id']] ?? null;
                if ($attributeCode) {
                    $value = $row['value'];

                    // For boolean attributes stored as int, ensure we return the right type
                    if ($backendType === 'int' && in_array($attributeCode, $this->getBooleanAttributes())) {
                        $value = (bool)$value;
                    }

                    $attributes[$attributeCode] = $value;
                }
            }
        }

        // Replace option IDs with their text values for select/multiselect attributes
        if (!empty($selectAttributes)) {
            $attributes = $this->replaceOptionIdsWithLabels($attributes, $selectAttributes);
        }

        // Dispatch event to allow other modules to modify the attributes
        $this->eventManager->dispatch('hyuga_product_attribute_specific_load_after', [
            'attributes' => &$attributes,
            'product_id' => $productId,
            'requested_attributes' => $attributeCodes
        ]);

        return $attributes;
    }

    /**
     * Replace option IDs with their text labels directly in the attribute values
     *
     * @param array $attributes
     * @param array $selectAttributes
     * @return array
     */
    private function replaceOptionIdsWithLabels(array $attributes, array $selectAttributes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $multiselectAttributes = $this->getMultiselectAttributes();

        $optionIds = [];
        foreach ($selectAttributes as $attributeCode) {
            if (isset($attributes[$attributeCode]) && !empty($attributes[$attributeCode])) {
                if (in_array($attributeCode, $multiselectAttributes)) {
                    // For multiselect, split the comma-separated values
                    $ids = explode(',', $attributes[$attributeCode]);
                    foreach ($ids as $id) {
                        $trimmedId = trim($id);
                        if (!empty($trimmedId)) {
                            $optionIds[] = $trimmedId;
                        }
                    }
                } elseif (is_numeric($attributes[$attributeCode])) {
                    $optionIds[] = $attributes[$attributeCode];
                }
            }
        }

        if (empty($optionIds)) {
            return $attributes;
        }

        // Get option labels
        $select = $connection->select()
            ->from(
                ['eaov' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                ['option_id', 'value']
            )
            ->where('eaov.option_id IN (?)', $optionIds)
            ->where('eaov.store_id = ?', 0); // Default store

        $optionLabels = $connection->fetchPairs($select);

        // Replace option IDs with their text labels directly
        foreach ($selectAttributes as $attributeCode) {
            if (isset($attributes[$attributeCode]) && !empty($attributes[$attributeCode])) {
                if (in_array($attributeCode, $multiselectAttributes)) {
                    // For multiselect, create a comma-separated list of labels
                    $ids = explode(',', $attributes[$attributeCode]);
                    $labels = [];

                    foreach ($ids as $id) {
                        $trimmedId = trim($id);
                        if (!empty($trimmedId) && isset($optionLabels[$trimmedId])) {
                            $labels[] = $optionLabels[$trimmedId];
                        }
                    }

                    $attributes[$attributeCode] = !empty($labels) ? implode(', ', $labels) : '';
                } elseif (isset($optionLabels[$attributes[$attributeCode]])) {
                    $attributes[$attributeCode] = $optionLabels[$attributes[$attributeCode]];
                }
            }
        }

        return $attributes;
    }

    public function loadDynamicAttributes(int $productId, array $dynamicAttributes, int $pincode = null): array
    {
        $product = $this->productRepository->getById($productId);
        $productStock = $this->getProductStockInfo($productId);
        $productData = [
            'id' => $product->getId(),
            'status' => $product->getStatus(),
            'sku' => $product->getSku(),
            'price' => $product->getPrice(),
            'type' => $product->getTypeId(),
            'visibility' => $product->getVisibility(),
            'stock_info' => [
                'qty' => $productStock->getQty(),
                'min_sale_qty' => $productStock->getMinSaleQty(),
                'max_sale_qty' => $productStock->getMaxSaleQty(),
                'is_in_stock' => $productStock->getIsInStock() && $product->getStatus() == 1
            ]
        ];

        if (!($pincode === null)) {
            $productData['estimated_delivery_time'] = $this->deliveryDateCalculator
                ->getEstimatedDelivery($product->getSku(), $pincode);
        }

        if ($product->getCustomAttribute('primary_l1_category')) {
            $primaryL1CategoryId = $product->getCustomAttribute('primary_l1_category')->getValue();
            $productData['primary_l1_category'] = $this->getCategoryNameAndSlugById($primaryL1CategoryId);
        }
        if ($product->getCustomAttribute('primary_l2_category')) {
            $primaryL2CategoryId = $product->getCustomAttribute('primary_l2_category')->getValue();
            $productData['primary_l2_category'] = $this->getCategoryNameAndSlugById($primaryL2CategoryId);
        }

        if ($product->getCustomAttribute('special_price')) {
            $productData['special_price'] = $product->getCustomAttribute('special_price')->getValue();
            $productData['special_from_date_formatted'] = $product->getCustomAttribute('special_from_date')
                ? $this->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_from_date')->getValue()
                )
                : "";
            $productData['special_to_date_formatted'] = $product->getCustomAttribute('special_to_date')
                ? $this->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_to_date')->getValue()
                )
                : "";
        }
        return $productData;
    }

    /**
     * Get Product Stock Info
     *
     * @param int $productId
     * @return StockItemInterface
     */
    public function getProductStockInfo(int $productId): StockItemInterface
    {
        return $this->stockItemRepository->getStockItem($productId);
    }

    /**
     * Get Category Slug By ID
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryNameAndSlugById(int $categoryId): array
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            return [
                'name' => $category->getName(),
                'slug' => $category->getUrlKey()
            ];
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
            return [];
        }
    }

    /**
     * Get Time Based On Timezone for Email
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public function getDateTimeBasedOnTimezone(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new DateTime($date), $locale)->format($format);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }
}
