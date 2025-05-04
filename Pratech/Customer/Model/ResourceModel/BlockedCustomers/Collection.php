<?php

namespace Pratech\Customer\Model\ResourceModel\BlockedCustomers;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Customer\Model\BlockedCustomers;

/**
 * Author collection class
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * _construct method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            BlockedCustomers::class,
            \Pratech\Customer\Model\ResourceModel\BlockedCustomers::class
        );
    }
}
