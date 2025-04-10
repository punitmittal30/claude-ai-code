<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Helper;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\Category\FilterableAttributeList;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;

/**
 * Helper class for product filters
 */
class FilterHelper extends AbstractHelper
{
    /**
     * @param Context $context
     * @param FilterableAttributeList $filterableAttributes
     * @param CategoryRepositoryInterface $categoryRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context                             $context,
        private FilterableAttributeList     $filterableAttributes,
        private CategoryRepositoryInterface $categoryRepository,
        private LoggerInterface             $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Get available filter options for a product collection
     *
     * @param Collection $collection
     * @param int|null $categoryId
     * @return array
     */
    public function getAvailableFilters(Collection $collection, ?int $categoryId = null): array
    {
        $result = [];

        try {
            // Get price ranges
            $result['price'] = $this->getPriceRanges($collection);

            // Get category filters if category ID is provided
            if ($categoryId) {
                $result['category'] = $this->getCategoryFilters($categoryId);
            }

            // Get attribute filters - simplified for debugging
            $result['attributes'] = $this->getAttributeFilters($collection);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in getAvailableFilters: ' . $e->getMessage());
            return [
                'price' => [],
                'category' => [],
                'attributes' => []
            ];
        }
    }

    /**
     * Get price range filters
     *
     * @param Collection $collection
     * @return array
     */
    private function getPriceRanges(Collection $collection): array
    {
        try {
            $connection = $collection->getConnection();
            $select = clone $collection->getSelect();

            // Reset columns and add only price stats
            $select->reset(Select::COLUMNS);
            $select->columns([
                'min_price' => 'MIN(price_index.min_price)',
                'max_price' => 'MAX(price_index.max_price)'
            ]);

            // Join with price index table to get prices
            $priceIndexJoin = $select->getPart(Select::FROM)['price_index'] ?? null;

            // Only join price index if not already joined
            if (!$priceIndexJoin) {
                $select->join(
                    ['price_index' => $collection->getTable('catalog_product_index_price')],
                    'e.entity_id = price_index.entity_id',
                    []
                );
            }

            // Execute query
            $result = $connection->fetchRow($select);

            // Get min/max prices
            $minPrice = floor((float)($result['min_price'] ?? 0));
            $maxPrice = ceil((float)($result['max_price'] ?? 0));

            if ($minPrice == $maxPrice || $minPrice > $maxPrice) {
                return [];
            }

            // Create nice rounded price ranges
            return $this->createNiceRanges($minPrice, $maxPrice);
        } catch (Exception $e) {
            $this->logger->error('Error getting price ranges: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create nice rounded price ranges (in hundreds or thousands)
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return array
     */
    private function createNiceRanges(float $minPrice, float $maxPrice): array
    {
        $ranges = [];
        $priceRange = $maxPrice - $minPrice;

        // Determine appropriate step size based on price range
        if ($priceRange <= 100) {
            // Small price range: use steps of 20
            $stepSize = 20;
            $start = floor($minPrice / $stepSize) * $stepSize;
        } elseif ($priceRange <= 500) {
            // Medium price range: use steps of 100
            $stepSize = 100;
            $start = floor($minPrice / $stepSize) * $stepSize;
        } elseif ($priceRange <= 2000) {
            // Larger price range: use steps of 500
            $stepSize = 500;
            $start = floor($minPrice / $stepSize) * $stepSize;
        } elseif ($priceRange <= 10000) {
            // Large price range: use steps of 1000
            $stepSize = 1000;
            $start = floor($minPrice / $stepSize) * $stepSize;
        } else {
            // Very large price range: use steps of 5000
            $stepSize = 5000;
            $start = floor($minPrice / $stepSize) * $stepSize;
        }

        // Generate price ranges
        $current = $start;
        $rangeCount = 0;

        while ($current < $maxPrice && $rangeCount < 5) {
            $next = $current + $stepSize;
            $ranges[] = [
                'from' => $current,
                'to' => $next,
                'label' => $this->formatPrice($current) . ' - ' . $this->formatPrice($next)
            ];
            $current = $next;
            $rangeCount++;
        }

        return $ranges;
    }

    /**
     * Format price value
     *
     * @param float $price
     * @return string
     */
    private function formatPrice(float $price): string
    {
        return number_format($price, 2);
    }

    /**
     * Get category filters
     *
     * @param int $categoryId
     * @return array
     */
    private function getCategoryFilters(int $categoryId): array
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            $childCategories = $category->getChildrenCategories();

            $result = [];
            foreach ($childCategories as $childCategory) {
                if ($childCategory->getIsActive()) {
                    $result[] = [
                        'id' => $childCategory->getId(),
                        'label' => $childCategory->getName(),
                        'count' => $childCategory->getProductCount()
                    ];
                }
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error getting category filters: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attribute filters (simplified for debugging)
     *
     * @param Collection $collection
     * @return array
     */
    private function getAttributeFilters(Collection $collection): array
    {
        $result = [];

        try {
            // Get all product IDs first (safer approach)
            $productIds = $this->getProductIdsFromCollection($collection);

            if (empty($productIds)) {
                return [];
            }

            // Get filterable attributes
            $attributeCollection = $this->filterableAttributes->getList();

            foreach ($attributeCollection as $attribute) {
                $this->logger->debug('CUSTOM_LOGGING', ['Attribute Filter' => $attribute->getAttributeCode()]);

                if ($attribute->getIsFilterable()) {
                    $options = $this->getAttributeOptionsSimple($attribute, $productIds);

                    if (!empty($options)) {
                        $result[$attribute->getAttributeCode()] = [
                            'attribute_id' => $attribute->getAttributeId(),
                            'attribute_code' => $attribute->getAttributeCode(),
                            'attribute_label' => $attribute->getStoreLabel(),
                            'options' => $options
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error getting attribute filters: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get product IDs from collection
     *
     * @param Collection $collection
     * @return array
     */
    private function getProductIdsFromCollection(Collection $collection): array
    {
        try {
            $clonedCollection = clone $collection;
            $clonedCollection->clear();
            $clonedCollection->getSelect()->reset(Select::COLUMNS);
            $clonedCollection->getSelect()->columns(['entity_id' => 'e.entity_id']);
            $clonedCollection->getSelect()->reset(Select::ORDER);
            $clonedCollection->getSelect()->reset(Select::LIMIT_COUNT);
            $clonedCollection->getSelect()->reset(Select::LIMIT_OFFSET);

            $ids = [];
            foreach ($clonedCollection as $product) {
                $ids[] = $product->getEntityId();
            }

            return $ids;
        } catch (Exception $e) {
            $this->logger->error('Error getting product IDs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attribute options (simplified method)
     *
     * @param Attribute $attribute
     * @param array $productIds
     * @return array
     */
    private function getAttributeOptionsSimple(Attribute $attribute, array $productIds): array
    {
        $options = [];

        try {
            // Just return some sample options for testing
            $allOptions = $attribute->getOptions();

            foreach ($allOptions as $option) {
                if (!$option->getValue()) {
                    continue;
                }

                $options[] = [
                    'value' => $option->getValue(),
                    'label' => $option->getLabel(),
                    'count' => 1 // Simplify for testing
                ];
            }

            return array_slice($options, 0, 5); // Return only first 5 for testing
        } catch (Exception $e) {
            $this->logger->error('Error getting attribute options simple: ' . $e->getMessage());
            return [];
        }
    }
}
