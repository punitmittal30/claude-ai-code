<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pratech\Recurring\Model;

use Magento\Framework\Model\AbstractModel;
use Pratech\Recurring\Api\Data\SubscriptionMappingInterface;

class SubscriptionMapping extends AbstractModel implements SubscriptionMappingInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Pratech\Recurring\Model\ResourceModel\SubscriptionMapping::class);
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionMappingId()
    {
        return $this->getData(self::SUBSCRIPTIONMAPPING_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSubscriptionMappingId($subscriptionMappingId)
    {
        return $this->setData(self::SUBSCRIPTIONMAPPING_ID, $subscriptionMappingId);
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionId()
    {
        return $this->getData(self::SUBSCRIPTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSubscriptionId($subscriptionId)
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}

