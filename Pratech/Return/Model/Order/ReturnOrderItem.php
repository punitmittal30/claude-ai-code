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

namespace Pratech\Return\Model\Order;

use Magento\Framework\DataObject;
use Pratech\Return\Api\Data\ReturnOrderItemInterface;

class ReturnOrderItem extends DataObject implements ReturnOrderItemInterface
{

    /**
     * @inheritdoc
     */
    public function setItem($item)
    {
        return $this->setData(ReturnOrderItemInterface::ITEM, $item);
    }

    /**
     * @inheritdoc
     */
    public function getItem()
    {
        return $this->_getData(ReturnOrderItemInterface::ITEM);
    }

    /**
     * @inheritdoc
     */
    public function setProductItem($productItem)
    {
        return $this->setData(ReturnOrderItemInterface::PRODUCT_ITEM, $productItem);
    }

    /**
     * @inheritdoc
     */
    public function getProductItem()
    {
        return $this->_getData(ReturnOrderItemInterface::PRODUCT_ITEM);
    }

    /**
     * @inheritdoc
     */
    public function setAvailableQty($qty)
    {
        return $this->setData(ReturnOrderItemInterface::AVAILABLE_QTY, (double)$qty);
    }

    /**
     * @inheritdoc
     */
    public function getAvailableQty()
    {
        return (double)$this->_getData(ReturnOrderItemInterface::AVAILABLE_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setPurchasedQty($qty)
    {
        return $this->setData(ReturnOrderItemInterface::PURCHASED_QTY, (double)$qty);
    }

    /**
     * @inheritdoc
     */
    public function getPurchasedQty()
    {
        return (double)$this->_getData(ReturnOrderItemInterface::PURCHASED_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setIsReturnable($isReturnable)
    {
        return $this->setData(ReturnOrderItemInterface::IS_RETURNABLE, (bool)$isReturnable);
    }

    /**
     * @inheritdoc
     */
    public function isReturnable()
    {
        return (bool)$this->_getData(ReturnOrderItemInterface::IS_RETURNABLE);
    }

    /**
     * @inheritdoc
     */
    public function setNoReturnableReason($reason)
    {
        return $this->setData(ReturnOrderItemInterface::NO_RETURNABLE_REASON, (int)$reason);
    }

    /**
     * @inheritdoc
     */
    public function getNoReturnableReason()
    {
        return (int)$this->_getData(ReturnOrderItemInterface::NO_RETURNABLE_REASON);
    }

    /**
     * @inheritdoc
     */
    public function setNoReturnableData($data)
    {
        return $this->setData(ReturnOrderItemInterface::NO_RETURNABLE_DATA, $data);
    }

    /**
     * @inheritdoc
     */
    public function getNoReturnableData()
    {
        if (empty($this->_getData(ReturnOrderItemInterface::NO_RETURNABLE_DATA))) {
            return [];
        }

        return $this->_getData(ReturnOrderItemInterface::NO_RETURNABLE_DATA);
    }
}
