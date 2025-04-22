<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pratech\Recurring\Api\Data;

interface SubscriptionMappingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get SubscriptionMapping list.
     * @return \Pratech\Recurring\Api\Data\SubscriptionMappingInterface[]
     */
    public function getItems();

    /**
     * Set subscription_id list.
     * @param \Pratech\Recurring\Api\Data\SubscriptionMappingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

