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

namespace Pratech\Return\Model\Data;

use Magento\Framework\DataObject;
use Pratech\Return\Api\Data\ReturnItemInterface;

/**
 * Class Return Item to get request data
 */
class ReturnItem extends DataObject implements ReturnItemInterface
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
    public function getReasonId()
    {
        return $this->_getData(self::REASON_ID);
    }

    /**
     * @inheritDoc
     */
    public function setReasonId(int $reasonId)
    {
        return $this->setData(self::REASON_ID, $reasonId);
    }

    /**
     * @inheritDoc
     */
    public function getSku()
    {
        return $this->_getData(self::SKU);
    }

    /**
     * @inheritDoc
     */
    public function setSku(string $sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritDoc
     */
    public function getQty()
    {
        return $this->_getData(self::QTY);
    }

    /**
     * @inheritDoc
     */
    public function setQty(int $qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * @inheritDoc
     */
    public function getMediaData()
    {
        return $this->_getData(self::MEDIADATA);
    }

    /**
     * @inheritDoc
     */
    public function setMediaData(array $mediaData)
    {
        return $this->setData(self::MEDIADATA, $mediaData);
    }
}
