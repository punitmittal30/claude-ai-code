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
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Warehouse\Api\Data\PincodeInterface;
use Pratech\Warehouse\Api\PincodeRepositoryInterface;
use Pratech\Warehouse\Api\WarehouseSlaRepositoryInterface;
use Pratech\Warehouse\Logger\InventorySyncLogger;
use Pratech\Warehouse\Model\ResourceModel\Pincode as ResourcePincode;
use Pratech\Warehouse\Model\ResourceModel\Pincode\CollectionFactory as PincodeCollectionFactory;
use Hyuga\CacheManagement\Model\CacheService;
use Pratech\Warehouse\Service\DarkStoreLocatorService;

class PincodeRepository implements PincodeRepositoryInterface
{
    /**
     * @param ResourcePincode $resource
     * @param PincodeFactory $pincodeFactory
     * @param PincodeCollectionFactory $pincodeCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param InventorySyncLogger $inventorySyncLogger
     * @param CacheService $cacheService
     * @param WarehouseSlaRepositoryInterface $warehouseSlaRepository
     * @param DarkStoreLocatorService $darkStoreLocator
     */
    public function __construct(
        private ResourcePincode                 $resource,
        private PincodeFactory                  $pincodeFactory,
        private PincodeCollectionFactory        $pincodeCollectionFactory,
        private SearchResultsInterfaceFactory   $searchResultsFactory,
        private InventorySyncLogger             $inventorySyncLogger,
        private CacheService                    $cacheService,
        private WarehouseSlaRepositoryInterface $warehouseSlaRepository,
        private DarkStoreLocatorService         $darkStoreLocator
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
        $collection = $this->pincodeCollectionFactory->create();

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
     * @param int $entityId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }

    /**
     * Delete.
     *
     * @param PincodeInterface $pincode
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(PincodeInterface $pincode): bool
    {
        try {
            $this->resource->delete($pincode);

            $this->cacheService->remove($this->cacheService->getPincodeCacheKey($pincode->getPincode()));

        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the pincode: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Get BY ID.
     *
     * @param int $entityId
     * @return PincodeInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): PincodeInterface
    {
        $pincodeModel = $this->pincodeFactory->create();
        $this->resource->load($pincodeModel, $entityId);
        if (!$pincodeModel->getId()) {
            throw new NoSuchEntityException(__('Pincode with id "%1" does not exist.', $entityId));
        }
        return $pincodeModel;
    }

    /**
     * Get Pincode Serviceability.
     *
     * @param int $pincode
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getPincodeServiceability(int $pincode): array
    {
        $cacheKey = $this->cacheService->getPincodeCacheKey($pincode);
        $cachedResult = $this->cacheService->get($cacheKey);

        if ($cachedResult) {
            return $cachedResult;
        }

        try {
            $pincodeData = $this->getByCode($pincode);
            if (!$pincodeData->getIsServiceable()) {
                throw new LocalizedException(__('Pincode with id "%1" is not serviceable.', $pincode));
            }

            $earliestAt = $this->warehouseSlaRepository->getEarliestAtByPincode($pincodeData->getPincode());
            try {
                $darkStore = $this->darkStoreLocator->findNearestDarkStore($pincode);
                $store = [
                    'is_dark_store' => true,
                    'name' => $darkStore['warehouse_name']
                ];
            } catch (NoSuchEntityException $e) {
                $store = [
                    'is_dark_store' => false
                ];
            }

            $result = [
                'pincode' => $pincode,
                'city' => $pincodeData->getCity(),
                'state' => $pincodeData->getState(),
                'is_serviceable' => $pincodeData->getIsServiceable(),
                'earliest_at' => $earliestAt,
                'store' => $store
            ];

            $this->cacheService->save(
                $cacheKey,
                $result,
                [CacheServiceInterface::CACHE_TAG_PINCODE],
                CacheServiceInterface::CACHE_LIFETIME_1_WEEK
            );
        } catch (NoSuchEntityException $e) {
            $this->inventorySyncLogger->error($e->getMessage());
            throw new NoSuchEntityException(__('Pincode with id "%1" is not serviceable.', $pincode));
        }
        return $result;
    }

    /**
     * Get By Code.
     *
     * @param int $pincode
     * @return PincodeInterface
     * @throws NoSuchEntityException
     */
    public function getByCode(int $pincode): PincodeInterface
    {
        $pincodeModel = $this->pincodeFactory->create();
        $this->resource->load($pincodeModel, $pincode, 'pincode');
        if (!$pincodeModel->getId()) {
            throw new NoSuchEntityException(__('Pincode "%1" does not exist.', $pincode));
        }
        return $pincodeModel;
    }

    /**
     * Save.
     *
     * @param PincodeInterface $pincode
     * @return PincodeInterface
     * @throws CouldNotSaveException
     */
    public function save(PincodeInterface $pincode): PincodeInterface
    {
        try {
            $this->resource->save($pincode);

            $this->cacheService->remove($this->cacheService->getPincodeCacheKey($pincode->getPincode()));

        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the pincode: %1',
                $exception->getMessage()
            ));
        }
        return $pincode;
    }
}
