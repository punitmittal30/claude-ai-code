<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model\History;

use Exception;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Api\Data\BookmarkSearchResultsInterfaceFactory;
use Pratech\Return\Api\Data\HistoryInterface;
use Pratech\Return\Api\HistoryRepositoryInterface;
use Pratech\Return\Model\History\ResourceModel\Collection;

class Repository implements HistoryRepositoryInterface
{
    /**
     * @param BookmarkSearchResultsInterfaceFactory $searchResultsFactory
     * @param ResourceModel\History $historyResource
     * @param HistoryFactory $historyFactory
     * @param ProcessMessage $processMessage
     * @param ResourceModel\CollectionFactory $historyCollectionFactory
     */
    public function __construct(
        private BookmarkSearchResultsInterfaceFactory $searchResultsFactory,
        private ResourceModel\History                 $historyResource,
        private HistoryFactory                        $historyFactory,
        private ProcessMessage                        $processMessage,
        private ResourceModel\CollectionFactory       $historyCollectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /**
         * @var Collection $historyCollection
         */
        $historyCollection = $this->historyCollectionFactory->create();
        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $historyCollection);
        }
        $searchResults->setTotalCount($historyCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $historyCollection);
        }
        $historyCollection->setCurPage($searchCriteria->getCurrentPage());
        $historyCollection->setPageSize($searchCriteria->getPageSize());
        $history = [];
        /**
         * @var HistoryInterface $item
         */
        foreach ($historyCollection->getItems() as $item) {
            $history[] = $this->getById($item->getHistoryId());
        }
        $searchResults->setItems($history);

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function create(HistoryInterface $history)
    {
        try {
            $this->historyResource->save($history);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__('Unable to save new rma event. Error: %1', $e->getMessage()));
        }

        return $history;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection $historyCollection
     *
     * @return void
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $historyCollection)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $historyCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    /**
     * Helper function that adds a SortOrder to the collection.
     *
     * @param SortOrder[] $sortOrders
     * @param Collection $historyCollection
     *
     * @return void
     */
    private function addOrderToCollection($sortOrders, Collection $historyCollection)
    {
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $historyCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? 'DESC' : 'ASC'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getById($eventId)
    {
        /**
         * @var History $event
         */
        $event = $this->historyFactory->create();
        $this->historyResource->load($event, $eventId);
        if (!$event->getEventId()) {
            throw new NoSuchEntityException(
                __('Rma Event with specified ID "%1" not found.', $eventId)
            );
        }

        return $event;
    }

    /**
     * @inheritDoc
     */
    public function getRequestEvents($requestId)
    {
        $collection = $this->getEmptyEventCollection()->addRequestFilter($requestId);
        $result = [];

        foreach ($collection->getItems() as $item) {
            $result[] = $this->processMessage->execute($item);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getEmptyEventCollection()
    {
        return $this->historyCollectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getEmptyEventModel()
    {
        return $this->historyFactory->create();
    }
}
