<?php

namespace Pratech\Warehouse\Model\Resolver\Products\DataProvider;

use Exception;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionPostProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch as OriginalProductSearch;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch\ProductCollectionSearchCriteriaBuilder;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\GraphQl\Model\Query\ContextInterface;
use Pratech\Warehouse\Service\InventoryLocatorService;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

class ProductSearch extends OriginalProductSearch
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionPreProcessor;

    /**
     * @var CollectionPostProcessorInterface
     */
    private $collectionPostProcessor;

    /**
     * @var SearchResultApplierFactory;
     */
    private $searchResultApplierFactory;

    /**
     * @var ProductCollectionSearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Visibility
     */
    private $catalogProductVisibility;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InventoryLocatorService
     */
    private $inventoryLocatorService;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionPreProcessor
     * @param CollectionPostProcessorInterface $collectionPostProcessor
     * @param SearchResultApplierFactory $searchResultsApplierFactory
     * @param ProductCollectionSearchCriteriaBuilder $searchCriteriaBuilder
     * @param Visibility $catalogProductVisibility
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     * @param InventoryLocatorService $inventoryLocatorService
     */
    public function __construct(
        CollectionFactory                      $collectionFactory,
        ProductSearchResultsInterfaceFactory   $searchResultsFactory,
        CollectionProcessorInterface           $collectionPreProcessor,
        CollectionPostProcessorInterface       $collectionPostProcessor,
        SearchResultApplierFactory             $searchResultsApplierFactory,
        ProductCollectionSearchCriteriaBuilder $searchCriteriaBuilder,
        Visibility                             $catalogProductVisibility,
        ResourceConnection                     $resource,
        LoggerInterface                        $logger,
        InventoryLocatorService                $inventoryLocatorService
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionPreProcessor = $collectionPreProcessor;
        $this->collectionPostProcessor = $collectionPostProcessor;
        $this->searchResultApplierFactory = $searchResultsApplierFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->resource = $resource;
        $this->logger = $logger;
        $this->inventoryLocatorService = $inventoryLocatorService;
    }

    /**
     * Get list of product data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param SearchResultInterface $searchResult
     * @param array $attributes
     * @param ContextInterface|null $context
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        SearchResultInterface   $searchResult,
        array                   $attributes = [],
        ContextInterface        $context = null
    ): SearchResultsInterface {
        $pincode = null;
        if ($context && $context->getExtensionAttributes()
            && method_exists($context->getExtensionAttributes(), 'getPincode')) {
            $pincode = (int)$context->getExtensionAttributes()->getPincode();
            $this->logger->debug('PINCODE_FROM_REQUEST', ['pincode' => $pincode]);
        }

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $searchCriteriaForCollection = $this->searchCriteriaBuilder->build($searchCriteria);
        $this->getSearchResultsApplier(
            $searchResult,
            $collection,
            $this->getSortOrderArray($searchCriteriaForCollection)
        )->apply();

        $collection->setFlag('search_resut_applied', true);
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInSiteIds());

        // If pincode is available, join with warehouse inventory and add stock status sorting
        if ($pincode) {
            $this->joinWarehouseInventory($collection, $pincode);
        }

        // Process the collection before loading
        $this->collectionPreProcessor->process($collection, $searchCriteriaForCollection, $attributes, $context);

        // Load the collection after adding inventory joins and sorting
        $collection->load();
        $this->collectionPostProcessor->process($collection, $attributes, $context);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteriaForCollection);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Join warehouse inventory data and add stock status sorting
     *
     * @param Collection $collection
     * @param int $pincode
     * @return void
     */
    private function joinWarehouseInventory(Collection $collection, int $pincode): void
    {
        try {
            $connection = $this->resource->getConnection();

            // Get the warehouse codes associated with this pincode
            $warehouseCodes = $this->getWarehouseCodesByPincode($pincode);

            if (empty($warehouseCodes)) {
                // If no warehouses found, default sorting will apply
                return;
            }

            // Get the current select object and its ORDER BY parts
            $select = $collection->getSelect();
            $currentOrderByParts = $select->getPart(Select::ORDER);

            // Reset the ORDER BY clause to apply our primary sort first
            $select->reset(Select::ORDER);

            // Join with the warehouse inventory table
            $inventoryTable = $this->resource->getTableName('pratech_warehouse_inventory');
            $entityIdField = 'e.entity_id';
            $skuField = 'e.sku';

            // Create the warehouse codes condition
            $warehouseCodesStr = "'" . implode("','", $warehouseCodes) . "'";

            // Add a left join to get inventory data
            // Only include inventory for the specified warehouses
            $select->joinLeft(
                ['wi' => $inventoryTable],
                "{$skuField} = wi.sku AND wi.warehouse_code IN ({$warehouseCodesStr})",
                []
            );

            // Add inventory columns for use in sorting and later access
            $select->columns([
                'inventory_qty' => new Zend_Db_Expr('IFNULL(SUM(wi.quantity), 0)'),
                'inventory_is_in_stock' => new Zend_Db_Expr('IF(IFNULL(SUM(wi.quantity), 0) > 0, 1, 0)')
            ]);

            // Group by the product entity ID to handle multiple warehouse inventory records
            $select->group($entityIdField);

            // Add the stock status as the primary sort criteria
            $select->order(new Zend_Db_Expr('inventory_is_in_stock DESC'));

            // Re-apply the original sort orders as secondary criteria
            if (!empty($currentOrderByParts)) {
                foreach ($currentOrderByParts as $orderPart) {
                    $select->order($orderPart);
                }
            }

            $this->logger->debug('WAREHOUSE_INVENTORY_JOINED', [
                'pincode' => $pincode,
                'warehouse_codes' => $warehouseCodes,
                'query' => (string)$select
            ]);
        } catch (Exception $e) {
            $this->logger->error('Error joining warehouse inventory: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get warehouse codes for a pincode
     *
     * @param int $pincode
     * @return array
     */
    private function getWarehouseCodesByPincode(int $pincode): array
    {
        try {
            $connection = $this->resource->getConnection();

            // Get warehouse codes from the warehouse_sla table
            $warehouseSlaTable = $this->resource->getTableName('pratech_warehouse_sla');
            $warehouseTable = $this->resource->getTableName('pratech_warehouse');

            $select = $connection->select()
                ->from(
                    ['ws' => $warehouseSlaTable],
                    []
                )
                ->join(
                    ['w' => $warehouseTable],
                    'ws.warehouse_pincode = w.pincode',
                    ['warehouse_code']
                )
                ->where('ws.customer_pincode = ?', $pincode)
                ->where('w.is_active = ?', 1);

            // Get all warehouse codes
            $warehouseCodes = $connection->fetchCol($select);

            // Return unique warehouse codes
            return array_unique($warehouseCodes);
        } catch (Exception $e) {
            $this->logger->error('Error getting warehouse codes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Create searchResultApplier
     *
     * @param SearchResultInterface $searchResult
     * @param Collection $collection
     * @param array $orders
     * @return SearchResultApplierInterface
     */
    private function getSearchResultsApplier(
        SearchResultInterface $searchResult,
        Collection            $collection,
        array                 $orders
    ): SearchResultApplierInterface {
        return $this->searchResultApplierFactory->create(
            [
                'collection' => $collection,
                'searchResult' => $searchResult,
                'orders' => $orders
            ]
        );
    }

    /**
     * Format sort orders into associative array
     *
     * E.g. ['field1' => 'DESC', 'field2' => 'ASC", ...]
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    private function getSortOrderArray(SearchCriteriaInterface $searchCriteria)
    {
        $ordersArray = [];
        $sortOrders = $searchCriteria->getSortOrders();
        if (is_array($sortOrders)) {
            foreach ($sortOrders as $sortOrder) {
                if ($sortOrder->getField() === '_id') {
                    $sortOrder->setField('entity_id');
                }
                $ordersArray[$sortOrder->getField()] = $sortOrder->getDirection();
            }
        }

        return $ordersArray;
    }
}
