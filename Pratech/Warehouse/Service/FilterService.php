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
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

/**
 * Service for generating product filters
 */
class FilterService
{
    /**
     * @param ResourceConnection $resource
     * @param ProductCollectionService $collectionService
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ResourceConnection                  $resource,
        private ProductCollectionService            $collectionService,
        private ProductAttributeRepositoryInterface $attributeRepository,
        private LoggerInterface                     $logger
    ) {
    }

    /**
     * Generate efficient filters using selective queries
     *
     * @param Collection $collection
     * @return array
     */
    public function generateEfficientFilters(Collection $collection): array
    {
        $filters = [];

        try {
            // Get price ranges
            $priceRanges = $this->getEfficientPriceRanges($collection);
            if (!empty($priceRanges)) {
                $filters[] = [
                    'label' => 'Price',
                    'attribute_code' => 'price',
                    'options' => $priceRanges
                ];
            }

            // Get product IDs from collection first
            $productIds = $this->collectionService->getProductIdsFromCollection($collection);

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
                            'label' => $attribute->getStoreLabel(),
                            'attribute_code' => $attributeCode,
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
            $select->reset(Select::COLUMNS);
            $select->reset(Select::ORDER);
            $select->reset(Select::LIMIT_COUNT);
            $select->reset(Select::LIMIT_OFFSET);

            // Join with price table to get the price data
            $select->joinLeft(
                ['price_table' => $this->resource->getTableName('catalog_product_entity_decimal')],
                "e.entity_id = price_table.entity_id AND price_table.attribute_id = {$priceAttributeId} AND price_table.store_id = 0",
                []
            );

            // Add only the columns needed for price calculation
            $select->columns([
                'min_price' => new Zend_Db_Expr('MIN(price_table.value)'),
                'max_price' => new Zend_Db_Expr('MAX(price_table.value)')
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
            $select->reset(Select::COLUMNS);
            $select->reset(Select::ORDER);
            $select->reset(Select::LIMIT_COUNT);
            $select->reset(Select::LIMIT_OFFSET);

            // Join with price table if not already joined
            $fromPart = $select->getPart(Select::FROM);

            if (!isset($fromPart['price_table'])) {
                $select->joinLeft(
                    ['price_table' => $this->resource->getTableName('catalog_product_entity_decimal')],
                    "e.entity_id = price_table.entity_id AND price_table.attribute_id = {$priceAttributeId} AND price_table.store_id = 0",
                    []
                );
            }

            // Just count the rows with price in range
            $select->columns(['count' => new Zend_Db_Expr('COUNT(DISTINCT e.entity_id)')])
                ->where('price_table.value >= ?', $from)
                ->where('price_table.value < ?', $to);

            return (int)$connection->fetchOne($select);
        } catch (Exception $e) {
            $this->logger->error('Error getting price range count: ' . $e->getMessage());
            return 0;
        }
    }
}
