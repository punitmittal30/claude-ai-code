<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Model\Data;

use Pratech\Recurring\Api\Data\SubscriptionRequestItemInterface;
use Magento\Framework\DataObject;

/**
 * Class SubscriptionRequestItem to get request data
 */
class SubscriptionRequestItem extends DataObject implements SubscriptionRequestItemInterface
{
    /**
     * @inheritDoc
     */
    public function getOrderItemId()
    {
        return $this->_getData(self::ORDER_ITEM_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderItemId(int $orderItemId)
    {
        return $this->setData(self::ORDER_ITEM_ID, $orderItemId);
    }

    /**
     * @inheritDoc
     */
    public function getProductQty()
    {
        return $this->_getData(self::PRODUCT_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setProductQty(?int $productQty)
    {
        return $this->setData(self::PRODUCT_QTY, $productQty);
    }

    /**
     * @inheritDoc
     */
    public function getDuration()
    {
        return $this->_getData(self::DURATION);
    }

    /**
     * @inheritDoc
     */
    public function setDuration(?int $duration)
    {
        return $this->setData(self::DURATION, $duration);
    }

    /**
     * @inheritDoc
     */
    public function getDurationType()
    {
        return $this->_getData(self::DURATION_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setDurationType(?string $durationType)
    {
        return $this->setData(self::DURATION_TYPE, $durationType);
    }

    /**
     * @inheritDoc
     */
    public function getMaxRepeat()
    {
        return $this->_getData(self::MAX_REPEAT);
    }

    /**
     * @inheritDoc
     */
    public function setMaxRepeat(?int $maxRepeat)
    {
        return $this->setData(self::MAX_REPEAT, $maxRepeat);
    }
}
