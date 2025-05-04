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

namespace Pratech\Warehouse\Model;

use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Webapi\Rest\Request;
use Pratech\Warehouse\Api\Data\WarehouseInventoryInterface;
use Pratech\Warehouse\Api\WarehouseInventoryRepositoryInterface;
use Pratech\Warehouse\Logger\InventorySyncLogger;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory as ResourceWarehouseInventory;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory\CollectionFactory as InventoryCollectionFactory;

class WarehouseInventoryRepository implements WarehouseInventoryRepositoryInterface
{
    /**
     * @param ResourceWarehouseInventory $resource
     * @param WarehouseInventoryFactory $inventoryFactory
     * @param InventoryCollectionFactory $inventoryCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param InventorySyncLogger $inventorySyncLogger
     * @param Request $request
     */
    public function __construct(
        private ResourceWarehouseInventory    $resource,
        private WarehouseInventoryFactory     $inventoryFactory,
        private InventoryCollectionFactory    $inventoryCollectionFactory,
        private SearchResultsInterfaceFactory $searchResultsFactory,
        private InventorySyncLogger           $inventorySyncLogger,
        private Request                       $request
    ) {
    }

    /**
     * Get List.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->inventoryCollectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * Delete By ID.
     *
     * @param int $inventoryId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $inventoryId): bool
    {
        return $this->delete($this->getById($inventoryId));
    }

    /**
     * Delete.
     *
     * @param WarehouseInventoryInterface $inventory
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(WarehouseInventoryInterface $inventory): bool
    {
        try {
            $this->resource->delete($inventory);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the inventory: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Get By ID.
     *
     * @param int $inventoryId
     * @return WarehouseInventoryInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $inventoryId): WarehouseInventoryInterface
    {
        $inventory = $this->inventoryFactory->create();
        $this->resource->load($inventory, $inventoryId);
        if (!$inventory->getId()) {
            throw new NoSuchEntityException(__('Inventory with id "%1" does not exist.', $inventoryId));
        }
        return $inventory;
    }

    /**
     * Update Stock.
     *
     * @param string $warehouseCode
     * @param string $sku
     * @param int $quantity
     * @return WarehouseInventoryInterface
     * @throws CouldNotSaveException
     */
    public function updateStock(string $warehouseCode, string $sku, int $quantity): WarehouseInventoryInterface
    {
        try {
            $inventory = $this->getByWarehouseSku($warehouseCode, $sku);
        } catch (NoSuchEntityException $e) {
            $inventory = $this->inventoryFactory->create();
            $inventory->setWarehouseCode($warehouseCode);
            $inventory->setSku($sku);
        }

        $inventory->setQuantity($quantity);
        return $this->save($inventory);
    }

    /**
     * Get By Warehouse Sku.
     *
     * @param string $warehouseCode
     * @param string $sku
     * @return WarehouseInventoryInterface
     * @throws NoSuchEntityException
     */
    public function getByWarehouseSku(string $warehouseCode, string $sku): WarehouseInventoryInterface
    {
        $inventory = $this->inventoryFactory->create();
        $this->resource->load($inventory, [
            'warehouse_code' => $warehouseCode,
            'sku' => $sku
        ]);
        if (!$inventory->getId()) {
            throw new NoSuchEntityException(__(
                'Inventory for warehouse ID "%1" and SKU "%2" does not exist.',
                $warehouseCode,
                $sku
            ));
        }
        return $inventory;
    }

    /**
     * Save.
     *
     * @param WarehouseInventoryInterface $inventory
     * @return WarehouseInventoryInterface
     * @throws CouldNotSaveException
     */
    public function save(WarehouseInventoryInterface $inventory): WarehouseInventoryInterface
    {
        try {
            $this->resource->save($inventory);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the inventory: %1',
                $exception->getMessage()
            ));
        }
        return $inventory;
    }

    /**
     * @inheritDoc
     */
    public function updateInventory($payload): array
    {
        try {
            $this->inventorySyncLogger->info(
                'Warehouse Inventory Update Request:',
                ['request' => $payload]
            );

            $this->inventorySyncLogger->info('Raw Request Body:', ['body' => $this->request->getContent()]);

            return [
                'success' => true,
                'message' => 'Inventory updated successfully',
                'data' => []
            ];
        } catch (ValidatorException $e) {
            $this->inventorySyncLogger->error('Validation error in warehouse inventory update: ' . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        } catch (Exception $e) {
            $this->inventorySyncLogger->error('Error in warehouse inventory update: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to update warehouse inventory. Please try again.'));
        }
    }

    /**
     * @inheritDoc
     */
    public function updateWarehouseInventory(array $Inventorylist): array
    {
        try {
            if (empty($Inventorylist)) {
                throw new LocalizedException(__('Invalid request format. Inventory list is required.'));
            }

            $this->inventorySyncLogger->info(
                'Inventory Update Request:',
                [
                    'InventoryList' => $this->request->getContent()
                ]
            );

            $totals = [];

            foreach ($Inventorylist as $item) {
                $warehouseCode = $item->getLocation();
                $qty = (int)$item->getQty();
                $sku = $item->getSkucode();
                $locationSkuKey = $warehouseCode . '_' . $sku;
                if (!isset($totals[$locationSkuKey])) {
                    $totals[$locationSkuKey] = [
                        'warehouse_code' => $warehouseCode,
                        'sku' => $sku,
                        'total_qty' => 0,
                    ];
                }
                $totals[$locationSkuKey]['total_qty'] += $qty;
            }

            $results = $this->resource->processInventoryItem(array_values($totals));

            return [
                'success' => true,
                'message' => 'Inventory update process completed',
                'results' => $results
            ];

        } catch (Exception $e) {
            $this->inventorySyncLogger->error('Error processing inventory update: ' . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
