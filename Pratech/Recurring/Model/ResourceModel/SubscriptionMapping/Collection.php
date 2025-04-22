<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pratech\Recurring\Model\ResourceModel\SubscriptionMapping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'subscription_mapping_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Pratech\Recurring\Model\SubscriptionMapping::class,
            \Pratech\Recurring\Model\ResourceModel\SubscriptionMapping::class
        );
    }
}

