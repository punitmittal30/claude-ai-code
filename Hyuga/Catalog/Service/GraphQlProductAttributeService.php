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

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class GraphQlProductAttributeService
{
    /**
     * Static attributes cache lifetime - 1 Week
     */
    private const STATIC_CACHE_LIFETIME = 604800;

    /**
     * Dynamic attributes cache lifetime - 1 hour
     */
    private const DYNAMIC_CACHE_LIFETIME = 3600;

    /**
     * Cache key prefix for review data
     */
    private const REVIEW_CACHE_KEY_PREFIX = 'product_review_data_';

    /**
     * In-memory cache for review data
     *
     * @var array
     */
    private array $reviewCache = [];

    /**
     * @param CacheServiceInterface $cacheService
     * @param SerializerInterface $serializer
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        private SerializerInterface   $serializer,
        private ResourceConnection    $resourceConnection,
        private StoreManagerInterface $storeManager,
        private LoggerInterface       $logger
    ) {
    }

    /**
     * Get static product attributes (rarely changing)
     *
     * @param int $productId
     * @return array
     */
    public function getStaticAttributes(int $productId): array
    {
        $cacheKey = $this->cacheService->getProductStaticAttributesCacheKey($productId);
        $cachedData = $this->cacheService->get($cacheKey);

        if ($cachedData) {
            try {
                return $this->serializer->unserialize($cachedData);
            } catch (Exception $e) {
                $this->logger->error('Error un-serializing static attributes: ' . $e->getMessage());
            }
        }

        $attributes = $this->fetchStaticAttributes($productId);

        try {
            $this->cacheService->save(
                $cacheKey,
                $this->serializer->serialize($attributes),
                ['product_combined_attributes_' . $productId],
                self::STATIC_CACHE_LIFETIME
            );
        } catch (Exception $e) {
            $this->logger->error('Error caching static attributes: ' . $e->getMessage());
        }

        return $attributes;
    }

    /**
     * Fetch static attributes from database optimized for performance
     *
     * @param int $productId
     * @return array
     */
    private function fetchStaticAttributes(int $productId): array
    {
        try {
            // Define the list of static attributes we need to fetch
            $staticAttributeCodes = [
                'badges',
                'color',
                'dietary_preference',
                'material',
                'size',
                'flavour',
                'item_weight',
                'pack_of',
                'pack_size',
                'deal_of_the_day',
                'is_hl_verified',
                'is_hm_verified',
                'number_of_servings'
            ];

            // Get EAV data in a single efficient query
            $attributeData = $this->getProductAttributesData($productId, $staticAttributeCodes);

            // Process attribute values
            $result = [];
            foreach ($attributeData as $code => $data) {
                // Convert option values to text for attributes that need label lookup
                if (in_array($code, ['badges', 'color', 'dietary_preference', 'material', 'size', 'flavour', 'item_weight', 'pack_of', 'pack_size'])
                    && !empty($data['value']) && !empty($data['is_option'])
                ) {
                    $result[$code . '_text'] = $this->getAttributeOptionText($code, $data['value']);
                }

                // Handle boolean values
                if ($code === 'deal_of_the_day') {
                    $result[$code] = !empty($data['value']);
                    continue;
                }

                // Handle integer values
                if (in_array($code, ['is_hl_verified', 'is_hm_verified'])) {
                    $result[$code] = (int)($data['value'] ?? 0);
                    continue;
                }

                // Add raw value for regular attributes
                $result[$code] = $data['value'] ?? '';
            }

            $reviewData = $this->getProductReviewData($productId);
            $result['review_count'] = $reviewData['review_count'];
            $result['rating_summary'] = $reviewData['rating_summary'];

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error fetching static attributes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get multiple product attribute values in a single efficient query
     *
     * @param int $productId
     * @param array $attributeCodes
     * @return array
     */
    private function getProductAttributesData(int $productId, array $attributeCodes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $entityTypeId = $this->getProductEntityTypeId();

        // Get attribute information (IDs, backend types)
        $attributeInfo = $this->getAttributeInfo($attributeCodes, $entityTypeId);

        if (empty($attributeInfo)) {
            return [];
        }

        // Group attributes by backend type for efficient querying
        $attributesByType = [];
        foreach ($attributeInfo as $code => $info) {
            $attributesByType[$info['backend_type']][$code] = $info['attribute_id'];
        }

        $result = [];

        // Query for each backend type separately
        foreach ($attributesByType as $backendType => $attributes) {
            if ($backendType === 'static') {
                // Handle static attributes (usually in entity table)
                $select = $connection->select()
                    ->from(
                        ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                        array_keys($attributes)
                    )
                    ->where('cpe.entity_id = ?', $productId);

                $staticData = $connection->fetchRow($select);

                if ($staticData) {
                    foreach ($staticData as $code => $value) {
                        $result[$code] = [
                            'value' => $value,
                            'is_option' => false
                        ];
                    }
                }

                continue;
            }

            // Skip invalid backend types
            if ($backendType === 'virtual' || empty($backendType)) {
                continue;
            }

            // Query for non-static attributes from their respective tables
            $tableName = $this->resourceConnection->getTableName("catalog_product_entity_$backendType");
            $attributeIds = array_values($attributes);
            $attributeCodeMap = array_flip($attributes);

            $select = $connection->select()
                ->from(
                    ['attr' => $tableName],
                    ['attribute_id', 'value']
                )
                ->where('attr.entity_id = ?', $productId)
                ->where('attr.attribute_id IN (?)', $attributeIds)
                ->where('attr.store_id IN (0, ?)', $this->getStoreId());

            $attributeData = $connection->fetchAll($select);

            // Process and add to results
            foreach ($attributeData as $data) {
                $attrId = $data['attribute_id'];
                if (isset($attributeCodeMap[$attrId])) {
                    $code = $attributeCodeMap[$attrId];
                    $result[$code] = [
                        'value' => $data['value'],
                        'is_option' => $attributeInfo[$code]['is_option'] ?? false
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Get cached product entity type ID
     *
     * @return int
     */
    private function getProductEntityTypeId(): int
    {
        static $entityTypeId = null;

        if ($entityTypeId === null) {
            try {
                $connection = $this->resourceConnection->getConnection();
                $select = $connection->select()
                    ->from(
                        ['et' => $this->resourceConnection->getTableName('eav_entity_type')],
                        ['entity_type_id']
                    )
                    ->where('et.entity_type_code = ?', 'catalog_product');

                $entityTypeId = (int)$connection->fetchOne($select);
            } catch (Exception $e) {
                $this->logger->error('Error getting product entity type ID: ' . $e->getMessage());
                $entityTypeId = 4; // Default fallback for catalog_product
            }
        }

        return $entityTypeId;
    }

    /**
     * Get cached attribute information
     *
     * @param array $attributeCodes
     * @param int $entityTypeId
     * @return array
     */
    private function getAttributeInfo(array $attributeCodes, int $entityTypeId): array
    {
        static $attributeInfoCache = [];
        $cacheKey = md5(json_encode($attributeCodes));

        if (isset($attributeInfoCache[$cacheKey])) {
            return $attributeInfoCache[$cacheKey];
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(
                    ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                    [
                        'attribute_id',
                        'attribute_code',
                        'backend_type',
                        'frontend_input'
                    ]
                )
                ->where('ea.entity_type_id = ?', $entityTypeId)
                ->where('ea.attribute_code IN (?)', $attributeCodes);

            $attributes = $connection->fetchAll($select);

            $result = [];
            foreach ($attributes as $attribute) {
                $result[$attribute['attribute_code']] = [
                    'attribute_id' => $attribute['attribute_id'],
                    'backend_type' => $attribute['backend_type'],
                    'is_option' => in_array($attribute['frontend_input'], ['select', 'multiselect'])
                ];
            }

            $attributeInfoCache[$cacheKey] = $result;
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error getting attribute info: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get current store ID
     *
     * @return int
     */
    private function getStoreId(): int
    {
        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (Exception $e) {
            $this->logger->error('Error getting store ID: ' . $e->getMessage());
            return 0; // Default to admin store view
        }
    }

    /**
     * Get attribute option text from option value
     *
     * @param string $attributeCode
     * @param int|string $optionValue
     * @return string
     */
    private function getAttributeOptionText(string $attributeCode, int|string $optionValue): string
    {
        static $optionsCache = [];

        // For multiselect values
        if (is_string($optionValue) && str_contains($optionValue, ',')) {
            $values = explode(',', $optionValue);
            $labels = [];

            foreach ($values as $value) {
                $labels[] = $this->getAttributeOptionText($attributeCode, $value);
            }

            return implode(', ', $labels);
        }

        // Check cache first
        $cacheKey = $attributeCode . '|' . $optionValue;
        if (isset($optionsCache[$cacheKey])) {
            return $optionsCache[$cacheKey];
        }

        try {
            $connection = $this->resourceConnection->getConnection();

            // Get attribute ID
            $select = $connection->select()
                ->from(
                    ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                    ['attribute_id']
                )
                ->where('ea.attribute_code = ?', $attributeCode)
                ->where('ea.entity_type_id = ?', $this->getProductEntityTypeId());

            $attributeId = $connection->fetchOne($select);

            if (!$attributeId) {
                return (string)$optionValue;
            }

            // Get option text
            $select = $connection->select()
                ->from(
                    ['eaov' => $this->resourceConnection->getTableName('eav_attribute_option_value')],
                    ['value']
                )
                ->join(
                    ['eao' => $this->resourceConnection->getTableName('eav_attribute_option')],
                    'eaov.option_id = eao.option_id',
                    []
                )
                ->where('eao.attribute_id = ?', $attributeId)
                ->where('eao.option_id = ?', $optionValue)
                ->where('eaov.store_id IN (0, ?)', $this->getStoreId())
                ->order('eaov.store_id DESC')  // Store-specific value takes precedence
                ->limit(1);

            $optionText = $connection->fetchOne($select);

            $result = $optionText !== false ? $optionText : (string)$optionValue;
            $optionsCache[$cacheKey] = $result;

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error getting option text: ' . $e->getMessage());
            return (string)$optionValue;
        }
    }

    /**
     * Get review data (count and rating) for a product
     *
     * @param int $productId
     * @return array
     */
    public function getProductReviewData(int $productId): array
    {
        $cacheKey = self::REVIEW_CACHE_KEY_PREFIX . $productId;

        // Check in-memory cache first
        if (isset($this->reviewCache[$cacheKey])) {
            return $this->reviewCache[$cacheKey];
        }

        // Check persistent cache
        $cachedData = $this->cacheService->get($cacheKey);
        if ($cachedData) {
            // Store in memory cache
            $this->reviewCache[$cacheKey] = $cachedData;
            return $cachedData;
        }

        // Default values
        $reviewData = [
            'rating_summary' => 0
        ];

        $connection = $this->resourceConnection->getConnection();

        // Get review count
        $reviewSelect = $connection->select()
            ->from(
                ['r' => $this->resourceConnection->getTableName('review')],
                ['review_count' => 'COUNT(*)']
            )
            ->join(
                ['rs' => $this->resourceConnection->getTableName('review_status')],
                'r.status_id = rs.status_id',
                []
            )
            ->where('r.entity_pk_value = ?', $productId)
            ->where('rs.status_code = ?', 'Approved');

        $reviewCount = (int)$connection->fetchOne($reviewSelect);
        $reviewData['review_count'] = $reviewCount;

        // Calculate average rating if reviews exist
        if ($reviewCount > 0) {
            $ratingSelect = $connection->select()
                ->from(
                    ['r' => $this->resourceConnection->getTableName('review')],
                    []
                )
                ->join(
                    ['rd' => $this->resourceConnection->getTableName('rating_option_vote')],
                    'r.review_id = rd.review_id',
                    ['rating_summary' => 'AVG(rd.percent)']
                )
                ->join(
                    ['rs' => $this->resourceConnection->getTableName('review_status')],
                    'r.status_id = rs.status_id',
                    []
                )
                ->where('r.entity_pk_value = ?', $productId)
                ->where('rs.status_code = ?', 'Approved');

            $ratingSummary = $connection->fetchOne($ratingSelect);
            $reviewData['rating_summary'] = $ratingSummary ? round((float)$ratingSummary, 1) : 0;
        }

        // Save to cache
        $this->cacheService->save(
            $cacheKey,
            $reviewData,
            ['product_reviews_data'],
            self::STATIC_CACHE_LIFETIME
        );

        // Store in memory cache
        $this->reviewCache[$cacheKey] = $reviewData;

        return $reviewData;
    }

    /**
     * Get dynamic product attributes (frequently changing)
     *
     * @param int $productId
     * @return array
     */
    public function getDynamicAttributes(int $productId): array
    {
        $cacheKey = $this->cacheService->getProductDynamicAttributesCacheKey($productId);
        $cachedData = $this->cacheService->get($cacheKey);

        if ($cachedData) {
            try {
                return $this->serializer->unserialize($cachedData);
            } catch (Exception $e) {
                $this->logger->error('Error un-serializing dynamic attributes: ' . $e->getMessage());
            }
        }

        $attributes = $this->fetchDynamicAttributes($productId);

        try {
            $this->cacheService->save(
                $cacheKey,
                $this->serializer->serialize($attributes),
                ['product_combined_attributes_' . $productId],
                self::DYNAMIC_CACHE_LIFETIME
            );
        } catch (Exception $e) {
            $this->logger->error('Error caching dynamic attributes: ' . $e->getMessage());
        }

        return $attributes;
    }

    /**
     * Fetch dynamic attributes from database optimized for performance
     *
     * @param int $productId
     * @return array
     */
    private function fetchDynamicAttributes(int $productId): array
    {
        try {
            // Define dynamic attribute codes
            $dynamicAttributeCodes = [
                'price',
                'special_price',
                'special_from_date',
                'special_to_date',
                'total_number_of_count',
                'total_volume_in_ml',
                'number_of_serving_for_price_per_serving',
                'protein_per_serving'
            ];

            // Get price and special price data
            $attributeData = $this->getProductAttributesData($productId, $dynamicAttributeCodes);

            // Process into result array
            $result = [];
            foreach ($attributeData as $code => $data) {
                if (in_array($code, ['price', 'special_price'])) {
                    $result[$code] = (float)($data['value'] ?? 0);
                } else {
                    $result[$code] = $data['value'] ?? '';
                }
            }

            // Get stock information directly from the stock table
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(
                    ['csi' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                    ['qty', 'is_in_stock', 'stock_status' => 'is_in_stock']
                )
                ->where('csi.product_id = ?', $productId)
                ->where('csi.stock_id = ?', 1);

            $stockData = $connection->fetchRow($select);

            if ($stockData) {
                $result['stock_status'] = (int)$stockData['is_in_stock'] ? 'IN_STOCK' : 'OUT_OF_STOCK';
            } else {
                $result['stock_status'] = 'OUT_OF_STOCK';
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error fetching dynamic attributes: ' . $e->getMessage());
            return [];
        }
    }
}
