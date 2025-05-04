<?php

namespace Pratech\Customer\Model;

use Magento\Framework\Model\AbstractModel;

class BlockedCustomers extends AbstractModel
{
    /**
     * Model constructor
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\BlockedCustomers::class);
    }
}
