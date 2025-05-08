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

namespace Pratech\Return\Model\Request\ResourceModel\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Pratech\Return\Api\Data\RequestInterface;
use Zend_Db_Expr;

class Collection extends SearchResult
{
    public const DAYS_EXPRESSION = 'datediff(main_table.' . RequestInterface::MODIFIED_AT
    . ', main_table.' . RequestInterface::CREATED_AT . ')';

    /**
     * Add Lead Time.
     *
     * @return Collection
     */
    public function addLeadTime()
    {
        $this->getSelect()->columns(
            ['days' => new Zend_Db_Expr(self::DAYS_EXPRESSION)]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        //need for csv grid export, current page and batch size sets in SearchCriteria
        //but no automatic applying for SearchResult, so need to reload items of collection
        $this->_setIsLoaded(false);
        $this->_items = [];
        $searchCriteria = $this->getSearchCriteria();
        $this->setPageSize($searchCriteria->getPageSize());
        $this->setCurPage($searchCriteria->getCurrentPage());
        return parent::getItems();
    }
}
