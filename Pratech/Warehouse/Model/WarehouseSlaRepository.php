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
use Pratech\Warehouse\Api\Data\WarehouseSlaInterface;
use Pratech\Warehouse\Api\WarehouseSlaRepositoryInterface;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla as ResourceWarehouseSla;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla\CollectionFactory as SlaCollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class WarehouseSlaRepository implements WarehouseSlaRepositoryInterface
{
    /**
     * @param ResourceWarehouseSla $resource
     * @param WarehouseSlaFactory $slaFactory
     * @param SlaCollectionFactory $slaCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param ResourceWarehouseSla $warehouseSlaResource
     */
    public function __construct(
        private ResourceWarehouseSla          $resource,
        private WarehouseSlaFactory           $slaFactory,
        private SlaCollectionFactory          $slaCollectionFactory,
        private SearchResultsInterfaceFactory $searchResultsFactory,
        private ResourceWarehouseSla          $warehouseSlaResource
    ) {
    }

    /**
     * Save.
     *
     * @param WarehouseSlaInterface $sla
     * @return WarehouseSlaInterface
     * @throws CouldNotSaveException
     */
    public function save(WarehouseSlaInterface $sla): WarehouseSlaInterface
    {
        try {
            $this->resource->save($sla);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the SLA: %1',
                $exception->getMessage()
            ));
        }
        return $sla;
    }

    /**
     * Get By SLA.
     *
     * @param int $customerPincode
     * @param int $warehousePincode
     * @return WarehouseSlaInterface
     * @throws NoSuchEntityException
     */
    public function getBySla(int $customerPincode, int $warehousePincode): WarehouseSlaInterface
    {
        $sla = $this->slaFactory->create();
        $this->resource->load($sla, [
            'customer_pincode' => $customerPincode,
            'warehouse_pincode' => $warehousePincode
        ]);
        if (!$sla->getId()) {
            throw new NoSuchEntityException(__(
                'SLA for customer pincode "%1" and warehouse pincode "%2" does not exist.',
                $customerPincode,
                $warehousePincode
            ));
        }
        return $sla;
    }

    /**
     * Get List.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->slaCollectionFactory->create();

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
     * @param int $slaId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $slaId): bool
    {
        return $this->delete($this->getById($slaId));
    }

    /**
     * Delete.
     *
     * @param WarehouseSlaInterface $sla
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(WarehouseSlaInterface $sla): bool
    {
        try {
            $this->resource->delete($sla);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SLA: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Get By ID.
     *
     * @param int $slaId
     * @return WarehouseSlaInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $slaId): WarehouseSlaInterface
    {
        $sla = $this->slaFactory->create();
        $this->resource->load($sla, $slaId);
        if (!$sla->getId()) {
            throw new NoSuchEntityException(__(
                'SLA with id "%1" does not exist.',
                $slaId
            ));
        }
        return $sla;
    }

    /**
     * Get Earliest At By Pincode.
     *
     * @param int $customerPincode
     * @return string
     * @throws NoSuchEntityException
     */
    public function getEarliestAtByPincode(int $customerPincode): string
    {
        try {
            $earliestAt = $this->warehouseSlaResource->getMinDeliveryTimeByPincode($customerPincode);
        } catch (LocalizedException $e) {
            throw new NoSuchEntityException(__(
                'SLA with pincode "%1" does not exist.',
                $customerPincode
            ));
        }
        return $earliestAt;
    }
}
