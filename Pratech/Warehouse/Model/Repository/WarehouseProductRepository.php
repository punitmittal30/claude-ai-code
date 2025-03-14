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

namespace Pratech\Warehouse\Model\Repository;

use Exception;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Warehouse\Api\Data\WarehouseInterface;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterfaceFactory;
use Pratech\Warehouse\Api\WarehouseProductRepositoryInterface;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Pratech\Warehouse\Helper\ProductResponseFormatter;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;

class WarehouseProductRepository implements WarehouseProductRepositoryInterface
{
    /**
     * @param ResourceConnection $resource
     * @param CollectionFactory $productCollectionFactory
     * @param WarehouseProductResultInterfaceFactory $resultFactory
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param LoggerInterface $logger
     * @param ProductResponseFormatter $responseFormatter
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        private ResourceConnection                     $resource,
        private CollectionFactory                      $productCollectionFactory,
        private WarehouseProductResultInterfaceFactory $resultFactory,
        private WarehouseRepositoryInterface           $warehouseRepository,
        private LoggerInterface                        $logger,
        private ProductResponseFormatter               $responseFormatter,
        private ProductAttributeRepositoryInterface    $attributeRepository
    ) {
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
    ) {
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
                ->from($slaTable)
                ->where('customer_pincode = ?', $pincode)
                ->where('warehouse_pincode IN (?)', $warehousePincodes)
                ->order('priority ASC')
                ->order('delivery_time ASC')
                ->limit(1);

            $slaData = $connection->fetchRow($select);

            if (!$slaData) {
                // No SLA data found, return first dark store
                return reset($darkStores);
            }

            // Get warehouse by pincode
            $warehousePincode = $slaData['warehouse_pincode'];

            foreach ($darkStores as $darkStore) {
                if ((int)$darkStore['warehouse_pincode'] === (int)$warehousePincode) {
                    return $darkStore;
                }
            }

            // If no matching warehouse found, return first dark store
            return reset($darkStores);
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
    ) {
        try {
            // Get warehouse info for validation and result data
            $warehouse = $this->getWarehouseByCode($warehouseCode);

            // Create product collection
            $collection = $this->getProductCollection();

            // Join with warehouse inventory
            $this->joinWithWarehouseInventory($collection, $warehouseCode);

            // Clone collection before applying filters to calculate total available options
            $unfilteredCollection = clone $collection;

            // Apply custom filters if provided
            if ($filters) {
                $this->applyFilters($collection, $filters);
            }

            // Generate available filters based on the current filtered collection
            $availableFilters = $this->generateAvailableFilters($collection, $unfilteredCollection, $filters);

            // Apply pagination
            $collection->setPageSize($pageSize);
            $collection->setCurPage($currentPage);

            // Apply sorting
            if ($sortField) {
                $collection->setOrder($sortField, $sortDirection);
            }

            // Format the items for the response
            $formattedItems = [];
            foreach ($collection->getItems() as $product) {
                $formattedItems[] = $this->responseFormatter->formatProduct($product);
            }

            // Create result object
            $result = $this->resultFactory->create();
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($warehouse->getName());
            $result->setItems($formattedItems);
            $result->setTotalCount($collection->getSize());

            // FIX: Set available filters as an array of objects directly, not as serialized strings
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
     * Get warehouse by code
     *
     * @param string $warehouseCode
     * @return WarehouseInterface
     * @throws NoSuchEntityException
     */
    private function getWarehouseByCode(string $warehouseCode)
    {
        try {
            return $this->warehouseRepository->getByCode($warehouseCode);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Warehouse not found: ' . $e->getMessage());
            throw new NoSuchEntityException(__('Warehouse with code "%1" does not exist.', $warehouseCode));
        }
    }

