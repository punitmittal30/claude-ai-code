<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pratech\Recurring\Api\Data;

interface SubscriptionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Subscription list.
     * @return \Pratech\Recurring\Api\Data\SubscriptionInterface[]
     */
    public function getItems();

    /**
     * Set order_id list.
     * @param \Pratech\Recurring\Api\Data\SubscriptionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

