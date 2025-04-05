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

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Pratech\Warehouse\Helper\Config;
use Psr\Log\LoggerInterface;

/**
 * Service class for building product collections
 */
class ProductCollectionService
{
    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ResourceConnection $resource
     * @param Config $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CollectionFactory $productCollectionFactory,
        private ResourceConnection $resource,
        private Config $configHelper,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get base product collection
     *
     * @return Collection
     */
    public function getBaseCollection(): Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['neq' => 1]);
        $collection->addStoreFilter();

        return $collection;
    }

    /**
     * Join collection with warehouse inventory
     *
     * @param Collection $collection
     * @param string $warehouseCode
     * @param bool $onlyInStock Only include products with positive stock
     * @return Collection
     */
    public function joinWithWarehouseInventory(
        Collection $collection,
        string $warehouseCode,
        bool $onlyInStock = true
    ): Collection {
        $collection->getSelect()->join(
            ['inventory' => $this->resource->getTableName('pratech_warehouse_inventory')],
            'e.sku = inventory.sku',
            ['warehouse_quantity' => 'inventory.quantity']
        )->where('inventory.warehouse_code = ?', $warehouseCode);

        if ($onlyInStock) {
            $collection->getSelect()->where('inventory.quantity > ?', 0);
        }

        return $collection;
    }

    /**
     * Apply filters to collection
     *
     * @param Collection $collection
     * @param array $filters
     * @return Collection
     */
    public function applyFilters(Collection $collection, array $filters): Collection
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

        return $collection;
    }

    /**
     * Add category filter
     *
     * @param Collection $collection
     * @param int|array $categoryIds
     * @return Collection
     */
    public function addCategoryFilter(Collection $collection, $categoryIds): Collection
    {
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $collection->addCategoriesFilter(['in' => $categoryIds]);
        return $collection;
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
    public function getOptimizedProductCollection(
        string $warehouseCode,
        int $pageSize,
        int $currentPage,
        ?string $sortField,
        ?string $sortDirection,
        array $filters
    ): Collection {
        $collection = $this->getBaseCollection();

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
     * Get product IDs from collection efficiently
     *
     * @param Collection $collection
     * @return array
     */
    public function getProductIdsFromCollection(Collection $collection): array
    {
        try {
            $select = clone $collection->getSelect();
            $select->reset(Select::COLUMNS);
            $select->columns(['entity_id' => 'e.entity_id']);
            $select->reset(Select::ORDER);
            $select->reset(Select::LIMIT_COUNT);
            $select->reset(Select::LIMIT_OFFSET);

            return $this->resource->getConnection()->fetchCol($select);
        } catch (\Exception $e) {
            $this->logger->error('Error getting product IDs: ' . $e->getMessage());
            return [];
        }
    }
}
