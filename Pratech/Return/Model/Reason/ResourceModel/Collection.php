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

namespace Pratech\Return\Model\Reason\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Return\Api\Data\ReasonInterface;

class Collection extends AbstractCollection
{
    /**
     * Add Not Deleted Filter.
     *
     * @return Collection
     */
    public function addNotDeletedFilter()
    {
        return $this->addFieldToFilter(ReasonInterface::IS_DELETED, 0);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \Pratech\Return\Model\Reason\Reason::class,
            Reason::class
        );
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
