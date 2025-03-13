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
use Magento\Framework\App\CacheInterface;
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

class PincodeRepository implements PincodeRepositoryInterface
{
    private const CACHE_TAG = 'pratech_serviceable_pincodes';

    private const CACHE_LIFETIME = 3600; // 1 hour

    /**
     * @param ResourcePincode $resource
     * @param PincodeFactory $pincodeFactory
     * @param PincodeCollectionFactory $pincodeCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param InventorySyncLogger $inventorySyncLogger
     * @param CacheInterface $cache
     * @param WarehouseSlaRepositoryInterface $warehouseSlaRepository
     */
    public function __construct(
        private ResourcePincode                 $resource,
        private PincodeFactory                  $pincodeFactory,
        private PincodeCollectionFactory        $pincodeCollectionFactory,
        private SearchResultsInterfaceFactory   $searchResultsFactory,
        private InventorySyncLogger             $inventorySyncLogger,
        private CacheInterface                  $cache,
        private WarehouseSlaRepositoryInterface $warehouseSlaRepository,
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
        $cacheKey = $this->generateCacheKey($pincode);
        $cachedResult = $this->cache->load($cacheKey);

        if ($cachedResult) {
            return json_decode($cachedResult, true);
        }
        try {
            $pincodeData = $this->getByCode($pincode);
            if (!$pincodeData->getIsServiceable()) {
                throw new LocalizedException(__('Pincode with id "%1" is not serviceable.', $pincode));
            }

            $earliestAt = $this->warehouseSlaRepository->getEarliestAtByPincode($pincodeData->getPincode());

            $result = [
                'pincode' => $pincode,
                'city' => $pincodeData->getCity(),
                'state' => $pincodeData->getState(),
                'is_serviceable' => $pincodeData->getIsServiceable(),
                'earliest_at' => $earliestAt
            ];
            $this->cache->save(
                json_encode($result),
                $cacheKey,
                [self::CACHE_TAG],
                self::CACHE_LIFETIME
            );
        } catch (NoSuchEntityException $e) {
            $this->inventorySyncLogger->error($e->getMessage());
            throw new NoSuchEntityException(__('Pincode with id "%1" is not serviceable.', $pincode));
        }
        return $result;
    }

    /**
     * Generate cache key for pincode serviceability.
     *
     * @param int $pincode
     * @return string
     */
    private function generateCacheKey(int $pincode): string
    {
        return self::CACHE_TAG . '_' . $pincode;
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
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the pincode: %1',
                $exception->getMessage()
            ));
        }
        return $pincode;
    }
}
