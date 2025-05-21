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

namespace Pratech\Return\Model\History\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Return\Api\Data\HistoryInterface;
use Pratech\Return\Model\History\History;
use Pratech\Return\Model\History\ResourceModel\History as HistoryResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Add Request Filter
     *
     * @param integer $requestId
     * @return $this
     */
    public function addRequestFilter(int $requestId)
    {
        $this->addFieldToFilter(HistoryInterface::REQUEST_ID, (int)$requestId);

        return $this;
    }

    /**
     * History Collection Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(History::class, HistoryResourceModel::class);
        $this->_setIdFieldName($this->getResource()->getIdFieldName())
            ->addOrder(HistoryInterface::EVENT_ID);
    }
}
