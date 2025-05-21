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

namespace Pratech\Warehouse\Ui\Component\Listing\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResultFactory;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla\Collection;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla\CollectionFactory;

class Sla extends DataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param CollectionFactory $collectionFactory
     * @param SearchResultFactory $searchResultFactory
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        protected CollectionFactory $collectionFactory,
        protected SearchResultFactory $searchResultFactory,
        protected FilterBuilderFactory $filterBuilderFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items['items']),
        ];
    }

    /**
     * Get collection
     *
     * @return Collection
     */
    public function getCollection(): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->collectionFactory->create();
            $this->collection->addWarehouseData();
        }
        return $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function getSearchResult()
    {
        $collection = $this->collectionFactory->create();
        $collection->addWarehouseData();

        $searchCriteria = $this->getSearchCriteria();

        // Apply filters
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        // Apply sorting
        foreach ($searchCriteria->getSortOrders() as $sortOrder) {
            $collection->addOrder(
                $sortOrder->getField(),
                ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }

        // Apply pagination
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResult = $this->searchResultFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * Add order by field
     *
     * @param string $field
     * @param string $direction
     * @return void
     */
    public function addOrder($field, $direction): void
    {
        $this->getCollection()->addOrder($field, $direction);
    }

    /**
     * Add field filter to collection
     *
     * @param Filter|string $field
     * @param mixed $condition
     * @return void
     */
    public function addFilter(Filter|string $field, mixed $condition = null)
    {
        if ($field instanceof Filter) {
            $this->getCollection()->addFieldToFilter(
                $field->getField(),
                [$field->getConditionType() => $field->getValue()]
            );
        } else {
            $this->getCollection()->addFieldToFilter($field, $condition);
        }
    }
}
