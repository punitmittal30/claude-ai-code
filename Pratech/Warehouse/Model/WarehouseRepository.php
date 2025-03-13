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
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Warehouse\Api\Data\WarehouseInterface;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Pratech\Warehouse\Model\ResourceModel\Warehouse as ResourceWarehouse;
use Pratech\Warehouse\Model\ResourceModel\Warehouse\CollectionFactory as WarehouseCollectionFactory;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    /**
     * @param ResourceWarehouse $resource
     * @param WarehouseFactory $warehouseFactory
     * @param WarehouseCollectionFactory $warehouseCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        protected ResourceWarehouse             $resource,
        protected WarehouseFactory              $warehouseFactory,
        protected WarehouseCollectionFactory    $warehouseCollectionFactory,
        protected SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * Save.
     *
     * @param WarehouseInterface $warehouse
     * @return WarehouseInterface
     * @throws CouldNotSaveException
     */
    public function save(WarehouseInterface $warehouse): WarehouseInterface
    {
        try {
            $this->resource->save($warehouse);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the warehouse: %1',
                $exception->getMessage()
            ));
        }
        return $warehouse;
    }

    /**
     * Get By Code.
     *
     * @param string $warehouseCode
     * @return WarehouseInterface
     * @throws NoSuchEntityException
     */
    public function getByCode(string $warehouseCode): WarehouseInterface
    {
        $warehouse = $this->warehouseFactory->create();
        $this->resource->load($warehouse, $warehouseCode, 'warehouse_code');
        if (!$warehouse->getId()) {
            throw new NoSuchEntityException(__('Warehouse with code "%1" does not exist.', $warehouseCode));
        }
        return $warehouse;
    }

    /**
     * Get List.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->warehouseCollectionFactory->create();

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
     * @param int $warehouseId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $warehouseId): bool
    {
        return $this->delete($this->getById($warehouseId));
    }

    /**
     * Delete.
     *
     * @param WarehouseInterface $warehouse
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(WarehouseInterface $warehouse): bool
    {
        try {
            $this->resource->delete($warehouse);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the warehouse: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Get BY ID.
     *
     * @param int $warehouseId
     * @return WarehouseInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $warehouseId): WarehouseInterface
    {
        $warehouse = $this->warehouseFactory->create();
        $this->resource->load($warehouse, $warehouseId);
        if (!$warehouse->getId()) {
            throw new NoSuchEntityException(__('Warehouse with id "%1" does not exist.', $warehouseId));
        }
        return $warehouse;
    }

    /**
     * @inheritDoc
     */
    public function getDarkStores(): array
    {
        $darkStores = [];
        $collection = $this->warehouseCollectionFactory->create();
        $collection->addFieldToFilter('is_dark_store', ['eq' => 1]);
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        /** @var WarehouseInterface $warehouse */
        foreach ($collection as $warehouse) {
            if ($warehouse->getWarehouseUrl()) {
                $darkStores['dark_stores'][] = $warehouse->getWarehouseUrl();
            }
        }
        return $darkStores;
    }

    /**
     * @inheritDoc
     */
    public function getAvailableDarkStores(): array
    {
        $darkStores = [];
        $collection = $this->warehouseCollectionFactory->create();
        $collection->addFieldToFilter('is_dark_store', ['eq' => 1]);
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        /** @var WarehouseInterface $warehouse */
        foreach ($collection as $warehouse) {
            if ($warehouse->getWarehouseUrl()) {
                $darkStores[] = [
                    'warehouse_name' => $warehouse->getName(),
                    'warehouse_code' => $warehouse->getWarehouseCode(),
                    'warehouse_url' => $warehouse->getWarehouseUrl(),
                    'warehouse_pincode' => (int)$warehouse->getPincode(),
                    'is_active' => (int)$warehouse->getIsActive()
                ];
            }
        }
        return $darkStores;
    }
}
