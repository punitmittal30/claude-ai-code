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

namespace Pratech\Return\Model\Request;

use Magento\Framework\Model\AbstractModel;
use Pratech\Return\Api\Data\RequestItemInterface;

class RequestItem extends AbstractModel implements RequestItemInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\RequestItem::class);
        $this->setIdFieldName(RequestItemInterface::REQUEST_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestItemId($requestItemId)
    {
        return $this->setData(RequestItemInterface::REQUEST_ITEM_ID, (int)$requestItemId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestItemId()
    {
        return (int)$this->_getData(RequestItemInterface::REQUEST_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(RequestItemInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(RequestItemInterface::REQUEST_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderItemId($orderItemId)
    {
        return $this->setData(RequestItemInterface::ORDER_ITEM_ID, (int)$orderItemId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderItemId()
    {
        return (int)$this->_getData(RequestItemInterface::ORDER_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setQty($qty)
    {
        return $this->setData(RequestItemInterface::QTY, (double)$qty);
    }

    /**
     * @inheritdoc
     */
    public function getQty()
    {
        return (double)$this->_getData(RequestItemInterface::QTY);
    }

    /**
     * @inheritDoc
     */
    public function setRequestQty($requestQty)
    {
        return $this->setData(RequestItemInterface::REQUEST_QTY, (double)$requestQty);
    }

    /**
     * @inheritDoc
     */
    public function getRequestQty()
    {
        return (double)$this->_getData(RequestItemInterface::REQUEST_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setRefundedAmount($refundedAmount)
    {
        return $this->setData(RequestItemInterface::REFUNDED_AMOUNT, (double)$refundedAmount);
    }

    /**
     * @inheritDoc
     */
    public function getRefundedAmount()
    {
        return (double)$this->_getData(RequestItemInterface::REFUNDED_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setReasonId($reasonId)
    {
        return $this->setData(RequestItemInterface::REASON_ID, (int)$reasonId);
    }

    /**
     * @inheritdoc
     */
    public function getReasonId()
    {
        return (int)$this->_getData(RequestItemInterface::REASON_ID);
    }

    /**
     * @inheritDoc
     */
    public function setItemStatus($itemStatus)
    {
        return $this->setData(RequestItemInterface::ITEM_STATUS, (int)$itemStatus);
    }

    /**
     * @inheritDoc
     */
    public function getItemStatus()
    {
        return (int)$this->_getData(RequestItemInterface::ITEM_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setImages($images)
    {
        return $this->setData(RequestItemInterface::IMAGES, $images);
    }

    /**
     * @inheritDoc
     */
    public function getImages()
    {
        return $this->_getData(RequestItemInterface::IMAGES);
    }
}
