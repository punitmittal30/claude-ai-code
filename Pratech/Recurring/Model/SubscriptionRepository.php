<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Recurring\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Recurring\Api\Data\SubscriptionInterface;
use Pratech\Recurring\Api\Data\SubscriptionInterfaceFactory;
use Pratech\Recurring\Api\Data\SubscriptionSearchResultsInterfaceFactory;
use Pratech\Recurring\Api\SubscriptionRepositoryInterface;
use Pratech\Recurring\Model\ResourceModel\Subscription as ResourceSubscription;
use Pratech\Recurring\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    /**
     * @param ResourceSubscription $resource
     * @param SubscriptionInterfaceFactory $subscriptionFactory
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param SubscriptionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        protected ResourceSubscription $resource,
        protected SubscriptionInterfaceFactory $subscriptionFactory,
        protected SubscriptionCollectionFactory $subscriptionCollectionFactory,
        protected SubscriptionSearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(SubscriptionInterface $subscription)
    {
        try {
            $this->resource->save($subscription);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription: %1',
                $exception->getMessage()
            ));
        }
        return $subscription;
    }

    /**
     * @inheritDoc
     */
    public function get($subscriptionId)
    {
        $subscription = $this->subscriptionFactory->create();
        $this->resource->load($subscription, $subscriptionId);
        if (!$subscription->getId()) {
            throw new NoSuchEntityException(__('Subscription with id "%1" does not exist.', $subscriptionId));
        }
        return $subscription;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->subscriptionCollectionFactory->create();
        
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
    public function delete(SubscriptionInterface $subscription)
    {
        try {
            $subscriptionModel = $this->subscriptionFactory->create();
            $this->resource->load($subscriptionModel, $subscription->getSubscriptionId());
            $this->resource->delete($subscriptionModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Subscription: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($subscriptionId)
    {
        return $this->delete($this->get($subscriptionId));
    }
}