    /**
     * Get base product collection
     *
     * @return Collection
     */
    private function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('status', Status::STATUS_ENABLED);
        $collection->addFieldToFilter('visibility', ['neq' => 1]);

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
     * Generate available filters based on the current filtered collection
     *
     * @param Collection $filteredCollection The collection after filters are applied
     * @param Collection $unfilteredCollection The original collection before filters
     * @param array $appliedFilters Currently applied filters
     * @return array
     */
    private function generateAvailableFilters(
        Collection $filteredCollection,
        Collection $unfilteredCollection,
        array $appliedFilters
    ): array {
        $filters = [];

        // Get price ranges
        $priceRanges = $this->getPriceRanges($filteredCollection);
        if (!empty($priceRanges)) {
            // Use a consistent format for all filter types
            $filters[] = [
                'name' => 'Price',
                'code' => 'price',
                'ranges' => $priceRanges
            ];
        }

        // Get attribute filters (brand, color, etc.)
        $attributeFilters = $this->getAttributeFilters($filteredCollection, $unfilteredCollection, $appliedFilters);

        // Add attribute filters to the list (they're already objects)
        if (!empty($attributeFilters)) {
            foreach ($attributeFilters as $filter) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Get price ranges from collection
     *
     * @param Collection $collection
     * @return array
     */
    private function getPriceRanges(Collection $collection): array
    {
        $priceRanges = [];

        try {
            $connection = $collection->getConnection();
            $select = clone $collection->getSelect();

            // Reset columns and add only price stats
            $select->reset(Select::COLUMNS);
            $select->columns([
                'min_price' => 'MIN(price_index.min_price)',
                'max_price' => 'MAX(price_index.max_price)'
            ]);

            // Join with price index table to get prices if not already joined
            $priceIndexJoin = $select->getPart(Select::FROM)['price_index'] ?? null;
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

            $start = floor($minPrice / $stepSize) * $stepSize;
            $current = $start;
            $rangeCount = 0;

            while ($current < $maxPrice && $rangeCount < 5) {
                $next = $current + $stepSize;
                $priceRanges[] = [
                    'from' => $current,
                    'to' => $next,
                    'label' => number_format($current, 2) . ' - ' . number_format($next, 2),
                    'count' => $this->countProductsInPriceRange($collection, $current, $next)
                ];
                $current = $next;
                $rangeCount++;
            }
        } catch (Exception $e) {
            $this->logger->error('Error getting price ranges: ' . $e->getMessage());
        }

        return $priceRanges;
    }

    /**
     * Count products in a price range
     *
     * @param Collection $collection
     * @param float $from
     * @param float $to
     * @return int
     */
    private function countProductsInPriceRange(Collection $collection, float $from, float $to): int
    {
        $clonedCollection = clone $collection;
        $clonedCollection->addFieldToFilter('price', ['from' => $from, 'to' => $to]);
        return $clonedCollection->getSize();
    }

    /**
     * Get attribute filters with counts
     *
     * @param Collection $filteredCollection
     * @param Collection $unfilteredCollection
     * @param array $appliedFilters
     * @return array
     */
    private function getAttributeFilters(
        Collection $filteredCollection,
        Collection $unfilteredCollection,
        array $appliedFilters
    ): array {
        $attributeFilters = [];

        // List of attribute codes to include as filters
        $attributesToFilter = [
            'brand',
            'dietary_preference',
            'form',
            'gender',
            'size',
            'color',
            'material'
        ];

        foreach ($attributesToFilter as $attributeCode) {
            // Skip if this filter is already applied (to avoid limiting options)
            if (isset($appliedFilters[$attributeCode])) {
                continue;
            }

            try {
                $attribute = $this->attributeRepository->get($attributeCode);
                $options = $attribute->getSource()->getAllOptions(false);

                if (empty($options)) {
                    continue;
                }

                $filterOptions = [];
                foreach ($options as $option) {
                    if (empty($option['value'])) {
                        continue;
                    }

                    // Count products with this attribute option
                    $count = $this->countProductsWithAttributeOption(
                        $filteredCollection,
                        $attributeCode,
                        $option['value']
                    );

                    // Only include options that have at least one product
                    if ($count > 0) {
                        $filterOptions[] = [
                            'value' => $option['value'],
                            'label' => $option['label'],
                            'count' => $count
                        ];
                    }
                }

                if (!empty($filterOptions)) {
                    // Fix: Return as direct object, not as serialized string
                    $attributeFilters[$attributeCode] = [
                        'name' => $attribute->getStoreLabel(),
                        'code' => $attributeCode,
                        'options' => $filterOptions
                    ];
                }
            } catch (Exception $e) {
                $this->logger->error(sprintf('Error processing attribute %s: %s', $attributeCode, $e->getMessage()));
            }
        }

        return $attributeFilters;
    }

    /**
     * Count products with specific attribute option
     *
     * @param Collection $collection
     * @param string $attributeCode
     * @param mixed $optionValue
     * @return int
     */
    private function countProductsWithAttributeOption(Collection $collection, string $attributeCode, $optionValue): int
    {
        $clonedCollection = clone $collection;
        $clonedCollection->addAttributeToFilter($attributeCode, $optionValue);
        return $clonedCollection->getSize();
    }
}
