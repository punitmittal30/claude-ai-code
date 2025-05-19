<?php

namespace Pratech\Warehouse\Model\Resolver\Products\DataProvider;

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
use Magento\GraphQl\Model\Query\ContextInterface;
use Pratech\Warehouse\Service\InventoryLocatorService;
use Psr\Log\LoggerInterface;

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

        // Process the collection before adding warehouse inventory
        $this->collectionPreProcessor->process($collection, $searchCriteriaForCollection, $attributes, $context);

        $collection->load();
        $this->collectionPostProcessor->process($collection, $attributes, $context);

        // Add inventory data to products if pincode is available
        if ($pincode) {
            $this->addInventoryDataToProducts($collection, $pincode);
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteriaForCollection);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Add inventory data to products using batch processing
     *
     * @param Collection $collection
     * @param int $pincode
     * @return void
     */
    private function addInventoryDataToProducts(Collection $collection, int $pincode): void
    {
        try {
            $items = $collection->getItems();

            if (empty($items)) {
                return;
            }

            // Extract SKUs from all products
            $skus = [];
            foreach ($items as $product) {
                $skus[] = $product->getSku();
            }

            // Get inventory data for all SKUs in a single operation
            $startTime = microtime(true);
            $inventoryData = $this->inventoryLocatorService->getBatchInventoryQtyByPincode($skus, $pincode);
            $endTime = microtime(true);

            // Update each product with its inventory data
            foreach ($items as $product) {
                $sku = $product->getSku();
                $qty = $inventoryData[$sku] ?? 0;

                $product->setData('warehouse_inventory_qty', $qty);
                $product->setData('inventory_is_in_stock', $qty > 0 ? 1 : 0);

                // Set the special attribute that will be used for sorting in the CustomSortOrder plugin
//                $product->setData('item_stock_status', $qty > 0 ? 1 : 0);
            }

            $this->logger->debug('BATCH_INVENTORY_ADDED', [
                'pincode' => $pincode,
                'product_count' => count($items),
                'time_taken' => round($endTime - $startTime, 4) . ' seconds'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error adding inventory data: ' . $e->getMessage(), [
                'pincode' => $pincode,
                'trace' => $e->getTraceAsString()
            ]);
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
