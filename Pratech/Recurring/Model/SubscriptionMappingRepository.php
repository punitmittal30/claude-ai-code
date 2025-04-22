<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pratech\Recurring\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Recurring\Api\Data\SubscriptionMappingInterface;
use Pratech\Recurring\Api\Data\SubscriptionMappingInterfaceFactory;
use Pratech\Recurring\Api\Data\SubscriptionMappingSearchResultsInterfaceFactory;
use Pratech\Recurring\Api\SubscriptionMappingRepositoryInterface;
use Pratech\Recurring\Model\ResourceModel\SubscriptionMapping as ResourceSubscriptionMapping;
use Pratech\Recurring\Model\ResourceModel\SubscriptionMapping\CollectionFactory as SubscriptionMappingCollectionFactory;

class SubscriptionMappingRepository implements SubscriptionMappingRepositoryInterface
{

    /**
     * @var SubscriptionMappingInterfaceFactory
     */
    protected $subscriptionMappingFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var SubscriptionMapping
     */
    protected $searchResultsFactory;

    /**
     * @var SubscriptionMappingCollectionFactory
     */
    protected $subscriptionMappingCollectionFactory;

    /**
     * @var ResourceSubscriptionMapping
     */
    protected $resource;


    /**
     * @param ResourceSubscriptionMapping $resource
     * @param SubscriptionMappingInterfaceFactory $subscriptionMappingFactory
     * @param SubscriptionMappingCollectionFactory $subscriptionMappingCollectionFactory
     * @param SubscriptionMappingSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceSubscriptionMapping $resource,
        SubscriptionMappingInterfaceFactory $subscriptionMappingFactory,
        SubscriptionMappingCollectionFactory $subscriptionMappingCollectionFactory,
        SubscriptionMappingSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->subscriptionMappingFactory = $subscriptionMappingFactory;
        $this->subscriptionMappingCollectionFactory = $subscriptionMappingCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        SubscriptionMappingInterface $subscriptionMapping
    ) {
        try {
            $this->resource->save($subscriptionMapping);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscriptionMapping: %1',
                $exception->getMessage()
            ));
        }
        return $subscriptionMapping;
    }

    /**
     * @inheritDoc
     */
    public function get($subscriptionMappingId)
    {
        $subscriptionMapping = $this->subscriptionMappingFactory->create();
        $this->resource->load($subscriptionMapping, $subscriptionMappingId);
        if (!$subscriptionMapping->getId()) {
            throw new NoSuchEntityException(__('SubscriptionMapping with id "%1" does not exist.', $subscriptionMappingId));
        }
        return $subscriptionMapping;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->subscriptionMappingCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        SubscriptionMappingInterface $subscriptionMapping
    ) {
        try {
            $subscriptionMappingModel = $this->subscriptionMappingFactory->create();
            $this->resource->load($subscriptionMappingModel, $subscriptionMapping->getSubscriptionMappingId());
            $this->resource->delete($subscriptionMappingModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SubscriptionMapping: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($subscriptionMappingId)
    {
        return $this->delete($this->get($subscriptionMappingId));
    }
}

