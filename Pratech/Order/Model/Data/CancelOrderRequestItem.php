<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Model\Data;

use Pratech\Order\Api\Data\CancelOrderRequestItemInterface;
use Magento\Framework\DataObject;

/**
 * Class CancelOrderRequestItem to get request data
 */
class CancelOrderRequestItem extends DataObject implements CancelOrderRequestItemInterface
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
    public function getCancelQty()
    {
        return $this->_getData(self::CANCEL_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setCancelQty(int $cancelQty)
    {
        return $this->setData(self::CANCEL_QTY, $cancelQty);
    }

    /**
     * @inheritDoc
     */
    public function getReason()
    {
        return $this->_getData(self::REASON);
    }

    /**
     * @inheritDoc
     */
    public function setReason(?string $reason)
    {
        return $this->setData(self::REASON, $reason);
    }
}
