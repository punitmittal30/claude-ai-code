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
use Hyuga\LogManagement\Logger\ProductApiLogger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

/**
 * Centralized Product Attribute Cache Service
 */
class RestApiProductAttributeService
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
     * @param CacheServiceInterface $cacheService
     * @param ResourceConnection $resourceConnection
     * @param StockRegistryInterface $stockItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param DeliveryDateCalculator $deliveryDateCalculator
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductApiLogger $productApiLogger
     */
    public function __construct(
        private CacheServiceInterface      $cacheService,
        private ResourceConnection         $resourceConnection,
        private StockRegistryInterface     $stockItemRepository,
        private ProductRepositoryInterface $productRepository,
        private DeliveryDateCalculator     $deliveryDateCalculator,
        private TimezoneInterface          $timezone,
        private ScopeConfigInterface       $scopeConfig,
        private ProductApiLogger           $productApiLogger
    ) {
    }

    /**
     * Get optimized attributes for cross-sell products
     *
     * @param int $productId
     * @param int|null $pincode
     * @param string $section
     * @return array
     */
    public function getAttributes(int $productId, int $pincode = null, string $section = ''): array
    {
        // Get stable attributes with long cache lifetime
        $stableAttributes = $this->getStableAttributes($productId);

        // Get dynamic attributes with short cache lifetime
        $dynamicAttributes = $this->getDynamicAttributes($productId, $pincode);

        // Merge and return all attributes
        return array_merge($stableAttributes, $dynamicAttributes);
    }

    /**
     * Get stable attributes (rarely changing) with long cache lifetime
     *
     * @param int $productId
     * @return array
     */
    private function getStableAttributes(int $productId): array
    {
        $cacheKey = 'rest_stable_attrs_' . $productId;

        $cachedData = $this->cacheService->get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $stableAttributeCodes = [
            'name',
            'image',
            'url_key',
            'is_hl_verified',
            'is_hm_verified',
            'deal_of_the_day',
            'brand',
            'dietary_preference',
            'item_weight',
            'number_of_servings'
        ];

        $attributes = $this->fetchAttributesFromDb($productId, $stableAttributeCodes);

        try {
            $product = $this->productRepository->getById($productId);
            $attributes['id'] = $productId;
            $attributes['sku'] = $product->getSku();
            $attributes['type'] = $product->getTypeId();
            $attributes['slug'] = $attributes['url_key'];
        } catch (Exception $e) {
            $this->productApiLogger->error('Error fetching product: ' . $e->getMessage());
        }

        $this->cacheService->save(
            $cacheKey,
            $attributes,
            ['rest_stable_attrs', 'product_' . $productId],
            604800 // 1 week
        );

        return $attributes;
    }

    /**
     * Fetch attributes from database efficiently
     *
     * @param int $productId
     * @param array $attributeCodes
     * @return array
     */
    private function fetchAttributesFromDb(int $productId, array $attributeCodes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $attributes = [];

        // Get attribute metadata
        $attributeMetadata = $this->getAttributeMetadata();
        $attributeIdToCodeMap = $attributeMetadata['attributeIdToCodeMap'];
        $attributesByType = $attributeMetadata['attributesByType'];

        // Create map of attribute code to ID
        $codeToIdMap = array_flip($attributeIdToCodeMap);

        // Identify select attributes that need label lookup
        $selectAttributes = array_intersect($this->getSelectAttributes(), $attributeCodes);

        // Group attributes by backend type for efficient querying
        $requestedAttributesByType = [];
        foreach ($attributeCodes as $attributeCode) {
            if (isset($codeToIdMap[$attributeCode])) {
                $attributeId = $codeToIdMap[$attributeCode];

                // Find which backend type this attribute belongs to
                foreach ($attributesByType as $backendType => $attributeIds) {
                    if (in_array($attributeId, $attributeIds)) {
                        $requestedAttributesByType[$backendType][] = $attributeId;
                        break;
                    }
                }
            }
        }

        // Batch fetch attributes by backend type - this reduces number of queries
        foreach ($requestedAttributesByType as $backendType => $attributeIds) {
            if (empty($attributeIds)) {
                continue;
            }

            $tableName = self::ATTRIBUTE_TYPES[$backendType];

            // Optimize the query to only select necessary columns
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

        // Process select attributes - replace IDs with text labels
        if (!empty($selectAttributes)) {
            $optionIds = [];
            foreach ($selectAttributes as $attributeCode) {
                if (isset($attributes[$attributeCode]) && !empty($attributes[$attributeCode])) {
                    if (in_array($attributeCode, $this->getMultiselectAttributes())) {
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

            if (!empty($optionIds)) {
                // One query to get all option labels
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
                        if (in_array($attributeCode, $this->getMultiselectAttributes())) {
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
            }
        }

        return $attributes;
    }

    /**
     * Get attribute metadata (attributesByType and attributeIdToCodeMap)
     *
     * @return array
     */
    private function getAttributeMetadata(): array
    {
        $cachedData = $this->cacheService->get(self::ATTRIBUTE_METADATA_CACHE_KEY);

        if ($cachedData) {
            return $cachedData;
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

        $this->cacheService->save(
            self::ATTRIBUTE_METADATA_CACHE_KEY,
            $metadata,
            [self::ATTRIBUTE_METADATA_CACHE_KEY],
            self::CACHE_LIFETIME_LONG
        );

        return $metadata;
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
     * Get dynamic attributes (frequently changing) with short cache lifetime
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     */
    private function getDynamicAttributes(int $productId, int $pincode = null): array
    {
        $cacheKey = 'rest_dynamic_attrs_' . $productId;
        if ($pincode) {
            $cacheKey .= '_' . $pincode;
        }

        $cachedData = $this->cacheService->get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $dynamicAttributeCodes = [
            'price',
            'special_price',
            'special_from_date',
            'special_to_date'
        ];

        $attributes = $this->fetchAttributesFromDb($productId, $dynamicAttributeCodes);

        $productStock = $this->getProductStockInfo($productId);
        $attributes['stock_info'] = [
            'qty' => $productStock->getQty(),
            'is_in_stock' => $productStock->getIsInStock(),
            'min_sale_qty' => $productStock->getMinSaleQty(),
            'max_sale_qty' => $productStock->getMaxSaleQty()
        ];

        if ($pincode) {
            try {
                $product = $this->productRepository->getById($productId);
                $attributes['estimated_delivery_time'] = $this->deliveryDateCalculator
                    ->getEstimatedDelivery($product->getSku(), $pincode);
            } catch (Exception $e) {
                $this->productApiLogger->error('Error getting delivery time: ' . $e->getMessage());
            }
        }

        if (isset($attributes['special_from_date'])) {
            $attributes['special_from_date_formatted'] = $this->getDateTimeBasedOnTimezone(
                $attributes['special_from_date']
            );
        }

        if (isset($attributes['special_to_date'])) {
            $attributes['special_to_date_formatted'] = $this->getDateTimeBasedOnTimezone(
                $attributes['special_to_date']
            );
        }

        $this->cacheService->save(
            $cacheKey,
            $attributes,
            ['rest_dynamic_attrs', 'product_' . $productId],
            300 // 5 min
        );

        return $attributes;
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
            $this->productApiLogger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }
}
