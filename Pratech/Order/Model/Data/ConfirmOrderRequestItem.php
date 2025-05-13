<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Model\Data;

use Pratech\Order\Api\Data\ConfirmOrderRequestItemInterface;
use Magento\Framework\DataObject;

/**
 * Class ConfirmOrderRequestItem to get request data
 */
class ConfirmOrderRequestItem extends DataObject implements ConfirmOrderRequestItemInterface
{
    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->_getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getRzpOrderId()
    {
        return $this->_getData(self::RZP_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRzpOrderId(?string $rzpOrderId)
    {
        return $this->setData(self::RZP_ORDER_ID, $rzpOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getRzpPaymentId()
    {
        return $this->_getData(self::RZP_PAYMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRzpPaymentId(?string $rzpPaymentId)
    {
        return $this->setData(self::RZP_PAYMENT_ID, $rzpPaymentId);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getSource()
    {
        return $this->_getData(self::SOURCE);
    }

    /**
     * @inheritDoc
     */
    public function setSource(?string $source)
    {
        return $this->setData(self::SOURCE, $source);
    }
}
