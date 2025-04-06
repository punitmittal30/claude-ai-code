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

namespace Pratech\Warehouse\Model\Cache;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Centralized Product Attribute Cache Service
 */
class ProductAttributeCache
{
    /**
     * Cache lifetime for all attributes (1 day)
     */
    public const CACHE_LIFETIME = 86400;

    /**
     * Cache key prefix for consolidated product attributes
     */
    private const CACHE_KEY_PREFIX = 'warehouse_product_all_attrs_';

    /**
     * Cache tag for all product attributes
     */
    private const CACHE_TAG = 'warehouse_product_attributes';

    /**
     * Cache key for attribute metadata
     */
    private const ATTRIBUTE_METADATA_CACHE_KEY = 'warehouse_attribute_metadata';

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
     * List of boolean attributes
     */
    private const BOOLEAN_ATTRIBUTES = [
        'is_hl_verified',
        'is_hm_verified',
        'deal_of_the_day'
    ];

    /**
     * List of select type attributes
     */
    private const SELECT_ATTRIBUTES = [
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

    /**
     * List of multiselect attributes
     */
    private const MULTISELECT_ATTRIBUTES = [
        'diet_type',
        'discount',
        'concern'
    ];

    /**
     * In-memory cache for attribute metadata
     *
     * @var array
     */
    private static array $attributeMetadataCache = [];

    /**
     * In-memory cache for attribute values
     *
     * @var array
     */
    private array $attributeCache = [];

    /**
     * Constructor
     *
     * @param CacheInterface $cache
     * @param ResourceConnection $resourceConnection
     * @param EventManager $eventManager
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheInterface $cache,
        private ResourceConnection $resourceConnection,
        private EventManager $eventManager,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get attribute value for a product
     *
     * @param int $productId
     * @param string $attributeCode
     * @return mixed
     */
    public function getAttribute(int $productId, string $attributeCode): mixed
    {
        $attributes = $this->getAllAttributes($productId);
        return $attributes[$attributeCode] ?? null;
    }

    /**
     * Get all cached attributes for a product
     *
     * @param int $productId
     * @return array
     */
    public function getAllAttributes(int $productId): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;

        // Check in-memory cache first
        if (isset($this->attributeCache[$cacheKey])) {
            return $this->attributeCache[$cacheKey];
        }

        // Try to get from persistent cache
        $cachedData = $this->cache->load($cacheKey);
        if ($cachedData !== false) {
            $attributes = $this->serializer->unserialize($cachedData);
            // Store in memory cache
            $this->attributeCache[$cacheKey] = $attributes;
            return $attributes;
        }

        // Load all attributes from database
        $attributes = $this->loadAllAttributesFromDatabase($productId);

        // Save to cache
        $this->cache->save(
            $this->serializer->serialize($attributes),
            $cacheKey,
            [self::CACHE_TAG, 'product_' . $productId],
            self::CACHE_LIFETIME
        );

        // Store in memory cache
        $this->attributeCache[$cacheKey] = $attributes;

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
                    if ($backendType === 'int' && in_array($attributeCode, self::BOOLEAN_ATTRIBUTES)) {
                        $value = (bool)$value;
                    }

                    $attributes[$attributeCode] = $value;
                }
            }
        }

        // Also fetch option text for select attributes
        $attributes = $this->addOptionLabels($attributes, $productId);

        // Dispatch event to allow other modules to modify the attributes
        $this->eventManager->dispatch('pratech_warehouse_product_attribute_cache_load_after', [
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
        // Check in-memory cache first
        if (!empty(self::$attributeMetadataCache)) {
            return self::$attributeMetadataCache;
        }

        // Try to get from persistent cache
        $cachedData = $this->cache->load(self::ATTRIBUTE_METADATA_CACHE_KEY);
        if ($cachedData !== false) {
            $metadata = $this->serializer->unserialize($cachedData);
            self::$attributeMetadataCache = $metadata;
            return $metadata;
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

        // Save to cache
        $this->cache->save(
            $this->serializer->serialize($metadata),
            self::ATTRIBUTE_METADATA_CACHE_KEY,
            ['warehouse_attribute_metadata'],
            self::CACHE_LIFETIME
        );

        // Store in memory cache
        self::$attributeMetadataCache = $metadata;

        return $metadata;
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

        $optionIds = [];
        foreach (self::SELECT_ATTRIBUTES as $attributeCode) {
            if (isset($attributes[$attributeCode]) && !empty($attributes[$attributeCode])) {
                if (in_array($attributeCode, self::MULTISELECT_ATTRIBUTES)) {
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
        foreach (self::SELECT_ATTRIBUTES as $attributeCode) {
            if (isset($attributes[$attributeCode]) && !empty($attributes[$attributeCode])) {
                if (in_array($attributeCode, self::MULTISELECT_ATTRIBUTES)) {
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
                } else {
                    $attributes[$attributeCode . '_text'] = '';
                }
            } else {
                $attributes[$attributeCode . '_text'] = '';
            }
        }

        return $attributes;
    }

    /**
     * Clear cache for a specific product
     *
     * @param int $productId
     * @return void
     */
    public function clearProductCache(int $productId): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;

        // Clear from persistent cache
        $this->cache->remove($cacheKey);

        // Clear from memory cache
        unset($this->attributeCache[$cacheKey]);
    }

    /**
     * Clear attribute metadata cache
     *
     * @return void
     */
    public function clearAttributeMetadataCache(): void
    {
        $this->cache->remove(self::ATTRIBUTE_METADATA_CACHE_KEY);
        self::$attributeMetadataCache = [];
    }

    /**
     * Clear all product attribute caches
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $this->cache->clean([self::CACHE_TAG]);
        $this->attributeCache = [];
    }
}
