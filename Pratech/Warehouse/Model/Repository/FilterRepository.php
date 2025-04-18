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
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Warehouse\Api\Data\AttributeFilterInterface;
use Pratech\Warehouse\Api\Data\AttributeFilterInterfaceFactory;
use Pratech\Warehouse\Api\Data\AttributeOptionInterface;
use Pratech\Warehouse\Api\Data\AttributeOptionInterfaceFactory;
use Pratech\Warehouse\Api\Data\CategoryFilterInterface;
use Pratech\Warehouse\Api\Data\CategoryFilterInterfaceFactory;
use Pratech\Warehouse\Api\Data\FilterResultInterface;
use Pratech\Warehouse\Api\Data\FilterResultInterfaceFactory;
use Pratech\Warehouse\Api\Data\PriceRangeInterfaceFactory;
use Pratech\Warehouse\Api\Data\WarehouseInterface;
use Pratech\Warehouse\Api\FilterRepositoryInterface;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Pratech\Warehouse\Helper\FilterHelper;
use Psr\Log\LoggerInterface;

/**
 * Filter repository implementation
 */
class FilterRepository implements FilterRepositoryInterface
{
    /**
     * @param CollectionFactory $productCollectionFactory
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param FilterHelper $filterHelper
     * @param ResourceConnection $resource
     * @param FilterResultInterfaceFactory $filterResultFactory
     * @param CategoryFilterInterfaceFactory $categoryFilterFactory
     * @param AttributeFilterInterfaceFactory $attributeFilterFactory
     * @param AttributeOptionInterfaceFactory $attributeOptionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CollectionFactory               $productCollectionFactory,
        private WarehouseRepositoryInterface    $warehouseRepository,
        private FilterHelper                    $filterHelper,
        private ResourceConnection              $resource,
        private FilterResultInterfaceFactory    $filterResultFactory,
        private CategoryFilterInterfaceFactory  $categoryFilterFactory,
        private AttributeFilterInterfaceFactory $attributeFilterFactory,
        private AttributeOptionInterfaceFactory $attributeOptionFactory,
        private LoggerInterface                 $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getDarkStoreFilters(
        int  $pincode,
        ?int $categoryId = null
    ): FilterResultInterface {
        try {
            // Get nearest dark store for this pincode
            $darkStore = $this->findNearestDarkStore($pincode);

            if (!$darkStore) {
                throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
            }

            // Use the warehouse code to get filters
            return $this->getWarehouseFilters($darkStore['warehouse_code'], $categoryId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Dark store not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving filters by pincode: ' . $e->getMessage());
            throw new LocalizedException(__('Could not retrieve filters for pincode: %1', $e->getMessage()));
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
    public function getWarehouseFilters(
        string $warehouseCode,
        ?int   $categoryId = null
    ): FilterResultInterface {
        try {
            // Get warehouse info
            $warehouse = $this->getWarehouseByCode($warehouseCode);

            // Create product collection with warehouse inventory filter
            $collection = $this->createProductCollection($warehouseCode, $categoryId);

            // Get available filters
            $filters = $this->filterHelper->getAvailableFilters($collection, $categoryId);

            // Create and populate result object
            $result = $this->filterResultFactory->create();
            $result->setWarehouseCode($warehouseCode);
            $result->setWarehouseName($warehouse->getName());

            // Set categories
            $categories = $this->createCategoryFilters($filters['category'] ?? []);
            $result->setCategories($categories);

            // Set attributes
            $attributes = $this->createAttributeFilters($filters['attributes'] ?? []);
            $result->setAttributes($attributes);

            return $result;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Warehouse not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving filters: ' . $e->getMessage());
            throw new LocalizedException(__('Could not retrieve filters: %1', $e->getMessage()));
        }
    }

    /**
     * Get warehouse by code
     *
     * @param string $warehouseCode
     * @return WarehouseInterface
     */
    private function getWarehouseByCode(string $warehouseCode)
    {
        return $this->warehouseRepository->getByCode($warehouseCode);
    }

    /**
     * Create product collection filtered by warehouse
     *
     * @param string $warehouseCode
     * @param int|null $categoryId
     * @return Collection
     */
    private function createProductCollection(string $warehouseCode, ?int $categoryId = null)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        // Join with warehouse inventory
        $collection->getSelect()->join(
            ['inventory' => $collection->getTable('pratech_warehouse_inventory')],
            'e.sku = inventory.sku',
            ['warehouse_quantity' => 'inventory.quantity']
        )->where('inventory.warehouse_code = ?', $warehouseCode)
            ->where('inventory.quantity > ?', 0);

        // Filter by category if provided
        if ($categoryId) {
            $collection->addCategoriesFilter(['eq' => $categoryId]);
        }

        return $collection;
    }

    /**
     * Create category filter objects
     *
     * @param array $categories
     * @return CategoryFilterInterface[]
     */
    private function createCategoryFilters(array $categories): array
    {
        $result = [];

        foreach ($categories as $category) {
            /** @var CategoryFilterInterface $categoryFilter */
            $categoryFilter = $this->categoryFilterFactory->create();
            $categoryFilter->setId((int)$category['id']);
            $categoryFilter->setLabel($category['label']);
            $categoryFilter->setCount((int)$category['count']);

            $result[] = $categoryFilter;
        }

        return $result;
    }

    /**
     * Create attribute filter objects
     *
     * @param array $attributes
     * @return AttributeFilterInterface[]
     */
    private function createAttributeFilters(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $attribute) {
            /** @var AttributeFilterInterface $attributeFilter */
            $attributeFilter = $this->attributeFilterFactory->create();
            $attributeFilter->setAttributeId((int)($attribute['attribute_id'] ?? 0));
            $attributeFilter->setAttributeCode($attribute['attribute_code']);
            $attributeFilter->setAttributeLabel($attribute['label']);

            // Create options
            $options = [];
            foreach ($attribute['options'] as $option) {
                /** @var AttributeOptionInterface $attributeOption */
                $attributeOption = $this->attributeOptionFactory->create();
                $attributeOption->setValue((string)$option['value']);
                $attributeOption->setLabel($option['label']);
                $attributeOption->setCount((int)($option['count'] ?? 0));

                $options[] = $attributeOption;
            }

            $attributeFilter->setOptions($options);
            $result[] = $attributeFilter;
        }

        return $result;
    }
}
