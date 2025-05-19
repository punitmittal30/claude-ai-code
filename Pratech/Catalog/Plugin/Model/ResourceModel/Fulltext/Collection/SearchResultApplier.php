<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Plugin\Model\ResourceModel\Fulltext\Collection;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;

/**
 * Resolve specific attributes for search criteria.
 */
class SearchResultApplier implements SearchResultApplierInterface
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var SearchResultInterface
     */
    private $searchResult;

    /**
     * @var array
     */
    private $orders;

    /**
     * @param Collection $collection
     * @param SearchResultInterface $searchResult
     * @param CollectionFactory $productCollectionFactory
     * @param array $orders
     */
    public function __construct(
        Collection $collection,
        SearchResultInterface $searchResult,
        private CollectionFactory $productCollectionFactory,
        array $orders
    ) {
        $this->collection = $collection;
        $this->searchResult = $searchResult;
        $this->orders = $orders;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        if (empty($this->searchResult->getItems())) {
            $this->collection->getSelect()->where('NULL');
            return;
        }
        $ids = [];
        foreach ($this->searchResult->getItems() as $item) {
            $ids[] = (int)$item->getId();
        }

        $this->collection->getSelect()->where('e.entity_id IN (?)', $ids);
       
        if (isset($this->orders['relevance'])) {

            $productIds = $this->productCollectionFactory->create()
                ->addAttributeToSelect('item_stock_status')
                ->addFieldToFilter('entity_id', ['in' => $ids])
                ->addFieldToFilter('item_stock_status', ['neq' => 1])
                ->getColumnValues('entity_id');
                
            $orderListArray = array_diff($ids, $productIds);
            $orderListArray = array_merge($orderListArray, $productIds);

            $orderList = implode(',', $orderListArray);
            
            $this->collection->getSelect()
                ->reset(\Magento\Framework\DB\Select::ORDER)
                ->order(new \Magento\Framework\DB\Sql\Expression("FIELD(e.entity_id, $orderList)"));
        }
    }
}
